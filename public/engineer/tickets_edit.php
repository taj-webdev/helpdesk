<?php
// public/engineer/tickets_edit.php
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

// Fetch ticket
$stmt = $pdo->prepare("
    SELECT * FROM tickets WHERE id = :id LIMIT 1
");
$stmt->execute([':id'=>$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header("Location: tickets.php?status=error&message=" . urlencode("Ticket tidak ditemukan."));
    exit;
}

// Fetch units/entities
$units = $pdo->query("SELECT id, unit_id, nama_unit FROM units ORDER BY nama_unit ASC")->fetchAll();
$entities = $pdo->query("SELECT id, unit_id, nama_entitas, nama_pengguna FROM entities ORDER BY nama_entitas ASC")->fetchAll();

// POST update
$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $unit_id = (int)$_POST['unit_id'];
    $entity_id = (int)$_POST['entity_id'];
    $problem_type = trim($_POST['problem_type']);
    $problem_detail = trim($_POST['problem_detail']);
    $phone = trim($_POST['phone_number']);

    if ($unit_id <= 0) $errors[] = "Unit wajib dipilih.";
    if ($entity_id <= 0) $errors[] = "Entitas wajib dipilih.";
    if (!in_array($problem_type, ['software','hardware','internet','accessories'])) $errors[] = "Problem type tidak valid.";
    if ($problem_detail === "") $errors[] = "Detail problem wajib diisi.";

    if (!$errors) {
        $upd = $pdo->prepare("
            UPDATE tickets SET 
                unit_id = :unit_id,
                entity_id = :entity_id,
                problem_type = :problem_type,
                problem_detail = :problem_detail,
                phone_number = :phone_number,
                updated_at = NOW()
            WHERE id = :id LIMIT 1
        ");

        $upd->execute([
            ':unit_id'=>$unit_id,
            ':entity_id'=>$entity_id,
            ':problem_type'=>$problem_type,
            ':problem_detail'=>$problem_detail,
            ':phone_number'=>$phone !== "" ? $phone : null,
            ':id'=>$id
        ]);

        header("Location: tickets.php?status=success&message=" . urlencode("Ticket berhasil diupdate."));
        exit;
    }
}

// header variables
$fullname = $_SESSION['fullname'];
$roleLabel = ucfirst($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Ticket - Engineer Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="icon" type="image/png" href="../assets/img/NIP.png">

<style>
@keyframes fadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.fade{animation:fadeIn .7s ease forwards;}
</style>
</head>
<body class="bg-slate-100 min-h-screen antialiased">

<div class="min-h-screen flex">

<?php include __DIR__."/sidebar_engineer.php"; ?>

<div class="flex-1 flex flex-col">

<?php include __DIR__."/header_engineer.php"; ?>

<main class="flex-1 px-5 py-6 fade space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl md:text-2xl font-semibold">Edit Ticket</h1>
            <p class="text-sm text-slate-500">Perbarui informasi ticket.</p>
        </div>
        <a href="tickets_detail.php?id=<?= $id ?>" class="px-4 py-2 rounded-2xl bg-slate-200 text-sm">← Detail</a>
    </div>

    <div class="bg-white rounded-3xl shadow border p-6 space-y-5">

        <?php if ($errors): ?>
            <div class="border border-rose-200 bg-rose-50 text-rose-700 px-4 py-3 rounded-lg text-sm">
                <ul class="list-disc ml-4">
                    <?php foreach($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="frm-edit" method="post" class="space-y-4">

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold">Unit</label>
                    <select name="unit_id" id="unit-select"
                        class="mt-1 w-full rounded-2xl border px-3 py-2 text-sm">
                        <?php foreach($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $u['id']==$ticket['unit_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['unit_id']." - ".$u['nama_unit']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold">Entitas / Perangkat</label>
                    <select name="entity_id" id="entity-select"
                        class="mt-1 w-full rounded-2xl border px-3 py-2 text-sm">
                        <?php foreach($entities as $e): ?>
                            <option value="<?= $e['id'] ?>"
                                data-unit="<?= $e['unit_id'] ?>"
                                <?= $e['id']==$ticket['entity_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nama_entitas']." — ".$e['nama_pengguna']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-semibold">Problem Type</label>
                <select name="problem_type"
                    class="mt-1 w-full rounded-2xl border px-3 py-2 text-sm">
                    <?php foreach(['software','hardware','internet','accessories'] as $pt): ?>
                        <option value="<?= $pt ?>" <?= $ticket['problem_type']==$pt ? "selected" : "" ?>>
                            <?= ucfirst($pt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold">Detail Problem</label>
                <textarea name="problem_detail" rows="5"
                    class="mt-1 w-full rounded-2xl border px-3 py-2 text-sm"><?= htmlspecialchars($ticket['problem_detail']) ?></textarea>
            </div>

            <div>
                <label class="text-xs font-semibold">Nomor Telepon</label>
                <input type="text" name="phone_number"
                    value="<?= htmlspecialchars($ticket['phone_number']) ?>"
                    class="mt-1 w-full rounded-2xl border px-3 py-2 text-sm">
            </div>

            <div class="flex justify-end gap-3">
                <a href="tickets_detail.php?id=<?= $id ?>" class="px-4 py-2 rounded-2xl bg-slate-200 text-sm">
                    Batal
                </a>

                <button type="button" id="btn-save"
                    class="px-4 py-2 rounded-2xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-500">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</main>

<?php include __DIR__."/footer_engineer.php"; ?>

</div>
</div>

<script>
const unitSel = document.getElementById("unit-select");
const entitySel = document.getElementById("entity-select");

unitSel.addEventListener("change", () => {
    let uid = unitSel.value;
    for (let opt of entitySel.options) {
        if (!opt.value) continue;
        if (opt.dataset.unit === uid) opt.style.display = "";
        else opt.style.display = "none";
    }
});

document.getElementById("btn-save").addEventListener("click", () => {
    Swal.fire({
        title: "Simpan perubahan?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Ya, simpan",
        cancelButtonText: "Batal"
    }).then(res => {
        if (res.isConfirmed) {
            document.getElementById("frm-edit").submit();
        }
    });
});
</script>

</body>
</html>
