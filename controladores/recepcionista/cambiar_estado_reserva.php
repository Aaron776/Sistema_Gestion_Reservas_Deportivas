<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['estado']) && isset($_POST['id_reserva'])) {
     // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir Datos
    $estado = trim($_POST['estado']);
    $id_reserva = urldecode(Crypto::decrypt($_POST['id_reserva']));
    $errores = [];
    

    // Vaidaciones y Sanitizacion
    
    if(empty($id_reserva)){
        $errores[]="El ID de la reserva es requerido";
    }elseif(!is_numeric($id_reserva) || $id_reserva <= 0){
        $errores[]="El ID de la reserva no es válido";
    }

    if(empty($estado)){
        $errores[]="El estado es requerido";
    }elseif(!in_array($estado, ['confirmada', 'cancelada', 'finalizada'])){
        $errores[]="El estado no es válido";
    }


    // Verificar que esa reserva exista y obtener el dueño
    try {
        $sql = $conexion->prepare("SELECT id, usuario_id FROM reservas WHERE id = :id_reserva");
        $sql->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
        $sql->execute();
        $reserva = $sql->fetch(PDO::FETCH_OBJ);
        if(!$reserva){
            $errores[]="No existe la reserva que quieres modificar";
        } else {
            $id_cliente = $reserva->usuario_id;
        }
    } catch (PDOException $e) {
        error_log("Error al verificar la reserva: " . $e->getMessage());
        $errores[]="Error al verificar la reserva";
    }

    // Si no hay errores, procedemos a cambiar el estado de la reserva
    if(empty($errores)){
        try {
            $conexion->beginTransaction();

            $sql = $conexion->prepare("UPDATE reservas SET estado = :estado WHERE id = :id_reserva");
            $sql->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
            $sql->bindParam(':estado', $estado, PDO::PARAM_STR);
            $sql->execute();

            // MEJORA: Si la reserva se cancela, marcamos su pago pendiente como 'fallido' para limpiar métricas financieras
            if ($estado === 'cancelada') {
                $sql_pago = $conexion->prepare("UPDATE pagos SET estado = 'fallido' WHERE reserva_id = :id_reserva AND estado = 'pendiente'");
                $sql_pago->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
                $sql_pago->execute();
            }

            // INSERTAR NOTIFICACIÓN PARA EL CLIENTE
            $mensaje_notificacion = "Un recepcionista ha cambiado el estado de tu reserva a: " . ucfirst($estado) . ".";
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) VALUES (:user_id, 'reserva', :mensaje, 'no')");
            $sql_notif->bindParam(':user_id', $id_cliente, PDO::PARAM_INT);
            $sql_notif->bindParam(':mensaje', $mensaje_notificacion, PDO::PARAM_STR);
            $sql_notif->execute();

            $conexion->commit();

            $_SESSION['exito']="Estado cambiado exitosamente";
            header("Location: ../../recepcionista/cambiar_estado_reserva.php?id_reserva=" . urlencode(Crypto::encrypt($id_reserva)));
            exit;
        } catch (PDOException $e) {
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            error_log("Error al editar la reserva: " . $e->getMessage());
            $_SESSION['errores']= ["Error crítico al editar la reserva"];
            header("Location: ../../recepcionista/cambiar_estado_reserva.php?id_reserva=" . urlencode(Crypto::encrypt($id_reserva)));
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../recepcionista/cambiar_estado_reserva.php?id_reserva=" . urlencode(Crypto::encrypt($id_reserva)));
        exit;
    } 
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../recepcionista/gestion_reservas.php");
    exit;
}





?>