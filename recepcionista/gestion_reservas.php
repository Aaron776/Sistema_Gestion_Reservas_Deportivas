<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Configuración de Paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

try {
    $hoy_db = date('Y-m-d');
    // Estadísticas para las tarjetas
    $stmt_stats = $conexion->prepare("SELECT COUNT(*) as total_hoy, SUM(CASE WHEN estado = 'checkin' THEN 1 ELSE 0 END) as checkins_hoy, SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes_hoy FROM reservas WHERE fecha = :hoy");
    $stmt_stats->execute(['hoy' => $hoy_db]);
    $res_stats = $stmt_stats->fetch(PDO::FETCH_OBJ);

    // 1. Contar total para el paginador (considerando los mismos filtros que la consulta principal)
    $total_query = $conexion->prepare("SELECT COUNT(*) FROM reservas");
    $total_query->execute();
    $total_registros = $total_query->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // 2. Obtener registros con LIMIT y OFFSET
    $sql = $conexion->prepare("SELECT r.id as id_reserva, u.nombre as nombre_cliente, r.fecha as fecha_reserva, h.hora_inicio, h.hora_fin, r.total as total, r.estado as estado, c.nombre as nombre_cancha, cm.nombre as nombre_complejo
                               FROM reservas r
                               JOIN usuarios u ON r.usuario_id = u.id
                               JOIN canchas c ON r.cancha_id = c.id
                               JOIN complejos cm ON c.complejo_id = cm.id
                               JOIN horarios h ON r.horario_id = h.id
                               ORDER BY r.fecha DESC 
                               LIMIT :limit OFFSET :offset");
    $sql->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $sql->bindParam(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $reservas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error en paginación complejos: " . $e->getMessage());
    $reservas = [];
    $total_registros = 0;
    $total_paginas = 0;
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/gestion_reservas.css">

<div class="reservas-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 20px; flex-wrap: wrap;">
    <div class="section-title" style="margin-bottom: 0;"><i class="fas fa-calendar-check"></i> Panel de Recepción</div>
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Buscar por cliente o cancha...">
    </div>
</div>

<!-- Tarjetas de estadísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <p>Reservas Hoy</p>
            <h3 id="reservasHoy"><?= $res_stats->total_hoy ?? 0 ?></h3>
        </div>
        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <p>Check-ins Realizados</p>
            <h3 id="checkinsHoy"><?= $res_stats->checkins_hoy ?? 0 ?></h3>
        </div>
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <p>Pendientes por Check-in</p>
            <h3 id="pendientesCheckin"><?= $res_stats->pendientes_hoy ?? 0 ?></h3>
        </div>
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <p>Ocupación Actual</p>
            <h3 id="ocupacionActual">65%</h3>
        </div>
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
    </div>
</div>

<!-- Tabla de reservas del día -->
<div class="section-title"><i class="fas fa-list-alt"></i> Reservas para Hoy</div>
<div class="reservas-container">
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
    <table id="reservasTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Complejo</th>
                <th>Cancha</th>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php if (count($reservas) > 0) : ?>
                <?php foreach ($reservas as $item) : ?>
                    <tr>
                        <td><?= htmlspecialchars($item->id_reserva) ?></td>
                        <td><strong><?= htmlspecialchars($item->nombre_cliente) ?></strong></td>
                        <td><?= htmlspecialchars($item->nombre_complejo) ?></td>
                        <td><?= htmlspecialchars($item->nombre_cancha) ?></td>
                        <td><?= date('d/m/Y', strtotime($item->fecha_reserva)) ?></td>
                        <td><?= date('h:i A', strtotime($item->hora_inicio)) ?> - <?= date('h:i A', strtotime($item->hora_fin)) ?></td>
                        <td>
                            <?php if ($item->estado === 'pendiente') { ?>
                                <span class="status-badge status-pendiente"><i class="fas fa-clock"></i> Pendiente</span>
                            <?php } elseif ($item->estado === 'confirmada') { ?>
                                <span class="status-badge status-confirmada"><i class="fas fa-check-circle"></i> Confirmada</span>
                            <?php } elseif ($item->estado === 'finalizada') { ?>
                                <span class="status-badge status-checkin"><i class="fas fa-user-check"></i> Finalizada</span>
                            <?php } elseif ($item->estado === 'cancelada') { ?>
                                <span class="status-badge status-cancelada"><i class="fas fa-times-circle"></i> Cancelada</span>
                            <?php } ?>
                        </td>
                        <td class="total-cell">$<?= number_format($item->total, 2) ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($item->estado !== 'finalizada') : ?>
                                    <a href="cambiar_estado_reserva.php?id_reserva=<?php echo urlencode(Crypto::encrypt($item->id_reserva)); ?>" class="btn-action btn-status" title="Cambiar Estado"><i class="fas fa-exchange-alt"></i> Estado</a>
                                <?php endif; ?>
                                <?php if ($item->estado === 'finalizada') : ?>
                                    <a href="registrar_pago.php?id_reserva=<?php echo urlencode(Crypto::encrypt($item->id_reserva)); ?>" class="btn-action btn-pago" title="Registrar Pago"><i class="fas fa-credit-card"></i> Pagar</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px; color: #8aaec0;">
                        <i class="fas fa-calendar-times" style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.5;"></i>
                        No se encontraron reservas registradas.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Gráfico de ocupación y acciones rápidas -->
<div class="two-columns">
    <div class="chart-card">
        <div class="section-title" style="margin-bottom: 16px;"><i class="fas fa-chart-pie"></i> Ocupación por Cancha (Hoy)</div>
        <canvas id="ocupacionChart" width="400" height="200" style="max-width:100%; height:auto;"></canvas>
    </div>
    <div class="quick-actions">
        <div class="section-title" style="margin-bottom: 16px;"><i class="fas fa-bolt"></i> Acciones Rápidas</div>
        <div class="quick-btn" onclick="alert('Nueva reserva - Formulario de registro (demo)')">
            <i class="fas fa-plus-circle"></i> Registrar nueva reserva
        </div>
        <div class="quick-btn" onclick="alert('Buscar cliente - Módulo de clientes (demo)')">
            <i class="fas fa-search"></i> Buscar cliente
        </div>
        <div class="quick-btn" onclick="alert('Registrar pago - Módulo de pagos (demo)')">
            <i class="fas fa-credit-card"></i> Registrar pago
        </div>
        <div class="quick-btn" onclick="alert('Reporte del día - Generando PDF (demo)')">
            <i class="fas fa-file-pdf"></i> Reporte del día
        </div>
    </div>
</div>
<script src="../app/js/recepcionista/gestion_reservas.js"></script>
<?php include_once '../templates/footer.php'; ?>