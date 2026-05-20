<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado");
}

$sql = $conexion->query("
    SELECT r.id, u.nombre as cliente, c.nombre as cancha, r.fecha, h.hora_inicio, h.hora_fin, r.estado, r.total
    FROM reservas r
    JOIN usuarios u ON r.usuario_id = u.id
    JOIN canchas c ON r.cancha_id = c.id
    JOIN horarios h ON r.horario_id = h.id
    ORDER BY r.fecha DESC, h.hora_inicio DESC
");
$data = $sql->fetchAll(PDO::FETCH_OBJ);

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Reservas</title>
    <style>
        body { font-family: "Helvetica", sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #22D3EE; padding-bottom: 10px; }
        .header h1 { color: #0a0f1c; margin: 0; font-size: 24px; }
        .header p { color: #666; margin: 5px 0 0 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #22D3EE; color: #fff; font-weight: bold; text-transform: uppercase; font-size: 11px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-weight: bold; font-size: 10px; color: white; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SportReserve</h1>
        <p>Sistema de Gestión de Reservas Deportivas</p>
        <h2>REPORTE GENERAL DE RESERVAS</h2>
        <p>Fecha de generación: ' . date('d/m/Y H:i') . '</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Cancha</th>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Estado</th>
                <th>Total ($)</th>
            </tr>
        </thead>
        <tbody>';

foreach ($data as $row) {
    $estado_color = '#6b7280';
    if($row->estado == 'confirmada') $estado_color = '#10b981';
    else if($row->estado == 'pendiente') $estado_color = '#f59e0b';
    else if($row->estado == 'cancelada') $estado_color = '#ef4444';
    else if($row->estado == 'finalizada') $estado_color = '#3b82f6';

    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($row->id) . '</td>';
    $html .= '<td>' . htmlspecialchars($row->cliente) . '</td>';
    $html .= '<td>' . htmlspecialchars($row->cancha) . '</td>';
    $html .= '<td>' . htmlspecialchars(date('d/m/Y', strtotime($row->fecha))) . '</td>';
    $html .= '<td>' . htmlspecialchars(date('H:i', strtotime($row->hora_inicio)) . ' - ' . date('H:i', strtotime($row->hora_fin))) . '</td>';
    $html .= '<td style="color: ' . $estado_color . '; font-weight: bold;">' . strtoupper(htmlspecialchars($row->estado)) . '</td>';
    $html .= '<td>$' . number_format($row->total, 2) . '</td>';
    $html .= '</tr>';
}

$html .= '
        </tbody>
    </table>
    <div class="footer">SportReserve - Reporte generado automáticamente</div>
</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('reporte_reservas.pdf', array("Attachment" => false));
