<?php
// public/engineer/tickets_action.php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
header('Content-Type: application/json; charset=utf-8');

// ================================
// Timezone FIX
// ================================
date_default_timezone_set('Asia/Jakarta');

// ================================
// Validate request method
// ================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ================================
// Auth check
// ================================
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'], ['engineer', 'project', 'admin'], true)
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ================================
// Input
// ================================
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
$text   = trim($_POST['text'] ?? '');

if ($action !== 'action_taken') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID tiket tidak valid']);
    exit;
}

if ($text === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Teks action tidak boleh kosong']);
    exit;
}

// Batasi panjang input agar aman
if (strlen($text) > 2000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Teks terlalu panjang (max 2000 karakter)']);
    exit;
}

try {
    $pdo = db();

    // ================================
    // Ambil data existing untuk append
    // ================================
    $stmt = $pdo->prepare("
        SELECT action_taken 
        FROM tickets 
        WHERE id = :id 
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tiket tidak ditemukan']);
        exit;
    }

    $current = $row['action_taken'] ?? '';

    // ================================
    // Format Entry Action Taken
    // ================================
    $userName = $_SESSION['fullname'] ?? ($_SESSION['username'] ?? 'Unknown User');

    // Gunakan DateTime agar konsisten dengan timezone
    $timestamp = (new DateTime())->format('Y-m-d H:i:s');

    $entry = sprintf("[%s] %s: %s", $timestamp, $userName, $text);

    // Jika sudah ada history → append newline
    $newValue = trim($current) === '' ? $entry : ($current . "\n" . $entry);

    // ================================
    // Update database
    // ================================
    $update = $pdo->prepare("
        UPDATE tickets 
        SET action_taken = :val, updated_at = NOW() 
        WHERE id = :id 
        LIMIT 1
    ");
    $update->execute([
        ':val' => $newValue,
        ':id'  => $id
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Action berhasil disimpan'
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
