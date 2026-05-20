<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo cliente puede editar su perfil)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cliente']) && isset($_POST['nombre']) && isset($_POST['email']) && isset($_POST['telefono'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_cliente = Crypto::decrypt(urldecode($_POST['id_cliente']));
    $nombre=trim($_POST['nombre']);
    $email=trim($_POST['email']);
    $telefono=trim($_POST['telefono']);
    $errores=[];

    // Validacion y sanitizacion

    if(empty($id_cliente)){
        $errores[]="El ID del cliente es requerido";
    }elseif(!is_numeric($id_cliente) || $id_cliente <= 0){
        $errores[]="El ID del cliente no es válido";
    }

    if(empty($nombre)){
        $errores[]="El nombre es requerido";
    }elseif(!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $nombre)){
        $errores[]="El nombre solo puede contener letras y espacios";
    }elseif(strlen($nombre)>100){
        $errores[]="El nombre es muy largo";
    }

    if(empty($telefono)){
        $errores[]="El telefono es requerido";
    }elseif(!preg_match("/^[0-9]+$/", $telefono)){
        $errores[]="El telefono solo puede contener numeros";
    }elseif(strlen($telefono)>20){
        $errores[]="El telefono es muy largo";
    }

    if(empty($email)){
        $errores[]="El email es requerido";
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errores[]="El email no es valido";
    }elseif(strlen($email)>100){
        $errores[]="El email es muy largo";
    }

    // Verifiacr que el cliente exista en la base de datos
    try {
        $sql=$conexion->prepare("SELECT id FROM usuarios WHERE id = :id_cliente AND rol='cliente' AND estado='activo'");
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

    // Verifiacar que no exista otro usuario que tenga el mismo email o telefono (independientemente del estado) diferente al usuario que se va a editar
    try {
        $sql=$conexion->prepare("SELECT id,email,telefono FROM usuarios WHERE (email = :email OR telefono = :telefono) AND id != :id_cliente");
        $sql->bindParam(":email", $email, PDO::PARAM_STR);
        $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
        $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
        $sql->execute();
        $usuario_existente = $sql->fetch(PDO::FETCH_OBJ);
        if($usuario_existente){
            if (!empty($email) && $usuario_existente->email === $email) {
                $errores[] = "El correo electrónico ya está registrado por otro usuario.";
            }

            if (!empty($telefono) && $usuario_existente->telefono === $telefono) {
                $errores[] = "El número de teléfono ya está registrado por otro usuario.";
            }
        }
    } catch (PDOException $e) {
        error_log("Error al verificar el usuario existente: " . $e->getMessage());
        $errores[]="Error al verificar el usuario existente";
    }

    // Si no hay errores, editar los datos del cliente
    if(empty($errores)){
        try{
                $conexion->beginTransaction();

                $sql=$conexion->prepare("UPDATE usuarios SET nombre=:nombre,email=:email,telefono=:telefono WHERE id=:id_cliente AND rol='cliente' AND estado='activo'");
                $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
                $sql->bindParam(":email", $email, PDO::PARAM_STR);
                $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
                $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
                $sql->execute();

                // Registrar notificación de seguridad
                $mensaje_notificacion = "Tu información personal ha sido modificada exitosamente.";
                $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) VALUES (:usuario_id, 'sistema', :mensaje, 'no')");
                $sql_notif->bindParam(":usuario_id", $id_cliente, PDO::PARAM_INT);
                $sql_notif->bindParam(":mensaje", $mensaje_notificacion, PDO::PARAM_STR);
                $sql_notif->execute();

                $conexion->commit();

                // Actualizar la variable de sesión para que la interfaz cambie al instante
                $_SESSION['nombre'] = $nombre;

                $_SESSION['exito']="¡Cambios exitosos!";
                header("Location: ../../cliente/configuracion_cuenta.php");
                exit;
        }catch(PDOException $e){
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            error_log("Error al cambiar la información personal: " . $e->getMessage());
            $errores[]="Error crítico al cambiar la información personal. Se han revertido los cambios.";
            $_SESSION['errores']=$errores;
            header("Location: ../../cliente/configuracion_cuenta.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../cliente/configuracion_cuenta.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../cliente/configuracion_cuenta.php");
    exit;
}
?>