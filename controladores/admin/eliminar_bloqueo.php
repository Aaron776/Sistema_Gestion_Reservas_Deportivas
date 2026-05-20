<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_bloqueo'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_bloqueo = trim(Crypto::decrypt($_POST['id_bloqueo']));
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_bloqueo)){
        $errores[]="El ID del bloqueo es requerido";
    }elseif(!is_numeric($id_bloqueo) || $id_bloqueo <= 0){
        $errores[]="El ID del bloqueo no es válido";
    }

    
    // Verificar que el bloqueo exista
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("SELECT bloqueos.id as id_bloqueo,canchas.nombre as nombre_cancha,bloqueos.fecha as fecha FROM bloqueos JOIN canchas ON bloqueos.cancha_id = canchas.id WHERE bloqueos.id=:id_bloqueo LIMIT 1");
            $sql->bindParam(":id_bloqueo", $id_bloqueo, PDO::PARAM_INT);
            $sql->execute();
            $bloqueo=$sql->fetch(PDO::FETCH_OBJ);

            if(!$bloqueo){
                $errores[]="El bloqueo que desea eliminar no existe";
            }
        }catch(PDOException $e){
            error_log("Error al verificar el bloqueo: " . $e->getMessage());
            $errores[]="Error de seguridad al validar bloqueos";
        }
    }

    // Si no hay errores, eliminar el bloqueo
    if(empty($errores)){
        try{
            $conexion->beginTransaction();

            // 1. Eliminar el bloqueo
            $sql=$conexion->prepare("DELETE FROM bloqueos WHERE id=:id_bloqueo");
            $sql->bindParam(":id_bloqueo", $id_bloqueo, PDO::PARAM_INT);
            $sql->execute();

            // 2. NOTIFICACIONES (Admins y Recepcionistas)
            $stmt_users = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista')");
            $stmt_users->execute();
            $usuarios_notif = $stmt_users->fetchAll(PDO::FETCH_OBJ);
            $mensaje_notif = "Se ha eliminado el bloqueo con ID " . $bloqueo->id_bloqueo . " de la cancha " . $bloqueo->nombre_cancha . " el día " . $bloqueo->fecha;
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) VALUES (:id_usuario, 'sistema', :mensaje, 'no')");
            foreach ($usuarios_notif as $item) {
                $sql_notif->bindParam(":id_usuario", $item->id, PDO::PARAM_INT);
                $sql_notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $sql_notif->execute();
            }

            $conexion->commit();

            $_SESSION['exito']="¡Bloqueo eliminado correctamente!";
            header("Location: ../../admin/gestion_bloqueos.php");
            exit;
        }catch(PDOException $e){
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            error_log("Error al eliminar el bloqueo: " . $e->getMessage());
            $errores[]="Error crítico al intentar eliminar el bloqueo";
            $_SESSION['errores']=$errores;
            header("Location: ../../admin/gestion_bloqueos.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/gestion_bloqueos.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_bloqueos.php");
    exit;
}
?>