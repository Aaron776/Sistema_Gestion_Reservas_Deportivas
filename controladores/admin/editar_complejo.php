<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede registrar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['direccion']) && isset($_POST['descripcion']) && isset($_FILES['imagen']) && isset($_POST['telefono']) && isset($_POST['id_complejo'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $direccion = trim($_POST['direccion']);
    $descripcion = trim($_POST['descripcion']);
    $telefono = trim($_POST['telefono']);
    $id_complejo = trim(Crypto::decrypt($_POST['id_complejo']));
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_complejo)){
        $errores[]="El ID del complejo es requerido";
    }elseif(!is_numeric($id_complejo) || $id_complejo <= 0){
        $errores[]="El ID del complejo no es válido";
    }

    if(empty($nombre)){
        $errores[]="El nombre es requerido";
    }elseif(!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9#.\- ]+$/", $nombre)){
        $errores[]="El nombre contiene caracteres no permitidos (solo letras, números, #, . y -)";
    }elseif(strlen($nombre)>100){
        $errores[]="El nombre es muy largo";
    }

    if(!empty($telefono)){
        if(!preg_match("/^[0-9]+$/", $telefono)){
            $errores[]="El telefono solo puede contener numeros";
        }elseif(strlen($telefono)>20){
            $errores[]="El telefono es muy largo";
        }
    }

    if(!empty($descripcion)){
        if(strlen($descripcion)>500){
            $errores[]="La descripcion es muy larga";
        }
    }

    if(!empty($direccion)){
        if(strlen($direccion)>255){
            $errores[]="La direccion es muy larga";
        }
    }

    // Verificar que el complejo exista
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("SELECT id,nombre FROM complejos WHERE id=:id_complejo LIMIT 1");
            $sql->bindParam(":id_complejo", $id_complejo, PDO::PARAM_INT);
            $sql->execute();
            $complejo=$sql->fetch(PDO::FETCH_OBJ);

            if(!$complejo){
                $errores[]="El complejo que desea editar no existe";
            }
        }catch(PDOException $e){
            error_log("Error al verificar el complejo: " . $e->getMessage());
            $errores[]="Error de seguridad al validar complejos críticos";
        }
    }


    // Si no hay errores iniciales, procedemos con la lógica de imagen y BD
    if(empty($errores)){
        // Primero consultamos la imagen actual para saber cuál eliminar después si se sube una nueva
        $sql_img = "SELECT imagen_url FROM complejos WHERE id = :id_complejo";
        $query_img = $conexion->prepare($sql_img);
        $query_img->bindParam(':id_complejo', $id_complejo);
        $query_img->execute();
        $complejo_actual = $query_img->fetch(PDO::FETCH_ASSOC);
        $imagen_anterior = $complejo_actual['imagen_url'] ?? "";

        $nueva_imagen_subida = false;
        $imagen_nombre = $imagen_anterior;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
             // Obtenemos la ruta temporal
            $imagen_temp = $_FILES['imagen']['tmp_name'];
            $imagen_extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array(strtolower($imagen_extension), $extensiones_permitidas)) {
                $errores[] = "El archivo debe ser una imagen válida (jpg, jpeg, png, gif, webp)";
            }
            
            $tamano_maximo = 2097152; // 2MB
            if ($_FILES['imagen']['size'] > $tamano_maximo) {
                $errores[] = "La imagen no debe superar los 2MB";
            }

            if (empty($errores)) {
                $imagen_nombre = uniqid("complejo_", true) . "." . $imagen_extension;
                if (move_uploaded_file($imagen_temp, __DIR__ . "/../../app/fotos_complejos/" . $imagen_nombre)) {
                    $nueva_imagen_subida = true;
                } else {
                    $errores[] = "Error al mover la imagen al servidor.";
                }
            }
        }
    }
        

    // Si no hay errores, editar el complejo
    if(empty($errores)){
        try{
            $conexion->beginTransaction(); // Iniciamos la transacción

            // 1. Insertar el Complejo
            $sql=$conexion->prepare("UPDATE complejos SET nombre=:nombre, descripcion=:descripcion, imagen_url=:imagen_url, direccion=:direccion, telefono=:telefono WHERE id=:id_complejo");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
            $sql->bindParam(":imagen_url", $imagen_nombre, PDO::PARAM_STR);
            $sql->bindParam(":direccion", $direccion, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->bindParam(":id_complejo", $id_complejo, PDO::PARAM_INT);
            $sql->execute();

            // 2. Obtener todos los destinatarios (Administradores y Recepcionistas)
            $stmt_users = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista')");
            $stmt_users->execute();
            $usuarios_notif = $stmt_users->fetchAll(PDO::FETCH_OBJ);

            // 3. Registrar Notificación individual para cada uno
            $mensaje_notif = "El administrador " . $_SESSION['nombre'] . " ha actualizado el complejo: " . $nombre;
            
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) 
                                           VALUES (:id_usuario, 'sistema', :mensaje, 'no')");

            foreach ($usuarios_notif as $user) {
                $sql_notif->bindParam(":id_usuario", $user->id, PDO::PARAM_INT);
                $sql_notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $sql_notif->execute();
            }

            // Si todo salió bien, confirmamos los cambios
            $conexion->commit();

            // LIMPIEZA: Si subimos una imagen nueva con éxito, borramos la antigua para no dejar basura
            if ($nueva_imagen_subida && !empty($imagen_anterior) && file_exists(__DIR__ . "/../../app/fotos_complejos/" . $imagen_anterior)) {
                unlink(__DIR__ . "/../../app/fotos_complejos/" . $imagen_anterior);
            }

            $_SESSION['exito']="¡Complejo editado exitosamente!";
            header("Location: ../../admin/editar_complejo.php?id_complejo=".urlencode(Crypto::encrypt($id_complejo)));
            exit;
        }catch(PDOException $e){
            // Si algo falla, revertimos los cambios en la BD
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }

            // Si se subió una imagen NUEVA, la borramos para no dejar basura tras el fallo
            if($nueva_imagen_subida && file_exists(__DIR__ . "/../../app/fotos_complejos/" . $imagen_nombre)){
                unlink(__DIR__ . "/../../app/fotos_complejos/" . $imagen_nombre);
            }

            error_log("Error en transacción de complejo: " . $e->getMessage());
            $errores[]="Error crítico al guardar en la base de datos. Se han revertido los cambios.";
            $_SESSION['errores']=$errores;
            header("Location: ../../admin/editar_complejo.php?id_complejo=".urlencode(Crypto::encrypt($id_complejo)));
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/editar_complejo.php?id_complejo=".urlencode(Crypto::encrypt($id_complejo)));
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/gestion_complejos.php");
    exit;
}



?>