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

// Obtener registro de complejos deportivos
try {
    $sql = $conexion->prepare("SELECT id as id_complejo, nombre FROM complejos");
    $sql->execute();
    $complejos = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener datos de complejos deportivos: " . $e->getMessage());
    header("Location: gestion_reservas.php");
    exit();
}


// Obtener registros de canchas
try {
    $sql = $conexion->prepare("SELECT id as id_cancha, nombre,complejo_id,precio_hora,tipo FROM canchas");
    $sql->execute();
    $canchas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener datos de las canchas: " . $e->getMessage());
    header("Location: gestion_reservas.php");
    exit();
}

// Obtener registros de horarios
try {
    $sql = $conexion->prepare("SELECT id as id_horario, hora_inicio, hora_fin FROM horarios");
    $sql->execute();
    $horarios = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener datos de los horarios: " . $e->getMessage());
    header("Location: gestion_reservas.php");
    exit();
}


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/cliente/registrar_reserva.css">
<div class="main-form-wrapper">
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-calendar-plus"></i>
        <i class="fas fa-clock"></i>
        <i class="fas fa-dollar-sign"></i>
    </div>

    <div class="form-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-calendar-plus"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Sistema de gestión de reservas deportivas</div>
        </div>

        <div class="form-icon">
            <i class="fas fa-calendar-plus"></i>
        </div>

        <h1>Registrar Nueva Reserva</h1>
        <div class="info-text">
            Complete los siguientes campos para realizar una nueva reserva deportiva.
        </div>

        <!-- Badge con información del cliente -->
        <div style="text-align: center;">
            <div class="user-badge" id="userBadge">
                <i class="fas fa-user-circle"></i> <span id="userName"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
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
        <form id="reservaForm" action="../controladores/cliente/registrar_reserva.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_cliente" value="<?= urlencode(Crypto::encrypt($id_cliente)) ?>">

            <!-- Campo: Complejo (select) -->
            <div class="input-group">
                <i class="fas fa-building"></i>
                <select name="id_complejo" id="complejoSelect" required>
                    <option value="">Seleccionar complejo</option>
                    <?php foreach ($complejos as $item) : ?>
                        <option value="<?= $item->id_complejo ?>"><?= htmlspecialchars($item->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campo: Cancha (select) - se carga dinámicamente según complejo -->
            <div class="input-group">
                <i class="fas fa-futbol"></i>
                <select name="id_cancha" id="canchaSelect" required disabled>
                    <option value="">Primero selecciona un complejo</option>
                    <?php foreach ($canchas as $item) : ?>
                        <option value="<?= $item->id_cancha ?>" data-complejo="<?= $item->complejo_id ?>" data-precio="<?= $item->precio_hora ?>"><?= htmlspecialchars($item->nombre) ?> - <?= htmlspecialchars($item->tipo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campo: Fecha -->
            <div class="input-group">
                <i class="fas fa-calendar-alt"></i>
                <input type="date" name="fecha" id="fechaReserva" required min="<?= date('Y-m-d') ?>">
            </div>

            <!-- Campo: Horario (select) -->
            <div class="input-group">
                <i class="fas fa-clock"></i>
                <select name="id_horario" id="horarioSelect" required>
                    <option value="">Selecciona un horario</option>
                    <?php foreach ($horarios as $item) : ?>
                        <option value="<?= $item->id_horario ?>"><?php echo date('g:i A', strtotime($item->hora_inicio)) ?> - <?php echo date('g:i A', strtotime($item->hora_fin)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campo: Total (calculado automáticamente) -->
            <div class="total-preview">
                Total a pagar: <span id="totalMostrado">$0.00</span>
                <input type="hidden" name="total" id="inputTotal" value="0">
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="fas fa-check-circle"></i> Confirmar Reserva
            </button>
        </form>

        <a href="gestion_reservas.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Mis Reservas
        </a>

        <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
            <i class="fas fa-shield-alt"></i> La reserva estará sujeta a disponibilidad
        </div>
    </div>
</div>

<script>
    const complejoSelect = document.getElementById('complejoSelect');
    const canchaSelect = document.getElementById('canchaSelect');
    const horarioSelect = document.getElementById('horarioSelect');
    const fechaReserva = document.getElementById('fechaReserva');
    const totalMostrado = document.getElementById('totalMostrado');
    const inputTotal = document.getElementById('inputTotal');

    // Datos exportados de PHP a JavaScript
    const canchas = [
        <?php foreach ($canchas as $c): ?>
            {
                id: <?= $c->id_cancha ?>,
                complejo_id: <?= $c->complejo_id ?>,
                precio: <?= $c->precio_hora ?>,
                texto: "<?= htmlspecialchars($c->nombre) ?> - <?= htmlspecialchars($c->tipo) ?>"
            },
        <?php endforeach; ?>
    ];

    // 1. Cuando cambia el complejo -> Filtrar canchas
    complejoSelect.addEventListener('change', function() {
        const complejoId = this.value;
        
        // Resetear cancha y todo lo que depende de ella
        canchaSelect.innerHTML = '<option value="">Selecciona una cancha</option>';
        totalMostrado.innerText = '$0.00';
        inputTotal.value = 0;

        if (complejoId) {
            canchaSelect.disabled = false;
            // Filtrar y agregar las canchas correspondientes
            const canchasFiltradas = canchas.filter(c => c.complejo_id == complejoId);
            canchasFiltradas.forEach(c => {
                const option = document.createElement('option');
                option.value = c.id;
                option.dataset.precio = c.precio;
                option.textContent = c.texto;
                canchaSelect.appendChild(option);
            });
        } else {
            canchaSelect.innerHTML = '<option value="">Primero selecciona un complejo</option>';
            canchaSelect.disabled = true;
        }
    });

    // 2. Cuando cambia la cancha -> Calcular total
    canchaSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            const precio = parseFloat(selectedOption.dataset.precio).toFixed(2);
            totalMostrado.innerText = '$' + precio;
            inputTotal.value = precio;
        } else {
            totalMostrado.innerText = '$0.00';
            inputTotal.value = 0;
        }
    });

    // Inicialización de la vista
    complejoSelect.value = "";
    canchaSelect.innerHTML = '<option value="">Primero selecciona un complejo</option>';
    canchaSelect.disabled = true;
    fechaReserva.value = "";
    horarioSelect.value = "";
    totalMostrado.innerText = '$0.00';
    inputTotal.value = 0;
</script>
<?php include_once '../templates/footer.php'; ?>