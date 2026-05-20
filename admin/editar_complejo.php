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

// Obtener datos del complejo que se va a editar
try {
    $sql = $conexion->prepare("SELECT nombre, descripcion, imagen_url as imagen, direccion, telefono FROM complejos WHERE id=:id_complejo AND estado='activo' limit 1");
    $sql->bindParam(":id_complejo", $id_complejo, PDO::PARAM_INT);
    $sql->execute();
    $complejo = $sql->fetch(PDO::FETCH_OBJ);
    if (empty($complejo)) {
        header("Location: gestion_complejos.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener el complejo a editar: " . $e->getMessage());
    header("Location: gestion_complejos.php");
    exit();
}


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/editar_complejo.css">
<div class="sport-bg">
    <i class="fas fa-building"></i>
    <i class="fas fa-futbol"></i>
    <i class="fas fa-edit"></i>
    <i class="fas fa-map-marker-alt"></i>
    <i class="fas fa-phone-alt"></i>
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
            <i class="fas fa-edit"></i>
        </div>

        <h1>Editar Complejo Deportivo</h1>
        <div class="info-text">
            Modifique los campos que desea actualizar del complejo.
        </div>

        <!-- Badge con ID del complejo -->
        <div style="text-align: center;">
            <div class="complex-badge" id="complexBadge">
                <i class="fas fa-building"></i> <span id="complexIdDisplay">ID: <?= htmlspecialchars($id_complejo) ?></span>
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
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['exito']); ?>
            </div>
            <?php unset($_SESSION['exito']); ?>
        <?php endif; ?>

        <!-- Formulario -->
        <form id="complexForm" action="../controladores/admin/editar_complejo.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="id_complejo" value="<?= urlencode(Crypto::encrypt($id_complejo)) ?>">

            <div class="input-group">
                <i class="fas fa-building"></i>
                <input type="text" id="compName" name="nombre" value="<?= htmlspecialchars($complejo->nombre) ?>" placeholder="Nombre del complejo deportivo" autocomplete="off" required>
            </div>

            <div class="input-group">
                <i class="fas fa-phone-alt"></i>
                <input type="tel" id="compPhone" name="telefono" value="<?= htmlspecialchars($complejo->telefono) ?>" placeholder="Teléfono de contacto" autocomplete="off">
            </div>

            <div class="input-group">
                <i class="fas fa-align-left"></i>
                <textarea id="compDesc" name="descripcion" placeholder="Descripción del complejo (instalaciones, servicios, horarios, etc.)" rows="3"><?= htmlspecialchars($complejo->descripcion) ?></textarea>
            </div>

            <div class="input-group file-input-group">
                <i class="fas fa-image"></i>
                <label for="compImage" class="custom-file-upload">
                    <span id="fileInputText">
                        <?php if (!empty($complejo->imagen)) : ?>
                            <?= htmlspecialchars($complejo->imagen) ?>
                        <?php else : ?>
                            Seleccionar nueva imagen...
                        <?php endif; ?>
                    </span>
                    <input type="file" id="compImage" name="imagen" accept="image/*">
                </label>
            </div>
            <div class="image-preview" id="imagePreviewContainer">
                <img id="previewImg" class="preview-img" src="<?= !empty($complejo->imagen) ? '../app/fotos_complejos/' . htmlspecialchars($complejo->imagen) : 'https://via.placeholder.com/100x100?text=Sin+Imagen' ?>" alt="Vista previa">
                <span class="preview-placeholder" id="previewText">
                    <?php if (!empty($complejo->imagen)) : ?>
                        Imagen actual
                    <?php else : ?>
                        Sin imagen cargada
                    <?php endif; ?>
                </span>
            </div>

            <div class="input-group">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" id="compAddress" name="direccion" value="<?= htmlspecialchars($complejo->direccion) ?>" placeholder="Dirección completa" autocomplete="off" required>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </form>

        <a href="gestion_complejos.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Gestión de Complejos
        </a>

        <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
            <i class="fas fa-shield-alt"></i> Los cambios se aplicarán inmediatamente
        </div>
    </div>
</div>
</div>


<script>
    // Elementos DOM
    const compName = document.getElementById('compName');
    const compPhone = document.getElementById('compPhone');
    const compDesc = document.getElementById('compDesc');
    const compImage = document.getElementById('compImage');
    const compAddress = document.getElementById('compAddress');
    const fileInputText = document.getElementById('fileInputText');
    const btnSubmit = document.getElementById('btnSubmit');
    const previewImg = document.getElementById('previewImg');
    const previewText = document.getElementById('previewText');

    // Previsualizar imagen seleccionada
    compImage.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            fileInputText.innerText = file.name; // Show file name
            const reader = new FileReader();
            previewText.innerText = 'Cargando...';
            previewText.style.color = '#5eead4';

            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewText.innerText = 'Imagen seleccionada correctamente';
                previewText.style.color = '#a3e635';
            };
            reader.readAsDataURL(file);
        } else {
            fileInputText.innerText = 'Seleccionar nueva imagen...'; // Reset text
            previewImg.src = '<?= !empty($complejo->imagen) ? '../app/fotos_complejos/' . htmlspecialchars($complejo->imagen) : 'https://via.placeholder.com/100x100?text=Sin+Imagen' ?>';
            previewText.innerText = '<?php if (!empty($complejo->imagen)) : ?>Imagen actual<?php else : ?>Sin imagen cargada<?php endif; ?>';
            previewText.style.color = '#6b95af';
        }
    });
</script>
<?php include_once '../templates/footer.php'; ?>