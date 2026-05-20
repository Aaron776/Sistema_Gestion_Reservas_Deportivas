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
    SELECT c.nombre as cancha, c.tipo, COUNT(r.id) as total_reservas, SUM(r.total) as total_generado
    FROM canchas c
    LEFT JOIN reservas r ON c.id = r.cancha_id AND r.estado IN ('confirmada', 'finalizada')
    GROUP BY c.id
    ORDER BY total_reservas DESC
");
$data = $sql->fetchAll(PDO::FETCH_OBJ);

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ocupación</title>
    <style>
        body { font-family: "Helvetica", sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #22D3EE; padding-bottom: 10px; }
        .header h1 { color: #0a0f1c; margin: 0; font-size: 24px; }
        .header p { color: #666; margin: 5px 0 0 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #22D3EE; color: #fff; font-weight: bold; text-transform: uppercase; font-size: 11px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SportReserve</h1>
        <p>Sistema de Gestión de Reservas Deportivas</p>
        <h2>REPORTE DE OCUPACIÓN DE CANCHAS</h2>
        <p>Fecha de generación: ' . date('d/m/Y H:i') . '</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Cancha</th>
                <th>Deporte / Tipo</th>
                <th>Total Reservas Efectivas</th>
                <th>Total Ingresos Generados ($)</th>
            </tr>
        </thead>
        <tbody>';

foreach ($data as $row) {
    $html .= '<tr>';
    $html .= '<td><strong>' . htmlspecialchars($row->cancha) . '</strong></td>';
    $html .= '<td>' . htmlspecialchars(ucfirst($row->tipo)) . '</td>';
    $html .= '<td>' . htmlspecialchars($row->total_reservas) . ' reservas</td>';
    $html .= '<td>$' . number_format($row->total_generado ?? 0, 2) . '</td>';
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
$dompdf->stream('reporte_ocupacion.pdf', array("Attachment" => false));
