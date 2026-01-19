<?php
// public/admin/entities_delete.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: entities.php?status=error&message=' . urlencode('ID entity tidak valid.'));
    exit;
}

// Ambil dulu nama entitas (optional, untuk pesan)
$select = $pdo->prepare("SELECT nama_entitas FROM entities WHERE id = :id LIMIT 1");
$select->bindValue(':id', $id, PDO::PARAM_INT);
$select->execute();
$entity = $select->fetch();

if (!$entity) {
    header('Location: entities.php?status=error&message=' . urlencode('Data entity tidak ditemukan.'));
    exit;
}

try {
    $del = $pdo->prepare("DELETE FROM entities WHERE id = :id LIMIT 1");
    $del->bindValue(':id', $id, PDO::PARAM_INT);
    $del->execute();

    header('Location: entities.php?status=success&message=' . urlencode('Entity "' . $entity['nama_entitas'] . '" berhasil dihapus.'));
    exit;
} catch (PDOException $e) {
    // Kalau gagal (mis: constraint tiket), kirim pesan error elegan
    $msg = 'Entity tidak dapat dihapus. Pastikan tidak ada tiket yang masih terhubung.';
    header('Location: entities.php?status=error&message=' . urlencode($msg));
    exit;
}
