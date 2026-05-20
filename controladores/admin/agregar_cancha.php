<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede registrar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['id_complejo']) && isset($_POST['descripcion']) && isset($_FILES['imagen']) && isset($_POST['precio_hora']) && isset($_POST['tipo'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $id_complejo = Crypto::decrypt(urldecode($_POST['id_complejo']));
    $descripcion = trim($_POST['descripcion']);
    $precio_hora = trim($_POST['precio_hora']);
    $tipo = trim($_POST['tipo']);
    $errores=[];

    // Validacion y sanitizacion
    if(empty($nombre)){
        $errores[]="El nombre es requerido";
    }elseif(!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9#.\- ]+$/", $nombre)){
        $errores[]="El nombre contiene caracteres no permitidos (solo letras, números, #, . y -)";
    }elseif(strlen($nombre)>100){
        $errores[]="El nombre es muy largo";
    }

    if(empty($precio_hora)){
        $errores[]="El precio por hora es requerido";
    }elseif (!is_numeric($precio_hora) || $precio_hora < 0) {
        $errores[] = "El precio por hora debe ser un valor numérico válido";
    }

    if(!empty($descripcion)){
        if(strlen($descripcion)>500){
            $errores[]="La descripcion es muy larga";
        }
    }

    if(empty($id_complejo)){
        $errores[]="El ID del complejo es requerido";
    }elseif(!is_numeric($id_complejo) || $id_complejo <= 0){
        $errores[]="El ID del complejo no es válido";
    }

    $tipos=["futbol", "basket", "tenis", "padel", "voley", "patinaje","otro"];
    if(empty($tipo)){
        $errores[]="El tipo de cancha es requerido";
    }elseif(!in_array($tipo, $tipos)){
        $errores[]="El tipo de cancha no es válido";
    }

    // Manejamos la subida de la imagen caso de haber una
    $imagen_nombre = null; // Cambiado a null por defecto
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
         // Obtenemos la ruta temporal donde el servidor almacenó el archivo inmediatamente
        $imagen_temp = $_FILES['imagen']['tmp_name'];
        
        // Extraemos la extensión del archivo original (ej: jpg, png, gif)
        $imagen_extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);

        // VALIDAR TIPO DE ARCHIVO (solo imágenes permitidas)
        // Definimos las extensiones válidas para evitar subir archivos peligroso
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        // Convertimos la extensión a minúsculas para comparar sin importar mayúsculas/minúsculas
        // Si la extensión NO está en el array de permitidas, agregamos error
        if (!in_array(strtolower($imagen_extension), $extensiones_permitidas)) {
            $errores[] = "El archivo debe ser una imagen válida (jpg, jpeg, png, gif, webp)";
        }
        
        // ---------------------------------------------------------
        // VALIDAR TAMAÑO (máximo 2MB)
        // ---------------------------------------------------------
        // Definimos el límite en bytes (2MB = 2097152 bytes)
        $tamano_maximo = 2097152;
        
        // Si el archivo supera el tamaño permitido, agregamos error
        if ($_FILES['imagen']['size'] > $tamano_maximo) {
            $errores[] = "La imagen no debe superar los 2MB";
        }

        // GENERAR NOMBRE ÚNICO PARA EL ARCHIVO
        $imagen_nombre = uniqid("cancha_", true) . "." . $imagen_extension;

        if (empty($errores)) {
            move_uploaded_file($imagen_temp, __DIR__ . "/../../app/fotos_canchas/" . $imagen_nombre);
        }
    }
        

    // Si no hay errores, registrar el complejo
    if(empty($errores)){
        try{
            $conexion->beginTransaction(); // Iniciamos la transacción

            // 1. Insertar la cancha
            $sql=$conexion->prepare("INSERT INTO canchas (complejo_id, nombre, descripcion, imagen_url, tipo, precio_hora) 
                                   VALUES (:complejo_id, :nombre, :descripcion, :imagen_url, :tipo, :precio_hora)");
            $sql->bindParam(":complejo_id", $id_complejo, PDO::PARAM_INT);
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
            $sql->bindParam(":imagen_url", $imagen_nombre, PDO::PARAM_STR);
            $sql->bindParam(":precio_hora", $precio_hora, PDO::PARAM_STR);
            $sql->bindParam(":tipo", $tipo, PDO::PARAM_STR);
            $sql->execute();

            // 2. Obtener todos los destinatarios (Administradores y Recepcionistas)
            $stmt_users = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista')");
            $stmt_users->execute();
            $usuarios_notif = $stmt_users->fetchAll(PDO::FETCH_OBJ);

            // 3. Registrar Notificación individual para cada uno
            // Obtener el nombre del complejo
            try {
                $sql_complejo = $conexion->prepare("SELECT nombre FROM complejos WHERE id = :id_complejo");
                $sql_complejo->bindParam(":id_complejo", $id_complejo, PDO::PARAM_INT);
                $sql_complejo->execute();
                $complejo = $sql_complejo->fetch(PDO::FETCH_OBJ);
            } catch (PDOException $e) {
                error_log("Error al obtener nombre del complejo: " . $e->getMessage());    
            }

            $nombre_complejo_notif = ($complejo) ? $complejo->nombre : "ID: " . $id_complejo;
            $mensaje_notif = "El administrador " . $_SESSION['nombre'] . " ha registrado una nueva cancha: " . $nombre . " en el complejo " . $nombre_complejo_notif;
            
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) 
                                           VALUES (:id_usuario, 'sistema', :mensaje, 'no')");

            foreach ($usuarios_notif as $user) {
                $sql_notif->bindParam(":id_usuario", $user->id, PDO::PARAM_INT);
                $sql_notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $sql_notif->execute();
            }

            // Si todo salió bien, confirmamos los cambios
            $conexion->commit();

            $_SESSION['exito']="¡Cancha agregada exitosamente!";
            header("Location: ../../admin/agregar_cancha.php?id_complejo=" . urlencode(Crypto::encrypt($id_complejo)));
            exit;
        }catch(PDOException $e){
            // Si algo falla, revertimos los cambios en la BD
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            
            // Si se subió una imagen, podríamos intentar borrarla para no dejar basura
            if($imagen_nombre && file_exists(__DIR__ . "/../../app/fotos_canchas/" . $imagen_nombre)){
                unlink(__DIR__ . "/../../app/fotos_canchas/" . $imagen_nombre);
            }

            error_log("Error en transacción de cancha: " . $e->getMessage());
            $errores[]="Error crítico al guardar en la base de datos. Se han revertido los cambios.";
            $_SESSION['errores']=$errores;
            header("Location: ../../admin/agregar_cancha.php?id_complejo=" . urlencode(Crypto::encrypt($id_complejo)));
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/agregar_cancha.php?id_complejo=" . urlencode(Crypto::encrypt($id_complejo)));
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/gestion_complejos.php");
    exit;
}



?>