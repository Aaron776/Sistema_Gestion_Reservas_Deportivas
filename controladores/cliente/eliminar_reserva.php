<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == "POST"  && isset($_POST['id_reserva'])) {
     // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir Datos
    $id_reserva = urldecode(Crypto::decrypt($_POST['id_reserva']));
    $id_cliente = $_SESSION['id_usuario'];
    $errores = [];
    

    // Vaidaciones y Sanitizacion
    if(empty($id_reserva)){
        $errores[]="El ID de la reserva es requerido";
    }elseif(!is_numeric($id_reserva) || $id_reserva <= 0){
        $errores[]="El ID de la reserva no es válido";
    }


    // Verificar que esa reserva pertenezca al usuario y que este en estado pendiente
    try {
        $sql = $conexion->prepare("SELECT id FROM reservas WHERE id = :id_reserva AND usuario_id = :id_cliente AND estado = 'pendiente'");
        $sql->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
        $sql->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        $sql->execute();
        $reserva = $sql->fetch(PDO::FETCH_OBJ);
        if(!$reserva){
            $errores[]="No existe una reserva con esos datos o no puede ser eliminada";
        }
    } catch (PDOException $e) {
        error_log("Error al verificar la reserva: " . $e->getMessage());
        $errores[]="Error al verificar la reserva";
    }

    if(empty($errores)){
        try {
            $conexion->beginTransaction();

            // ELIMINAR EL PAGO PENDIENTE ASOCIADO A ESTA RESERVA ANTES DE ELIMINAR LA RESERVA
            $sql_pago = $conexion->prepare("DELETE FROM pagos WHERE reserva_id = :id_reserva AND estado = 'pendiente'");
            $sql_pago->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
            $sql_pago->execute();

            $sql = $conexion->prepare("DELETE FROM reservas WHERE id = :id_reserva AND usuario_id = :id_cliente AND estado = 'pendiente'");
            $sql->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
            $sql->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
            $sql->execute();

            // INSERTAR NOTIFICACIÓN PARA EL CLIENTE
            $mensaje_notificacion = "Has eliminado exitosamente tu reserva.";
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) VALUES (:user_id, 'reserva', :mensaje, 'no')");
            $sql_notif->bindParam(':user_id', $id_cliente, PDO::PARAM_INT);
            $sql_notif->bindParam(':mensaje', $mensaje_notificacion, PDO::PARAM_STR);
            $sql_notif->execute();

            $conexion->commit();

            $_SESSION['exito']="Reserva eliminada exitosamente";
            header("Location: ../../cliente/gestion_reservas.php");
            exit;
        } catch (PDOException $e) {
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            error_log("Error al eliminar la reserva: " . $e->getMessage());
            $_SESSION['errores']= ["Error crítico al eliminar la reserva"];
            header("Location: ../../cliente/gestion_reservas.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../cliente/gestion_reservas.php");
        exit;
    } 
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../cliente/gestion_reservas.php");
    exit;
}





?>