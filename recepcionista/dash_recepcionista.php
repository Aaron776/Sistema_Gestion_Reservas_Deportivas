<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Obtener cantidad de reservas con la fecha de hoy
try {
    $fecha_actual = date('Y-m-d');
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM reservas WHERE fecha = :fecha");
    $sql->bindParam("fecha", $fecha_actual);
    $sql->execute();
    $cantidad_reservas_hoy = $sql->fetch(PDO::FETCH_OBJ);


    // Obtener cantidad de reservas en estado finalizado
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM reservas WHERE estado = 'finalizada'");
    $sql->execute();
    $cantidad_reservas_finalizadas = $sql->fetch(PDO::FETCH_OBJ);

    // Obtener cantidad de reservas en estado pendiente
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM reservas WHERE estado = 'pendiente'");
    $sql->execute();
    $cantidad_reservas_pendientes = $sql->fetch(PDO::FETCH_OBJ);

    // 1. Obtener el total de canchas activas
    $sql_total = $conexion->query("SELECT COUNT(*) FROM canchas WHERE estado = 'disponible'");
    $total_canchas = $sql_total->fetchColumn();

    // 2. Obtener cuántas canchas tienen al menos una reserva hoy
    $sql_ocupadas = $conexion->prepare("SELECT COUNT(DISTINCT cancha_id) FROM reservas WHERE fecha = :fecha AND estado IN ('confirmada', 'pendiente')");
    $sql_ocupadas->execute(['fecha' => $fecha_actual]);
    $canchas_ocupadas_hoy = $sql_ocupadas->fetchColumn();

    // 3. Calcular porcentaje
    $porcentaje_ocupacion = ($total_canchas > 0) ? round(($canchas_ocupadas_hoy / $total_canchas) * 100) : 0;

    // 4. Obtener reservas del día para la tabla
    $sql_reservas = $conexion->prepare("
        SELECT r.id, r.estado, h.hora_inicio as hora, u.nombre as cliente, c.nombre as cancha, cm.nombre as complejo
        FROM reservas r
        JOIN usuarios u ON r.usuario_id = u.id
        JOIN canchas c ON r.cancha_id = c.id
        JOIN complejos cm ON c.complejo_id = cm.id
        JOIN horarios h ON r.horario_id = h.id
        WHERE r.fecha = :fecha
        ORDER BY h.hora_inicio ASC
    ");
    $sql_reservas->execute(['fecha' => $fecha_actual]);
    $lista_reservas_hoy = $sql_reservas->fetchAll(PDO::FETCH_OBJ);

    // 5. Obtener datos para el gráfico de ocupación
    $sql_chart = $conexion->prepare("
        SELECT c.nombre as cancha, COUNT(r.id) as cantidad
        FROM canchas c
        LEFT JOIN reservas r ON c.id = r.cancha_id AND r.fecha = :fecha AND r.estado IN ('confirmada', 'pendiente', 'finalizada')
        WHERE c.estado = 'disponible'
        GROUP BY c.id
    ");
    $sql_chart->execute(['fecha' => $fecha_actual]);
    $datos_grafico = $sql_chart->fetchAll(PDO::FETCH_OBJ);

    $labels_grafico = [];
    $valores_grafico = [];
    foreach ($datos_grafico as $dato) {
        $labels_grafico[] = $dato->cancha;
        $valores_grafico[] = $dato->cantidad;
    }
} catch (PDOException $e) {
    error_log("Error al obtener las estadísticas: " . print_r($e->getMessage(), true));
    $cantidad_reservas_hoy = (object)['total' => 0];
    $cantidad_reservas_finalizadas = (object)['total' => 0];
    $cantidad_reservas_pendientes = (object)['total' => 0];
    $porcentaje_ocupacion = 0;
    $lista_reservas_hoy = [];
    $labels_grafico = [];
    $valores_grafico = [];
}




include_once '../templates/header.php';
?>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: radial-gradient(circle at 10% 20%, #0a0f1c, #02060c);
        color: #eef5ff;
        min-height: 100vh;
    }

    /* ========= SIDEBAR ========= */
    .dashboard-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .sidebar {
        width: 280px;
        background: rgba(12, 22, 32, 0.92);
        backdrop-filter: blur(16px);
        border-right: 1px solid rgba(72, 187, 120, 0.3);
        transition: all 0.3s;
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
        font-weight: 800;
        font-size: 1.7rem;
        background: linear-gradient(135deg, #A3E635, #22D3EE);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .logo i {
        color: #2dd4bf;
        font-size: 2rem;
    }

    .nav-menu {
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
        text-decoration: none;
        transition: 0.2s;
    }

    .nav-link i {
        width: 24px;
    }

    .nav-link:hover {
        background: rgba(34, 211, 238, 0.15);
        color: white;
    }

    .nav-link.active {
        background: linear-gradient(95deg, rgba(34, 197, 94, 0.2), rgba(20, 184, 166, 0.2));
        border-left: 3px solid #22D3EE;
        color: white;
    }

    /* ========= MAIN CONTENT ========= */
    .main-content {
        flex: 1;
        padding: 24px 32px;
        overflow-x: auto;
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

    .welcome-text {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .welcome-text h3 {
        font-weight: 500;
    }

    .welcome-text span {
        color: #5eead4;
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 20px;
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
        font-size: 1.1rem;
    }

    /* Tarjetas de estadísticas */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
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
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: #22D3EE;
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
        color: #9aaebf;
    }

    .stat-icon i {
        font-size: 2.5rem;
        opacity: 0.8;
        color: #2dd4bf;
    }

    /* Sección de reservas del día */
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

    .reservas-container {
        background: rgba(15, 25, 35, 0.65);
        backdrop-filter: blur(12px);
        border-radius: 32px;
        padding: 20px;
        border: 1px solid rgba(88, 204, 140, 0.2);
        margin-bottom: 32px;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }

    th,
    td {
        text-align: left;
        padding: 14px 12px;
        border-bottom: 1px solid rgba(72, 187, 120, 0.15);
    }

    th {
        color: #9bc0d4;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .status-confirmada {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .status-pendiente {
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
    }

    .status-checkin {
        background: rgba(34, 211, 238, 0.2);
        color: #22D3EE;
    }

    .btn-checkin {
        background: linear-gradient(95deg, #10b981, #06b6d4);
        border: none;
        padding: 5px 12px;
        border-radius: 30px;
        color: white;
        font-size: 0.7rem;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-checkin:hover {
        transform: scale(1.05);
    }

    /* Gráfico y acciones rápidas */
    .two-columns {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        margin-bottom: 32px;
    }

    .chart-card {
        flex: 1.5;
        min-width: 280px;
        background: rgba(15, 25, 35, 0.65);
        backdrop-filter: blur(12px);
        border-radius: 32px;
        padding: 20px;
        border: 1px solid rgba(88, 204, 140, 0.2);
    }

    .quick-actions {
        flex: 1;
        background: rgba(15, 25, 35, 0.65);
        backdrop-filter: blur(12px);
        border-radius: 32px;
        padding: 20px;
        border: 1px solid rgba(88, 204, 140, 0.2);
    }

    .quick-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(34, 211, 238, 0.1);
        padding: 12px 16px;
        border-radius: 24px;
        margin-bottom: 12px;
        cursor: pointer;
        transition: 0.2s;
    }

    .quick-btn:hover {
        background: rgba(34, 211, 238, 0.25);
        transform: translateX(5px);
    }

    .quick-btn i {
        font-size: 1.3rem;
        color: #2dd4bf;
    }

    @media (max-width: 992px) {
        .sidebar {
            width: 80px;
        }

        .sidebar .logo span,
        .sidebar .nav-link span {
            display: none;
        }

        .main-content {
            padding: 16px;
        }
    }
</style>


<!-- Tarjetas de estadísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <p>Reservas Hoy</p>
            <h3 id="reservasHoy"><?= htmlspecialchars($cantidad_reservas_hoy->total) ?></h3>
        </div>
        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <p>Check-ins Realizados</p>
            <h3 id="checkinsHoy"><?= htmlspecialchars($cantidad_reservas_finalizadas->total) ?></h3>
        </div>
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <p>Pendientes por Check-in</p>
            <h3 id="pendientesCheckin"><?= htmlspecialchars($cantidad_reservas_pendientes->total) ?></h3>
        </div>
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <p>Ocupación Actual</p>
            <h3 id="ocupacionActual"><?= $porcentaje_ocupacion ?>%</h3>
        </div>
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
    </div>
</div>

<!-- Tabla de reservas del día -->
<div class="section-title"><i class="fas fa-list-alt"></i> Reservas para Hoy</div>
<div class="reservas-container">
    <table id="reservasTable">
        <thead>
            <tr>
                <th>Hora</th>
                <th>Cliente</th>
                <th>Cancha</th>
                <th>Complejo</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody id="reservasBody">
            <?php if (empty($lista_reservas_hoy)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #9aaebf;">
                        <i class="fas fa-calendar-times" style="font-size: 2.5rem; margin-bottom: 15px; display: block; opacity: 0.6; color: #2dd4bf;"></i> 
                        No hay reservas registradas para el día de hoy.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($lista_reservas_hoy as $reserva): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('H:i', strtotime($reserva->hora))) ?></td>
                        <td><?= htmlspecialchars(ucfirst($reserva->cliente)) ?></td>
                        <td><?= htmlspecialchars(ucfirst($reserva->cancha)) ?></td>
                        <td><?= htmlspecialchars(ucfirst($reserva->complejo)) ?></td>
                        <td>
                            <?php if ($reserva->estado === 'finalizada'): ?>
                                <span class="status-badge status-checkin">Check-in realizado</span>
                            <?php elseif ($reserva->estado === 'pendiente'): ?>
                                <span class="status-badge status-pendiente">Pendiente pago</span>
                            <?php else: ?>
                                <span class="status-badge status-confirmada">Confirmada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($reserva->estado === 'confirmada'): ?>
                                <a href="cambiar_estado_reserva.php?id_reserva=<?= urlencode(Crypto::encrypt($reserva->id)) ?>" class="btn-checkin" style="text-decoration: none; display: inline-block;">
                                    <i class="fas fa-check-circle"></i> Check-in
                                </a>
                            <?php elseif ($reserva->estado === 'finalizada'): ?>
                                <span style="color:#10b981;"><i class="fas fa-check"></i> Completado</span>
                            <?php else: ?>
                                <span style="color:#f59e0b;"><i class="fas fa-clock"></i> Pendiente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
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

<script>
    // Gráfico de ocupación por cancha
    let ocupacionChart;

    function initChart() {
        const ctx = document.getElementById('ocupacionChart').getContext('2d');

        // Datos de ocupación por cancha (desde base de datos)
        const canchas = <?= json_encode($labels_grafico) ?>;
        const reservasPorCancha = <?= json_encode($valores_grafico) ?>;

        ocupacionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: canchas,
                datasets: [{
                    label: 'Reservas hoy',
                    data: reservasPorCancha,
                    backgroundColor: 'rgba(34, 211, 238, 0.6)',
                    borderColor: '#22D3EE',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#cbd5e6',
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#101d2b',
                        titleColor: '#90e0d0'
                    }
                },
                scales: {
                    y: {
                        grid: {
                            color: 'rgba(72, 187, 120, 0.15)'
                        },
                        ticks: {
                            color: '#b9d0e5',
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#b9d0e5',
                            rotation: 0,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }

    // Inicializar el gráfico con los datos reales
    initChart();
</script>
<?php include_once '../templates/footer.php'; ?>