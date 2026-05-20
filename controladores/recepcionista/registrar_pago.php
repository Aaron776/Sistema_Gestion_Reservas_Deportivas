<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin', 'recepcionista'])) {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_reserva']) && isset($_POST['metodo']) && isset($_POST['fecha_pago'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_reserva = Crypto::decrypt(urldecode($_POST['id_reserva']));
    $metodo = trim($_POST['metodo']);
    $fecha_pago = trim($_POST['fecha_pago']);
    $referencia_transaccion = isset($_POST['referencia_transaccion']) ? trim($_POST['referencia_transaccion']) : '';
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_reserva)){
        $errores[]="La reserva es requerida";
    }elseif(!is_numeric($id_reserva) || $id_reserva <= 0){
        $errores[]="La reserva no es válida";
    }

    if(empty($metodo)){
        $errores[]="El metodo de pago es requerido";
    }elseif(!in_array($metodo,['efectivo','transferencia','tarjeta'])){
        $errores[]="El metodo de pago no es válido";
    }

    if(!empty($referencia_transaccion)){
        if(strlen($referencia_transaccion) > 100){
            $errores[]="La referencia de la transacción no debe superar los 100 caracteres";
        }elseif(strlen($referencia_transaccion) < 5){
            $errores[]="La referencia de la transacción no debe ser menor a 5 caracteres";
        }
    }

    if(empty($fecha_pago)){
        $errores[]="La fecha de pago es requerida";
    }


    // Manejamos la subida del comprobante de pago caso de haber uno
    $comprobante_nombre = null; // Cambiado a null por defecto
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
         // Obtenemos la ruta temporal donde el servidor almacenó el archivo inmediatamente
        $comprobante_temp = $_FILES['comprobante']['tmp_name'];
        
        // Extraemos la extensión del archivo original (ej: jpg, png, gif)
        $comprobante_extension = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);

        // VALIDAR TIPO DE ARCHIVO (solo comprobantes permitidos)
        // Definimos las extensiones válidas para evitar subir archivos peligroso
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp','pdf'];

        // Convertimos la extensión a minúsculas para comparar sin importar mayúsculas/minúsculas
        // Si la extensión NO está en el array de permitidas, agregamos error
        if (!in_array(strtolower($comprobante_extension), $extensiones_permitidas)) {
            $errores[] = "El archivo debe ser una imagen válida (jpg, jpeg, png, gif, webp,pdf)";
        }
        
        // ---------------------------------------------------------
        // VALIDAR TAMAÑO (máximo 2MB)
        // ---------------------------------------------------------
        // Definimos el límite en bytes (2MB = 2097152 bytes)
        $tamano_maximo = 2097152;
        
        // Si el archivo supera el tamaño permitido, agregamos error
        if ($_FILES['comprobante']['size'] > $tamano_maximo) {
            $errores[] = "El comprobante no debe superar los 2MB";
        }

        // GENERAR NOMBRE ÚNICO PARA EL ARCHIVO
        $comprobante_nombre = uniqid("comprobante_", true) . "." . $comprobante_extension;

        if (empty($errores)) {
            move_uploaded_file($comprobante_temp, __DIR__ . "/../../app/comprobantes_pagos/" . $comprobante_nombre);
        }
    }
        

    // Si no hay errores, registrar el pago
    if(empty($errores)){
        try{
            $conexion->beginTransaction(); // Iniciamos la transacción

            // OBTENER DATOS DE LA RESERVA PARA LAS NOTIFICACIONES
            $stmt_info = $conexion->prepare("
                SELECT r.id, r.usuario_id, r.fecha, r.total, u.nombre as cliente_nombre, c.nombre as cancha_nombre, h.hora_inicio
                FROM reservas r
                JOIN usuarios u ON r.usuario_id = u.id
                JOIN canchas c ON r.cancha_id = c.id
                JOIN horarios h ON r.horario_id = h.id
                WHERE r.id = :id_reserva
            ");
            $stmt_info->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $stmt_info->execute();
            $info = $stmt_info->fetch(PDO::FETCH_OBJ);

            if (!$info) {
                throw new Exception("No se encontró información de la reserva.");
            }

            // 1. Actualziar el pago de la reserva
            $sql=$conexion->prepare(" UPDATE pagos SET metodo = :metodo, fecha_pago = :fecha_pago,
                                   comprobante = :comprobante_pago, referencia_transaccion = :referencia, estado = 'pagado' WHERE reserva_id = :id_reserva");
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->bindParam(":metodo", $metodo, PDO::PARAM_STR);
            $sql->bindParam(":fecha_pago",  $fecha_pago, PDO::PARAM_STR);
            $sql->bindParam(":comprobante_pago", $comprobante_nombre, PDO::PARAM_STR);
            $sql->bindParam(":referencia", $referencia_transaccion, PDO::PARAM_STR);
            $sql->execute();

            // 2. Notifiacion a los Administradores
            $stmt_users = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin')");
            $stmt_users->execute();
            $usuarios_notif = $stmt_users->fetchAll(PDO::FETCH_OBJ);

            // 3. Registrar Notificación individual para cada uno (Mensaje Detallado)
            $mensaje_admin = "El recepcionista " . $_SESSION['nombre'] . " ha registrado un pago de $" . number_format($info->total, 2) . 
                            " para la reserva #" . $info->id . " del cliente " . $info->cliente_nombre;
            
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) 
                                           VALUES (:id_usuario, 'pago', :mensaje, 'no')");

            foreach ($usuarios_notif as $user) {
                $sql_notif->bindParam(":id_usuario", $user->id, PDO::PARAM_INT);
                $sql_notif->bindParam(":mensaje", $mensaje_admin, PDO::PARAM_STR);
                $sql_notif->execute();
            }


            // 4. Notifiacion al cliente del pago de su reserva
            $mensaje_cliente = "¡Hola " . $info->cliente_nombre . "! Confirmamos la recepción de tu pago por $" . number_format($info->total, 2) . 
                               " para tu reserva en la cancha '" . $info->cancha_nombre . "' el día " . date('d/m/Y', strtotime($info->fecha)) . 
                               " en el horario de las " . $info->hora_inicio . ".";
            
            $sql_notif->bindParam(":id_usuario", $info->usuario_id, PDO::PARAM_INT);
            $sql_notif->bindParam(":mensaje", $mensaje_cliente, PDO::PARAM_STR);
            $sql_notif->execute();


            // Si todo salió bien, confirmamos los cambios
            $conexion->commit();

            $_SESSION['exito']="¡Pago registrado exitosamente!";
            header("Location: ../../recepcionista/gestion_reservas.php");
            exit;
        }catch(PDOException $e){
            // Si algo falla, revertimos los cambios en la BD
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            
            // Si se subió una imagen, podríamos intentar borrarla para no dejar basura
            if($comprobante_nombre && file_exists(__DIR__ . "/../../app/comprobantes_pagos/" . $comprobante_nombre)){
                unlink(__DIR__ . "/../../app/comprobantes_pagos/" . $comprobante_nombre);
            }

            error_log("Error en transacción de pago de reserva: " . $e->getMessage());
            $errores[]="Error crítico al guardar en la base de datos. Se han revertido los cambios.";
            $_SESSION['errores']=$errores;
            header("Location: ../../recepcionista/gestion_reservas.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../recepcionista/registrar_pago.php?id_reserva=".Crypto::encrypt($id_reserva));
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../recepcionista/gestion_reservas.php");
    exit;
}



?>