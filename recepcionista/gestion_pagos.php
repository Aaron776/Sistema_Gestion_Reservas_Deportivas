<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';


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
    // 1. Contar total para el paginador (considerando los mismos filtros que la consulta principal)
    $total_query = $conexion->query("SELECT COUNT(*) FROM pagos JOIN reservas r ON pagos.reserva_id = r.id WHERE pagos.estado='pagado'");
    $total_registros = $total_query->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // 2. Obtener registros con LIMIT y OFFSET
    $sql = $conexion->prepare("SELECT pagos.id as id_pago,pagos.comprobante as comprobante,pagos.fecha_pago as fecha_pago,pagos.metodo as metodo,pagos.monto as monto,c.nombre as nombre_cancha,u.nombre as nombre_cliente FROM pagos
                               JOIN reservas r ON pagos.reserva_id = r.id
                               JOIN canchas c ON c.id = r.cancha_id
                               JOIN usuarios u ON u.id = r.usuario_id
                               WHERE pagos.estado='pagado'
                               ORDER BY pagos.fecha_pago DESC
                               LIMIT :limit OFFSET :offset");
    $sql->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $sql->bindParam(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $pagos = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error en paginación pagos: " . $e->getMessage());
    $pagos = [];
    $total_registros = 0;
    $total_paginas = 0;
}

// Obtener estadísticas generales de pagos
try {
    // Total recaudado
    $stats_total = $conexion->query("SELECT SUM(monto) FROM pagos WHERE estado='pagado'")->fetchColumn() ?: 0;
    // Total transacciones
    $stats_count = $conexion->query("SELECT COUNT(*) FROM pagos WHERE estado='pagado'")->fetchColumn() ?: 0;
    // Por tarjeta
    $stats_tarjeta = $conexion->query("SELECT SUM(monto) FROM pagos WHERE estado='pagado' AND metodo='tarjeta'")->fetchColumn() ?: 0;
    // Por transferencia
    $stats_transferencia = $conexion->query("SELECT SUM(monto) FROM pagos WHERE estado='pagado' AND metodo='transferencia'")->fetchColumn() ?: 0;
    // Por efectivo
    $stats_efectivo = $conexion->query("SELECT SUM(monto) FROM pagos WHERE estado='pagado' AND metodo='efectivo'")->fetchColumn() ?: 0;
} catch (PDOException $e) {
    error_log("Error en stats pagos: " . $e->getMessage());
    $stats_total = $stats_count = $stats_tarjeta = $stats_transferencia = $stats_efectivo = 0;
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/gestion_pagos.css">
<div class="pagos-header">
    <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
        <h2><i class="fas fa-credit-card" style="color:#2dd4bf;"></i> Gestión de Pagos</h2>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Buscar por cliente o cancha...">
        </div>
    </div>
    <div class="stats-summary" id="statsSummary">
        <span>💰 Total recaudado: <strong id="totalRecaudado">$<?= number_format($stats_total, 2) ?></strong></span>
        <span>📊 Total transacciones: <strong id="totalTransacciones"><?= $stats_count ?></strong></span>
        <span>💳 Tarjeta: <strong id="totalTarjeta">$<?= number_format($stats_tarjeta, 2) ?></strong></span>
        <span>🏦 Transferencia: <strong id="totalTransferencia">$<?= number_format($stats_transferencia, 2) ?></strong></span>
        <span>💵 Efectivo: <strong id="totalEfectivo">$<?= number_format($stats_efectivo, 2) ?></strong></span>
    </div>
</div>

<div class="table-container">
    <table id="pagosTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cancha Reservada</th>
                <th>Método de Pago</th>
                <th>Monto</th>
                <th>Comprobante</th>
                <th>Fecha de Pago</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php if (count($pagos) > 0) : ?>
                <?php foreach ($pagos as $item) : ?>
                    <tr>
                        <td><?= htmlspecialchars($item->id_pago) ?></td>
                        <td><strong><?= htmlspecialchars($item->nombre_cancha) ?></strong><br>Cliente: <span style="font-size:0.7rem; color:#9bc0d4;"><?= htmlspecialchars($item->nombre_cliente) ?></span></td>
                        <td>
                            <?php if ($item->metodo === 'tarjeta') { ?>
                                <span class="metodo-badge metodo-tarjeta"><i class="fas fa-credit-card"></i> Tarjeta</span>
                            <?php } elseif ($item->metodo === 'transferencia') { ?>
                                <span class="metodo-badge metodo-transferencia"><i class="fas fa-university"></i> Transferencia</span>
                            <?php } elseif ($item->metodo === 'efectivo') { ?>
                                <span class="metodo-badge metodo-efectivo"><i class="fas fa-money-bill-wave"></i> Efectivo</span>
                            <?php } ?>
                        </td>
                        <td class="monto-cell">$<?= htmlspecialchars(number_format($item->monto, 2)) ?></td>
                        <td>
                            <?php if ($item->comprobante != "") { ?>
                                <a class="comprobante-link" href="../app/comprobantes_pagos/<?= htmlspecialchars($item->comprobante) ?>" target="_blank"><i class="fas fa-file-image"></i> Ver comprobante</a>
                            <?php } else { ?>
                                <span class="metodo-badge badge-sin-comprobante"><i class="fas fa-ban"></i> Sin comprobante</span>
                            <?php } ?>
                        </td>
                        <td><?= htmlspecialchars(date('d/m/Y h:i A', strtotime($item->fecha_pago))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="fas fa-money-bill-transfer"></i>
                        No se encontraron registros de pagos realizados.
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

<script src="../app/js/recepcionista/gestion_pagos.js"></script>
<?php include_once '../templates/footer.php'; ?>