<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['id_cliente']) && isset($_POST['id_cancha']) && isset($_POST['fecha']) && isset($_POST['total']) && isset($_POST['id_horario'])) {
     // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir Datos
    $id_cliente = urldecode(Crypto::decrypt($_POST['id_cliente']));
    $id_cancha = trim($_POST['id_cancha']);
    $fecha = trim($_POST['fecha']);
    $total = trim($_POST['total']);
    $id_horario = trim($_POST['id_horario']);
    $errores = [];
    

    // Vaidaciones y Sanitizacion
    if(empty($id_cliente)){
        $errores[]="El ID del cliente es requerido";
    }elseif(!is_numeric($id_cliente) || $id_cliente <= 0){
        $errores[]="El ID del cliente no es válido";
    }

    if(empty($id_cancha)){
        $errores[]="La cancha es requerida";
    }elseif(!is_numeric($id_cancha) || $id_cancha <= 0){
        $errores[]="La cancha no es válida";
    }

    if(empty($fecha)){
        $errores[]="La fecha es requerida";
    }elseif($fecha < date('Y-m-d')){
        $errores[]="La fecha no puede ser en el pasado";
    }

    if(empty($total)){
        $errores[]="El total es requerido";
    }elseif(!is_numeric($total) || $total <= 0){
        $errores[]="El total no es válido";
    }

    if(empty($id_horario)){
        $errores[]="El horario es requerido";
    }elseif(!is_numeric($id_horario) || $id_horario <= 0){
        $errores[]="El horario no es válido";
    }

    // Validar que no exista otra reserva en la mismca cancha en la misma fecha y en el mismo horario y que su estado este confirmada
    if(empty($errores)){
        try {
            $sql = $conexion->prepare("SELECT id FROM reservas WHERE cancha_id = :id_cancha AND fecha = :fecha AND horario_id = :id_horario AND estado IN ('confirmada', 'pendiente')");
            $sql->bindParam(':id_cancha', $id_cancha, PDO::PARAM_INT);
            $sql->bindParam(':fecha', $fecha, PDO::PARAM_STR);
            $sql->bindParam(':id_horario', $id_horario, PDO::PARAM_INT);
            $sql->execute();
            $reserva = $sql->fetch(PDO::FETCH_OBJ);
            if($reserva){
                $errores[]="Ya existe una reserva en la misma cancha en la misma fecha y en el mismo horario";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la reserva: " . $e->getMessage());
            $errores[]="Error al verificar la reserva";
        }
    }

    // Validar que el cliente no tenga otra reserva en el mismo horario, la misma fecha y que su estado este confirmada
    if(empty($errores)){
        try {
            $sql = $conexion->prepare("SELECT id FROM reservas WHERE usuario_id = :id_cliente AND horario_id = :id_horario AND fecha = :fecha AND estado IN ('confirmada', 'pendiente')");
            $sql->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
            $sql->bindParam(':id_horario', $id_horario, PDO::PARAM_INT);
            $sql->bindParam(':fecha', $fecha, PDO::PARAM_STR);
            $sql->execute();
            $reserva = $sql->fetch(PDO::FETCH_OBJ);
            if($reserva){
                $errores[]="Ya existe una reserva en el mismo horario en la misma fecha";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la reserva: " . $e->getMessage());
            $errores[]="Error al verificar la reserva";
        }
    }

    if(empty($errores)){
        try {
            $conexion->beginTransaction();

            $sql = $conexion->prepare("INSERT INTO reservas (usuario_id, cancha_id, fecha, horario_id, total) VALUES (:id_cliente, :id_cancha, :fecha, :id_horario, :total)");
            $sql->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
            $sql->bindParam(':id_cancha', $id_cancha, PDO::PARAM_INT);
            $sql->bindParam(':fecha', $fecha, PDO::PARAM_STR);
            $sql->bindParam(':id_horario', $id_horario, PDO::PARAM_INT);
            $sql->bindParam(':total', $total, PDO::PARAM_STR);
            $sql->execute();

            // Obtener el ID de la reserva insertada
            $id_reserva = $conexion->lastInsertId();

            // En la tabla Pagos insertar el registro del pago de esta reserva en pendiente con el monto total
            $sql_pago = $conexion->prepare("INSERT INTO pagos (reserva_id, monto, estado) VALUES (:id_reserva, :monto, 'pendiente')");
            $sql_pago->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
            $sql_pago->bindParam(':monto', $total, PDO::PARAM_STR);
            $sql_pago->execute();

            // INSERTAR NOTIFICACIÓN PARA EL CLIENTE
            $mensaje_notificacion = "Has registrado exitosamente una nueva reserva para la fecha " . date('d/m/Y', strtotime($fecha)) . ". Su estado actual es Pendiente.";
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) VALUES (:user_id, 'reserva', :mensaje, 'no')");
            $sql_notif->bindParam(':user_id', $id_cliente, PDO::PARAM_INT);
            $sql_notif->bindParam(':mensaje', $mensaje_notificacion, PDO::PARAM_STR);
            $sql_notif->execute();

            $conexion->commit();

            $_SESSION['exito']="Reserva registrada exitosamente";
            header("Location: ../../cliente/registrar_reserva.php");
            exit;
        } catch (PDOException $e) {
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            error_log("Error al registrar la reserva: " . $e->getMessage());
            $_SESSION['errores']= ["Error crítico al registrar la reserva"];
            header("Location: ../../cliente/registrar_reserva.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../cliente/registrar_reserva.php");
        exit;
    } 
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../cliente/gestion_reservas.php");
    exit;
}





?>