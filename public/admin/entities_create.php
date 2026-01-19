<?php
// public/admin/entities_create.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Simple auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

// Ambil daftar units untuk dropdown
$unitsStmt = $pdo->query("
    SELECT id, unit_id, nama_unit, kab_kota, provinsi
    FROM units
    ORDER BY nama_unit ASC
");
$units = $unitsStmt->fetchAll();

// SweetAlert dari redirect (kalau mau dipakai)
$swalStatus  = $_GET['status']  ?? null;
$swalMessage = $_GET['message'] ?? null;

// Default values form
$unit_id       = $_POST['unit_id']       ?? '';
$nama_pengguna = $_POST['nama_pengguna'] ?? '';
$nama_entitas  = $_POST['nama_entitas']  ?? '';
$serial_number = $_POST['serial_number'] ?? '';
$tipe_entitas  = $_POST['tipe_entitas']  ?? '';
$brand         = $_POST['brand']         ?? '';

$localError = null;

$allowedTypes = [
    ''          => 'Pilih tipe entitas...',
    'pc'        => 'PC',
    'monitor'   => 'Monitor',
    'laptop'    => 'Laptop',
    'printer'   => 'Printer',
    'other'     => 'Lainnya',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_id       = (int)($_POST['unit_id'] ?? 0);
    $nama_pengguna = trim($_POST['nama_pengguna'] ?? '');
    $nama_entitas  = trim($_POST['nama_entitas'] ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '');
    $tipe_entitas  = strtolower(trim($_POST['tipe_entitas'] ?? ''));
    $brand         = trim($_POST['brand'] ?? '');

    // Validasi sederhana
    if ($unit_id <= 0 || $nama_entitas === '') {
        $localError = 'Unit dan Nama Entitas wajib diisi.';
    } elseif (!array_key_exists($tipe_entitas === '' ? '' : $tipe_entitas, $allowedTypes)) {
        $localError = 'Tipe entitas tidak valid.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO entities (
                    unit_id, nama_pengguna, nama_entitas, serial_number,
                    tipe_entitas, brand
                ) VALUES (
                    :unit_id, :nama_pengguna, :nama_entitas, :serial_number,
                    :tipe_entitas, :brand
                )
            ");

            $stmt->bindValue(':unit_id', $unit_id, PDO::PARAM_INT);
            $stmt->bindValue(':nama_pengguna', $nama_pengguna !== '' ? $nama_pengguna : null, PDO::PARAM_STR);
            $stmt->bindValue(':nama_entitas', $nama_entitas, PDO::PARAM_STR);
            $stmt->bindValue(':serial_number', $serial_number !== '' ? $serial_number : null, PDO::PARAM_STR);
            $stmt->bindValue(':tipe_entitas', $tipe_entitas !== '' ? $tipe_entitas : null, PDO::PARAM_STR);
            $stmt->bindValue(':brand', $brand !== '' ? $brand : null, PDO::PARAM_STR);

            $stmt->execute();

            header('Location: entities.php?status=success&message=' . urlencode('Entity berhasil ditambahkan.'));
            exit;
        } catch (PDOException $e) {
            $localError = 'Gagal menyimpan data entity: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Entity - Helpdesk NIP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="../assets/img/NIP.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        <main class="flex-1 px-4 md:px-6 lg:px-8 py-6 space-y-6 fade-in-soft-delayed">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-semibold">Tambah Entity</h1>
                    <p class="text-xs md:text-sm text-slate-500">
                        Tambahkan perangkat / entitas baru ke dalam sistem helpdesk.
                    </p>
                </div>
                <a href="entities.php"
                   class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-xs md:text-sm
                          font-medium text-slate-700 shadow-sm hover:bg-slate-50 hover:-translate-y-[1px] active:translate-y-0 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-5 md:h-5" viewBox="0 0 24 24" fill="none">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Kembali ke Entities</span>
                </a>
            </div>

            <section class="bg-white rounded-3xl shadow-lg border border-slate-200 p-4 md:p-6 fade-in-soft">
                <div class="flex items-center gap-3 mb-4">
                    <div class="inline-flex items-center justify-center w-9 h-9 rounded-2xl bg-indigo-50 text-indigo-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="7" width="13" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M9 17V19H6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <rect x="17" y="9" width="4" height="8" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm md:text-base font-semibold text-slate-800">Form Tambah Entity</h2>
                        <p class="text-xs md:text-sm text-slate-500">
                            Isi informasi entitas / perangkat sesuai data aktual di lapangan.
                        </p>
                    </div>
                </div>

                <form action="" method="post" class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-4 md:gap-6">
                        <!-- Unit -->
                        <div class="space-y-1.5">
                            <label class="block text-xs md:text-sm font-medium text-slate-700">
                                Unit <span class="text-rose-500">*</span>
                            </label>
                            <select
                                name="unit_id"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs md:text-sm
                                       outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500"
                                required
                            >
                                <option value="">Pilih unit...</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= (int)$u['id']; ?>"
                                        <?= (string)$u['id'] === (string)$unit_id ? 'selected' : ''; ?>>
                                        <?php
                                        $parts = [];
                                        if (!empty($u['unit_id'])) { $parts[] = $u['unit_id']; }
                                        if (!empty($u['nama_unit'])) { $parts[] = $u['nama_unit']; }
                                        if (!empty($u['kab_kota'])) { $parts[] = $u['kab_kota']; }
                                        if (!empty($u['provinsi'])) { $parts[] = $u['provinsi']; }
                                        echo htmlspecialchars(implode(' - ', $parts), ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Nama Entitas -->
                        <div class="space-y-1.5">
                            <label class="block text-xs md:text-sm font-medium text-slate-700">
                                Nama Entitas / Perangkat <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="nama_entitas"
                                value="<?= htmlspecialchars($nama_entitas, ENT_QUOTES, 'UTF-8'); ?>"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs md:text-sm
                                       outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500"
                                required
                            >
                        </div>

                        <!-- Nama Pengguna -->
                        <div class="space-y-1.5">
                            <label class="block text-xs md:text-sm font-medium text-slate-700">
                                Nama Pengguna
                            </label>
                            <input
                                type="text"
                                name="nama_pengguna"
                                value="<?= htmlspecialchars($nama_pengguna, ENT_QUOTES, 'UTF-8'); ?>"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs md:text-sm
                                       outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500"
                                placeholder="Contoh: Budi Setiawan"
                            >
                        </div>

                        <!-- Tipe Entitas -->
                        <div class="space-y-1.5">
                            <label class="block text-xs md:text-sm font-medium text-slate-700">
                                Tipe Entitas / Perangkat
                            </label>
                            <select
                                name="tipe_entitas"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs md:text-sm
                                       outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500"
                            >
                                <?php foreach ($allowedTypes as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?= (string)$value === (string)$tipe_entitas ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Brand -->
                        <div class="space-y-1.5">
                            <label class="block text-xs md:text-sm font-medium text-slate-700">
                                Brand
                            </label>
                            <input
                                type="text"
                                name="brand"
                                value="<?= htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'); ?>"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs md:text-sm
                                       outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500"
                                placeholder="Contoh: Lenovo, Dell, Asus..."
                            >
                        </div>

                        <!-- Serial Number -->
                        <div class="space-y-1.5">
                            <label class="block text-xs md:text-sm font-medium text-slate-700">
                                Serial Number
                            </label>
                            <input
                                type="text"
                                name="serial_number"
                                value="<?= htmlspecialchars($serial_number, ENT_QUOTES, 'UTF-8'); ?>"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs md:text-sm
                                       outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500"
                                placeholder="Nomor seri perangkat"
                            >
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 pt-2">
                        <a href="entities.php"
                           class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-xs md:text-sm
                                  font-medium text-slate-700 hover:bg-slate-50 transition">
                            Batal
                        </a>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-2xl bg-indigo-500 px-4 py-2 text-xs md:text-sm
                                   font-semibold text-white shadow-lg hover:bg-indigo-400 hover:shadow-xl
                                   hover:-translate-y-[1px] active:translate-y-0 transition"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-5 md:h-5" viewBox="0 0 24 24" fill="none">
                                <path d="M5 13L9 17L19 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Simpan Entity
                        </button>
                    </div>
                </form>
            </section>
        </main>

        <?php include __DIR__ . '/footer_admin.php'; ?>
    </div>
</div>

<script>
    <?php if ($localError): ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: <?= json_encode($localError, JSON_UNESCAPED_UNICODE); ?>,
    });
    <?php endif; ?>

    <?php if ($swalStatus && $swalMessage): ?>
    Swal.fire({
        icon: '<?= htmlspecialchars($swalStatus, ENT_QUOTES, 'UTF-8'); ?>',
        title: <?= json_encode($swalStatus === 'success' ? 'Berhasil' : ($swalStatus === 'error' ? 'Gagal' : 'Info')); ?>,
        text: <?= json_encode($swalMessage, JSON_UNESCAPED_UNICODE); ?>,
        timer: 2200,
        showConfirmButton: false,
        timerProgressBar: true
    });
    <?php endif; ?>
</script>

</body>
</html>
