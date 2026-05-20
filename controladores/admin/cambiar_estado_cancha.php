<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cancha']) && isset($_POST['estado'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_cancha = trim(Crypto::decrypt($_POST['id_cancha']));
    $estado = trim($_POST['estado']);
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_cancha)) {
        $errores[] = "El ID de la cancha es requerido";
    } elseif (!is_numeric($id_cancha) || $id_cancha <= 0) {
        $errores[] = "El ID de la cancha no es válido";
    }

    $estados_posibles = ['disponible', 'mantenimiento'];
    if (empty($estado)) {
        $errores[] = "El estado de la cancha es requerido";
    } elseif (!in_array($estado, $estados_posibles)) {
        $errores[] = "El estado de la cancha no es válido";
    }

    // Verificar que la cancha que se va a cambiar de estado exista
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT canchas.id,canchas.nombre as nombre_cancha,canchas.complejo_id,complejos.nombre as complejo_nombre FROM canchas INNER JOIN complejos ON canchas.complejo_id = complejos.id WHERE canchas.id=:id_cancha LIMIT 1");
            $sql->bindParam(":id_cancha", $id_cancha, PDO::PARAM_INT);
            $sql->execute();
            $cancha = $sql->fetch(PDO::FETCH_OBJ);
            if (!$cancha) {
                $errores[] = "La cancha que desea cambiar el estado no existe";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la cancha: " . $e->getMessage());
            $errores[] = "Error de seguridad al validar la cancha";
        }
    }

    // Si no hay errores, cambiar el estado de la cancha
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // 1. Cambiar el estado de la cancha seleccionada
            $sql = $conexion->prepare("UPDATE canchas SET estado=:estado WHERE id=:id_cancha");
            $sql->bindParam(":estado", $estado, PDO::PARAM_STR);
            $sql->bindParam(":id_cancha", $id_cancha, PDO::PARAM_INT);
            $sql->execute();

            // 2. Obtener destinatarios de notificación (Admins y Recepcionistas)
            $stmt_users = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista')");
            $stmt_users->execute();
            $usuarios_notif = $stmt_users->fetchAll(PDO::FETCH_OBJ);

            // 3. Registrar Notificación individual
            $mensaje_notif = "El administrador " . $_SESSION['nombre'] . " ha cambiado el estado de la cancha: " . $cancha->nombre_cancha . " del complejo: " . $cancha->complejo_nombre . " a: " . $estado;

            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) 
                                           VALUES (:id_usuario, 'sistema', :mensaje, 'no')");

            foreach ($usuarios_notif as $item) {
                $sql_notif->bindParam(":id_usuario", $item->id, PDO::PARAM_INT);
                $sql_notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $sql_notif->execute();
            }

            $conexion->commit();

            $_SESSION['exito'] = "¡Estado de la cancha cambiado correctamente!";
            header("Location: ../../admin/cambiar_estado_cancha.php?id_cancha=" . urlencode(Crypto::encrypt($id_cancha)));
            exit;
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error al cambiar el estado de la cancha: " . $e->getMessage());
            $errores[] = "Error crítico al intentar cambiar el estado de la cancha";
            $_SESSION['errores'] = $errores;
            header("Location: ../../admin/cambiar_estado_cancha.php?id_cancha=" . urlencode(Crypto::encrypt($id_cancha)));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/cambiar_estado_cancha.php?id_cancha=" . urlencode(Crypto::encrypt($id_cancha)));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_complejos.php");
    exit;
}
