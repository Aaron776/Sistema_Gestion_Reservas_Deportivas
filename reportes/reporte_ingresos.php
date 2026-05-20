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
    SELECT p.id as pago_id, u.nombre as cliente, c.nombre as cancha, p.metodo, p.monto, p.fecha_pago
    FROM pagos p
    JOIN reservas r ON p.reserva_id = r.id
    JOIN usuarios u ON r.usuario_id = u.id
    JOIN canchas c ON r.cancha_id = c.id
    WHERE p.estado = 'pagado'
    ORDER BY p.fecha_pago DESC
");
$data = $sql->fetchAll(PDO::FETCH_OBJ);

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ingresos</title>
    <style>
        body { font-family: "Helvetica", sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #22D3EE; padding-bottom: 10px; }
        .header h1 { color: #0a0f1c; margin: 0; font-size: 24px; }
        .header p { color: #666; margin: 5px 0 0 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #22D3EE; color: #fff; font-weight: bold; text-transform: uppercase; font-size: 11px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .total-row { background-color: #e5e7eb !important; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SportReserve</h1>
        <p>Sistema de Gestión de Reservas Deportivas</p>
        <h2>REPORTE DE INGRESOS ECONÓMICOS</h2>
        <p>Fecha de generación: ' . date('d/m/Y H:i') . '</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID Pago</th>
                <th>Cliente</th>
                <th>Cancha</th>
                <th>Método de Pago</th>
                <th>Fecha de Pago</th>
                <th>Monto ($)</th>
            </tr>
        </thead>
        <tbody>';

$total_ingresos = 0;
foreach ($data as $row) {
    $total_ingresos += $row->monto;
    $html .= '<tr>';
    $html .= '<td>#' . htmlspecialchars($row->pago_id) . '</td>';
    $html .= '<td>' . htmlspecialchars($row->cliente) . '</td>';
    $html .= '<td>' . htmlspecialchars($row->cancha) . '</td>';
    $html .= '<td>' . ucfirst(htmlspecialchars($row->metodo)) . '</td>';
    $html .= '<td>' . htmlspecialchars(date('d/m/Y H:i', strtotime($row->fecha_pago))) . '</td>';
    $html .= '<td>$' . number_format($row->monto, 2) . '</td>';
    $html .= '</tr>';
}

$html .= '
            <tr class="total-row">
                <td colspan="5" style="text-align: right;">TOTAL INGRESOS:</td>
                <td>$' . number_format($total_ingresos, 2) . '</td>
            </tr>
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
$dompdf->stream('reporte_ingresos.pdf', array("Attachment" => false));
