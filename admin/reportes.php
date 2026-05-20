<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/reportes.css">
<div class="reports-header">
    <h2><i class="fas fa-chart-bar" style="color:#2dd4bf;"></i> Reportes y Estadísticas</h2>
    <p>Genera reportes detallados en PDF de las diferentes áreas del sistema. Haz clic en cualquier tarjeta para previsualizar los datos antes de exportar.</p>
</div>

<!-- Grid de tarjetas de reportes -->
<div class="reports-grid">
    <!-- Reporte 1: Reservas por período -->
    <div class="report-card" data-report="reservas">
        <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
        <h3>Reservas por período</h3>
        <p>Reporte detallado de todas las reservas realizadas en un rango de fechas, con estado y montos.</p>
        <div class="card-footer">
            <span class="badge-info"><i class="far fa-clock"></i> Últimos 30 días</span>
            <button class="btn-pdf" data-report-pdf="reservas"><i class="fas fa-file-pdf"></i> PDF</button>
        </div>
    </div>

    <!-- Reporte 2: Ocupación de canchas -->
    <div class="report-card" data-report="ocupacion">
        <div class="card-icon"><i class="fas fa-futbol"></i></div>
        <h3>Ocupación de canchas</h3>
        <p>Porcentaje de uso por cancha, horas ocupadas vs disponibles en el período seleccionado.</p>
        <div class="card-footer">
            <span class="badge-info"><i class="fas fa-chart-pie"></i> Porcentaje de uso</span>
            <button class="btn-pdf" data-report-pdf="ocupacion"><i class="fas fa-file-pdf"></i> PDF</button>
        </div>
    </div>

    <!-- Reporte 3: Ingresos económicos -->
    <div class="report-card" data-report="ingresos">
        <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
        <h3>Ingresos económicos</h3>
        <p>Resumen financiero con total de ingresos por mes, por cancha y por tipo de deporte.</p>
        <div class="card-footer">
            <span class="badge-info"><i class="fas fa-chart-line"></i> Tendencia mensual</span>
            <button class="btn-pdf" data-report-pdf="ingresos"><i class="fas fa-file-pdf"></i> PDF</button>
        </div>
    </div>

    <!-- Reporte 4: Usuarios activos -->
    <div class="report-card" data-report="usuarios">
        <div class="card-icon"><i class="fas fa-users"></i></div>
        <h3>Usuarios activos</h3>
        <p>Listado de usuarios registrados, sus roles y frecuencia de reservas en el sistema.</p>
        <div class="card-footer">
            <span class="badge-info"><i class="fas fa-user-check"></i> Activos</span>
            <button class="btn-pdf" data-report-pdf="usuarios"><i class="fas fa-file-pdf"></i> PDF</button>
        </div>
    </div>

    <!-- Reporte 5: Bloqueos y mantenimiento -->
    <div class="report-card" data-report="bloqueos">
        <div class="card-icon"><i class="fas fa-ban"></i></div>
        <h3>Bloqueos y mantenimiento</h3>
        <p>Registro de bloqueos programados, mantenimientos y horas no disponibles por cancha.</p>
        <div class="card-footer">
            <span class="badge-info"><i class="fas fa-tools"></i> Mantenimientos</span>
            <button class="btn-pdf" data-report-pdf="bloqueos"><i class="fas fa-file-pdf"></i> PDF</button>
        </div>
    </div>

    <!-- Reporte 6: Top canchas más reservadas -->
    <div class="report-card" data-report="top">
        <div class="card-icon"><i class="fas fa-trophy"></i></div>
        <h3>Top canchas más reservadas</h3>
        <p>Ranking de popularidad de canchas según cantidad de reservas y horas ocupadas.</p>
        <div class="card-footer">
            <span class="badge-info"><i class="fas fa-fire"></i> Popularidad</span>
            <button class="btn-pdf" data-report-pdf="top"><i class="fas fa-file-pdf"></i> PDF</button>
        </div>
    </div>
</div>

<!-- Área de vista previa del reporte -->
<div id="reportPreview" class="report-preview">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <h3><i class="fas fa-chart-simple"></i> <span id="previewTitle">Vista previa del reporte</span></h3>
        <button class="close-preview" id="closePreviewBtn"><i class="fas fa-times"></i> Cerrar</button>
    </div>
    <div id="previewContent"></div>
</div>
<script src="../app/js/admin/reportes.js"></script>
<?php include_once '../templates/footer.php'; ?>