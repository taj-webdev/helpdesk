<?php
// public/engineer/tickets_export_excel.php
session_start();
date_default_timezone_set('Asia/Jakarta'); // ✅ FIX WIB

require_once __DIR__ . '/../../app/config/database.php';

// Composer autoload
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// role check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['engineer','project','admin'], true)) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

$pdo = db();

try {
    // ambil data tickets
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

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Tickets Export');

    // Header info
    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1', 'NINJAS IN PYJAMAS - Tickets Export');
    $sheet->mergeCells('A2:G2');
    $sheet->setCellValue('A2', 'Alamat: Jl. RTA Milono Km. 5 No. 10 | WhatsApp: 082251548898');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2')->getFont()->setSize(10);

    // Column titles
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
    $rowStart = 4;

    foreach ($columns as $col => $title) {
        $sheet->setCellValue("$col$rowStart", $title);
    }

    // style header 
    $headerRange = "A{$rowStart}:J{$rowStart}";
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Data rows
    $r = $rowStart + 1;

    foreach ($rows as $row) {
        $sheet->setCellValue("A{$r}", $row['ticket_no']);
        $sheet->setCellValue("B{$r}", $row['nama_entitas'] ?: '-');
        $sheet->setCellValue("C{$r}", ($row['unit_nama'] ? $row['unit_nama'] . " ({$row['unit_kode']})" : '-'));
        $sheet->setCellValue("D{$r}", ucfirst($row['problem_type']) . " - " . $row['problem_detail']);
        $sheet->setCellValue("E{$r}", ($row['reporter_name'] ?: '-') . ($row['phone_number'] ? " / {$row['phone_number']}" : ''));
        $sheet->setCellValue("F{$r}", strtoupper($row['status']));
        $sheet->setCellValue("G{$r}", $row['created_at']);
        $sheet->setCellValue("H{$r}", $row['action_taken']);
        $sheet->setCellValue("I{$r}", $row['close_remarks']);
        $sheet->setCellValue("J{$r}", $row['close_date']);

        $sheet->getStyle("D{$r}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("H{$r}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("I{$r}")->getAlignment()->setWrapText(true);

        $r++;
    }

    // Autosize
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Footer
    $sheet->setCellValue("A" . ($r + 1), 'Dicetak Pada: ' . date('d M Y H:i:s')); // ✔ WIB

    // Output
    $filename = 'tickets_export_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "Error generating Excel: " . htmlspecialchars($e->getMessage());
    exit;
}
