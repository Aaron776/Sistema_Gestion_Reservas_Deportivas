<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['id_cancha']) && isset($_POST['id_horario']) && isset($_POST['motivo']) && isset($_POST['fecha'])){
     // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    $id_cancha = (int)trim($_POST['id_cancha']);
    $id_horario = (int)trim($_POST['id_horario']);
    $motivo = trim($_POST['motivo']);
    $fecha=trim($_POST['fecha']);
    $errores=[];

    // Validadicones y Sanitizacion
    if(empty($id_cancha)){
        $errores[]="El ID de la cancha es requerido";
    }elseif(!is_numeric($id_cancha) || $id_cancha <= 0){
        $errores[]="El ID de la cancha no es válido";
    }

    if(empty($id_horario)){
        $errores[]="El ID del horario es requerido";
    }elseif(!is_numeric($id_horario) || $id_horario <= 0){
        $errores[]="El ID del horario no es válido";
    }

    if(empty($motivo)){
        $errores[]="El motivo es requerido";
    }elseif(strlen($motivo) > 255){
        $errores[]="El motivo es muy largo (máximo 255 caracteres)";
    }

    if(empty($fecha)){
        $errores[]="La fecha es requerida";
    }elseif(strtotime($fecha) < strtotime(date('Y-m-d'))){
        $errores[]="La fecha no puede ser anterior al día de hoy";
    }

    // Verifiacr si ya existe un bloqueo con la misma cancha y horario en la misma fecha
    if(empty($errores)){
        $sql = $conexion->prepare("SELECT id FROM bloqueos WHERE cancha_id = :cancha_id AND horario_id = :horario_id AND fecha = :fecha limit 1");
        $sql->bindParam(":cancha_id", $id_cancha, PDO::PARAM_INT);
        $sql->bindParam(":horario_id", $id_horario, PDO::PARAM_INT);
        $sql->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $sql->execute();
        $bloqueo_existe=$sql->fetch(PDO::FETCH_OBJ);
        if($bloqueo_existe){
            $errores[]="Ya existe un bloqueo con la misma cancha y horario en la misma fecha";
        }
    }

    // Verificar si existe una reservación activa para esa cancha en ese horario y fecha
    try{
        $sql = $conexion->prepare("SELECT id FROM reservas WHERE cancha_id = :cancha_id AND horario_id = :horario_id AND fecha = :fecha AND estado IN ('confirmada', 'pendiente') limit 1");
        $sql->bindParam(":cancha_id", $id_cancha, PDO::PARAM_INT);
        $sql->bindParam(":horario_id", $id_horario, PDO::PARAM_INT);
        $sql->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $sql->execute();
        $reserva_activa=$sql->fetch(PDO::FETCH_OBJ);
        if($reserva_activa){
            $errores[]="Ya existe una reservación activa para esa cancha en ese horario y fecha";
        }
    }catch(PDOException $e){
        error_log("Error al verificar reservación activa: " . $e->getMessage());
        $errores[]="Error al verificar reservación activa";
    }

    
    // Si no hay errores, procedemos a guardar en la base de datos
    if(empty($errores)){
        try{
            $conexion->beginTransaction();
            // 1. Registrar Bloqueo
            $sql=$conexion->prepare("INSERT INTO bloqueos(cancha_id,fecha,horario_id,motivo) VALUES(:cancha_id,:fecha,:horario_id,:motivo)");
            $sql->bindParam(":cancha_id", $id_cancha, PDO::PARAM_INT);
            $sql->bindParam(":horario_id", $id_horario, PDO::PARAM_INT);
            $sql->bindParam(":fecha", $fecha, PDO::PARAM_STR);
            $sql->bindParam(":motivo", $motivo, PDO::PARAM_STR);
            $sql->execute();

            // 2. NOTIFICACIONES (Admins y Recepcionistas)
            $stmt_users = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista')");
            $stmt_users->execute();
            $usuarios_notif = $stmt_users->fetchAll(PDO::FETCH_OBJ);
            
            // Obtener nombre de la cancha para la notificación
            $sql_cancha = $conexion->prepare("SELECT nombre FROM canchas WHERE id = :id_cancha");
            $sql_cancha->bindParam(":id_cancha", $id_cancha, PDO::PARAM_INT);
            $sql_cancha->execute();
            $cancha_obj = $sql_cancha->fetch(PDO::FETCH_OBJ);
            $nombre_cancha = $cancha_obj ? $cancha_obj->nombre : "ID " . $id_cancha;

            $mensaje_notif = "Se ha registrado un nuevo bloqueo para la cancha " . $nombre_cancha . " el día " . $fecha . ". Motivo: " . $motivo;
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) VALUES (:id_usuario, 'sistema', :mensaje, 'no')");
            foreach ($usuarios_notif as $user) {
                $sql_notif->bindParam(":id_usuario", $user->id, PDO::PARAM_INT);
                $sql_notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $sql_notif->execute();
            }
            
            // 3. Confirmar transacción
            $conexion->commit();
            $_SESSION['exito']="Bloqueo registrado exitosamente";
            header("Location: ../../admin/agregar_bloqueo.php");
            exit();
        }catch(PDOException $e){
            if($conexion->inTransaction()) $conexion->rollBack();
            error_log("Error al agregar bloqueo: " . $e->getMessage());
            $errores[]="Error crítico al agregar el bloqueo.";
            $_SESSION['errores']=$errores;
            header("Location: ../../admin/agregar_bloqueo.php");
            exit();
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/agregar_bloqueo.php");
        exit();
    }
}else{
    $_SESSION['errores']=['Solicitud invalida'];
    header("Location: ../../admin/gestion_bloqueos.php");
    exit();
}






?>