<?php
require_once '../conexion/session.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Verificar si el usuario está logueado y es cliente (o admin, si quisieras que admin también descargue)
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'cliente' && $_SESSION['rol'] !== 'admin')) {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (!isset($_GET['id_reserva'])) {
    die("ID de reserva no proporcionado.");
}

$id_reserva = urldecode(Crypto::decrypt($_GET['id_reserva']));
$id_usuario_sesion = $_SESSION['id_usuario'];
$rol = $_SESSION['rol'];

// Construir la consulta para obtener todos los detalles de la reserva y el pago
// Si es cliente, verificamos que sea su reserva. Si es admin, puede ver cualquiera.
$condicion_usuario = ($rol === 'cliente') ? "AND r.usuario_id = :id_usuario" : "";

try {
    $query = "
        SELECT 
            r.id as id_reserva, 
            r.fecha as fecha_reserva, 
            r.total as total_reserva,
            c.nombre as nombre_cancha, 
            c.tipo as tipo_cancha,
            cm.nombre as nombre_complejo,
            h.hora_inicio, 
            h.hora_fin,
            u.nombre as nombre_cliente, 
            u.email as email_cliente, 
            u.telefono as telefono_cliente,
            p.id as id_pago, 
            p.monto as monto_pagado, 
            p.metodo as metodo_pago, 
            p.fecha_pago
        FROM reservas r
        JOIN canchas c ON r.cancha_id = c.id
        JOIN complejos cm ON c.complejo_id = cm.id
        JOIN horarios h ON r.horario_id = h.id
        JOIN usuarios u ON r.usuario_id = u.id
        LEFT JOIN pagos p ON r.id = p.reserva_id
        WHERE r.id = :id_reserva AND r.estado = 'finalizada' $condicion_usuario
    ";
    
    $sql = $conexion->prepare($query);
    $sql->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
    
    if ($rol === 'cliente') {
        $sql->bindParam(':id_usuario', $id_usuario_sesion, PDO::PARAM_INT);
    }
    
    $sql->execute();
    $factura = $sql->fetch(PDO::FETCH_OBJ);

    if (!$factura) {
        die("No se encontró la reserva, no pertenece a este usuario o aún no está finalizada.");
    }
} catch (PDOException $e) {
    error_log("Error al obtener datos para la factura: " . $e->getMessage());
    die("Error interno al procesar la factura.");
}

// Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

// Generar el contenido HTML
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura de Reserva #' . str_pad($factura->id_reserva, 6, "0", STR_PAD_LEFT) . '</title>
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #22D3EE;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #10b981;
            margin: 0;
            font-size: 28px;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        .invoice-details {
            width: 100%;
            margin-bottom: 40px;
        }
        .invoice-details td {
            vertical-align: top;
            width: 50%;
        }
        .box {
            background-color: #f8fcfd;
            border: 1px solid #e0f2f1;
            padding: 15px;
            border-radius: 8px;
        }
        .box-title {
            font-weight: bold;
            color: #0c1620;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .data-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 100px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.items th {
            background-color: #0c1620;
            color: #fff;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }
        table.items td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        .totals {
            width: 100%;
            margin-top: 20px;
            text-align: right;
        }
        .totals td {
            padding: 10px;
            font-size: 16px;
        }
        .total-row td {
            font-weight: bold;
            color: #10b981;
            font-size: 20px;
            border-top: 2px solid #22D3EE;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .badge {
            display: inline-block;
            background-color: #10b981;
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>SPORT RESERVE</h1>
        <p>Sistema de Gestión de Reservas Deportivas</p>
    </div>

    <table class="invoice-details">
        <tr>
            <td style="padding-right: 10px;">
                <div class="box">
                    <div class="box-title">Datos del Cliente</div>
                    <div><span class="data-label">Nombre:</span> ' . htmlspecialchars($factura->nombre_cliente) . '</div>
                    <div><span class="data-label">Email:</span> ' . htmlspecialchars($factura->email_cliente) . '</div>
                    <div><span class="data-label">Teléfono:</span> ' . htmlspecialchars($factura->telefono_cliente) . '</div>
                </div>
            </td>
            <td style="padding-left: 10px;">
                <div class="box">
                    <div class="box-title">Detalles de Factura</div>
                    <div><span class="data-label">N° Factura:</span> #' . str_pad($factura->id_reserva, 6, "0", STR_PAD_LEFT) . '</div>
                    <div><span class="data-label">N° Reserva:</span> ' . htmlspecialchars($factura->id_reserva) . '</div>
                    <div><span class="data-label">Fecha Emisión:</span> ' . date("d/m/Y H:i:s") . '</div>
                    <div><span class="data-label">Estado:</span> <span class="badge">PAGADO</span></div>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Descripción de la Reserva</th>
                <th>Fecha</th>
                <th>Horario</th>
                <th style="text-align: right;">Importe</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>' . htmlspecialchars($factura->nombre_cancha) . '</strong><br>
                    <span style="color: #666; font-size: 12px;">Complejo: ' . htmlspecialchars($factura->nombre_complejo) . ' - Tipo: ' . htmlspecialchars($factura->tipo_cancha) . '</span>
                </td>
                <td>' . date("d/m/Y", strtotime($factura->fecha_reserva)) . '</td>
                <td>' . date("g:i A", strtotime($factura->hora_inicio)) . ' - ' . date("g:i A", strtotime($factura->hora_fin)) . '</td>
                <td style="text-align: right;">$' . number_format($factura->total_reserva, 2) . '</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td style="width: 70%;"></td>
            <td style="width: 15%; text-align: left; color:#555;">Subtotal:</td>
            <td style="width: 15%;">$' . number_format($factura->total_reserva, 2) . '</td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align: left; color:#555;">Impuestos (0%):</td>
            <td>$0.00</td>
        </tr>
        <tr class="total-row">
            <td></td>
            <td style="text-align: left;">Total Pagado:</td>
            <td>$' . number_format($factura->monto_pagado ?? $factura->total_reserva, 2) . '</td>
        </tr>
    </table>

    <div class="box" style="margin-top: 40px; background-color: #f9f9f9; border: 1px dashed #ccc;">
        <div class="box-title">Información del Pago</div>
        <table style="width: 100%; font-size: 13px;">
            <tr>
                <td style="width: 33%"><strong>ID de Pago:</strong> #' . str_pad($factura->id_pago ?? 0, 6, "0", STR_PAD_LEFT) . '</td>
                <td style="width: 33%"><strong>Método:</strong> ' . htmlspecialchars(ucfirst($factura->metodo_pago ?? "No registrado")) . '</td>
                <td style="width: 33%"><strong>Fecha de Pago:</strong> ' . ($factura->fecha_pago ? date("d/m/Y H:i A", strtotime($factura->fecha_pago)) : "N/A") . '</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Gracias por tu preferencia. Esta factura es un comprobante válido de tu pago en <strong>Sport Reserve</strong>.</p>
        <p>Si tienes alguna duda sobre esta transacción, por favor contáctanos.</p>
    </div>

</body>
</html>
';

// Cargar el HTML
$dompdf->loadHtml($html);

// Configurar el tamaño del papel
$dompdf->setPaper('A4', 'portrait');

// Renderizar el PDF
$dompdf->render();

// Forzar la descarga
$nombre_archivo = "Factura_Reserva_" . str_pad($factura->id_reserva, 6, "0", STR_PAD_LEFT) . ".pdf";
$dompdf->stream($nombre_archivo, array("Attachment" => false)); // false = lo abre en el navegador, true = descarga forzada
exit();
?>
