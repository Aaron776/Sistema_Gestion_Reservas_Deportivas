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

// Configuración de Paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

try {
    // 1. Contar total para el paginador (considerando los mismos filtros que la consulta principal)
    $total_query = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE usuario_id=:id_usuario");
    $total_query->bindParam(':id_usuario', $id_cliente, PDO::PARAM_INT);
    $total_query->execute();
    $total_registros = $total_query->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // 2. Obtener registros con LIMIT y OFFSET
    $sql = $conexion->prepare("SELECT r.id as id_reserva, r.fecha as fecha_reserva, h.hora_inicio, h.hora_fin, r.total as total, r.estado as estado, c.nombre as nombre_cancha,cm.nombre as nombre_complejo
                               FROM reservas r
                               JOIN canchas c ON r.cancha_id = c.id
                               JOIN complejos cm ON c.complejo_id=cm.id
                               JOIN horarios h ON r.horario_id = h.id
                               WHERE r.usuario_id=:id_usuario
                               ORDER BY r.fecha DESC 
                               LIMIT :limit OFFSET :offset");
    $sql->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $sql->bindParam(':offset', $offset, PDO::PARAM_INT);
    $sql->bindParam(':id_usuario', $id_cliente, PDO::PARAM_INT);
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
<link rel="stylesheet" href="../app/css/cliente/gestion_reservas.css">

<div class="reservas-header">
    <h2><i class="fas fa-calendar-check" style="color:#2dd4bf;"></i> Gestión de Mis Reservas</h2>
    <div class="reservas-header-actions">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Buscar por cancha o complejo...">
        </div>
        <a href="registrar_reserva.php" class="btn-add" id="btnNuevaReserva"><i class="fas fa-plus"></i> Nueva Reserva</a>
    </div>
</div>

<div class="table-container">
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
                <th>Cancha</th>
                <th>Complejo Deportivo</th>
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
                        <td><?php echo htmlspecialchars($item->id_reserva); ?></td>
                        <td><strong><?php echo htmlspecialchars($item->nombre_cancha); ?></strong></td>
                        <td><?php echo htmlspecialchars($item->nombre_complejo); ?></td>
                        <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($item->fecha_reserva))); ?></td>
                        <td><?php echo htmlspecialchars(date('h:i A', strtotime($item->hora_inicio)) . ' - ' . date('h:i A', strtotime($item->hora_fin))); ?></td>
                        <td>
                            <?php if ($item->estado === 'pendiente') { ?>
                                <span class="status-badge status-pendiente"><i class="fas fa-clock"></i> Pendiente</span>
                            <?php } elseif ($item->estado === 'confirmada') { ?>
                                <span class="status-badge status-confirmada"><i class="fas fa-check-circle"></i> Confirmada</span>
                            <?php } elseif ($item->estado === 'cancelada') { ?>
                                <span class="status-badge status-cancelada"><i class="fas fa-times-circle"></i> Cancelada</span>
                            <?php } elseif ($item->estado === 'finalizada') { ?>
                                <span class="status-badge status-finalizada"><i class="fas fa-flag-checkered"></i> Finalizada</span>
                            <?php } ?>
                        </td>
                        <td class="total-cell">$<?php echo htmlspecialchars(number_format($item->total, 2)); ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($item->estado === 'pendiente'): ?>
                                    <a href="editar_reserva.php?id_reserva=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_reserva))); ?>" class="action-btn edit" title="Editar reserva"><i class="fas fa-edit"></i></a>
                                    <form action="../controladores/cliente/eliminar_reserva.php" method="POST" class="form-eliminar-reserva" style="display:inline-block; margin:0; padding:0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="id_reserva" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_reserva))); ?>">
                                        <button type="submit" name="cancelar" class="action-btn delete" title="Cancelar reserva"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                <?php elseif ($item->estado === 'finalizada'): ?>
                                    <a href="../factura/generar_factura.php?id_reserva=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_reserva))); ?>" target="_blank" class="action-btn download" title="Descargar Factura"><i class="fas fa-file-pdf"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        No tienes ninguna reserva registrada aún.
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
            <span>(Total: <b><?= $total_registros ?></b> reservas)</span>
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

<script src="../app/js/cliente/gestion_reservas.js"></script>
<?php include_once '../templates/footer.php'; ?>