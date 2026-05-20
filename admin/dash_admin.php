<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Obtener cantidad total de reservas
try{
$sql=$conexion->prepare("SELECT COUNT(*) as total FROM reservas WHERE estado='confirmada'");
$sql->execute();
$total_reservas = $sql->fetch(PDO::FETCH_OBJ);

// Obtener cantidad total de canchas en estado disponible
$sql=$conexion->prepare("SELECT COUNT(*) as total FROM canchas WHERE estado='disponible'");
$sql->execute();
$total_canchas = $sql->fetch(PDO::FETCH_OBJ);

// Obtener el total de ingresos
$sql=$conexion->prepare("SELECT SUM(monto) as monto_total FROM pagos WHERE estado='pagado'");
$sql->execute();
$total_ingresos = $sql->fetch(PDO::FETCH_OBJ);


// Cantidad de usuarios activos
$sql=$conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE estado='activo'");
$sql->execute();
$total_usuarios = $sql->fetch(PDO::FETCH_OBJ);

// Tendencia de reservas (últimos 7 días)
$fechas_chart = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $fechas_chart[$fecha] = 0;
}
$sql_tendencia = $conexion->query("SELECT DATE(fecha) as dia, COUNT(*) as total FROM reservas WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(fecha)");
while ($row = $sql_tendencia->fetch(PDO::FETCH_ASSOC)) {
    if (isset($fechas_chart[$row['dia']])) {
        $fechas_chart[$row['dia']] = $row['total'];
    }
}
$labels_chart = json_encode(array_map(function($f) { return date('d/m', strtotime($f)); }, array_keys($fechas_chart)));
$data_chart = json_encode(array_values($fechas_chart));

// Reservas recientes
$sql_recientes = $conexion->query("
    SELECT r.id, c.nombre as cancha, r.fecha, h.hora_inicio, h.hora_fin, u.nombre as usuario, r.estado
    FROM reservas r
    JOIN canchas c ON r.cancha_id = c.id
    JOIN horarios h ON r.horario_id = h.id
    JOIN usuarios u ON r.usuario_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 5
");
$reservas_recientes = $sql_recientes->fetchAll(PDO::FETCH_OBJ);
}catch (Exception $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
    $total_reservas = 0;
    $total_canchas = 0;
    $total_ingresos = 0;
    $total_usuarios = 0;
}

include_once '../templates/header.php';
?>
<!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                     <div class="stat-info">
                         <p>Reservas Totales</p>
                        <h3><?= htmlspecialchars($total_reservas->total) ?></h3>
                    </div>
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                </div>
                <div class="stat-card">
                     <div class="stat-info">
                         <p>Canchas Disponibles</p>
                        <h3><?= htmlspecialchars($total_canchas->total) ?></h3>
                    </div>
                    <div class="stat-icon"><i class="fas fa-basketball-ball"></i></div>
                </div>
                <div class="stat-card">
                     <div class="stat-info">
                         <p>Ingresos Totales</p>
                        <h3>$<?= htmlspecialchars(number_format($total_ingresos->monto_total, 2)) ?></h3>
                    </div>
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                </div>
                <div class="stat-card">
                     <div class="stat-info">
                         <p>Usuarios Activos</p>
                        <h3><?= htmlspecialchars($total_usuarios->total) ?></h3>
                    </div>
                    <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
                </div>
            </div>

            <!-- Row: Chart + Recent Reservations Table -->
            <div class="dashboard-row">
                 <div class="chart-card">
                    <div class="section-title"><i class="fas fa-chart-simple"></i> Tendencia semanal de reservas</div>
                    <canvas id="bookingChart" width="400" height="250" style="max-width:100%; height:auto;"></canvas>
                     <div style="margin-top: 12px; font-size:0.7rem; text-align:center; color:#8aaec0;">Pico en fines de semana · 35% aumento en fútbol</div>
                </div>

                 <div class="reservations-table">
                    <div class="section-title"><i class="fas fa-clock"></i> Reservas Recientes</div>
                    <table>
                        <thead>
                            <tr>
                                 <th>Cancha</th>
                                 <th>Fecha</th>
                                 <th>Hora</th>
                                 <th>Usuario</th>
                                 <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($reservas_recientes)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No hay reservas recientes.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reservas_recientes as $res): ?>
                                 <tr>
                                     <td><?= htmlspecialchars($res->cancha) ?></td>
                                     <td><?= htmlspecialchars(date('d-m-Y', strtotime($res->fecha))) ?></td>
                                     <td><?= htmlspecialchars(date('H:i', strtotime($res->hora_inicio))) ?> - <?= htmlspecialchars(date('H:i', strtotime($res->hora_fin))) ?></td>
                                     <td><?= htmlspecialchars($res->usuario) ?></td>
                                     <td>
                                         <?php if ($res->estado === 'confirmada'): ?>
                                             <span class="status-badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem;"><i class="fas fa-check-circle"></i> Confirmada</span>
                                         <?php elseif ($res->estado === 'pendiente'): ?>
                                             <span class="status-badge status-pending" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem;"><i class="fas fa-clock"></i> Pendiente</span>
                                         <?php elseif ($res->estado === 'cancelada'): ?>
                                             <span class="status-badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem;"><i class="fas fa-times-circle"></i> Cancelada</span>
                                         <?php elseif ($res->estado === 'finalizada'): ?>
                                             <span class="status-badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem;"><i class="fas fa-flag-checkered"></i> Finalizada</span>
                                         <?php endif; ?>
                                     </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                     <div style="text-align:right; margin-top:12px;"><span class="btn-soft" style="font-size:0.7rem;"><i class="fas-regular fa-eye"></i> Ver todas las 42 reservas</span></div>
                </div>
            </div>

            <!-- Upcoming matches / events section (sporty dynamic) -->
             <div>
                 <div class="section-title"><i class="fas fa-futbol"></i> Próximos Partidos y Eventos</div>
                <div class="events-grid">
                     <div class="event-card"><i class="fas fa-futbol"></i> <strong>Torneo 5-a-side</strong><br>10 de mayo, 10:00 AM · Cancha 1<br><span class="btn-soft" style="margin-top:8px; display:inline-block;">8 cupos restantes</span></div>
                    <div class="event-card"><i class="fas fa-basketball-ball"></i> <strong>3x3 Baloncesto</strong><br>12 de mayo, 19:00 PM · Cancha B<br><span class="btn-soft">Regístrate ahora</span></div>
                    <div class="event-card"><i class="fas fa-table-tennis"></i> <strong>Ping Pong Open</strong><br>14 de mayo, 16:00 PM · Sala 2<br><span class="btn-soft">Entrada libre</span></div>
                    <div class="event-card"><i class="fas fa-swimmer"></i> <strong>Aqua Fitness</strong><br>15 de mayo, 08:30 AM · Piscina<br><span class="btn-soft">Cupos limitados</span></div>
                </div>
            </div>
<?php include_once '../templates/footer.php'; ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('bookingChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= $labels_chart ?>,
                datasets: [{
                    label: 'Reservas por día',
                    data: <?= $data_chart ?>,
                    borderColor: '#22D3EE',
                    backgroundColor: 'rgba(34, 211, 238, 0.2)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 25, 35, 0.9)',
                        titleColor: '#22D3EE',
                        bodyColor: '#eef5ff',
                        borderColor: 'rgba(72, 187, 120, 0.3)',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }, 
                        ticks: { color: '#9fc3d4', stepSize: 1 } 
                    },
                    x: { 
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }, 
                        ticks: { color: '#9fc3d4' } 
                    }
                }
            }
        });
    }
});
</script>