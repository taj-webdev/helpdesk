<?php
// public/admin/tickets_export_pdf.php
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/config/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// admin role check
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['project','admin'], true)) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

$pdo = db();

try {
    // read filters (same as tickets.php)
    $search = trim($_GET['q'] ?? '');
    $statusFilter = $_GET['status_filter'] ?? '';
    $authorFilter = (int)($_GET['author_id'] ?? 0);
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';

    $fromSql = "FROM tickets t
        LEFT JOIN users u_reporter ON t.reporter_id = u_reporter.id
        LEFT JOIN entities e ON t.entity_id = e.id
        LEFT JOIN units un ON t.unit_id = un.id
        WHERE 1=1
    ";
    $params = [];

    if ($search !== '') {
        $fromSql .= " AND CONCAT(
            COALESCE(t.ticket_no,''),' ',
            COALESCE(t.problem_detail,''),' ',
            COALESCE(u_reporter.fullname,''),' ',
            COALESCE(e.nama_entitas,''),' ',
            COALESCE(e.serial_number,''),' ',
            COALESCE(e.brand,'')
        ) LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    if (in_array($statusFilter, ['open','waiting','confirmed','closed','cancelled'], true)) {
        $fromSql .= " AND t.status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($authorFilter > 0) {
        $fromSql .= " AND t.reporter_id = :author";
        $params[':author'] = $authorFilter;
    }
    if ($dateFrom !== '') {
        $fromSql .= " AND DATE(t.created_at) >= :date_from";
        $params[':date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $fromSql .= " AND DATE(t.created_at) <= :date_to";
        $params[':date_to'] = $dateTo;
    }

    // fetch rows
    $dataSql = "SELECT
        t.ticket_no, t.problem_type, t.problem_detail, t.phone_number, t.status, t.action_taken, t.close_remarks, t.close_date, t.created_at,
        u_reporter.fullname AS reporter_name,
        e.nama_entitas, e.tipe_entitas, e.brand, e.serial_number,
        un.unit_id AS unit_kode, un.nama_unit AS unit_nama
        " . $fromSql . "
        ORDER BY t.created_at ASC
    ";
    $stmt = $pdo->prepare($dataSql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // logo inline
    $logoPath = realpath(__DIR__ . '/../assets/img/NIP.png');
    $logoDataUri = '';
    if ($logoPath && file_exists($logoPath)) {
        $mime = mime_content_type($logoPath) ?: 'image/png';
        $b64  = base64_encode(file_get_contents($logoPath));
        $logoDataUri = "data:{$mime};base64,{$b64}";
    }

    $printedAt = date('d M Y H:i:s');
    $companyName = 'NINJAS IN PYJAMAS';
    $companyAddress = 'Jl. RTA Milono Km. 5 No. 10';
    $companyPhone = 'WhatsApp: 082251548898';

    // build HTML (landscape-ready table)
    $html = '<!doctype html><html><head><meta charset="utf-8"><title>Export Tickets - PDF</title>';
    $html .= '<style>
        body{font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size:12px; color:#111;margin:18px;}
        .header{display:flex;align-items:center;gap:12px;border-bottom:2px solid #eee;padding-bottom:8px;margin-bottom:12px}
        .company h2{margin:0;font-size:16px}
        .company p{margin:0;font-size:11px;color:#555}
        table{width:100%;border-collapse:collapse;margin-top:10px;font-size:11px}
        th,td{border:1px solid #e6e6e6;padding:8px;vertical-align:top}
        th{background:#f7f7f7;text-align:left;font-weight:700}
        .small{font-size:10px;color:#666}
        .footer{position:fixed;bottom:10px;left:0;right:0;text-align:right;font-size:10px;color:#666}
        .mono{font-family:monospace}
        .wrap{white-space:pre-wrap;word-wrap:break-word}
        .badge{display:inline-block;padding:4px 8px;border-radius:12px;font-size:10px;color:#fff}
        .status-open{background:#3b82f6} .status-waiting{background:#f59e0b}
        .status-confirmed{background:#0891b2} .status-closed{background:#10b981} .status-cancelled{background:#ef4444}
    </style>';
    $html .= '</head><body>';

    // header
    $html .= '<div class="header">';
    if ($logoDataUri !== '') {
        $html .= '<div><img src="' . $logoDataUri . '" style="height:56px;object-fit:contain"></div>';
    }
    $html .= '<div class="company">';
    $html .= "<h2>" . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . "</h2>";
    $html .= "<p>" . htmlspecialchars($companyAddress, ENT_QUOTES, 'UTF-8') . " &nbsp; | &nbsp; " . htmlspecialchars($companyPhone, ENT_QUOTES, 'UTF-8') . "</p>";
    $html .= "<p class='small'>Dicetak Pada: " . htmlspecialchars($printedAt, ENT_QUOTES, 'UTF-8') . "</p>";
    $html .= '</div></div>';

    $html .= '<h3 style="margin:6px 0 12px 0">Laporan Tickets (Export PDF)</h3>';

    // table header
    $html .= '<table><thead><tr>
        <th style="width:12%;">Ticket No</th>
        <th style="width:18%;">Entitas / Unit</th>
        <th style="width:26%;">Problem</th>
        <th style="width:12%;">Reporter</th>
        <th style="width:8%;">Status</th>
        <th style="width:12%;">Created</th>
        <th style="width:20%;">Notes / Action</th>
    </tr></thead><tbody>';

    if (empty($rows)) {
        $html .= '<tr><td colspan="7" style="text-align:center;padding:20px;color:#777">Tidak ada data tiket.</td></tr>';
    } else {
        foreach ($rows as $r) {
            $ticketNo = htmlspecialchars($r['ticket_no'] ?? '-', ENT_QUOTES, 'UTF-8');
            $entitas = htmlspecialchars($r['nama_entitas'] ?? '-', ENT_QUOTES, 'UTF-8');
            $unit = $r['unit_nama'] ? htmlspecialchars($r['unit_nama'] . " ({$r['unit_kode']})", ENT_QUOTES, 'UTF-8') : '-';
            $problemType = htmlspecialchars(ucfirst($r['problem_type'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $problemDetail = nl2br(htmlspecialchars($r['problem_detail'] ?? '', ENT_QUOTES, 'UTF-8'));
            $reporter = htmlspecialchars($r['reporter_name'] ?? '-', ENT_QUOTES, 'UTF-8');
            $status = $r['status'] ?? '-';
            $created = $r['created_at'] ? date('Y-m-d H:i:s', strtotime($r['created_at'])) : '-';
            $actionTaken = $r['action_taken'] ? nl2br(htmlspecialchars($r['action_taken'], ENT_QUOTES, 'UTF-8')) : '';
            $closeRemarks = $r['close_remarks'] ? nl2br(htmlspecialchars($r['close_remarks'], ENT_QUOTES, 'UTF-8')) : '';

            // status badge
            $statusCls = 'status-open';
            if ($status === 'waiting') $statusCls = 'status-waiting';
            if ($status === 'confirmed') $statusCls = 'status-confirmed';
            if ($status === 'closed') $statusCls = 'status-closed';
            if ($status === 'cancelled') $statusCls = 'status-cancelled';

            $html .= "<tr>
                <td class='mono'><strong>{$ticketNo}</strong></td>
                <td><strong>{$entitas}</strong><div class='small'>{$unit}</div>";
            if (!empty($r['serial_number'])) {
                $html .= "<div class='small'>SN: " . htmlspecialchars($r['serial_number'], ENT_QUOTES, 'UTF-8') . "</div>";
            }
            $html .= "</td>
                <td class='wrap'><strong>{$problemType}</strong><div class='small' style='margin-top:6px'>{$problemDetail}</div></td>
                <td style='text-align:center'>{$reporter}</td>
                <td style='text-align:center'><span class='badge {$statusCls}'>" . strtoupper(htmlspecialchars($status, ENT_QUOTES, 'UTF-8')) . "</span></td>
                <td class='small' style='text-align:center'>{$created}</td>
                <td class='small wrap'>{$actionTaken}<div style='margin-top:6px'>{$closeRemarks}</div></td>
            </tr>";
        }
    }

    $html .= '</tbody></table>';

    $html .= '<div class="footer">Dicetak Pada: ' . htmlspecialchars($printedAt, ENT_QUOTES, 'UTF-8') . '</div>';
    $html .= '</body></html>';

    // Dompdf render (landscape)
    $options = new Options();
    $options->setIsRemoteEnabled(true);
    $options->setDefaultFont('DejaVu Sans');
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    // A4 landscape
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $filename = 'tickets_export_admin_' . date('Ymd_His') . '.pdf';
    // stream as attachment
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error generating PDF: ' . htmlspecialchars($e->getMessage());
    exit;
}
