<?php
// Comprobar el estado actual de la sesión
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../conexion/session.php';
}

// Si no hay sesión iniciada, redirigir al login
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit;
}

// Calcular la ruta base relativa hacia la raíz del proyecto
// Obtenemos el archivo que incluye este header
$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
$including_file = isset($backtrace[0]['file']) ? $backtrace[0]['file'] : __FILE__;
$including_dir = dirname($including_file);
$root_dir = dirname(__DIR__); // Directorio raíz del proyecto

// Normalizar las rutas para que funcionen en Windows y Linux
$including_dir = str_replace('\\', '/', $including_dir);
$root_dir = str_replace('\\', '/', $root_dir);

// Calcular la ruta relativa desde el directorio del archivo que incluye el header hacia la raíz
$relative_path = str_replace($root_dir, '', $including_dir);
$relative_path = trim($relative_path, '/');
$depth = !empty($relative_path) ? substr_count($relative_path, '/') + 1 : 0;

// Construir la ruta base: si está en bodeguero/ o cajero/ o admin/, necesitamos "../", si está en la raíz, ""
$base_url = $depth > 0 ? str_repeat('../', $depth) : '';

// Obtener la página actual para marcar el menú activo
$current_page = basename($_SERVER['PHP_SELF']);

// Función para verificar si el enlace está activo
function isActive($page, $current)
{
    return $page === $current ? 'active' : '';
}

