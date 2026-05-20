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

$id_complejo = Crypto::decrypt(urldecode($_GET['id_complejo']));
if (empty($id_complejo) || !is_numeric($id_complejo) || $id_complejo <= 0) {
    header("Location: gestion_complejos.php");
    exit();
}

// Obtener nombre del complejo
try {
    $sql = $conexion->prepare("SELECT nombre FROM complejos WHERE id = :id_complejo");
    $sql->bindParam(':id_complejo', $id_complejo, PDO::PARAM_INT);
    $sql->execute();
    $complejo = $sql->fetch(PDO::FETCH_OBJ);
    $nombre_complejo = $complejo ? htmlspecialchars($complejo->nombre) : 'Complejo desconocido';
} catch (PDOException $e) {
    error_log("Error al obtener nombre del complejo: " . $e->getMessage());
    $nombre_complejo = 'Complejo desconocido';
    header("Location: gestion_complejos.php");
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/agregar_cancha.css">
<div class="sport-bg">
    <i class="fas fa-futbol"></i>
    <i class="fas fa-basketball-ball"></i>
    <i class="fas fa-table-tennis"></i>
    <i class="fas fa-plus-circle"></i>
    <i class="fas fa-volleyball-ball"></i>
</div>

<div class="main-form-wrapper">
    <div class="form-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-futbol"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Sistema de gestión de reservas deportivas</div>
        </div>

        <div class="form-icon">
            <i class="fas fa-plus-circle"></i>
        </div>

        <h1>Agregar Nueva Cancha</h1>
        <div class="info-text">
            Complete los siguientes campos para registrar una nueva cancha en el complejo deportivo.
        </div>

        <!-- Badge con información del complejo -->
        <div style="text-align: center;">
            <div class="complex-badge" id="complexBadge">
                <i class="fas fa-building"></i> <span id="complexName"><?php echo $nombre_complejo; ?></span>
            </div>
        </div>

        <!-- Alerts for success/error messages -->
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
        <form id="courtForm" action="../controladores/admin/agregar_cancha.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_complejo" value="<?= urlencode(Crypto::encrypt($id_complejo)) ?>">

            <div class="input-group">
                <i class="fas fa-tag"></i>
                <input type="text" name="nombre" id="courtName" placeholder="Nombre de la cancha (ej: Cancha Central, Pista 1, etc.)" autocomplete="off" required>
            </div>

            <div class="input-group">
                <i class="fas fa-align-left"></i>
                <textarea name="descripcion" id="courtDesc" placeholder="Descripción de la cancha (dimensiones, iluminación, superficie, etc.)" rows="3"></textarea>
            </div>

            <div class="input-group file-input-group">
                <i class="fas fa-image"></i>
                <label for="courtImage" class="custom-file-upload">
                    <span id="fileInputText">Seleccionar foto de la cancha...</span>
                    <input type="file" id="courtImage" name="imagen" accept="image/*">
                </label>
            </div>
            <div class="image-preview" id="imagePreviewContainer">
                <img id="previewImg" class="preview-img" src="https://via.placeholder.com/100x100?text=Foto" alt="Vista previa">
                <span class="preview-placeholder" id="previewText">Vista previa de la imagen</span>
            </div>

            <div class="input-group">
                <i class="fas fa-futbol"></i>
                <select name="tipo" id="courtType">
                    <option value="futbol">⚽ Fútbol</option>
                    <option value="padel">🎾 Pádel</option>
                    <option value="tenis">🎾 Tenis</option>
                    <option value="basket">🏀 Básquetbol</option>
                    <option value="voley">🏐 Voleibol</option>
                    <option value="patinaje">🛼 Patinaje</option>
                </select>
            </div>

            <div class="input-group">
                <i class="fas fa-dollar-sign"></i>
                <input type="number" name="precio_hora" id="courtPrice" placeholder="Precio por hora (ej: 350)" step="0.01" autocomplete="off" required>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="fas fa-save"></i> Registrar Cancha
            </button>
        </form>

        <a href="gestion_canchas_complejo.php?id_complejo=<?php echo urlencode(Crypto::encrypt($id_complejo)); ?>" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Gestión de Canchas
        </a>

        <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
            <i class="fas fa-shield-alt"></i> Los datos se almacenan de forma segura
        </div>
    </div>
</div>

<script src="../app/js/admin/agregar_cancha.js"></script>
<?php include_once '../templates/footer.php'; ?>