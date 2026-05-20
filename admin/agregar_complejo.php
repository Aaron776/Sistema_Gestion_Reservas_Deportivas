<?php
require_once '../autorizacion/auth.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/agregar_complejo.css">
<div class="sport-bg">
    <i class="fas fa-building"></i>
    <i class="fas fa-futbol"></i>
    <i class="fas fa-map-marker-alt"></i>
    <i class="fas fa-phone-alt"></i>
    <i class="fas fa-image"></i>
</div>

<div class="main-form-wrapper">
    <div class="form-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-building"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Sistema de gestión de reservas deportivas</div>
        </div>

        <div class="form-icon">
            <i class="fas fa-building"></i>
        </div>

        <h1>Agregar Nuevo Complejo</h1>
        <div class="info-text">
            Complete los siguientes campos para registrar un nuevo complejo deportivo en el sistema.
        </div>

        <!-- Formulario -->
        <form id="complexForm" action="../controladores/admin/agregar_complejo.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
            <div class="input-group">
                <i class="fas fa-building"></i>
                <input type="text" id="compName" name="nombre" placeholder="Nombre del complejo deportivo" autocomplete="off" required>
            </div>

            <div class="input-group file-input-group">
                <i class="fas fa-image"></i>
                <label for="compImage" class="custom-file-upload">
                    <span id="fileInputText">Seleccionar imagen...</span>
                    <input type="file" id="compImage" name="imagen" accept="image/*">
                </label>
            </div>
            <div class="image-preview" id="imagePreviewContainer">
                <img id="previewImg" class="preview-img" src="https://via.placeholder.com/60x60?text=Foto" alt="Vista previa">
                <span class="preview-placeholder" id="previewText">Selecciona una foto para el complejo</span>
            </div>

            <div class="input-group">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" id="compAddress" name="direccion" placeholder="Dirección completa" autocomplete="off" required>
            </div>

            <div class="input-group">
                <i class="fas fa-align-left"></i>
                <textarea id="compDesc" name="descripcion" placeholder="Descripción del complejo (instalaciones, servicios, horarios, etc.)" rows="3"></textarea>
            </div>

            <div class="input-group">
                <i class="fas fa-phone-alt"></i>
                <input type="tel" id="compPhone" name="telefono" placeholder="Teléfono de contacto" autocomplete="off">
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="fas fa-save"></i> Registrar Complejo
            </button>
        </form>

        <a href="gestion_complejos.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Gestión de Complejos
        </a>

        <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
            <i class="fas fa-shield-alt"></i> Los datos se almacenan de forma segura
        </div>
    </div>
</div>

<script src="../app/js/admin/agregar_complejo.js"></script>
<?php include_once '../templates/footer.php'; ?>