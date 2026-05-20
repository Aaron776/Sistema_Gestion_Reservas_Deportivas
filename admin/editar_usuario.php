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

$id_usuario = Crypto::decrypt(urldecode($_GET['id_usuario']));
if (empty($id_usuario) || !is_numeric($id_usuario) || $id_usuario <= 0) {
    header("Location: gestion_usuarios.php");
    exit();
}

// Obtener datos del usaurio que se va a editar
try {
    $sql = $conexion->prepare("SELECT nombre, email, rol,telefono FROM usuarios WHERE id=:id_usuario AND estado='activo' limit 1");
    $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
    $sql->execute();
    $usuario = $sql->fetch(PDO::FETCH_OBJ);
    if (empty($usuario)) {
        header("Location: gestion_usuarios.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener el usuario a editar: " . $e->getMessage());
    header("Location: gestion_usuarios.php");
    exit();
}


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/editar_usuario.css">

<div class="sport-bg">
    <i class="fas fa-user-edit"></i>
    <i class="fas fa-users"></i>
    <i class="fas fa-user-cog"></i>
    <i class="fas fa-id-card"></i>
    <i class="fas fa-shield-alt"></i>
</div>

<div class="main-form-wrapper">
    <div class="form-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-user-edit"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Sistema de gestión de reservas deportivas</div>
        </div>

        <div class="form-icon">
            <i class="fas fa-user-edit"></i>
        </div>

        <h1>Editar Usuario</h1>
        <div class="info-text">
            Modifique los campos que desea actualizar del usuario.
        </div>

        <!-- Badge con ID del usuario -->
        <div style="text-align: center;">
            <div class="user-badge" id="userIdBadge">
                <i class="fas fa-id-card"></i> <span id="userIdDisplay">ID: <?= htmlspecialchars($id_usuario) ?></span>
            </div>
        </div>

        <!-- Formulario -->
        <form id="userForm" action="../controladores/admin/editar_usuario.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_usuario" value="<?= urlencode(Crypto::encrypt($id_usuario)) ?>">
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
                <i class="fas fa-user"></i>
                <input type="text" id="userName" name="nombre" value="<?= htmlspecialchars($usuario->nombre) ?>" placeholder="Nombre completo" autocomplete="name">
            </div>

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" id="userEmail" name="email" value="<?= htmlspecialchars($usuario->email) ?>" placeholder="Correo electrónico" autocomplete="email">
            </div>

            <div class="input-group">
                <i class="fas fa-phone-alt"></i>
                <input type="tel" id="userPhone" name="telefono" value="<?= htmlspecialchars($usuario->telefono) ?>" placeholder="Teléfono" autocomplete="tel">
            </div>

            <div class="input-group">
                <i class="fas fa-user-tag"></i>
                <select id="userRole" name="rol">
                    <option value="recepcionista" <?= $usuario->rol == 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                    <option value="admin" <?= $usuario->rol == 'admin' ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </form>

        <a href="gestion_usuarios.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Gestión de Usuarios
        </a>

        <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
            <i class="fas fa-shield-alt"></i> Los cambios se aplicarán inmediatamente
        </div>
    </div>
</div>
<?php include_once '../templates/footer.php'; ?>