<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../acceso_denegado.php");
    exit();
}
$id_cliente = $_SESSION['id_usuario'];

// Obtener cantidad de reseras de este cliente
$sql = $conexion->prepare("SELECT count(*) as total FROM reservas WHERE usuario_id = :id_cliente");
$sql->bindParam("id_cliente", $id_cliente, PDO::PARAM_INT);
$sql->execute();
$totalReservas = $sql->fetch(PDO::FETCH_OBJ);

//Obtener cantidad reservas pendientes del cliente
$sql = $conexion->prepare("SELECT count(*) as total FROM reservas WHERE usuario_id = :id_cliente AND estado = 'pendiente'");
$sql->bindParam("id_cliente", $id_cliente, PDO::PARAM_INT);
$sql->execute();
$reservasPendientes = $sql->fetch(PDO::FETCH_OBJ);

//Obtener monto total pagado por el cliente en sus reservas
$sql = $conexion->prepare("SELECT SUM(total) as total FROM reservas WHERE usuario_id = :id_cliente");
$sql->bindParam("id_cliente", $id_cliente, PDO::PARAM_INT);
$sql->execute();
$montoPagado = $sql->fetch(PDO::FETCH_OBJ);

// Obtener reservas activas en estado confirmada
$sql_reservas = $conexion->prepare("
    SELECT r.id, c.nombre as cancha, r.fecha, h.hora_inicio, h.hora_fin, r.estado, r.total
    FROM reservas r
    JOIN canchas c ON r.cancha_id = c.id
    JOIN horarios h ON r.horario_id = h.id
    WHERE r.usuario_id = :id_cliente AND r.estado = 'confirmada'
    ORDER BY r.fecha DESC, h.hora_inicio ASC
");
$sql_reservas->bindParam("id_cliente", $id_cliente, PDO::PARAM_INT);
$sql_reservas->execute();
$reservas_confirmadas = $sql_reservas->fetchAll(PDO::FETCH_OBJ);

// Obtener próxima reserva confirmada del cliente
$sql_proxima = $conexion->prepare("
    SELECT r.fecha, h.hora_inicio
    FROM reservas r
    JOIN horarios h ON r.horario_id = h.id
    WHERE r.usuario_id = :id_cliente AND r.estado = 'confirmada' AND r.fecha >= CURDATE()
    ORDER BY r.fecha ASC, h.hora_inicio ASC
    LIMIT 1
");
$sql_proxima->bindParam("id_cliente", $id_cliente, PDO::PARAM_INT);
$sql_proxima->execute();
$proxima_reserva = $sql_proxima->fetch(PDO::FETCH_OBJ);

// Obtener historial de pagos del cliente
$sql_pagos = $conexion->prepare("
    SELECT p.id as pago_id, p.reserva_id, c.nombre as cancha, p.monto, p.fecha_pago
    FROM pagos p
    JOIN reservas r ON p.reserva_id = r.id
    JOIN canchas c ON r.cancha_id = c.id
    WHERE r.usuario_id = :id_cliente
    ORDER BY p.fecha_pago DESC
");
$sql_pagos->bindParam("id_cliente", $id_cliente, PDO::PARAM_INT);
$sql_pagos->execute();
$lista_pagos = $sql_pagos->fetchAll(PDO::FETCH_OBJ);

// Obtener canchas disponibles
$sql_canchas = $conexion->prepare("
    SELECT id, nombre, tipo, precio_hora, imagen_url
    FROM canchas
    WHERE estado = 'disponible'
    LIMIT 4
");
$sql_canchas->execute();
$canchas_disponibles = $sql_canchas->fetchAll(PDO::FETCH_OBJ);

// Datos para el gráfico de reservas por mes (últimos 6 meses)
$sql_grafico = $conexion->prepare("
    SELECT 
        DATE_FORMAT(fecha, '%b') as mes,
        COUNT(*) as total
    FROM reservas
    WHERE usuario_id = :id_cliente
    AND fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY MONTH(fecha)
    ORDER BY fecha ASC
");
$sql_grafico->bindParam("id_cliente", $id_cliente, PDO::PARAM_INT);
$sql_grafico->execute();
$datos_grafico = $sql_grafico->fetchAll(PDO::FETCH_OBJ);

$meses = [];
$totales = [];
foreach ($datos_grafico as $dato) {
    $meses[] = $dato->mes;
    $totales[] = (int)$dato->total;
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

    /* Sección de reservas activas */
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

    .btn-cancelar {
        background: rgba(239, 68, 68, 0.15);
        border: none;
        padding: 5px 12px;
        border-radius: 30px;
        color: #f87171;
        font-size: 0.7rem;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-cancelar:hover {
        background: rgba(239, 68, 68, 0.3);
    }

    /* Canchas disponibles - grid */
    .canchas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .cancha-card {
        background: rgba(15, 25, 35, 0.7);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        padding: 1.2rem;
        border: 1px solid rgba(72, 187, 120, 0.25);
        transition: 0.25s;
    }

    .cancha-card:hover {
        transform: translateY(-4px);
        border-color: #22D3EE;
    }

    .cancha-img {
        width: 100%;
        height: 140px;
        border-radius: 20px;
        object-fit: cover;
        margin-bottom: 12px;
        background: #1e2a36;
    }

    .cancha-card h4 {
        font-size: 1.1rem;
        margin-bottom: 6px;
    }

    .cancha-card p {
        font-size: 0.8rem;
        color: #9fc3d4;
        margin-bottom: 8px;
    }

    .precio {
        color: #a3e635;
        font-weight: 600;
        font-size: 1rem;
    }

    .btn-reservar {
        background: linear-gradient(95deg, #10b981, #06b6d4);
        border: none;
        padding: 8px 16px;
        border-radius: 40px;
        font-weight: 600;
        font-size: 0.8rem;
        color: white;
        cursor: pointer;
        transition: 0.2s;
        width: 100%;
        margin-top: 12px;
    }

    .btn-reservar:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px -5px #06b6d4;
    }

    /* Historial de pagos */
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
            <p>Mis Reservas</p>
            <h3 id="totalReservas"><?php echo htmlspecialchars($totalReservas->total); ?></h3>
        </div>
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <p>Reservas Pendientes</p>
            <h3 id="reservasActivas"><?php echo htmlspecialchars($reservasPendientes->total); ?></h3>
        </div>
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <p>Total Gastado</p>
            <h3 id="totalGastado">$ <?php echo htmlspecialchars(number_format($montoPagado->total, 2)); ?></h3>
        </div>
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <p>Próxima Reserva</p>
            <h3 id="proximaReserva">
                <?php if ($proxima_reserva): ?>
                    <?php echo htmlspecialchars(date('d/m', strtotime($proxima_reserva->fecha)) . ' ' . date('H:i', strtotime($proxima_reserva->hora_inicio))); ?>
                <?php else: ?>
                    Sin reservas
                <?php endif; ?>
            </h3>
        </div>
        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
    </div>
</div>

<!-- Gráficos y Actividad -->
<div class="dashboard-row">
    <div class="chart-card">
        <div class="section-title"><i class="fas fa-chart-line"></i> Mi Actividad de Reservas</div>
        <div class="chart-container">
            <canvas id="reservasChart"></canvas>
        </div>
    </div>

    <div class="chart-card" style="flex: 1;">
        <div class="section-title"><i class="fas fa-chart-pie"></i> Estado de Reservas</div>
        <div class="chart-container">
            <canvas id="estadoChart"></canvas>
        </div>
    </div>
</div>


<!-- Mis Reservas Activas -->
<div class="section-title"><i class="fas fa-list-alt"></i> Mis Reservas Activas</div>
<div class="reservas-container">
    <table id="reservasTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cancha</th>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody id="reservasBody">
            <?php if (empty($reservas_confirmadas)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 25px; color: #8aaec0;">
                        <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 10px; display: block; opacity: 0.5; color: #06b6d4;"></i>
                        No tienes reservas activas en estado confirmada.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($reservas_confirmadas as $res): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($res->id) ?></td>
                        <td><strong><?= htmlspecialchars(ucfirst($res->cancha)) ?></strong></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($res->fecha))) ?></td>
                        <td><?= htmlspecialchars(date('H:i', strtotime($res->hora_inicio))) ?> - <?= htmlspecialchars(date('H:i', strtotime($res->hora_fin))) ?></td>
                        <td>
                            <span class="status-badge status-confirmada"><i class="fas fa-check-circle"></i> Confirmada</span>
                        </td>
                        <td><span style="color:#a3e635;">$<?= htmlspecialchars(number_format($res->total, 2)) ?></span></td>
                        <td>
                            <span style="color: #10b981; font-size: 0.85rem;"><i class="fas fa-lock"></i> Asegurada</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Canchas Disponibles para Reservar -->
<div class="section-title"><i class="fas fa-futbol"></i> Canchas Disponibles</div>
<div class="canchas-grid" id="canchasGrid"></div>

<!-- Historial de Pagos Recientes -->
<div class="section-title"><i class="fas fa-credit-card"></i> Historial de Pagos Recientes</div>
<div class="reservas-container">
    <table id="pagosTable">
        <thead>
            <tr>
                <th>ID Pago</th>
                <th>Reserva</th>
                <th>Cancha</th>
                <th>Monto</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody id="pagosBody">
            <?php if (empty($lista_pagos)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 25px; color: #8aaec0;">
                        <i class="fas fa-credit-card" style="font-size: 2rem; margin-bottom: 10px; display: block; opacity: 0.5; color: #06b6d4;"></i>
                        No tienes pagos registrados en tus reservas.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($lista_pagos as $pago): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($pago->pago_id) ?></td>
                        <td>#<?= htmlspecialchars($pago->reserva_id) ?></td>
                        <td><strong><?= htmlspecialchars(ucfirst($pago->cancha)) ?></strong></td>
                        <td><span class="total-cell">$<?= htmlspecialchars(number_format($pago->monto, 2)) ?></span></td>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($pago->fecha_pago))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // Inicialización de gráficos
    document.addEventListener('DOMContentLoaded', function() {
        // Gráfico de Líneas - Actividad Mensual
        const ctxRes = document.getElementById('reservasChart').getContext('2d');
        new Chart(ctxRes, {
            type: 'line',
            data: {
                labels: <?= json_encode($meses) ?>,
                datasets: [{
                    label: 'Reservas por mes',
                    data: <?= json_encode($totales) ?>,
                    borderColor: '#22D3EE',
                    backgroundColor: 'rgba(34, 211, 238, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#22D3EE',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: '#9aaebf',
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#9aaebf'
                        }
                    }
                }
            }
        });

        // Gráfico de Rosquilla - Estado de Reservas
        const ctxEstado = document.getElementById('estadoChart').getContext('2d');
        new Chart(ctxEstado, {
            type: 'doughnut',
            data: {
                labels: ['Confirmadas', 'Pendientes'],
                datasets: [{
                    data: [<?= (int)$totalReservas->total - (int)$reservasPendientes->total ?>, <?= (int)$reservasPendientes->total ?>],
                    backgroundColor: ['#10b981', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#9aaebf',
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    });

    // Datos de canchas disponibles obtenidos directamente de la base de datos
    const canchasDisponibles = <?= json_encode(array_map(function ($c) {
                                    $tipos_es = [
                                        'futbol' => 'Fútbol',
                                        'basket' => 'Básquetbol',
                                        'tenis' => 'Tenis',
                                        'padel' => 'Pádel',
                                        'voley' => 'Vóley',
                                        'patinaje' => 'Patinaje',
                                        'otro' => 'Otro'
                                    ];
                                    $tipo_es = isset($tipos_es[$c->tipo]) ? $tipos_es[$c->tipo] : ucfirst($c->tipo);
                                    $imagen = $c->imagen_url ? $c->imagen_url : '';
                                    if ($c->imagen_url && strpos($c->imagen_url, 'http') !== 0 && strpos($c->imagen_url, '../') !== 0) {
                                        $imagen = '../uploads/' . $c->imagen_url;
                                    }
                                    return [
                                        'id' => $c->id,
                                        'nombre' => $c->nombre,
                                        'tipo' => $tipo_es,
                                        'precio_hora' => $c->precio_hora,
                                        'imagen' => $imagen
                                    ];
                                }, $canchas_disponibles)) ?>;

    // Renderizar canchas disponibles con imágenes robustas y fallbacks neomórficos
    function renderCanchas() {
        const grid = document.getElementById("canchasGrid");
        grid.innerHTML = "";

        const icons = {
            'Fútbol': 'fas fa-futbol',
            'Básquetbol': 'fas fa-basketball-ball',
            'Tenis': 'fas fa-table-tennis',
            'Pádel': 'fas fa-table-tennis',
            'Vóley': 'fas fa-volleyball-ball',
            'Patinaje': 'fas fa-skating'
        };

        canchasDisponibles.forEach(c => {
            const card = document.createElement("div");
            card.className = "cancha-card";
            const iconClass = icons[c.tipo] || 'fas fa-running';

            card.innerHTML = `
                <div class="cancha-img-wrapper" style="position: relative; width: 100%; height: 140px; margin-bottom: 12px; border-radius: 20px; overflow: hidden; background: #1e293b;">
                    <img src="${c.imagen}" alt="${c.nombre}" class="cancha-img" style="width: 100%; height: 100%; object-fit: cover; display: ${c.imagen ? 'block' : 'none'};" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="cancha-placeholder" style="display: ${c.imagen ? 'none' : 'flex'}; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.8)); border: 1px dashed rgba(34, 211, 238, 0.35); border-radius: 20px; align-items: center; justify-content: center; flex-direction: column; gap: 8px;">
                        <i class="${iconClass}" style="font-size: 2.5rem; color: #22d3ee; opacity: 0.85; filter: drop-shadow(0 0 8px rgba(34, 211, 238, 0.4));"></i>
                        <span style="font-size: 0.7rem; color: #94a3b8; font-weight: 500; letter-spacing: 0.5px; text-transform: uppercase;">Imagen no disponible</span>
                    </div>
                </div>
                <h4>${c.nombre}</h4>
                <p><i class="fas fa-running"></i> ${c.tipo}</p>
                <p class="precio">$${c.precio_hora}/hora</p>
                <button class="btn-reservar" data-id="${c.id}" data-nombre="${c.nombre}" data-precio="${c.precio_hora}">
                    <i class="fas fa-calendar-plus"></i> Reservar
                </button>
            `;
            grid.appendChild(card);
        });

        document.querySelectorAll(".btn-reservar").forEach(btn => {
            btn.addEventListener("click", (e) => {
                const id = btn.getAttribute("data-id");
                const nombre = btn.getAttribute("data-nombre");
                const precio = btn.getAttribute("data-precio");
                alert(`🔜 Funcionalidad de reserva para "${nombre}"\nPrecio: $${precio}/hora\n\nEn desarrollo. Próximamente podrás seleccionar fecha y horario.`);
            });
        });
    }

    renderCanchas();
</script>
<?php include_once '../templates/footer.php'; ?>