<?php
// public/engineer/tickets_detail.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Auth
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['engineer','project','admin'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: tickets.php?status=error&message=" . urlencode("ID ticket tidak valid."));
    exit;
}

// fetch ticket join data
$stmt = $pdo->prepare("
    SELECT t.*, 
           u.fullname AS reporter_name,
           e.nama_entitas, e.tipe_entitas, e.brand, e.serial_number, e.nama_pengguna,
           un.unit_id AS kode_unit, un.nama_unit, un.kab_kota, un.provinsi, un.alamat
    FROM tickets t
    LEFT JOIN users u ON t.reporter_id = u.id
    LEFT JOIN entities e ON t.entity_id = e.id
    LEFT JOIN units un ON t.unit_id = un.id
    WHERE t.id = :id
    LIMIT 1
");
$stmt->execute([':id'=>$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header("Location: tickets.php?status=error&message=" . urlencode("Ticket tidak ditemukan."));
    exit;
}

$fullname  = $_SESSION['fullname'];
$roleLabel = ucfirst($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail Ticket - Engineer Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="icon" type="image/png" href="../assets/img/NIP.png">

<style>
@keyframes fadeIn {
  from { opacity:0; transform:translateY(10px); }
  to   { opacity:1; transform:translateY(0); }
}
.fade { animation: fadeIn .7s ease forwards; }
</style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">

<div class="min-h-screen flex">
<?php include __DIR__."/sidebar_engineer.php"; ?>

<div class="flex-1 flex flex-col">
<?php include __DIR__."/header_engineer.php"; ?>

<main class="flex-1 px-5 py-6 fade space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl md:text-2xl font-semibold">Detail Ticket</h1>
            <p class="text-xs md:text-sm text-slate-500">Informasi lengkap ticket.</p>
        </div>
        <a href="tickets.php" class="px-4 py-2 rounded-2xl bg-slate-200 text-sm hover:bg-slate-300">← Kembali</a>
    </div>

    <!-- Card Detail -->
    <div class="bg-white rounded-3xl shadow-lg border border-slate-200 p-6 space-y-5">

        <!-- Header Ticket -->
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500">Ticket No</p>
                <p class="text-lg font-semibold text-slate-800"><?= htmlspecialchars($ticket['ticket_no']); ?></p>
            </div>
            <span class="px-4 py-1 rounded-full text-sm font-semibold
                <?php
                    $status = $ticket['status'];
                    echo [
                        'open'=>'bg-indigo-100 text-indigo-700',
                        'waiting'=>'bg-amber-100 text-amber-700',
                        'confirmed'=>'bg-sky-100 text-sky-700',
                        'closed'=>'bg-emerald-100 text-emerald-700',
                        'cancelled'=>'bg-rose-100 text-rose-700'
                    ][$status] ?? 'bg-slate-100 text-slate-700';
                ?>
            "><?= strtoupper($status); ?></span>
        </div>

        <!-- Info Grid -->
        <div class="grid md:grid-cols-2 gap-4">

            <div class="p-4 rounded-2xl bg-slate-50 border text-sm space-y-1">
                <p class="font-semibold text-slate-700">Reporter:</p>
                <p><?= htmlspecialchars($ticket['reporter_name'] ?? "-") ?></p>

                <p class="font-semibold mt-3">Tanggal dibuat:</p>
                <p><?= htmlspecialchars($ticket['created_at']); ?></p>

                <?php if ($ticket['close_date']): ?>
                    <p class="font-semibold mt-3">Tanggal selesai:</p>
                    <p><?= htmlspecialchars($ticket['close_date']); ?></p>
                <?php endif; ?>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border text-sm space-y-1">
                <p class="font-semibold text-slate-700">Unit:</p>
                <p><?= htmlspecialchars($ticket['kode_unit']." - ".$ticket['nama_unit']); ?></p>
                <p class="text-xs"><?= htmlspecialchars($ticket['alamat']); ?></p>

                <p class="font-semibold mt-3 text-slate-700">Entitas / Perangkat:</p>
                <p><?= htmlspecialchars($ticket['nama_entitas']); ?> — <?= htmlspecialchars($ticket['nama_pengguna']); ?></p>
                <p class="text-xs">SN: <?= htmlspecialchars($ticket['serial_number']); ?></p>
            </div>
        </div>

        <!-- Problem -->
        <div class="p-4 rounded-2xl bg-white border">
            <p class="font-semibold text-slate-700">Problem Type:</p>
            <p class="text-sm capitalize"><?= htmlspecialchars($ticket['problem_type']); ?></p>

            <p class="font-semibold mt-3 text-slate-700">Detail Problem:</p>
            <p class="text-sm whitespace-pre-line"><?= nl2br(htmlspecialchars($ticket['problem_detail'])); ?></p>
        </div>

        <?php if ($ticket['action_taken']): ?>
        <div class="p-4 rounded-2xl bg-white border">
            <p class="font-semibold">Action Taken:</p>
            <p class="text-sm whitespace-pre-line"><?= nl2br(htmlspecialchars($ticket['action_taken'])); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($ticket['close_remarks']): ?>
        <div class="p-4 rounded-2xl bg-white border">
            <p class="font-semibold">Close Remarks:</p>
            <p class="text-sm whitespace-pre-line"><?= nl2br(htmlspecialchars($ticket['close_remarks'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Edit Button -->
        <div class="flex justify-end">
            <a href="tickets_edit.php?id=<?= $ticket['id']; ?>" class="px-4 py-2 rounded-2xl bg-indigo-500 text-white text-sm hover:bg-indigo-600">Edit Ticket</a>
        </div>

    </div>
</main>

<?php include __DIR__."/footer_engineer.php"; ?>
</div>
</div>

</body>
</html>
