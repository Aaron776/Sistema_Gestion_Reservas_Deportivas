<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_reserva = Crypto::decrypt(urldecode($_GET['id_reserva']));

if (empty($id_reserva) || $id_reserva <= 0 || !is_numeric($id_reserva)) {
    header("Location: gestion_reservas.php");
    exit();
}

// Obtener datos de la reserva
$sql = $conexion->prepare("SELECT r.*,u.nombre as nombre_cliente,c.nombre as nombre_cancha,cm.nombre as nombre_complejo,h.hora_inicio,h.hora_fin 
                           FROM reservas r 
                           INNER JOIN usuarios u ON r.usuario_id = u.id 
                           INNER JOIN canchas c ON r.cancha_id = c.id 
                           INNER JOIN complejos cm ON c.complejo_id = cm.id 
                           INNER JOIN horarios h ON r.horario_id = h.id 
                           WHERE r.id = :id_reserva");
$sql->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
$sql->execute();
$reserva = $sql->fetch(PDO::FETCH_OBJ);

if (!$reserva) {
    header("Location: gestion_reservas.php");
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/cambiar_estado_reserva.css">
<div class="main-form-wrapper">
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-exchange-alt"></i>
        <i class="fas fa-calendar-check"></i>
        <i class="fas fa-flag-checkered"></i>
    </div>

    <div class="form-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-exchange-alt"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Sistema de gestión de reservas deportivas</div>
        </div>

        <div class="form-icon">
            <i class="fas fa-exchange-alt"></i>
        </div>

        <h1>Cambiar Estado de Reserva</h1>
        <div class="info-text">
            Actualice el estado de la reserva para reflejar su situación actual.
        </div>

        <!-- Tarjeta de información de la reserva (simulada) -->
        <div class="reserva-info-card">
            <div class="reserva-icon">
                <i class="fas fa-futbol"></i>
            </div>
            <div class="reserva-details">
                <h3 id="reservaCancha"><?php echo htmlspecialchars(ucfirst($reserva->nombre_cancha)); ?></h3>
                <p id="reservaInfo"><i class="fas fa-building"></i> Complejo: <?php echo htmlspecialchars(ucfirst($reserva->nombre_complejo)); ?> • Cliente: <?php echo htmlspecialchars(ucfirst($reserva->nombre_cliente)); ?></p>
                <p id="reservaFechaHora"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($reserva->fecha); ?> • <?php echo htmlspecialchars($reserva->hora_inicio); ?> - <?php echo htmlspecialchars($reserva->hora_fin); ?></p>
                <div>
                    <span class="current-status status-<?php echo $reserva->estado; ?>" id="currentStatusBadge"><?php echo htmlspecialchars(ucfirst($reserva->estado)); ?></span>
                </div>
            </div>
        </div>

        <!-- Alerts for success/error messages (Dinámico) -->
        <?php if (isset($_SESSION['errores'])) : ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($_SESSION['errores'] as $error) : ?>
                        <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errores']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['exito'])) : ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars(is_array($_SESSION['exito']) ? $_SESSION['exito'][0] : $_SESSION['exito']); ?>
            </div>
            <?php unset($_SESSION['exito']); ?>
        <?php endif; ?>

        <!-- Formulario -->
        <form id="statusForm" action="../controladores/recepcionista/cambiar_estado_reserva.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_reserva" value="<?= urlencode(Crypto::encrypt($id_reserva)) ?>">
            <div class="input-group">
                <i class="fas fa-tag"></i>
                <select id="reservaStatus" name="estado">
                    <option value="confirmada" class="option-confirmada" <?php echo ($reserva->estado == 'confirmada') ? 'selected' : ''; ?>>✅ Confirmada - Reserva activa y confirmada</option>
                    <option value="cancelada" class="option-cancelada" <?php echo ($reserva->estado == 'cancelada') ? 'selected' : ''; ?>>❌ Cancelada - Reserva cancelada</option>
                    <option value="finalizada" class="option-finalizada" <?php echo ($reserva->estado == 'finalizada') ? 'selected' : ''; ?>>🏁 Finalizada - Reserva completada</option>
                </select>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="fas fa-save"></i> Actualizar Estado
            </button>
        </form>

        <a href="gestion_reservas.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Gestión de Reservas
        </a>

        <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
            <i class="fas fa-shield-alt"></i> Cambiar el estado afectará la disponibilidad y los reportes
        </div>
    </div>
</div>
<?php include_once '../templates/footer.php'; ?>