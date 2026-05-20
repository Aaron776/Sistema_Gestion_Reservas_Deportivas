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

// Configuración de Paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

try {
    // 1. Contar total para el paginador
    $total_query = $conexion->query("SELECT COUNT(*) FROM bloqueos");
    $total_registros = $total_query->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // 2. Obtener registros con LIMIT y OFFSET
    $sql = $conexion->prepare("SELECT bloqueos.id as id_bloqueo,canchas.nombre as nombre_cancha,bloqueos.fecha as fecha,horarios.hora_inicio as hora_inicio,horarios.hora_fin as hora_fin,bloqueos.motivo as motivo
                               FROM bloqueos 
                               INNER JOIN canchas ON bloqueos.cancha_id = canchas.id
                               INNER JOIN horarios ON bloqueos.horario_id = horarios.id
                               ORDER BY bloqueos.id DESC 
                               LIMIT :limit OFFSET :offset");
    $sql->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $sql->bindParam(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $bloqueos = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error en paginación bloqueos: " . $e->getMessage());
    $bloqueos = [];
    $total_registros = 0;
    $total_paginas = 0;
}


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/gestion_bloqueos.css">
<div class="block-header">
    <div>
        <h2><i class="fas fa-ban" style="color:#f59e0b;"></i> Gestión de Bloqueos de Canchas</h2>
    </div>
    <a href="agregar_bloqueo.php" class="btn-add" id="btnNuevoBloqueo"><i class="fas fa-plus"></i> Nuevo Bloqueo</a>
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
    <table id="blocksTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cancha</th>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Motivo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php foreach ($bloqueos as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item->id_bloqueo); ?></td>
                <td><?php echo htmlspecialchars($item->nombre_cancha); ?></td>
                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($item->fecha))); ?></td>
                <td class="horario-cell"><?php echo htmlspecialchars(date('g:i A', strtotime($item->hora_inicio)) . ' - ' . date('g:i A', strtotime($item->hora_fin))); ?></td>
                <td class="motivo-cell"><?php echo htmlspecialchars($item->motivo); ?></td>
                <td class="action-buttons">
                    <a href="editar_bloqueo.php?id_bloqueo=<?php echo urlencode(Crypto::encrypt($item->id_bloqueo)); ?>" class="action-btn edit" title="Editar"><i class="fas fa-edit"></i></a>
                    <form action="../controladores/admin/eliminar_bloqueo.php" method="post" class="delete-block-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="id_bloqueo" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_bloqueo))); ?>">
                        <button class="action-btn delete" title="Eliminar" type="submit"><i class="fas fa-trash-alt"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Paginador Footer Estilizado -->
    <div class="pagination-footer">
        <div class="pagination-info">
            <i class="fas fa-database"></i>
            Mostrando página <b><?= ($total_paginas > 0) ? $pagina_actual : 0 ?></b> de <b><?= ($total_paginas > 0) ? $total_paginas : 0 ?></b> 
            <span>(Total: <b><?= $total_registros ?></b> bloqueos)</span>
        </div>

        <div class="pagination-container">
            <!-- Botón Anterior -->
            <a href="?pagina=<?= $pagina_actual - 1 ?>" class="page-link <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>" title="Página anterior">
                <i class="fas fa-chevron-left"></i>
            </a>

            <?php
            if ($total_paginas <= 1) {
                echo '<a href="#" class="page-link active">1</a>';
            } else {
                $rango = 2;
                for ($i = 1; $i <= $total_paginas; $i++) {
                    if ($i == 1 || $i == $total_paginas || ($i >= $pagina_actual - $rango && $i <= $pagina_actual + $rango)) {
                        echo '<a href="?pagina=' . $i . '" class="page-link ' . ($pagina_actual == $i ? 'active' : '') . '">' . $i . '</a>';
                    } elseif ($i == $pagina_actual - $rango - 1 || $i == $pagina_actual + $rango + 1) {
                        echo '<span style="color: #4b6a88; margin: 0 5px;">•••</span>';
                    }
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
<script src="../app/js/admin/gestion_bloqueos.js"></script>
<?php include_once '../templates/footer.php'; ?>

