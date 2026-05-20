<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_complejo'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_complejo = trim(Crypto::decrypt($_POST['id_complejo']));
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_complejo)){
        $errores[]="El ID del complejo es requerido";
    }elseif(!is_numeric($id_complejo) || $id_complejo <= 0){
        $errores[]="El ID del complejo no es válido";
    }

    
    // Verificar que el complejo exista y este en estado activo
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("SELECT id,nombre FROM complejos WHERE id=:id_complejo AND estado='activo' LIMIT 1");
            $sql->bindParam(":id_complejo", $id_complejo, PDO::PARAM_INT);
            $sql->execute();
            $complejo=$sql->fetch(PDO::FETCH_OBJ);

            if(!$complejo){
                $errores[]="El complejo que desea eliminar no existe o no está activo";
            }
        }catch(PDOException $e){
            error_log("Error al verificar el complejo: " . $e->getMessage());
            $errores[]="Error de seguridad al validar complejos críticos";
        }
    }

    // Si no hay errores, eliminar (desactivar) el complejo
    if(empty($errores)){
        try{
            $conexion->beginTransaction();

            // 1. Desactivar el Complejo
            $sql=$conexion->prepare("UPDATE complejos SET estado='inactivo' WHERE id=:id_complejo");
            $sql->bindParam(":id_complejo", $id_complejo, PDO::PARAM_INT);
            $sql->execute();

            // 2. Desactivar en cascada todas las canchas asociadas a este complejo
            $sql_canchas = $conexion->prepare("UPDATE canchas SET estado='inactivo' WHERE complejo_id=:id_complejo");
            $sql_canchas->bindParam(":id_complejo", $id_complejo, PDO::PARAM_INT);
            $sql_canchas->execute();

            // 3. Registrar Notificación para el equipo
            $mensaje_notif = "El administrador " . $_SESSION['nombre'] . " ha desactivado el complejo: " . $complejo->nombre;
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) 
                                           SELECT id, 'sistema', :mensaje, 'no' 
                                           FROM usuarios 
                                           WHERE rol IN ('admin', 'recepcionista')");
            $sql_notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
            $sql_notif->execute();

            $conexion->commit();

            $_SESSION['exito']="¡Complejo y sus canchas desactivados correctamente!";
            header("Location: ../../admin/gestion_complejos.php");
            exit;
        }catch(PDOException $e){
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            error_log("Error al eliminar el complejo: " . $e->getMessage());
            $errores[]="Error crítico al intentar eliminar el complejo y sus dependencias";
            $_SESSION['errores']=$errores;
            header("Location: ../../admin/gestion_complejos.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/gestion_complejos.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_complejos.php");
    exit;
}
?>