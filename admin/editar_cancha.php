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

$id_cancha = Crypto::decrypt(urldecode($_GET['id_cancha']));
if (empty($id_cancha) || !is_numeric($id_cancha) || $id_cancha <= 0) {
    header("Location: gestion_complejos.php");
    exit();
}

// Obtener nombre del cancha
try {
    $sql = $conexion->prepare("SELECT c.nombre, c.descripcion, c.imagen_url as imagen, c.tipo, c.precio_hora, c.estado, c.complejo_id, cp.nombre as nombre_complejo
                               FROM canchas c 
                               INNER JOIN complejos cp ON c.complejo_id = cp.id 
                               WHERE c.id = :id_cancha");
    $sql->bindParam(':id_cancha', $id_cancha, PDO::PARAM_INT);
    $sql->execute();
    $cancha = $sql->fetch(PDO::FETCH_OBJ);
    if (!$cancha) {
        header("Location: gestion_complejos.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener datos de la cancha: " . $e->getMessage());
    header("Location: gestion_complejos.php");
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/editar_cancha.css">
</head>

<body>
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-edit"></i>
        <i class="fas fa-table-tennis"></i>
        <i class="fas fa-volleyball-ball"></i>
    </div>

    <div class="main-form-wrapper">
        <div class="form-container">
            <div class="logo-area">
                <div class="logo">
                    <i class="fas fa-edit"></i>
                    <span>SportReserve</span>
                </div>
                <div class="subtitle">Sistema de gestión de reservas deportivas</div>
            </div>

            <div class="form-icon">
                <i class="fas fa-edit"></i>
            </div>

            <h1>Editar Cancha</h1>
            <div class="info-text">
                Modifique los campos que desea actualizar de la cancha.
            </div>

            <!-- Badge con información de la cancha -->
            <div style="text-align: center;">
                <div class="court-badge" id="courtBadge">
                    <i class="fas fa-futbol"></i> <span id="courtIdDisplay">ID: <?= htmlspecialchars($id_cancha) ?></span>
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
            <form id="courtForm" action="../controladores/admin/editar_cancha.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_cancha" value="<?= urlencode(Crypto::encrypt($id_cancha)) ?>">
                <input type="hidden" name="id_complejo" value="<?= urlencode(Crypto::encrypt($cancha->complejo_id)) ?>">

                <div class="input-group">
                    <i class="fas fa-tag"></i>
                    <input type="text" name="nombre" id="courtName" value="<?= htmlspecialchars($cancha->nombre) ?>" placeholder="Nombre de la cancha" autocomplete="off" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-align-left"></i>
                    <textarea name="descripcion" id="courtDesc" placeholder="Descripción de la cancha (dimensiones, iluminación, superficie, etc.)" rows="3"><?= htmlspecialchars($cancha->descripcion) ?></textarea>
                </div>

                <div class="input-group file-input-group">
                    <i class="fas fa-image"></i>
                    <label for="courtImage" class="custom-file-upload">
                        <span id="fileInputText"><?= !empty($cancha->imagen) ? htmlspecialchars($cancha->imagen) : 'Seleccionar nueva foto...' ?></span>
                        <input type="file" id="courtImage" name="imagen" accept="image/*">
                    </label>
                </div>
                <div class="image-preview" id="imagePreviewContainer">
                    <img id="previewImg" class="preview-img" src="<?= !empty($cancha->imagen) ? '../app/fotos_canchas/' . htmlspecialchars($cancha->imagen) : 'https://via.placeholder.com/100x100?text=Sin+Foto' ?>" alt="Vista previa">
                    <span class="preview-placeholder" id="previewText"><?= !empty($cancha->imagen) ? 'Imagen actual' : 'Sin imagen cargada' ?></span>
                </div>

                <div class="input-group">
                    <i class="fas fa-sports"></i>
                    <select name="tipo" id="courtType">
                        <option value="futbol" <?= $cancha->tipo == 'futbol' ? 'selected' : '' ?>>⚽ Fútbol</option>
                        <option value="padel" <?= $cancha->tipo == 'padel' ? 'selected' : '' ?>>🎾 Pádel</option>
                        <option value="tenis" <?= $cancha->tipo == 'tenis' ? 'selected' : '' ?>>🎾 Tenis</option>
                        <option value="basket" <?= $cancha->tipo == 'basket' ? 'selected' : '' ?>>🏀 Básquetbol</option>
                        <option value="voley" <?= $cancha->tipo == 'voley' ? 'selected' : '' ?>>🏐 Voleibol</option>
                        <option value="patinaje" <?= $cancha->tipo == 'patinaje' ? 'selected' : '' ?>>🛼 Patinaje</option>
                        <option value="otro" <?= $cancha->tipo == 'otro' ? 'selected' : '' ?>>🏟️ Otro</option>
                    </select>
                </div>

                <div class="input-group">
                    <i class="fas fa-dollar-sign"></i>
                    <input type="number" name="precio_hora" id="courtPrice" value="<?= htmlspecialchars($cancha->precio_hora) ?>" placeholder="Precio por hora (ej: 350)" step="0.01" autocomplete="off" required>
                </div>


                <button type="submit" class="btn-submit" id="btnSubmit">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </form>

            <a href="gestion_canchas_complejo.php?id_complejo=<?= urlencode(Crypto::encrypt($cancha->complejo_id)) ?>" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Gestión de Canchas
            </a>

            <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
                <i class="fas fa-shield-alt"></i> Los cambios se aplicarán inmediatamente
            </div>
        </div>
    </div>

    <script src="../app/js/admin/editar_cancha.js"></script>
    <?php include_once '../templates/footer.php'; ?>