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

$id_reserva = Crypto::decrypt(urldecode($_GET['id_reserva']));
if (empty($id_reserva) || !is_numeric($id_reserva) || $id_reserva <= 0) {
    header("Location: gestion_reservas.php");
    exit();
}

// Obtener datos de la reserva a editar
try {
    $sql = $conexion->prepare("SELECT r.fecha, r.horario_id, r.cancha_id, r.total, c.complejo_id 
                               FROM reservas r 
                               JOIN canchas c ON r.cancha_id = c.id 
                               WHERE r.id=:id_reserva");
    $sql->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
    $sql->execute();
    $reserva = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener datos de la reserva a editar: " . $e->getMessage());
    header("Location: gestion_reservas.php");
    exit();
}

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
<link rel="stylesheet" href="../app/css/cliente/editar_reserva.css">
<div class="main-form-wrapper">
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-edit"></i>
        <i class="fas fa-clock"></i>
    </div>

    <div class="form-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-calendar-alt"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Modifica los detalles de tu reserva deportiva</div>
        </div>

        <div class="form-icon"><i class="fas fa-edit"></i></div>

        <h1>Editar Mi Reserva</h1>
        <p class="info-text">Actualiza el complejo, la cancha o el horario según tu disponibilidad.</p>

        <div style="text-align: center;">
            <div class="user-badge">
                <i class="fas fa-user-circle"></i> <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            </div>
        </div>

        <form action="../controladores/cliente/editar_reserva.php" method="POST">
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
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_reserva" value="<?= urlencode(Crypto::encrypt($id_reserva)) ?>">
            <input type="hidden" name="id_cliente" value="<?= urlencode(Crypto::encrypt($id_cliente)) ?>">

            <div class="input-group">
                <i class="fas fa-building"></i>
                <select name="id_complejo" id="complejoSelect" required>
                    <option value="">Seleccionar complejo</option>
                    <?php foreach ($complejos as $item) : ?>
                        <option value="<?= $item->id_complejo ?>" <?= ($item->id_complejo == $reserva->complejo_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item->nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <i class="fas fa-futbol"></i>
                <select name="id_cancha" id="canchaSelect" required>
                    <?php foreach ($canchas as $item) : ?>
                        <?php if ($item->complejo_id == $reserva->complejo_id) : ?>
                            <option value="<?= $item->id_cancha ?>" data-precio="<?= $item->precio_hora ?>" <?= ($item->id_cancha == $reserva->cancha_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item->nombre) ?> - <?= htmlspecialchars($item->tipo) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <i class="fas fa-calendar-alt"></i>
                <input type="date" name="fecha" id="fechaReserva" value="<?= $reserva->fecha ?>" required min="<?= date('Y-m-d') ?>">
            </div>

            <div class="input-group">
                <i class="fas fa-clock"></i>
                <select name="id_horario" id="horarioSelect" required>
                    <?php foreach ($horarios as $item) : ?>
                        <option value="<?= $item->id_horario ?>" <?= ($item->id_horario == $reserva->horario_id) ? 'selected' : '' ?>>
                            <?= date('g:i A', strtotime($item->hora_inicio)) ?> - <?= date('g:i A', strtotime($item->hora_fin)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="total-preview">
                Total a pagar: <span id="totalMostrado">$<?= number_format($reserva->total, 2) ?></span>
                <input type="hidden" name="total" id="inputTotal" value="<?= $reserva->total ?>">
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </form>

        <a href="gestion_reservas.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Mis Reservas
        </a>
    </div>
</div>

<script>
    const complejoSelect = document.getElementById('complejoSelect');
    const canchaSelect = document.getElementById('canchaSelect');
    const totalMostrado = document.getElementById('totalMostrado');
    const inputTotal = document.getElementById('inputTotal');

    const canchas = [
        <?php foreach ($canchas as $c): ?> {
                id: <?= $c->id_cancha ?>,
                complejo_id: <?= $c->complejo_id ?>,
                precio: <?= $c->precio_hora ?>,
                texto: "<?= htmlspecialchars($c->nombre) ?> - <?= htmlspecialchars($c->tipo) ?>"
            },
        <?php endforeach; ?>
    ];

    complejoSelect.addEventListener('change', function() {
        const complejoId = this.value;
        canchaSelect.innerHTML = '<option value="">Selecciona una cancha</option>';

        if (complejoId) {
            const filtradas = canchas.filter(c => c.complejo_id == complejoId);
            filtradas.forEach(c => {
                const option = document.createElement('option');
                option.value = c.id;
                option.dataset.precio = c.precio;
                option.textContent = c.texto;
                canchaSelect.appendChild(option);
            });
        }
        actualizarTotal();
    });

    canchaSelect.addEventListener('change', actualizarTotal);

    function actualizarTotal() {
        const selectedOption = canchaSelect.options[canchaSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.precio) {
            const precio = parseFloat(selectedOption.dataset.precio).toFixed(2);
            totalMostrado.innerText = '$' + precio;
            inputTotal.value = precio;
        } else {
            totalMostrado.innerText = '$0.00';
            inputTotal.value = 0;
        }
    }
</script>
<?php include_once '../templates/footer.php'; ?>