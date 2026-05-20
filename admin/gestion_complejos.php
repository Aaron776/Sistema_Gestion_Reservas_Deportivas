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
    $total_query = $conexion->query("SELECT COUNT(*) FROM complejos");
    $total_registros = $total_query->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // 2. Obtener registros con LIMIT y OFFSET
    $sql = $conexion->prepare("SELECT id as id_complejo, nombre, descripcion, direccion, telefono, estado, imagen_url as imagen 
                               FROM complejos 
                               ORDER BY id DESC 
                               LIMIT :limit OFFSET :offset");
    $sql->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $sql->bindParam(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $complejos = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error en paginación complejos: " . $e->getMessage());
    $complejos = [];
    $total_registros = 0;
    $total_paginas = 0;
}


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/gestion_complejos.css">
<div class="complex-header">
    <h2><i class="fas fa-building" style="color:#2dd4bf;"></i> Gestión de Complejos Deportivos</h2>
    <a href="agregar_complejo.php" class="btn-add" id="btnNuevoComplejo"><i class="fas fa-plus"></i> Nuevo Complejo</a>
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
    <table id="complexesTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php foreach ($complejos as $item) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($item->id_complejo); ?></td>
                    <td>
                        <?php if (!empty($item->imagen)) : ?>
                            <img src="../app/fotos_complejos/<?php echo $item->imagen; ?>" alt="Imagen" class="complex-img">
                        <?php else : ?>
                            <div class="no-image-placeholder" title="No se ha subido una foto para este complejo">
                                <i class="fas fa-image"></i>
                                <span>SIN FOTO</span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(ucfirst($item->nombre)); ?></td>
                    <td class="desc-preview">
                        <?php if (!empty($item->descripcion)) : ?>
                            <?php echo htmlspecialchars($item->descripcion); ?>
                        <?php else : ?>
                            <span class="no-data"><i class="fas fa-info-circle"></i> Sin descripción</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($item->direccion)) : ?>
                            <?php echo htmlspecialchars($item->direccion); ?>
                        <?php else : ?>
                            <span class="no-data"><i class="fas fa-map-marker-alt"></i> No registrada</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($item->telefono)) : ?>
                            <?php echo htmlspecialchars($item->telefono); ?>
                        <?php else : ?>
                            <span class="no-data"><i class="fas fa-phone-slash"></i> Sin contacto</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $item->estado === 'activo' ? 'status-active' : 'status-inactive'; ?>"><?php echo htmlspecialchars(ucfirst($item->estado)); ?></span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="editar_complejo.php?id_complejo=<?php echo urlencode(Crypto::encrypt($item->id_complejo)); ?>" class="action-btn edit" title="Editar complejo"><i class="fas fa-edit"></i></a>
                            
                            <form id="form-delete-<?php echo $item->id_complejo; ?>" action="../controladores/admin/eliminar_complejo.php" method="post" style="display:inline;">
                                <input type="hidden" name="id_complejo" value="<?php echo htmlspecialchars(Crypto::encrypt($item->id_complejo)); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <button type="button" class="action-btn delete" title="Eliminar complejo" 
                                        onclick="confirmDeleteComplex('<?php echo $item->id_complejo; ?>', '<?php echo htmlspecialchars($item->nombre); ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>

                            <a href="gestion_canchas_complejo.php?id_complejo=<?php echo urlencode(Crypto::encrypt($item->id_complejo)); ?>" class="action-btn courts" title="Ver canchas del complejo"><i class="fas fa-futbol"></i></a>
                        </div>
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
            <span>(Total: <b><?= $total_registros ?></b> complejos)</span>
        </div>

        <div class="pagination-container">
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

            <a href="?pagina=<?= $pagina_actual + 1 ?>" class="page-link <?= ($pagina_actual >= $total_paginas || $total_paginas <= 1) ? 'disabled' : '' ?>" title="Siguiente página">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
</div>

<script src="../app/js/admin/gestion_complejos.js"></script>
<?php include_once '../templates/footer.php'; ?>