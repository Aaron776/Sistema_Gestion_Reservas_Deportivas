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
    SELECT u.nombre, u.email, u.telefono, u.rol, u.estado, u.created_at, COUNT(r.id) as total_reservas
    FROM usuarios u
    LEFT JOIN reservas r ON u.id = r.usuario_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$data = $sql->fetchAll(PDO::FETCH_OBJ);

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Usuarios</title>
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
        <h2>REPORTE DE USUARIOS ACTIVOS E INACTIVOS</h2>
        <p>Fecha de generación: ' . date('d/m/Y H:i') . '</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Nombre Completo</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Reservas Históricas</th>
                <th>Registrado el</th>
            </tr>
        </thead>
        <tbody>';

foreach ($data as $row) {
    $html .= '<tr>';
    $html .= '<td><strong>' . htmlspecialchars($row->nombre) . '</strong></td>';
    $html .= '<td>' . htmlspecialchars($row->email) . '</td>';
    $html .= '<td>' . htmlspecialchars($row->telefono ?? 'N/A') . '</td>';
    $html .= '<td>' . ucfirst(htmlspecialchars($row->rol)) . '</td>';
    $html .= '<td style="color: ' . ($row->estado == 'activo' ? '#10b981' : '#ef4444') . ';">' . strtoupper(htmlspecialchars($row->estado)) . '</td>';
    $html .= '<td>' . htmlspecialchars($row->total_reservas) . '</td>';
    $html .= '<td>' . htmlspecialchars(date('d/m/Y', strtotime($row->created_at))) . '</td>';
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
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('reporte_usuarios.pdf', array("Attachment" => false));
