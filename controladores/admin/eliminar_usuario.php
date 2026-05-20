<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_usuario'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_usuario = trim(Crypto::decrypt($_POST['id_usuario']));
    $errores=[];

     // Validacion y sanitizacion
    if(empty($id_usuario)){
        $errores[]="El ID del usuario es requerido";
    }elseif(!is_numeric($id_usuario) || $id_usuario <= 0){
        $errores[]="El ID del usuario no es válido";
    }elseif($id_usuario == $_SESSION['id_usuario']){
        $errores[]="No puedes eliminar tu propia cuenta mientras estás en sesión";
    }

    
    // Verificar que el usuario exista y obtener su rol en una sola consulta
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("SELECT id, rol FROM usuarios WHERE id=:id_usuario AND estado='activo' LIMIT 1");
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();
            $usuario=$sql->fetch(PDO::FETCH_OBJ);

            if(!$usuario){
                $errores[]="El usuario que desea eliminar no existe o no está activo";
            }else{
                // Protección: No permitir eliminar al único administrador
                if($usuario->rol === 'admin'){
                    $sqlCount = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol='admin' AND estado='activo'");
                    $totalAdmins = $sqlCount->fetchColumn();
                    
                    if($totalAdmins <= 1){
                        $errores[]="Protección de Sistema: No puedes eliminar al único administrador activo";
                    }
                }
            }
        }catch(PDOException $e){
            error_log("Error al verificar el usuario: " . $e->getMessage());
            $errores[]="Error de seguridad al validar roles críticos";
        }
    }

    // Si no hay errores, eliminar al usuario
    if(empty($errores)){
        try{
           
            $sql=$conexion->prepare("UPDATE usuarios SET estado='inactivo' WHERE id=:id_usuario");
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();

            $_SESSION['exito']="¡Usuario eliminado correctamente!";
            header("Location: ../../admin/gestion_usuarios.php");
            exit;
        }catch(PDOException $e){
            error_log("Error al eliminar el usuario: " . $e->getMessage());
            $errores[]="Error crítico al intentar eliminar el usuario";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/gestion_usuarios.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_usuarios.php");
    exit;
}
?>