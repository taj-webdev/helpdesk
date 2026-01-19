<?php
// public/admin/units_edit.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: units.php?status=error&message=' . urlencode('Unit tidak ditemukan.'));
    exit;
}

// Ambil data awal
$stmt = $pdo->prepare("SELECT * FROM units WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$unit = $stmt->fetch();

if (!$unit) {
    header('Location: units.php?status=error&message=' . urlencode('Unit tidak ditemukan.'));
    exit;
}

$errors = [];

// Set nilai awal (untuk pertama kali GET)
$unit_id   = $unit['unit_id'] ?? '';
$nama_unit = $unit['nama_unit'] ?? '';
$alamat    = $unit['alamat'] ?? '';
$kab_kota  = $unit['kab_kota'] ?? '';
$provinsi  = $unit['provinsi'] ?? '';
$tat_target_raw = $unit['tat_target'] !== null ? (string)$unit['tat_target'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_id   = trim($_POST['unit_id']   ?? '');
    $nama_unit = trim($_POST['nama_unit'] ?? '');
    $alamat    = trim($_POST['alamat']    ?? '');
    $kab_kota  = trim($_POST['kab_kota']  ?? '');
    $provinsi  = trim($_POST['provinsi']  ?? '');
    $tat_target_raw = trim($_POST['tat_target'] ?? '');

    if ($nama_unit === '') {
        $errors[] = 'Nama unit wajib diisi.';
    }

    $tat_target = null;
    if ($tat_target_raw !== '') {
        if (!ctype_digit($tat_target_raw)) {
            $errors[] = 'TAT Target harus berupa angka (dalam jam).';
        } else {
            $tat_target = (int) $tat_target_raw;
        }
    }

    try {
        // Cek unik nama_unit (kecuali diri sendiri)
        if ($nama_unit !== '') {
            $stmt = $pdo->prepare("SELECT id FROM units WHERE nama_unit = :nama_unit AND id <> :id LIMIT 1");
            $stmt->execute([
                ':nama_unit' => $nama_unit,
                ':id'        => $id,
            ]);
            if ($stmt->fetch()) {
                $errors[] = 'Nama unit sudah digunakan oleh unit lain.';
            }
        }

        // Cek unik unit_id (jika diisi, kecuali diri sendiri)
        if ($unit_id !== '') {
            $stmt = $pdo->prepare("SELECT id FROM units WHERE unit_id = :unit_id AND id <> :id LIMIT 1");
            $stmt->execute([
                ':unit_id' => $unit_id,
                ':id'      => $id,
            ]);
            if ($stmt->fetch()) {
                $errors[] = 'Unit ID sudah digunakan oleh unit lain.';
            }
        }

        if (empty($errors)) {
            $sql = "UPDATE units
                    SET unit_id = :unit_id,
                        nama_unit = :nama_unit,
                        alamat = :alamat,
                        kab_kota = :kab_kota,
                        provinsi = :provinsi,
                        tat_target = :tat_target
                    WHERE id = :id
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);

            $stmt->bindValue(':unit_id',   $unit_id !== '' ? $unit_id : null, $unit_id !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':nama_unit', $nama_unit, PDO::PARAM_STR);
            $stmt->bindValue(':alamat',    $alamat !== '' ? $alamat : null, $alamat !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':kab_kota',  $kab_kota !== '' ? $kab_kota : null, $kab_kota !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':provinsi',  $provinsi !== '' ? $provinsi : null, $provinsi !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            if ($tat_target !== null) {
                $stmt->bindValue(':tat_target', $tat_target, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':tat_target', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $stmt->execute();

            header('Location: units.php?status=success&message=' . urlencode('Unit berhasil diperbarui.'));
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = 'Terjadi kesalahan pada server saat menyimpan perubahan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Unit - Helpdesk NIP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="../assets/img/NIP.png">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @keyframes fadeInUpSoft {
            0% { opacity: 0; transform: translateY(18px); filter: blur(2px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .fade-in-soft {
            animation: fadeInUpSoft 0.7s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
        }
        .fade-in-soft-delayed {
            animation: fadeInUpSoft 0.9s cubic-bezier(0.22, 0.61, 0.36, 1) 0.1s forwards;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">

<div class="min-h-screen flex">

    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <div class="flex-1 flex flex-col">

        <?php include __DIR__ . '/header_admin.php'; ?>

        <main class="flex-1 px-4 md:px-6 lg:px-8 py-6 fade-in-soft-delayed">
            <div class="max-w-3xl mx-auto space-y-5">
                <!-- Header -->
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl md:text-2xl font-semibold">Edit Unit</h1>
                        <p class="text-xs md:text-sm text-slate-500">
                            Ubah informasi unit: <?= htmlspecialchars($nama_unit, ENT_QUOTES, 'UTF-8'); ?>.
                        </p>
                    </div>
                </div>

                <!-- Card Form -->
                <div class="bg-white rounded-3xl shadow-lg border border-slate-200 p-5 md:p-6 fade-in-soft">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-sky-50 text-sky-600">
                            <!-- icon edit -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <path d="M5 19L5.5 16L15.5 6L18 8.5L8 18.5L5 19Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                <path d="M14 6.5L16.5 4L20 7.5L17.5 10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Form Edit Unit</p>
                            <p class="text-[11px] text-slate-500">Perbarui data unit sesuai kebutuhan.</p>
                        </div>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-[11px] md:text-xs text-red-700">
                            <ul class="list-disc list-inside space-y-1">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Unit ID -->
                            <div class="space-y-1.5">
                                <label for="unit_id" class="text-xs font-medium text-slate-700">
                                    Unit ID
                                </label>
                                <input
                                    type="text"
                                    id="unit_id"
                                    name="unit_id"
                                    value="<?= htmlspecialchars($unit_id, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs md:text-sm
                                           outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500
                                           placeholder:text-slate-400"
                                    placeholder="Misal: UNIT-001"
                                >
                            </div>

                            <!-- Nama Unit -->
                            <div class="space-y-1.5">
                                <label for="nama_unit" class="text-xs font-medium text-slate-700">
                                    Nama Unit <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama_unit"
                                    name="nama_unit"
                                    required
                                    value="<?= htmlspecialchars($nama_unit, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs md:text-sm
                                           outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500
                                           placeholder:text-slate-400"
                                >
                            </div>
                        </div>

                        <!-- Alamat -->
                        <div class="space-y-1.5">
                            <label for="alamat" class="text-xs font-medium text-slate-700">
                                Alamat
                            </label>
                            <textarea
                                id="alamat"
                                name="alamat"
                                rows="3"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs md:text-sm
                                       outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500
                                       placeholder:text-slate-400"
                            ><?= htmlspecialchars($alamat, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Kab/Kota -->
                            <div class="space-y-1.5">
                                <label for="kab_kota" class="text-xs font-medium text-slate-700">
                                    Kab / Kota
                                </label>
                                <input
                                    type="text"
                                    id="kab_kota"
                                    name="kab_kota"
                                    value="<?= htmlspecialchars($kab_kota, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs md:text-sm
                                           outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500
                                           placeholder:text-slate-400"
                                >
                            </div>

                            <!-- Provinsi -->
                            <div class="space-y-1.5">
                                <label for="provinsi" class="text-xs font-medium text-slate-700">
                                    Provinsi
                                </label>
                                <input
                                    type="text"
                                    id="provinsi"
                                    name="provinsi"
                                    value="<?= htmlspecialchars($provinsi, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs md:text-sm
                                           outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500
                                           placeholder:text-slate-400"
                                >
                            </div>

                            <!-- TAT Target -->
                            <div class="space-y-1.5">
                                <label for="tat_target" class="text-xs font-medium text-slate-700">
                                    TAT Target (jam)
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    id="tat_target"
                                    name="tat_target"
                                    value="<?= htmlspecialchars($tat_target_raw, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs md:text-sm
                                           outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500
                                           placeholder:text-slate-400"
                                >
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="mt-4 flex flex-col sm:flex-row justify-end gap-2">
                            <a href="units.php"
                               class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200
                                      bg-white px-4 py-2 text-xs md:text-sm font-medium text-slate-700
                                      hover:bg-slate-50 hover:border-slate-300 transition">
                                Batal
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 py-2
                                           text-xs md:text-sm font-semibold text-white shadow-lg hover:bg-sky-500
                                           hover:shadow-xl hover:-translate-y-[1px] active:translate-y-0 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-5 md:h-5" viewBox="0 0 24 24" fill="none">
                                    <path d="M5 7H19V19H5V7Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    <path d="M9 11L11 13L15 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/footer_admin.php'; ?>
    </div>
</div>
</body>
</html>
