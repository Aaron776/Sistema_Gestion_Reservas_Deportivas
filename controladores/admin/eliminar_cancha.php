<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_complejo']) && isset($_POST['id_cancha'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_complejo = trim(Crypto::decrypt($_POST['id_complejo']));
    $id_cancha = trim(Crypto::decrypt($_POST['id_cancha']));
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_complejo)){
        $errores[]="El ID del complejo es requerido";
    }elseif(!is_numeric($id_complejo) || $id_complejo <= 0){
        $errores[]="El ID del complejo no es válido";
    }

    if(empty($id_cancha)){
        $errores[]="El ID de la cancha es requerido";
    }elseif(!is_numeric($id_cancha) || $id_cancha <= 0){
        $errores[]="El ID de la cancha no es válido";
    }

    
    // Verificar que la cancha exista y que le pertenezca al complejo
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("SELECT canchas.id as id_cancha,canchas.nombre as nombre_cancha,complejos.nombre as complejo_nombre FROM canchas INNER JOIN complejos ON canchas.complejo_id=complejos.id WHERE canchas.id=:id_cancha AND canchas.complejo_id=:id_complejo LIMIT 1");
            $sql->bindParam(":id_complejo", $id_complejo, PDO::PARAM_INT);
            $sql->bindParam(":id_cancha", $id_cancha, PDO::PARAM_INT);
            $sql->execute();
            $cancha=$sql->fetch(PDO::FETCH_OBJ);

            if(!$cancha){
                $errores[]="La cancha que desea eliminar no existe o no pertenece a este complejo";
            }
        }catch(PDOException $e){
            error_log("Error al verificar la cancha: " . $e->getMessage());
            $errores[]="Error de seguridad al validar la cancha";
        }
    }

    // Si no hay errores, eliminar (desactivar) la cancha
    if(empty($errores)){
        try{
            $conexion->beginTransaction();

            // 1. Desactivar ÚNICAMENTE la cancha seleccionada
            $sql=$conexion->prepare("UPDATE canchas SET estado='inactivo' WHERE id=:id_cancha AND complejo_id=:id_complejo");
            $sql->bindParam(":id_cancha", $id_cancha, PDO::PARAM_INT);
            $sql->bindParam(":id_complejo", $id_complejo, PDO::PARAM_INT);
            $sql->execute();

            // 2. Obtener destinatarios de notificación (Admins y Recepcionistas)
            $stmt_users = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista')");
            $stmt_users->execute();
            $usuarios_notif = $stmt_users->fetchAll(PDO::FETCH_OBJ);

            // 3. Registrar Notificación individual
            $mensaje_notif = "El administrador " . $_SESSION['nombre'] . " ha desactivado la cancha: " . $cancha->nombre_cancha . " del complejo: " . $cancha->complejo_nombre;
            
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) 
                                           VALUES (:id_usuario, 'sistema', :mensaje, 'no')");

            foreach ($usuarios_notif as $user) {
                $sql_notif->bindParam(":id_usuario", $user->id, PDO::PARAM_INT);
                $sql_notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $sql_notif->execute();
            }

            $conexion->commit();

            $_SESSION['exito']="¡Cancha desactivada correctamente!";
            header("Location: ../../admin/gestion_canchas_complejo.php?id_complejo=" . urlencode(Crypto::encrypt($id_complejo)));
            exit;
        }catch(PDOException $e){
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            error_log("Error al eliminar la cancha: " . $e->getMessage());
            $errores[]="Error crítico al intentar eliminar la cancha y sus dependencias";
            $_SESSION['errores']=$errores;
            header("Location: ../../admin/gestion_canchas_complejo.php?id_complejo=" . urlencode(Crypto::encrypt($id_complejo)));
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/gestion_canchas_complejo.php?id_complejo=" . urlencode(Crypto::encrypt($id_complejo)));
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_complejos.php");
    exit;
}
?>