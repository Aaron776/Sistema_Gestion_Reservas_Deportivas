<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_bloqueo = Crypto::decrypt(urldecode($_GET['id_bloqueo']));
if (empty($id_bloqueo) || !is_numeric($id_bloqueo) || $id_bloqueo <= 0) {
    header("Location: gestion_bloqueos.php");
    exit();
}

// Obtener datos del bloqueo que se va a editar
try {
    $sql = $conexion->prepare("SELECT cancha_id,horario_id,fecha,motivo FROM bloqueos WHERE id=:id_bloqueo limit 1");
    $sql->bindParam(":id_bloqueo", $id_bloqueo, PDO::PARAM_INT);
    $sql->execute();
    $bloqueo = $sql->fetch(PDO::FETCH_OBJ);
    if (empty($bloqueo)) {
        header("Location: gestion_bloqueos.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener el bloqueo a editar: " . $e->getMessage());
    header("Location: gestion_bloqueos.php");
    exit();
}

// Obtener canchas
try {
    $sql = $conexion->prepare("SELECT id as id_cancha,nombre FROM canchas");
    $sql->execute();
    $canchas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener canchas: " . $e->getMessage());
    $canchas = [];
}

// Obtener horarios
try {
    $sql = $conexion->prepare("SELECT id as id_horario,hora_inicio,hora_fin FROM horarios");
    $sql->execute();
    $horarios = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener horarios: " . $e->getMessage());
    $horarios = [];
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/editar_bloqueo.css">

<div class="main-form-wrapper">
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-edit"></i>
        <i class="fas fa-calendar-times"></i>
        <i class="fas fa-tools"></i>
    </div>

    <div class="form-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-edit"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Sistema de gestión de reservas deportivas</div>
        </div>

        <div class="form-icon">
            <i class="fas fa-calendar-times"></i>
        </div>

        <h1>Editar Bloqueo de Cancha</h1>
        <div class="info-text">
            Modifique los campos que desea actualizar del bloqueo.
        </div>

        <!-- Badge con información del bloqueo -->
        <div style="text-align: center;">
            <div class="block-badge" id="blockBadge">
                <i class="fas fa-ban"></i> <span id="blockIdDisplay">ID: <?php echo htmlspecialchars($id_bloqueo); ?></span>
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
        <form id="blockForm" action="../controladores/admin/editar_bloqueo.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_bloqueo" value="<?= urlencode(Crypto::encrypt($id_bloqueo)) ?>">

            <!-- Campo: Cancha (select) -->
            <div class="input-group">
                <i class="fas fa-futbol"></i>
                <select name="id_cancha" id="blockCourt" required>
                    <option value="">Seleccionar cancha</option>
                    <?php foreach ($canchas as $item) : ?>
                        <option value="<?= $item->id_cancha ?>" <?php if($item->id_cancha == $bloqueo->cancha_id){ echo 'selected';} ?>>
                            <?= htmlspecialchars($item->nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campo: Fecha -->
            <div class="input-group">
                <i class="fas fa-calendar-alt"></i>
                <input type="date" name="fecha" id="blockDate" value="<?php echo htmlspecialchars($bloqueo->fecha); ?>" required>
            </div>

            <!-- Campo: Horario (select dinámico) -->
            <div class="input-group">
                <i class="fas fa-clock"></i>
                <select name="id_horario" id="blockSchedule" required>
                    <option value="">Seleccionar Horario</option>
                    <?php foreach ($horarios as $item) : ?>
                        <option value="<?php echo $item->id_horario ?>" <?php if($item->id_horario == $bloqueo->horario_id){ echo 'selected';} ?>>
                            <?php echo date('g:i A', strtotime($item->hora_inicio)) ?> - <?php echo date('g:i A', strtotime($item->hora_fin)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campo: Motivo -->
            <div class="input-group">
                <i class="fas fa-comment"></i>
                <textarea name="motivo" id="blockReason" placeholder="Motivo del bloqueo" rows="3" required><?php echo htmlspecialchars($bloqueo->motivo); ?></textarea>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </form>

        <a href="gestion_bloqueos.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Gestión de Bloqueos
        </a>

        <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
            <i class="fas fa-shield-alt"></i> Los cambios se aplicarán inmediatamente
        </div>
    </div>
</div>
<?php include_once '../templates/footer.php'; ?>