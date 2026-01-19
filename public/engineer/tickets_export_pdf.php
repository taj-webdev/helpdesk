<?php
// public/engineer/tickets_export_pdf.php
session_start();
date_default_timezone_set('Asia/Jakarta'); // ✅ FIX WIB

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['engineer','project','admin'], true)) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

$pdo = db();

try {
    $sql = "SELECT
                t.ticket_no, t.problem_type, t.problem_detail, t.phone_number,
                t.status, t.action_taken, t.close_remarks, t.close_date, t.created_at,
                u.fullname AS reporter_name,
                e.nama_entitas, e.serial_number, e.tipe_entitas, e.brand,
                un.unit_id AS unit_kode, un.nama_unit AS unit_nama
            FROM tickets t
            LEFT JOIN users u ON t.reporter_id = u.id
            LEFT JOIN entities e ON t.entity_id = e.id
            LEFT JOIN units un ON t.unit_id = un.id
            ORDER BY t.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // WIB timestamp
    $printedAt = date('d M Y H:i:s'); // ✔ WIB

    // ==== HTML BUILD (tidak diubah kecuali timestamp) ====
    $companyName = 'NINJAS IN PYJAMAS';
    $companyAddress = 'Jl. RTA Milono Km. 5 No. 10';
    $companyPhone = 'WhatsApp: 082251548898';

    $logoPath = __DIR__ . '/../assets/img/NIP.png';
    $logoDataUri = '';

    if (file_exists($logoPath)) {
        $img = file_get_contents($logoPath);
        $base64 = base64_encode($img);
        $mime = mime_content_type($logoPath);
        $logoDataUri = "data:$mime;base64,$base64";
    }

    $html = '<!doctype html><html><head><meta charset="utf-8">
    <title>Export Tickets - PDF</title>
    <style>
        body{font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size:12px; color:#111;}
        .header{display:flex;align-items:center;gap:12px;border-bottom:2px solid #eee;padding-bottom:8px;margin-bottom:12px}
        .company{line-height:1.05}
        .company h2{margin:0;font-size:16px}
        .company p{margin:0;font-size:11px;color:#555}
        table{width:100%;border-collapse:collapse;margin-top:10px}
        th,td{border:1px solid #e6e6e6;padding:8px;font-size:11px;vertical-align:top}
        th{background:#f7f7f7;text-align:left;font-weight:700}
        .small{font-size:10px;color:#666}
        .footer{position:fixed;bottom:10px;left:0;right:0;text-align:right;font-size:10px;color:#666}
        .badge{display:inline-block;padding:3px 6px;border-radius:12px;font-size:10px;color:#fff}
        .status-open{background:#3b82f6} .status-waiting{background:#f59e0b}
        .status-confirmed{background:#0891b2} .status-closed{background:#10b981} .status-cancelled{background:#ef4444}
    </style>
    </head><body>';

    $html .= '<div class="header">';
    if ($logoDataUri !== '') $html .= '<img src="'.$logoDataUri.'" style="height:56px">';
    $html .= '<div class="company">
                <h2>'.$companyName.'</h2>
                <p>'.$companyAddress.' | '.$companyPhone.'</p>
                <p class="small">Dicetak Pada: '.$printedAt.'</p>
              </div></div>';

    $html .= '<h3>Laporan Tickets (Export PDF)</h3>';

    $html .= '<table><thead><tr>
        <th>Ticket No</th>
        <th>Entitas / Unit</th>
        <th>Problem</th>
        <th>Reporter</th>
        <th>Status</th>
        <th>Created</th>
        <th>Notes / Action</th>
    </tr></thead><tbody>';

    if (empty($rows)) {
        $html .= '<tr><td colspan="7" style="text-align:center;padding:20px;color:#777">Tidak ada data tiket.</td></tr>';
    } else {
        foreach ($rows as $r) {

            $statusCls = [
                'open' => 'status-open',
                'waiting' => 'status-waiting',
                'confirmed' => 'status-confirmed',
                'closed' => 'status-closed',
                'cancelled' => 'status-cancelled'
            ][$r['status']] ?? 'status-open';

            $html .= "<tr>
                <td><strong>{$r['ticket_no']}</strong></td>
                <td>{$r['nama_entitas']}<br><span class=\"small\">{$r['unit_nama']} ({$r['unit_kode']})</span></td>
                <td><strong>".ucfirst($r['problem_type'])."</strong><br><span class=\"small\">".nl2br(htmlspecialchars($r['problem_detail']))."</span></td>
                <td>{$r['reporter_name']}</td>
                <td><span class=\"badge $statusCls\">".strtoupper($r['status'])."</span></td>
                <td>{$r['created_at']}</td>
                <td><span class=\"small\">".nl2br(htmlspecialchars($r['action_taken']))."</span><br><span class=\"small\">".nl2br(htmlspecialchars($r['close_remarks']))."</span></td>
            </tr>";
        }
    }

    $html .= '</tbody></table>';
    $html .= '<div class="footer">Dicetak Pada: '.$printedAt.'</div>';
    $html .= '</body></html>';

    // DOMPDF
    $options = new Options();
    $options->setIsRemoteEnabled(true);
    $options->setDefaultFont('DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $filename = 'tickets_export_' . date('Ymd_His') . '.pdf';
    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo $dompdf->output();
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "Error generating PDF: " . htmlspecialchars($e->getMessage());
    exit;
}
