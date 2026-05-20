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
    $sql = $conexion->prepare("SELECT c.nombre, c.estado, c.complejo_id, cp.nombre as nombre_complejo 
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
<style>
    .main-form-wrapper {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 1.5rem;
    }

    /* fondo deportivo decorativo */
    .sport-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        opacity: 0.08;
        pointer-events: none;
    }

    .sport-bg i {
        position: absolute;
        font-size: 12rem;
        color: #22D3EE;
    }

    .sport-bg .fa-futbol {
        top: 5%;
        left: -2%;
        transform: rotate(15deg);
    }

    .sport-bg .fa-basketball-ball {
        bottom: 10%;
        right: -2%;
        transform: rotate(-10deg);
    }

    .sport-bg .fa-toggle-on {
        top: 50%;
        left: 85%;
        font-size: 8rem;
        opacity: 0.5;
    }

    .sport-bg .fa-wrench {
        bottom: 20%;
        left: 5%;
        font-size: 7rem;
    }

    /* contenedor principal */
    .form-container {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 550px;
        background: rgba(12, 22, 32, 0.88);
        backdrop-filter: blur(18px);
        border-radius: 48px;
        padding: 2.5rem 2rem;
        border: 1px solid rgba(34, 211, 238, 0.4);
        box-shadow: 0 30px 50px -20px rgba(0, 0, 0, 0.6);
        animation: fadeInUp 0.6s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .logo-area {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .subtitle {
        text-align: center;
        color: #9fc3d4;
        font-size: 0.85rem;
    }

    .form-icon {
        text-align: center;
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .form-icon i {
        background: linear-gradient(135deg, #f59e0b, #22D3EE);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    h1 {
        text-align: center;
        font-size: 1.6rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        font-family: 'Outfit', sans-serif;
        color: #eef5ff;
    }

    .info-text {
        text-align: center;
        color: #cbd5e6;
        font-size: 0.8rem;
        margin-bottom: 1.8rem;
    }

    /* Tarjeta de información de la cancha */
    .court-info-card {
        background: rgba(34, 211, 238, 0.08);
        border-radius: 32px;
        padding: 1.2rem;
        margin-bottom: 1.8rem;
        border: 1px solid rgba(34, 211, 238, 0.25);
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .court-icon {
        font-size: 2.5rem;
    }

    .court-details {
        flex: 1;
    }

    .court-details h3 {
        font-size: 1.2rem;
        margin-bottom: 0.3rem;
    }

    .court-details p {
        font-size: 0.8rem;
        color: #9fc3d4;
    }

    .current-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }

    .status-disponible {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .status-mantenimiento {
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
    }

    .input-group {
        margin-bottom: 1.8rem;
        position: relative;
    }

    .input-group i {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #5eead4;
        font-size: 1rem;
        z-index: 1;
    }

    .input-group select {
        width: 100%;
        background: rgba(20, 35, 45, 0.8);
        border: 1px solid rgba(34, 211, 238, 0.3);
        border-radius: 60px;
        padding: 0.85rem 1rem 0.85rem 3rem;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        color: white;
        outline: none;
        transition: all 0.2s;
        appearance: none;
        cursor: pointer;
        background: rgba(20, 35, 45, 0.8) url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%235eead4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>') no-repeat right 1rem center;
        background-size: 1rem;
    }

    .input-group select:focus {
        border-color: #22D3EE;
        box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2);
    }

    /* Estilos personalizados para las opciones del select */
    .status-option-disponible {
        color: #10b981;
    }

    .status-option-mantenimiento {
        color: #f59e0b;
    }

    .btn-submit {
        width: 100%;
        background: linear-gradient(95deg, #10b981, #06b6d4);
        border: none;
        border-radius: 60px;
        padding: 0.9rem;
        font-weight: 700;
        font-size: 1rem;
        color: white;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 0.5rem;
        margin-bottom: 1.2rem;
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px -10px #06b6d4;
    }

    .btn-secondary {
        width: 100%;
        background: transparent;
        border: 1px solid rgba(34, 211, 238, 0.4);
        border-radius: 60px;
        padding: 0.8rem;
        font-weight: 500;
        font-size: 0.9rem;
        color: #5eead4;
        cursor: pointer;
        transition: 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-secondary:hover {
        background: rgba(34, 211, 238, 0.1);
        transform: translateY(-2px);
    }

    .error-message {
        background: rgba(239, 68, 68, 0.15);
        border-left: 3px solid #ef4444;
        padding: 0.8rem;
        border-radius: 16px;
        margin-bottom: 1.2rem;
        display: none;
        align-items: center;
        gap: 10px;
        font-size: 0.8rem;
        color: #fca5a5;
    }

    .success-message {
        background: rgba(16, 185, 129, 0.15);
        border-left: 3px solid #10b981;
        padding: 0.8rem;
        border-radius: 16px;
        margin-bottom: 1.2rem;
        display: none;
        align-items: center;
        gap: 10px;
        font-size: 0.8rem;
        color: #a3e635;
    }

    .warning-message {
        background: rgba(245, 158, 11, 0.15);
        border-left: 3px solid #f59e0b;
        padding: 0.8rem;
        border-radius: 16px;
        margin-bottom: 1.2rem;
        display: none;
        align-items: center;
        gap: 10px;
        font-size: 0.8rem;
        color: #fde68a;
    }

    @media (max-width: 550px) {
        .form-container {
            padding: 1.8rem;
        }

        h1 {
            font-size: 1.3rem;
        }

        .court-info-card {
            flex-direction: column;
            text-align: center;
        }
    }
</style>
</head>

<body>
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-toggle-on"></i>
        <i class="fas fa-wrench"></i>
        <i class="fas fa-tools"></i>
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
                <i class="fas fa-toggle-on"></i>
            </div>

            <h1>Cambiar Estado de Cancha</h1>
            <div class="info-text">
                Modifique el estado de la cancha para controlar su disponibilidad.
            </div>

            <!-- Tarjeta de información de la cancha -->
            <div class="court-info-card">
                <div class="court-icon">
                    <i class="fas fa-futbol"></i>
                </div>
                <div class="court-details">
                    <h3 id="courtName"><?= htmlspecialchars(ucfirst($cancha->nombre)) ?></h3>
                    <p id="courtLocation"><i class="fas fa-building"></i> Complejo: <?= htmlspecialchars(ucfirst(ucfirst($cancha->nombre_complejo))) ?></p>
                    <div>
                        <span class="current-status <?= $cancha->estado === 'disponible' ? 'status-disponible' : 'status-mantenimiento' ?>" id="currentStatusBadge">
                            <?= ucfirst(ucfirst($cancha->estado)) ?>
                        </span>
                    </div>
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
            <form id="statusForm" action="../controladores/admin/cambiar_estado_cancha.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_cancha" value="<?= urlencode(Crypto::encrypt($id_cancha)) ?>">

                <div class="input-group">
                    <i class="fas fa-info-circle"></i>
                    <select id="courtStatus" name="estado">
                        <option value="disponible" <?php if ($cancha->estado == 'disponible') { echo 'selected'; } ?> class="status-option-disponible">✅ Disponible - Cancha operativa para reservas</option>
                        <option value="mantenimiento" <?php if ($cancha->estado == 'mantenimiento') { echo 'selected'; } ?> class="status-option-mantenimiento">🔧 Mantenimiento - Cancha en reparación</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit" id="btnSubmit">
                    <i class="fas fa-save"></i> Actualizar Estado
                </button>
            </form>

            <a href="gestion_canchas_complejo.php?id_complejo=<?= urlencode(Crypto::encrypt($cancha->complejo_id)) ?>" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Gestión de Canchas
            </a>

            <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
                <i class="fas fa-shield-alt"></i> Cambiar el estado afectará la disponibilidad para reservas
            </div>
        </div>
    </div>

    <script>
        // Elementos DOM
        const courtStatusSelect = document.getElementById('courtStatus');
        const btnSubmit = document.getElementById('btnSubmit');
        const errorDiv = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        const successDiv = document.getElementById('successMessage');
        const successText = document.getElementById('successText');
        const warningDiv = document.getElementById('warningMessage');
        const warningText = document.getElementById('warningText');
        const courtNameSpan = document.getElementById('courtName');
        const courtLocationSpan = document.getElementById('courtLocation');
        const currentStatusBadge = document.getElementById('currentStatusBadge');

        // Efecto visual al cambiar el select
        courtStatusSelect.addEventListener('change', () => {
            const valor = courtStatusSelect.value;
            if (valor === 'disponible') {
                courtStatusSelect.style.borderColor = '#10b981';
            } else {
                courtStatusSelect.style.borderColor = '#f59e0b';
            }
        });
    </script>
    <?php include_once '../templates/footer.php'; ?>