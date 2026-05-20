<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';
require_once '../helpers/Formatos.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_usuario = $_SESSION['id_usuario']; // obtenemos el id del usuario logueado


// Configuración de Paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener listado de usuarios con paginación
try {
    // 1. Contar el total de registros para saber cuántas páginas hay
    $total_query = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'activo' AND rol != 'cliente'");
    $total_registros = $total_query->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // 2. Obtener los registros de la página actual
    $sql = $conexion->prepare("SELECT id as id_usuario,nombre,email,ultimo_acceso,telefono,rol FROM usuarios 
                               WHERE estado = 'activo' AND rol != 'cliente' 
                               ORDER BY created_at DESC 
                               LIMIT :limit OFFSET :offset");
    $sql->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $sql->bindParam(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $usuarios = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error en paginación: " . $e->getMessage());
    exit();
}


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/gestion_usuarios.css">
<div class="users-header">
    <h2><i class="fas fa-users" style="color:#2dd4bf;"></i> Gestión de Usuarios</h2>
    <a href="agregar_usuario.php" class="btn-add"><i class="fas fa-plus"></i> Nuevo Usuario</a>
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
    <table id="usersTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Email</th>
                <th>Último Acceso</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php foreach ($usuarios as $item) : ?>
                <tr>
                    <td><?php echo htmlspecialchars(ucfirst($item->id_usuario)); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($item->nombre)); ?></td>
                    <td>
                        <?php if ($item->rol == 'admin') { ?>
                            <span class="role-badge role-admin"><?php echo htmlspecialchars(ucfirst($item->rol)); ?></span>
                        <?php } elseif ($item->rol == 'recepcionista') { ?>
                            <span class="role-badge role-recepcionista"><?php echo htmlspecialchars(ucfirst($item->rol)); ?></span>
                        <?php } else { ?>
                            <span class="role-badge role-cliente"><?php echo htmlspecialchars(ucfirst($item->rol)); ?></span>
                        <?php } ?>
                    </td>
                    <td><?php echo htmlspecialchars($item->email); ?></td>
                    <td style="color: #94a3b8;" title="<?php echo $item->ultimo_acceso ? 'Fecha exacta: ' . date('d/m/Y H:i:s', strtotime($item->ultimo_acceso)) : ''; ?>">
                        <?php
                        if ($item->ultimo_acceso == null) {
                            echo '<span style="font-size: 0.75rem; opacity: 0.7;">Sin actividad registrada</span>';
                        } else {
                            echo htmlspecialchars(Formatos::tiempoAgo($item->ultimo_acceso));
                        }
                        ?>
                        <i class="far fa-clock" style="margin-left: 8px; font-size: 0.8rem; color: #22D3EE;"></i>
                    </td>
                    <td class="action-buttons">
                        <a href="editar_usuario.php?id_usuario=<?= urlencode(Crypto::encrypt($item->id_usuario)) ?>" class="action-btn edit" title="Editar usuario"><i class="fas fa-edit"></i></a>
                        <?php if ($item->id_usuario != $id_usuario): ?>
                            <form action="../controladores/admin/eliminar_usuario.php" method="POST" class="delete-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_usuario))); ?>">
                                <button type="submit" class="action-btn delete" title="Eliminar usuario"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        <?php endif; ?>
                        <!-- Formulario para restablecer contraseña -->
                        <form action="../controladores/admin/editar_password_usuario.php" method="POST" class="reset-form">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_usuario))); ?>">
                            <button type="submit" class="action-btn password" title="Restablecer contraseña"><i class="fas fa-key"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    </table>

    <!-- Paginador Footer Estilizado -->
    <div class="pagination-footer">
        <div class="pagination-info">
            <i class="fas fa-database"></i>
            Mostrando página <b><?= ($total_paginas > 0) ? $pagina_actual : 0 ?></b> de <b><?= ($total_paginas > 0) ? $total_paginas : 0 ?></b> 
            <span>(Total: <b><?= $total_registros ?></b> usuarios)</span>
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
<script src="../app/js/admin/gestion_usuarios.js"></script>
<?php include_once '../templates/footer.php'; ?>