// Función mejorada para verificar si una página está activa (soporta múltiples páginas por sección)
function isMenuActive($pages, $current_page)
{
    foreach ($pages as $page) {
        if (stripos($current_page, $page) !== false) {
            return 'active';
        }
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>SportReserve | Panel de Control Ultra-Moderno</title>
    <!-- Google Fonts: Inter & Outfit for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 (free icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 10% 20%, rgba(10, 20, 28, 1) 0%, rgba(2, 10, 18, 1) 100%);
            color: #eef5ff;
            overflow-x: hidden;
        }

        /* Ultramodern glassmorphism + neomorphic accents */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* ========= SIDEBAR (ADMINLTE style but futuristic) ========= */
        .sidebar {
            width: 280px;
            background: rgba(15, 25, 35, 0.75);
            backdrop-filter: blur(16px);
            border-right: 1px solid rgba(72, 187, 120, 0.25);
            box-shadow: 8px 0 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            z-index: 100;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 28px 20px;
            border-bottom: 1px solid rgba(72, 187, 120, 0.4);
            margin-bottom: 24px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.7rem;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #A3E635, #22D3EE);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .logo i {
            background: none;
            -webkit-background-clip: unset;
            background-clip: unset;
            color: #22D3EE;
            font-size: 2rem;
            color: #2dd4bf;
        }

        .nav-menu {
            flex: 1;
            padding: 0 16px;
        }

        .nav-item {
            list-style: none;
            margin-bottom: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 18px;
            border-radius: 18px;
            font-weight: 500;
            color: #cbd5e6;
            transition: all 0.2s ease;
            text-decoration: none;
            font-size: 0.95rem;
        }

        .nav-link i {
            width: 24px;
            font-size: 1.2rem;
        }

        .nav-link:hover {
            background: rgba(34, 211, 238, 0.15);
            color: #ffffff;
            transform: translateX(4px);
        }

        .nav-link.active {
            background: linear-gradient(95deg, rgba(34, 197, 94, 0.2), rgba(20, 184, 166, 0.2));
            border-left: 3px solid #22D3EE;
            color: white;
            box-shadow: 0 6px 12px -8px rgba(0, 0, 0, 0.4);
        }

        /* ========= MAIN CONTENT ========= */
        .main-content {
            flex: 1;
            padding: 24px 32px;
            overflow-x: hidden;
        }

        /* top navbar */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 32px;
            background: rgba(10, 20, 28, 0.6);
            backdrop-filter: blur(12px);
            padding: 12px 24px;
            border-radius: 48px;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }

        .search-bar {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 60px;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(34, 211, 238, 0.3);
        }

        .search-bar i {
            color: #5eead4;
        }

        .search-bar input {
            background: transparent;
            border: none;
            outline: none;
            color: white;
            font-size: 0.9rem;
            width: 200px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-badge {
            position: relative;
            cursor: pointer;
        }

        .badge-dot {
            position: absolute;
            top: -2px;
            right: -5px;
            width: 10px;
            height: 10px;
            background: #f97316;
            border-radius: 50%;
            border: 2px solid #0a141c;
        }

        .avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(145deg, #10b981, #06b6d4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 0 0 2px rgba(34, 211, 238, 0.5);
        }

        /* stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(18, 28, 38, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 28px;
            padding: 22px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(72, 187, 120, 0.25);
            transition: all 0.25s ease;
            box-shadow: 0 15px 35px -12px rgba(0, 0, 0, 0.3);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #22D3EE;
            background: rgba(22, 34, 46, 0.8);
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #f0f9ff, #b5f5ec);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .stat-info p {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9aaebf;
        }

        .stat-icon i {
            font-size: 2.5rem;
            opacity: 0.8;
            color: #2dd4bf;
        }

        /* charts & tables section */
        .dashboard-row {
            display: flex;
            flex-wrap: wrap;
            gap: 28px;
            margin-bottom: 40px;
        }

        .chart-card {
            flex: 1.5;
            min-width: 280px;
            background: rgba(15, 25, 35, 0.65);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 20px 20px 20px 20px;
            border: 1px solid rgba(88, 204, 140, 0.2);
            box-shadow: 0 20px 35px -12px black;
        }

        .reservations-table {
            flex: 2;
            background: rgba(15, 25, 35, 0.65);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 20px;
            border: 1px solid rgba(88, 204, 140, 0.2);
            overflow-x: auto;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #22D3EE;
            padding-left: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        th,
        td {
            text-align: left;
            padding: 14px 8px;
            border-bottom: 1px solid rgba(72, 187, 120, 0.2);
        }

        th {
            color: #9bc0d4;
            font-weight: 500;
        }

        .status-badge {
            background: rgba(34, 197, 94, 0.2);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
            color: #4ade80;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        .alert {
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #f87171;
            color: #fecaca;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #34d399;
            color: #a7f3d0;
        }

        .alert ul {
            margin-left: 20px;
        }


        /* upcoming events grid */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .event-card {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 24px;
            padding: 16px;
            border: 1px solid rgba(45, 212, 191, 0.3);
            transition: 0.2s;
        }

        .event-card i {
            color: #2dd4bf;
            margin-right: 10px;
        }

        .btn-soft {
            background: rgba(34, 211, 238, 0.1);
            border: 1px solid #22D3EE;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 500;
            cursor: default;
            display: inline-block;
        }

        /* responsiveness */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                transition: 0.2s;
                overflow: visible;
            }

            .sidebar .logo span,
            .sidebar .nav-link span:not(.menu-text) {
                display: none;
            }

            .sidebar .logo i {
                font-size: 1.8rem;
                margin: 0 auto;
            }

            .sidebar .nav-link {
                justify-content: center;
                padding: 12px 0;
            }

            .sidebar .nav-link i {
                margin: 0;
            }

            .sidebar-header {
                padding: 20px 0;
                text-align: center;
            }

            .main-content {
                padding: 16px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-row {
                flex-direction: column;
            }

            .top-nav {
                flex-direction: column;
                align-items: stretch;
            }

            .stats-grid {
                gap: 16px;
            }
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #0f1a22;
        }

        ::-webkit-scrollbar-thumb {
            background: #2dd4bf;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <!-- SIDEBAR ultramodern -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-futbol"></i>
                    <span>SportReserve</span>
                </div>
            </div>
            <ul class="nav-menu">
                <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'){ ?>
                    <li class="nav-item"><a href="<?= $base_url ?>admin/dash_admin.php" class="nav-link <?= isActive('dash_admin.php', $current_page) ?>"><i class="fas fa-tachometer-alt"></i> <span>Panel</span></a></li>
                    <li class="nav-item"><a href="<?= $base_url ?>admin/gestion_usuarios.php" class="nav-link <?= isActive('gestion_usuarios.php', $current_page) ?>"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
                    <li class="nav-item"><a href="<?= $base_url ?>admin/gestion_complejos.php" class="nav-link <?= isActive('gestion_complejos.php', $current_page) ?>"><i class="fas fa-map-marker-alt"></i> <span>Complejos Deportivos</span></a></li>
                    <li class="nav-item"><a href="<?= $base_url ?>admin/gestion_reservas.php" class="nav-link <?= isActive('gestion_reservas.php', $current_page) ?>"><i class="fas fa-calendar-alt"></i> <span>Reservas</span></a></li>
                    <li class="nav-item"><a href="<?= $base_url ?>admin/gestion_bloqueos.php" class="nav-link <?= isActive('gestion_bloqueos.php', $current_page) ?>"><i class="fas fa-ban"></i> <span>Bloqueos Horarios</span></a></li>
                    <li class="nav-item"><a href="<?= $base_url ?>admin/pagos.php" class="nav-link <?= isActive('pagos.php', $current_page) ?>"><i class="fas fa-money-bill-wave"></i> <span>Pagos</span></a></li>
                    <li class="nav-item"><a href="<?= $base_url ?>admin/reportes.php" class="nav-link <?= isActive('reportes.php', $current_page) ?>"><i class="fas fa-chart-line"></i> <span>Reportes</span></a></li>
                <?php }?>
                <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'cliente'){ ?>
                    <li class="nav-item"><a href="<?= $base_url ?>cliente/dash_cliente.php" class="nav-link <?= isActive('dash_cliente.php', $current_page) ?>"><i class="fas fa-tachometer-alt"></i> <span>Panel</span></a></li>
                    <li class="nav-item"><a href="<?= $base_url ?>cliente/gestion_reservas.php" class="nav-link <?= isActive('gestion_reservas.php', $current_page) ?>"><i class="fas fa-calendar-alt"></i> <span>Reservas</span></a></li>
                    <li class="nav-item"><a href="<?= $base_url ?>cliente/cambiar_password.php" class="nav-link <?= isActive('cambiar_password.php', $current_page) ?>"><i class="fas fa-key"></i> <span>Cambiar Contraseña</span></a></li>
                    <li class="nav-item"><a href="<?= $base_url ?>cliente/configuracion_cuenta.php" class="nav-link <?= isActive('configuracion_cuenta.php', $current_page) ?>"><i class="fas fa-cog"></i> <span>Configuración</span></a></li>
                <?php }?>
                <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'recepcionista'){ ?>
                    <li class="nav-item"><a href="<?= $base_url ?>recepcionista/dash_recepcionista.php" class="nav-link <?= isActive('dash_recepcionista.php', $current_page) ?>"><i class="fas fa-tachometer-alt"></i> <span>Panel</span></a></li>
                    <li class="nav-item"><a href="<?= $base_url ?>recepcionista/gestion_reservas.php" class="nav-link <?= isActive('gestion_reservas.php', $current_page) ?>"><i class="fas fa-calendar-alt"></i> <span>Reservas</span></a></li>
                    <li class="nav-item"><a href="<?= $base_url ?>recepcionista/gestion_pagos.php" class="nav-link <?= isActive('gestion_pagos.php', $current_page) ?>"><i class="fas fa-money-bill-wave"></i> <span>Pagos</span></a></li>
                <?php }?>
                <li class="nav-item"><a href="<?= $base_url ?>controladores/logout.php" class="nav-link" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
            </ul>
            <div style="padding: 20px 16px; margin-top: auto;">
                <div class="btn-soft" style="text-align: center; cursor: default;"><i class="fas fa-headset"></i> Soporte 24/7</div>
            </div>
        </aside>

        <main class="main-content">
            <!-- top navbar -->
            <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'){ ?>
                <?php include 'sidebar_admin.php'; ?>
            <?php }?>
            <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'cliente'){ ?>
                <?php include 'sidebar_cliente.php'; ?>
            <?php }?>
            <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'recepcionista'){ ?>
                <?php include 'sidebar_recepcionista.php'; ?>
            <?php }?>

            

           