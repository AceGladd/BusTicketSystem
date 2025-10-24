<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$ticket_id = $_GET['ticket_id'] ?? '';

if (empty($ticket_id)) {
    header('Location: /user/tickets.php');
    exit;
}

// Get ticket details
$stmt = $db->prepare("
    SELECT t.*, tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time,
           bc.name as company_name, u.full_name, u.email
    FROM tickets t
    JOIN trips tr ON t.trip_id = tr.id
    JOIN bus_company bc ON tr.company_id = bc.id
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticket_id, $_SESSION['user_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: /user/tickets.php');
    exit;
}

// Generate PDF content
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="bilet-' . $ticket_id . '.pdf"');

// Simple PDF generation using HTML to PDF approach
// For production, use libraries like TCPDF or FPDF
// This is a basic implementation

// Create HTML content
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        .header { text-align: center; border-bottom: 2px solid #0d6efd; padding-bottom: 20px; margin-bottom: 30px; }
        .ticket-info { margin: 20px 0; }
        .ticket-info table { width: 100%; border-collapse: collapse; }
        .ticket-info td { padding: 10px; border: 1px solid #ddd; }
        .ticket-info td:first-child { background-color: #f8f9fa; font-weight: bold; width: 30%; }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }
        .status { display: inline-block; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1>OTOBÜS BİLETİ</h1>
        <p>Bilet No: ' . htmlspecialchars($ticket_id) . '</p>
    </div>

    <div class="ticket-info">
        <table>
            <tr>
                <td>Yolcu Adı</td>
                <td>' . htmlspecialchars($ticket['full_name']) . '</td>
            </tr>
            <tr>
                <td>E-posta</td>
                <td>' . htmlspecialchars($ticket['email']) . '</td>
            </tr>
            <tr>
                <td>Firma</td>
                <td>' . htmlspecialchars($ticket['company_name']) . '</td>
            </tr>
            <tr>
                <td>Kalkış</td>
                <td>' . htmlspecialchars($ticket['departure_city']) . ' - ' . date('d.m.Y H:i', strtotime($ticket['departure_time'])) . '</td>
            </tr>
            <tr>
                <td>Varış</td>
                <td>' . htmlspecialchars($ticket['destination_city']) . ' - ' . date('d.m.Y H:i', strtotime($ticket['arrival_time'])) . '</td>
            </tr>
            <tr>
                <td>Koltuk No</td>
                <td><strong>' . $ticket['seat_number'] . '</strong></td>
            </tr>
            <tr>
                <td>Bilet Fiyatı</td>
                <td>' . number_format($ticket['total_price'], 2) . ' TL</td>
            </tr>
            <tr>
                <td>Bilet Durumu</td>
                <td>
                    <span class="status status-' . ($ticket['status'] === 'active' ? 'active' : 'cancelled') . '">
                        ' . ($ticket['status'] === 'active' ? 'AKTİF' : 'İPTAL EDİLDİ') . '
                    </span>
                </td>
            </tr>
            <tr>
                <td>Satın Alma Tarihi</td>
                <td>' . date('d.m.Y H:i', strtotime($ticket['created_at'])) . '</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Bu bilet elektronik ortamda oluşturulmuştur.</p>
        <p>Yolculuğunuz sırasında bu belgeyi yanınızda bulundurmanız gerekmektedir.</p>
        <p>&copy; 2025 Bilet Satın Alma Platformu</p>
    </div>
</body>
</html>
';

// For this demo, we'll output HTML. In production, use a proper PDF library
// Install TCPDF: composer require tecnickcom/tcpdf
// Or use FPDF, mPDF, etc.

// Simple approach: Convert HTML to PDF using DomPDF or similar
// For now, we'll use a workaround by setting content type to HTML for demo
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: inline; filename="bilet-' . $ticket_id . '.html"');

echo $html;

// NOTE: For production, implement proper PDF generation:
/*
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("bilet-{$ticket_id}.pdf", ["Attachment" => 1]);
*/
