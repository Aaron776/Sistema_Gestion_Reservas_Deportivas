<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_cliente = $_SESSION['id_usuario']; // obtenemos el id del usuario logclienteueado

// Obtener datos actuales del usuario
try {
    $sql = $conexion->prepare("SELECT nombre, email, telefono FROM usuarios WHERE id = :id_cliente AND rol = 'cliente'");
    $sql->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $sql->execute();
    $usuario = $sql->fetch(PDO::FETCH_OBJ);
    if (!$usuario) {
        header("Location: dash_cliente.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener datos del cliente: " . $e->getMessage());
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/cliente/configuracion_cuenta.css">
<div class="main-form-wrapper">
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-user-cog"></i>
        <i class="fas fa-id-card"></i>
        <i class="fas fa-shield-alt"></i>
    </div>

    <div class="form-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-user-cog"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Sistema de gestión de reservas deportivas</div>
        </div>

        <div class="form-icon">
            <i class="fas fa-user-cog"></i>
        </div>

        <h1>Configuración de Cuenta</h1>
        <div class="info-text">
            Actualiza tus datos personales para mantener tu cuenta segura y actualizada.
        </div>

        <!-- Badge con información del usuario -->
        <div style="text-align: center;">
            <div class="user-badge" id="userBadge">
                <i class="fas fa-user-circle"></i> <span id="userRole"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
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
        <form id="profileForm" action="../controladores/cliente/configuracion_cuenta.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_cliente" value="<?= urlencode(Crypto::encrypt($id_cliente)) ?>">

            <!-- Campo: Nombre -->
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="nombre" id="userName" value="<?php echo htmlspecialchars($usuario->nombre); ?>" placeholder="Nombre completo" autocomplete="name" required>
            </div>

            <!-- Campo: Email -->
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="userEmail" value="<?php echo htmlspecialchars($usuario->email); ?>" placeholder="Correo electrónico" autocomplete="email" required>
            </div>

            <!-- Campo: Teléfono -->
            <div class="input-group">
                <i class="fas fa-phone-alt"></i>
                <input type="tel" name="telefono" id="userPhone" value="<?php echo htmlspecialchars($usuario->telefono); ?>" placeholder="Teléfono de contacto" autocomplete="tel">
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </form>

        <a href="dash_cliente.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>

        <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
            <i class="fas fa-shield-alt"></i> Tus datos están seguros y no se compartirán con terceros
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>