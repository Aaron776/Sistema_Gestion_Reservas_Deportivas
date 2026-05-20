<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo cliente puede cambiar contraseña)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cliente']) && isset($_POST['password_actual']) && isset($_POST['password_nueva'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_cliente = Crypto::decrypt(urldecode($_POST['id_cliente']));
    $password_actual = trim($_POST['password_actual']);
    $password_nueva = trim($_POST['password_nueva']);
    $errores=[];

    // Validacion y sanitizacion

    if(empty($id_cliente)){
        $errores[]="El ID del cliente es requerido";
    }elseif(!is_numeric($id_cliente) || $id_cliente <= 0){
        $errores[]="El ID del cliente no es válido";
    }

    if(empty($password_actual)){
        $errores[]="La contraseña actual es requerida";
    }elseif(strlen($password_actual) < 5){
        $errores[]="La contraseña actual debe tener al menos 5 caracteres";
    }

    if(empty($password_nueva)){
        $errores[]="La contraseña nueva es requerida";
    }elseif(strlen($password_nueva) < 5){
        $errores[]="La contraseña nueva debe tener al menos 5 caracteres";
    }elseif($password_nueva == $password_actual){
        $errores[]="La contraseña nueva no puede ser igual a la contraseña actual";
    }

    // Verifiacr que el cliente exista en la base de datos
    try {
        $sql=$conexion->prepare("SELECT password FROM usuarios WHERE id = :id_cliente AND rol='cliente' AND estado='activo'");
        $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
        $sql->execute();
        $cliente = $sql->fetch(PDO::FETCH_OBJ);
        if(!$cliente){
            $errores[]="El cliente no existe o esta inactivo";
        }
    } catch (PDOException $e) {
        error_log("Error al verificar el cliente: " . $e->getMessage());
        $errores[]="Error al verificar el cliente";
    }

    // Si no hay errores, actualizar la contraseña
    if(empty($errores)){
        try{
            if (password_verify($password_actual, $cliente->password)) {
                $conexion->beginTransaction();

                $password_nueva_hasheada=password_hash($password_nueva, PASSWORD_DEFAULT);
                $sql=$conexion->prepare("UPDATE usuarios SET password=:password_nueva_hasheada WHERE id=:id_cliente AND rol='cliente' AND estado='activo'");
                $sql->bindParam(":password_nueva_hasheada", $password_nueva_hasheada, PDO::PARAM_STR);
                $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
                $sql->execute();

                // Registrar notificación de seguridad
                $mensaje_notificacion = "Tu contraseña ha sido actualizada exitosamente.";
                $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) VALUES (:usuario_id, 'sistema', :mensaje, 'no')");
                $sql_notif->bindParam(":usuario_id", $id_cliente, PDO::PARAM_INT);
                $sql_notif->bindParam(":mensaje", $mensaje_notificacion, PDO::PARAM_STR);
                $sql_notif->execute();

                $conexion->commit();

                $_SESSION['exito']="¡Contraseña cambiada exitosamente!";
                header("Location: ../../cliente/cambiar_password.php");
                exit;
            }else{
                $errores[]="La contraseña actual es incorrecta";
                $_SESSION['errores']=$errores;
                header("Location: ../../cliente/cambiar_password.php");
                exit;
            }
        }catch(PDOException $e){
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            error_log("Error al cambiar la contraseña: " . $e->getMessage());
            $errores[]="Error crítico al cambiar la contraseña. Se han revertido los cambios.";
            $_SESSION['errores']=$errores;
            header("Location: ../../cliente/cambiar_password.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../cliente/cambiar_password.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../cliente/cambiar_password.php");
    exit;
}
?>