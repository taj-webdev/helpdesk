<?php
// public/admin/tickets_export_excel.php
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// minimal role check (admin only since this is admin export)
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['project','admin'], true)) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

$pdo = db();

try {
    // read same filters as tickets.php
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

    // fetch all matching rows
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

    // Spreadsheet build
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Tickets Export');

    // Header / company info
    $sheet->mergeCells('A1:J1');
    $sheet->setCellValue('A1', 'NINJAS IN PYJAMAS');
    $sheet->mergeCells('A2:J2');
    $sheet->setCellValue('A2', 'Alamat: Jl. RTA Milono Km. 5 No. 10 | WhatsApp: 082251548898');
    $sheet->mergeCells('A3:J3');
    $sheet->setCellValue('A3', 'Dicetak Pada: ' . date('d M Y H:i:s'));

    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2')->getFont()->setSize(10);
    $sheet->getStyle('A3')->getFont()->setSize(10);

    // Column titles (A..J)
    $columns = [
        'A' => 'Ticket No',
        'B' => 'Entitas',
        'C' => 'Unit (Kode)',
        'D' => 'Problem Type / Detail',
        'E' => 'Reporter / Phone',
        'F' => 'Status',
        'G' => 'Created At',
        'H' => 'Action Taken',
        'I' => 'Close Remarks',
        'J' => 'Close Date'
    ];
    $rowStart = 5;
    foreach ($columns as $col => $title) {
        $sheet->setCellValue("{$col}{$rowStart}", $title);
    }

    // header styling
    $headerRange = "A{$rowStart}:J{$rowStart}";
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Fill rows
    $r = $rowStart + 1;
    if (empty($rows)) {
        $sheet->setCellValue("A{$r}", 'Tidak ada data tiket.');
    } else {
        foreach ($rows as $row) {
            $ticketNo = $row['ticket_no'] ?? '';
            $entitas = $row['nama_entitas'] ?: '-';
            $unit = $row['unit_nama'] ? ($row['unit_nama'] . " ({$row['unit_kode']})") : '-';
            $ptype = isset($row['problem_type']) ? ucfirst($row['problem_type']) : '-';
            $pdetail = $row['problem_detail'] ?? '';
            $reporter = $row['reporter_name'] ?? '-';
            $phone = $row['phone_number'] ?? '';
            $status = strtoupper($row['status'] ?? '-');
            // created_at / close_date in Asia/Jakarta (db timestamps assumed UTC/local) -> format for export
            $created = $row['created_at'] ? date('Y-m-d H:i:s', strtotime($row['created_at'])) : '';
            $actionTaken = $row['action_taken'] ?? '';
            $closeRemarks = $row['close_remarks'] ?? '';
            $closeDate = $row['close_date'] ? date('Y-m-d H:i:s', strtotime($row['close_date'])) : '';

            $sheet->setCellValue("A{$r}", $ticketNo);
            $sheet->setCellValue("B{$r}", $entitas);
            $sheet->setCellValue("C{$r}", $unit);
            $sheet->setCellValue("D{$r}", $ptype . ' - ' . $pdetail);
            $sheet->setCellValue("E{$r}", $reporter . ($phone ? " / $phone" : ''));
            $sheet->setCellValue("F{$r}", $status);
            $sheet->setCellValue("G{$r}", $created);
            $sheet->setCellValue("H{$r}", $actionTaken);
            $sheet->setCellValue("I{$r}", $closeRemarks);
            $sheet->setCellValue("J{$r}", $closeDate);

            // wrap long cells
            $sheet->getStyle("D{$r}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("H{$r}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("I{$r}")->getAlignment()->setWrapText(true);
            $r++;
        }
    }

    // Auto-size
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Freeze header
    $sheet->freezePane('A' . ($rowStart + 1));

    // Footer printed at
    $sheet->setCellValue("A" . ($r + 1), 'Dicetak Pada: ' . date('d M Y H:i:s'));

    // Output
    $filename = 'tickets_export_admin_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo "Error generating Excel: " . htmlspecialchars($e->getMessage());
    exit;
}
