<?php
// public/admin/tickets_delete.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: tickets.php?status=error&message=" . urlencode("ID tidak valid."));
    exit;
}

// cek tiket ada
$cek = $pdo->prepare("SELECT id FROM tickets WHERE id = :id");
$cek->execute([':id'=>$id]);
if (!$cek->fetch()) {
    header("Location: tickets.php?status=error&message=" . urlencode("Ticket tidak ditemukan."));
    exit;
}

// delete
$del = $pdo->prepare("DELETE FROM tickets WHERE id = :id LIMIT 1");
$del->execute([':id'=>$id]);

header("Location: tickets.php?status=success&message=" . urlencode("Ticket berhasil dihapus."));
exit;
