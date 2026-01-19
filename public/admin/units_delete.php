<?php
// public/admin/units_delete.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: units.php?status=error&message=' . urlencode('Parameter ID tidak valid.'));
    exit;
}

try {
    // cek dulu untuk info nama (opsional)
    $stmt = $pdo->prepare("SELECT nama_unit FROM units WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $unit = $stmt->fetch();

    if (!$unit) {
        header('Location: units.php?status=error&message=' . urlencode('Unit tidak ditemukan.'));
        exit;
    }

    $delete = $pdo->prepare("DELETE FROM units WHERE id = :id LIMIT 1");
    $delete->execute([':id' => $id]);

    if ($delete->rowCount() > 0) {
        header('Location: units.php?status=success&message=' . urlencode('Unit "' . $unit['nama_unit'] . '" berhasil dihapus.'));
        exit;
    } else {
        // tidak terhapus tapi tidak error → kemungkinan sudah hilang
        header('Location: units.php?status=info&message=' . urlencode('Tidak ada perubahan pada data unit.'));
        exit;
    }

} catch (PDOException $e) {
    // Kalau gagal karena foreign key (masih ada entities yang refer ke unit ini)
    if ($e->getCode() === '23000') {
        $msg = 'Unit tidak dapat dihapus karena masih memiliki entities atau relasi tiket.';
    } else {
        $msg = 'Terjadi kesalahan saat menghapus unit.';
    }

    header('Location: units.php?status=error&message=' . urlencode($msg));
    exit;
}
