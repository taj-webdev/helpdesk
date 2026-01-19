<?php
// public/admin/tickets_detail.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

// Ambil ID tiket
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: tickets.php?status=error&message=" . urlencode("ID ticket tidak valid."));
    exit;
}

// Ambil data lengkap ticket
$stmt = $pdo->prepare("
    SELECT 
        t.*, 
        u.fullname AS reporter_name,
        e.nama_entitas, e.serial_number, e.brand, e.tipe_entitas,
        un.unit_id AS unit_kode, un.nama_unit AS unit_nama,
        un.kab_kota, un.provinsi
    FROM tickets t
    LEFT JOIN users u ON t.reporter_id = u.id
    LEFT JOIN entities e ON t.entity_id = e.id
    LEFT JOIN units un ON t.unit_id = un.id
    WHERE t.id = :id
");
$stmt->execute([':id' => $id]);
$tk = $stmt->fetch();

if (!$tk) {
    header("Location: tickets.php?status=error&message=" . urlencode("Ticket tidak ditemukan."));
    exit;
}

// Status badge
$badgeMap = [
    'open'      => 'bg-indigo-100 text-indigo-700',
    'waiting'   => 'bg-amber-100 text-amber-700',
    'confirmed' => 'bg-sky-100 text-sky-700',
    'closed'    => 'bg-emerald-100 text-emerald-700',
    'cancelled' => 'bg-rose-100 text-rose-700',
];
$badgeCls = $badgeMap[$tk['status']] ?? 'bg-slate-100 text-slate-700';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Ticket</title>
    <link rel="icon" type="image/png" href="../assets/img/NIP.png">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @keyframes fadeInUpSoft {
            0% { opacity: 0; transform: translateY(18px); filter: blur(2px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .fade-in-soft { animation: fadeInUpSoft 0.7s cubic-bezier(.22,.61,.36,1) forwards; }
        .fade-in-soft-delayed { animation: fadeInUpSoft 0.9s cubic-bezier(.22,.61,.36,1) 0.1s forwards; }
    </style>
</head>

<body class="bg-slate-100 min-h-screen flex">

<?php include __DIR__ . '/sidebar_admin.php'; ?>

<div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/header_admin.php'; ?>

    <main class="p-6 space-y-6 fade-in-soft">

        <!-- HEADER -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Detail Ticket</h1>

            <a href="tickets.php" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border rounded-xl text-sm shadow hover:bg-slate-50">
                ← Kembali ke Tickets
            </a>
        </div>

        <!-- CARD DETAIL -->
        <div class="bg-white rounded-3xl shadow-lg border p-6 space-y-6">

            <!-- ROW 1 -->
            <div class="grid md:grid-cols-3 gap-6">

                <div>
                    <p class="text-xs text-slate-500">Ticket No</p>
                    <p class="text-lg font-semibold"><?= htmlspecialchars($tk['ticket_no']); ?></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Status</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full <?= $badgeCls ?> text-sm font-semibold">
                        <?= strtoupper($tk['status']); ?>
                    </span>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Created At</p>
                    <p class="font-medium"><?= htmlspecialchars($tk['created_at']); ?></p>
                </div>

            </div>

            <!-- ROW 2 -->
            <div class="grid md:grid-cols-2 gap-6">

                <div>
                    <p class="text-xs text-slate-500">Reporter</p>
                    <p class="text-base font-semibold"><?= htmlspecialchars($tk['reporter_name']); ?></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Unit</p>
                    <p class="font-semibold"><?= htmlspecialchars($tk['unit_nama']); ?> (<?= htmlspecialchars($tk['unit_kode']); ?>)</p>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars($tk['kab_kota'] . ', ' . $tk['provinsi']); ?></p>
                </div>

            </div>

            <!-- ROW 3 ENTITY -->
            <div class="border rounded-2xl p-4 bg-slate-50">
                <p class="text-xs text-slate-500 mb-1">Entitas</p>

                <div class="text-base font-semibold mb-1">
                    <?= htmlspecialchars($tk['nama_entitas']); ?>
                </div>

                <div class="flex flex-wrap gap-2 text-xs text-slate-600">
                    <span class="bg-white px-3 py-1 rounded-full border">
                        <?= strtoupper($tk['tipe_entitas']); ?>
                    </span>
                    <span class="bg-white px-3 py-1 rounded-full border">
                        Brand: <?= htmlspecialchars($tk['brand']); ?>
                    </span>
                    <span class="bg-white px-3 py-1 rounded-full border">
                        SN: <?= htmlspecialchars($tk['serial_number']); ?>
                    </span>
                </div>
            </div>

            <!-- PROBLEM DETAIL -->
            <div>
                <p class="text-xs text-slate-500">Problem Type</p>
                <p class="font-semibold text-base mb-2"><?= ucfirst($tk['problem_type']); ?></p>

                <p class="text-xs text-slate-500 mb-1">Problem Detail</p>
                <div class="bg-slate-50 rounded-xl p-4 whitespace-pre-line text-sm">
                    <?= nl2br(htmlspecialchars($tk['problem_detail'])); ?>
                </div>
            </div>

            <!-- ACTION TAKEN -->
            <div>
                <p class="text-xs text-slate-500 mb-1">Action Taken</p>
                <div class="bg-slate-50 rounded-xl p-4 text-sm whitespace-pre-line">
                    <?= $tk['action_taken'] ? nl2br(htmlspecialchars($tk['action_taken'])) : '<span class="text-slate-400">Belum ada tindakan.</span>'; ?>
                </div>
            </div>

            <!-- CLOSE REMARKS -->
            <?php if ($tk['status'] === 'closed'): ?>
                <div>
                    <p class="text-xs text-slate-500 mb-1">Close Remarks</p>
                    <div class="bg-slate-50 rounded-xl p-4 text-sm whitespace-pre-line">
                        <?= $tk['close_remarks'] ? nl2br(htmlspecialchars($tk['close_remarks'])) : '<span class="text-slate-400">-</span>'; ?>
                    </div>

                    <div class="text-xs text-slate-500 mt-2">
                        Closed at: <span class="font-medium"><?= htmlspecialchars($tk['close_date']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </main>
</div>

</body>
</html>
