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

// Consultar Pago de esta reserva y verifiacr si el campo estado del pago es "pagado" y guardar en una variable
$sql_pago = $conexion->prepare("SELECT estado as estado_pago FROM pagos WHERE reserva_id = :id_reserva");
$sql_pago->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
$sql_pago->execute();
$pago = $sql_pago->fetch(PDO::FETCH_OBJ);
if ($pago->estado_pago === "pagado") {
    $_SESSION['exito'] = ["La reserva ya se encuentra pagada"];
    header("Location: gestion_reservas.php");
    exit();
}


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/registrar_pago.css">

<div class="main-form-wrapper">
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-credit-card"></i>
        <i class="fas fa-money-bill-wave"></i>
        <i class="fas fa-receipt"></i>
    </div>

    <div class="form-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-cash-register"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Módulo de Facturación y Pagos</div>
        </div>

        <h1>Registrar Pago</h1>
        <div class="info-text">
            Complete el cobro de la reserva seleccionada para finalizar el proceso.
        </div>

        <!-- Tarjeta de Resumen Financiero -->
        <div class="finance-card">
            <div>
                <div class="total-label">Importe Total</div>
                <div class="total-amount">$<?= number_format($reserva->total, 2) ?></div>
            </div>
            <div class="finance-details">
                <div class="detail-group">
                    <div class="detail-item"><i class="fas fa-user"></i> <?= htmlspecialchars(ucwords($reserva->nombre_cliente)) ?></div>
                    <div class="detail-item" style="margin-top: 4px;"><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($reserva->fecha)) ?></div>
                </div>
                <div class="detail-group" style="text-align: right;">
                    <div class="detail-item"><i class="fas fa-futbol"></i> <?= htmlspecialchars(ucwords($reserva->nombre_cancha)) ?></div>
                    <div class="detail-item" style="margin-top: 4px;"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($reserva->hora_inicio)) ?></div>
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

        <!-- Formulario de Pago -->
        <form action="../controladores/recepcionista/registrar_pago.php" method="POST" enctype="multipart/form-data" id="paymentForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_reserva" value="<?= urlencode(Crypto::encrypt($reserva->id)) ?>">

            <div class="input-group">
                <label>Fecha de Pago</label>
                <i class="fas fa-calendar-alt input-icon"></i>
                <input type="datetime-local" name="fecha_pago" class="input-control" value="<?= htmlspecialchars(date('Y-m-d\TH:i')) ?>" readonly>
            </div>

            <div class="input-group">
                <label>Monto a Cobrar</label>
                <i class="fas fa-dollar-sign input-icon"></i>
                <input type="number" step="0.01" name="monto" class="input-control" value="<?= htmlspecialchars($reserva->total) ?>" required>
            </div>

            <div class="input-group">
                <label>Método de Pago</label>
                <i class="fas fa-wallet input-icon"></i>
                <select name="metodo" class="input-control" required id="metodoSelect">
                    <option value="efectivo">💵 Efectivo</option>
                    <option value="tarjeta">💳 Tarjeta (Débito / Crédito)</option>
                    <option value="transferencia">📱 Transferencia Bancaria</option>
                </select>
            </div>

            <div class="input-group" id="referenciaGroup">
                <label>Referencia / Nº Operación</label>
                <i class="fas fa-hashtag input-icon"></i>
                <input type="text" name="referencia_transaccion" class="input-control" placeholder="Ej: TXN-123456" maxlength="100">
            </div>

            <div class="input-group" id="comprobanteContainer">
                <label>Comprobante de Pago (Imagen/PDF)</label>
                <div class="file-upload-container">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span id="fileName">Haz clic para subir comprobante</span>
                    <input type="file" name="comprobante" id="fileInput" accept="image/*,.pdf">
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-check-circle"></i> Procesar Registro de Pago
            </button>
        </form>

        <a href="gestion_reservas.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Gestión
        </a>
    </div>
</div>

<script src="../app/js/recepcionista/registrar_pago.js"></script>
<?php include_once '../templates/footer.php'; ?>