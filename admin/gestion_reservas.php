<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';


if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
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
    // 1. Contar total para el paginador (considerando los mismos filtros que la consulta principal)
    $total_query = $conexion->query("SELECT COUNT(*) FROM reservas r JOIN usuarios u ON r.usuario_id = u.id WHERE u.rol = 'cliente'");
    $total_registros = $total_query->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // 2. Obtener registros con LIMIT y OFFSET
    $sql = $conexion->prepare("SELECT r.id as id_reserva, r.fecha as fecha_reserva, h.hora_inicio, h.hora_fin, r.total as total, r.estado as estado, u.nombre as nombre_cliente, c.nombre as nombre_cancha
                               FROM reservas r
                               JOIN usuarios u ON r.usuario_id = u.id
                               JOIN canchas c ON r.cancha_id = c.id
                               JOIN horarios h ON r.horario_id = h.id
                               WHERE u.rol = 'cliente'
                               ORDER BY r.id DESC 
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

// Obtener cantidad total de resevas
try {
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM reservas");
    $sql->execute();
    $total_reservas = $sql->fetch(PDO::FETCH_OBJ);
    if ($total_reservas->total == 0) {
        $total_reservas->total = 0;
    }
} catch (PDOException $e) {
    error_log("Error en paginación complejos: " . $e->getMessage());
    $total_reservas = 0;
}

// Obtener cantidad total de resevas con estado confirmada
try {
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM reservas WHERE estado = 'confirmada'");
    $sql->execute();
    $total_reservas_confirmadas = $sql->fetch(PDO::FETCH_OBJ);
    if ($total_reservas_confirmadas->total == 0) {
        $total_reservas->total = 0;
    }
} catch (PDOException $e) {
    error_log("Error en paginación complejos: " . $e->getMessage());
    $total_reservas = 0;
}

// Obtener cantidad total de resevas en estado pendiente
try {
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM reservas WHERE estado = 'pendiente'");
    $sql->execute();
    $total_reservas_pendiente = $sql->fetch(PDO::FETCH_OBJ);
    if ($total_reservas_pendiente->total == 0) {
        $total_reservas->total = 0;
    }
} catch (PDOException $e) {
    error_log("Error en paginación complejos: " . $e->getMessage());
    $total_reservas = 0;
}

// Obtener el monto total de la columna total de la tabla reservas
try {
    $sql = $conexion->prepare("SELECT SUM(total) as total FROM reservas");
    $sql->execute();
    $total_reservas_monto = $sql->fetch(PDO::FETCH_OBJ);
    if ($total_reservas_monto->total == 0) {
        $total_reservas_monto->total = 0;
    }
} catch (PDOException $e) {
    error_log("Error en paginación complejos: " . $e->getMessage());
    $total_reservas = 0;
}




include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/gestion_reservas.css">
<div class="reservations-header">
    <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
        <h2><i class="fas fa-calendar-check" style="color:#2dd4bf;"></i> Gestión de Reservas</h2>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Buscar por cliente o cancha...">
        </div>
    </div>
    <div class="stats-summary" id="statsSummary">
        <span>📊 Total reservas: <strong id="totalReservas"><?php echo htmlspecialchars($total_reservas->total); ?></strong></span>
        <span>✅ Confirmadas: <strong id="totalConfirmadas"><?php echo htmlspecialchars($total_reservas_confirmadas->total); ?></strong></span>
        <span>⏳ Pendientes: <strong id="totalPendientes"><?php echo htmlspecialchars($total_reservas_pendiente->total); ?></strong></span>
        <span>💰 Ingresos totales: <strong id="totalIngresos">$<?php echo htmlspecialchars(number_format($total_reservas_monto->total, 2)); ?></strong></span>
    </div>
</div>

<div class="table-container">
    <table id="reservationsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Cancha</th>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Estado</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php if (count($reservas) > 0): ?>
                <?php foreach ($reservas as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item->id_reserva); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($item->nombre_cliente)); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($item->nombre_cancha)); ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($item->fecha_reserva))); ?></td>
                        <td><?php echo htmlspecialchars(date('g:i A', strtotime($item->hora_inicio))); ?>-<?php echo htmlspecialchars(date('g:i A', strtotime($item->hora_fin))); ?></td>
                        <td>
                            <?php if (strtolower($item->estado) == 'confirmada') { ?>
                                <span class="status-badge status-confirmada"><?php echo htmlspecialchars(ucfirst($item->estado)); ?></span>
                            <?php } elseif (strtolower($item->estado) == 'pendiente') { ?>
                                <span class="status-badge status-pendiente"><?php echo htmlspecialchars(ucfirst($item->estado)); ?></span>
                            <?php } elseif (strtolower($item->estado) == 'cancelada') { ?>
                                <span class="status-badge status-cancelada"><?php echo htmlspecialchars(ucfirst($item->estado)); ?></span>
                            <?php } elseif (strtolower($item->estado) == 'finalizada') { ?>
                                <span class="status-badge status-finalizada"><?php echo htmlspecialchars(ucfirst($item->estado)); ?></span>
                            <?php } else { ?>
                                <span class="status-badge status-pendiente"><?php echo htmlspecialchars(ucfirst($item->estado)); ?></span>
                            <?php } ?>
                        </td>
                        <td class="total-cell">$<?php echo htmlspecialchars(number_format($item->total, 2)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        No se encontraron reservas registradas actualmente.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginador Footer Estilizado -->
    <div class="pagination-footer">
        <div class="pagination-info">
            <i class="fas fa-database"></i>
            Mostrando página <b><?= ($total_paginas > 0) ? $pagina_actual : 0 ?></b> de <b><?= ($total_paginas > 0) ? $total_paginas : 0 ?></b>
            <span>(Total: <b><?= $total_registros ?></b> registros)</span>
        </div>

        <div class="pagination-container">
            <!-- Botón Anterior -->
            <a href="?pagina=<?= $pagina_actual - 1 ?>" class="page-link <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>" title="Página anterior">
                <i class="fas fa-chevron-left"></i>
            </a>

            <?php
            // Lógica de páginas numeradas
            $rango = 2;
            for ($i = 1; $i <= $total_paginas; $i++) {
                if ($total_paginas > 7) {
                    if ($i == 1 || $i == $total_paginas || ($i >= $pagina_actual - $rango && $i <= $pagina_actual + $rango)) {
                        echo '<a href="?pagina=' . $i . '" class="page-link ' . ($pagina_actual == $i ? 'active' : '') . '">' . $i . '</a>';
                    } elseif ($i == $pagina_actual - $rango - 1 || $i == $pagina_actual + $rango + 1) {
                        echo '<span style="color: #4b6a88; margin: 0 5px;">•••</span>';
                    }
                } else {
                    echo '<a href="?pagina=' . $i . '" class="page-link ' . ($pagina_actual == $i ? 'active' : '') . '">' . $i . '</a>';
                }
            }
            ?>

            <!-- Botón Siguiente -->
            <a href="?pagina=<?= $pagina_actual + 1 ?>" class="page-link <?= ($pagina_actual >= $total_paginas || $total_paginas <= 1) ? 'disabled' : '' ?>" title="Siguiente página">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
</div>

<script src="../app/js/admin/gestion_reservas.js"></script>
<?php include_once '../templates/footer.php'; ?>