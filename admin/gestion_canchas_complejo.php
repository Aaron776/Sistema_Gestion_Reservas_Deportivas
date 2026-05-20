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

$id_complejo = Crypto::decrypt(urldecode($_GET['id_complejo']));
if (empty($id_complejo) || !is_numeric($id_complejo) || $id_complejo <= 0) {
    header("Location: gestion_complejos.php");
    exit();
}

try {
    $sql = $conexion->prepare("SELECT nombre FROM complejos WHERE id = :id_complejo");
    $sql->bindParam(':id_complejo', $id_complejo, PDO::PARAM_INT);
    $sql->execute();
    $complejo = $sql->fetch(PDO::FETCH_OBJ);
    $nombre_complejo = $complejo ? htmlspecialchars($complejo->nombre) : 'Complejo desconocido';
} catch (PDOException $e) {
    error_log("Error al obtener nombre del complejo: " . $e->getMessage());
    $nombre_complejo = 'Complejo desconocido';
}

// Configuración de Paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

try {
    // 1. Contar total para el paginador
    $total_query = $conexion->prepare("SELECT COUNT(*) FROM canchas WHERE complejo_id = :id_complejo");
    $total_query->bindParam(':id_complejo', $id_complejo, PDO::PARAM_INT);
    $total_query->execute();
    $total_registros = $total_query->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // 2. Obtener registros con LIMIT y OFFSET
    $sql = $conexion->prepare("SELECT id as id_cancha, nombre, descripcion, tipo, precio_hora, estado, imagen_url as imagen 
                                       FROM canchas 
                                       WHERE complejo_id = :id_complejo
                                       ORDER BY id DESC 
                                       LIMIT :limit OFFSET :offset");
    $sql->bindParam(':id_complejo', $id_complejo, PDO::PARAM_INT);
    $sql->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $sql->bindParam(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $canchas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error en paginación canchas: " . $e->getMessage());
    $canchas = [];
    $total_registros = 0;
    $total_paginas = 0;
    header("Location: gestion_complejos.php");
    exit();
}
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/gestion_canchas.css">
<div class="court-header">
    <div>
        <h2><i class="fas fa-futbol" style="color:#2dd4bf;"></i> Gestión de Canchas</h2>
        <div class="complex-info" id="complexInfo">
            <i class="fas fa-building"></i> Complejo: <span id="complexName"><?php echo $nombre_complejo; ?></span> (ID: <span id="complexId"><?php echo $id_complejo; ?></span>)
        </div>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="gestion_complejos.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver a Complejos
        </a>
        <a href="agregar_cancha.php?id_complejo=<?php echo Crypto::encrypt($id_complejo); ?>" class="btn-add">
            <i class="fas fa-plus"></i> Nueva Cancha
        </a>
    </div>
</div>

<div class="table-container">
    <table id="courtsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Precio/hora</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php foreach ($canchas as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item->id_cancha) ?></td>
                    <td>
                        <?php if (!empty($item->imagen)) : ?>
                            <img src="../app/fotos_canchas/<?php echo $item->imagen; ?>" alt="Imagen" class="court-img">
                        <?php else : ?>
                            <div class="no-image-placeholder" title="No se ha subido una foto para este cancha">
                                <i class="fas fa-image"></i>
                                <span>SIN FOTO</span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars(ucfirst($item->nombre)) ?></td>
                    <td class="desc-preview">
                        <?php if (!empty($item->descripcion)) : ?>
                            <?php echo htmlspecialchars(ucfirst($item->descripcion)); ?>
                        <?php else : ?>
                            <span class="no-data"><i class="fas fa-info-circle"></i> Sin descripción</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $tipo = strtolower($item->tipo);
                        $icon = 'fa-question-circle';
                        $class = 'type-otro';
                        
                        if($tipo == 'futbol') { $icon = 'fa-futbol'; $class = 'type-futbol'; }
                        elseif($tipo == 'basket') { $icon = 'fa-basketball-ball'; $class = 'type-basket'; }
                        elseif($tipo == 'tenis') { $icon = 'fa-table-tennis'; $class = 'type-tenis'; }
                        elseif($tipo == 'padel') { $icon = 'fa-racket'; $class = 'type-padel'; }
                        elseif($tipo == 'voley') { $icon = 'fa-volleyball-ball'; $class = 'type-voley'; }
                        elseif($tipo == 'patinaje') { $icon = 'fa-skating'; $class = 'type-patinaje'; }
                        ?>
                        <span class="type-badge <?= $class ?>">
                            <i class="fas <?= $icon ?>"></i> <?= htmlspecialchars(ucfirst($item->tipo)) ?>
                        </span>
                    </td>
                    <td class="precio">$<?= htmlspecialchars(number_format($item->precio_hora, 2)) ?></td>
                    <td>
                        <?php 
                        $estado = $item->estado;
                        if($estado == 'disponible'){
                            echo '<span class="status-badge status-disponible">Disponible</span>';
                        } elseif($estado == 'mantenimiento') {
                            echo '<span class="status-badge status-mantenimiento">Mantenimiento</span>';
                        } else {
                            echo '<span class="status-badge status-inactivo">Inactivo</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="editar_cancha.php?id_cancha=<?php echo urlencode(Crypto::encrypt($item->id_cancha)); ?>" class="action-btn edit" title="Editar Cancha"><i class="fas fa-edit"></i></a>
                            <a href="cambiar_estado_cancha.php?id_cancha=<?php echo urlencode(Crypto::encrypt($item->id_cancha)); ?>" class="action-btn status" title="Cambiar Estado"><i class="fas fa-sync-alt"></i></a>
                            <form id="form-delete-<?php echo $item->id_cancha; ?>" action="../controladores/admin/eliminar_cancha.php" method="POST">
                                <input type="hidden" name="id_cancha" value="<?php echo urlencode(Crypto::encrypt($item->id_cancha)); ?>">
                                <input type="hidden" name="id_complejo" value="<?php echo urlencode(Crypto::encrypt($id_complejo)); ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="button" class="action-btn delete" title="Eliminar Cancha" 
                                        onclick="confirmDeleteCourt('<?php echo $item->id_cancha; ?>', '<?php echo htmlspecialchars($item->nombre); ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Paginador Footer Estilizado (Sincronizado con Usuarios) -->
    <div class="pagination-footer">
        <div class="pagination-info">
            <i class="fas fa-database"></i>
            Mostrando página <b><?= ($total_paginas > 0) ? $pagina_actual : 0 ?></b> de <b><?= ($total_paginas > 0) ? $total_paginas : 0 ?></b> 
            <span>(Total: <b><?= $total_registros ?></b> canchas)</span>
        </div>

        <div class="pagination-container">
            <?php 
            $base_url = "?id_complejo=" . urlencode($_GET['id_complejo']) . "&pagina=";
            ?>
            
            <!-- Botón Anterior -->
            <a href="<?= $base_url . ($pagina_actual - 1) ?>" class="page-link <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>" title="Página anterior">
                <i class="fas fa-chevron-left"></i>
            </a>

            <!-- Números de Página -->
            <?php
            if ($total_paginas <= 1) {
                echo '<a href="#" class="page-link active">1</a>';
            } else {
                $rango = 2;
                for ($i = 1; $i <= $total_paginas; $i++) {
                    if ($i == 1 || $i == $total_paginas || ($i >= $pagina_actual - $rango && $i <= $pagina_actual + $rango)) {
                        echo '<a href="' . $base_url . $i . '" class="page-link ' . ($pagina_actual == $i ? 'active' : '') . '">' . $i . '</a>';
                    } elseif ($i == $pagina_actual - $rango - 1 || $i == $pagina_actual + $rango + 1) {
                        echo '<span style="color: #4b6a88; margin: 0 5px;">•••</span>';
                    }
                }
            }
            ?>

            <!-- Botón Siguiente -->
            <a href="<?= $base_url . ($pagina_actual + 1) ?>" class="page-link <?= ($pagina_actual >= $total_paginas || $total_paginas <= 1) ? 'disabled' : '' ?>" title="Siguiente página">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
</div>

<script src="../app/js/admin/gestion_canchas.js"></script>
<?php include_once '../templates/footer.php'; ?>