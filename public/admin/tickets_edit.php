<?php
// public/admin/tickets_edit.php
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

// Ambil data tiket
$stmt = $pdo->prepare("
    SELECT t.*, u.fullname AS reporter_name,
           e.nama_entitas, e.serial_number, e.brand, e.tipe_entitas,
           un.unit_id AS unit_kode, un.nama_unit AS unit_nama
    FROM tickets t
    LEFT JOIN users u ON t.reporter_id = u.id
    LEFT JOIN entities e ON t.entity_id = e.id
    LEFT JOIN units un ON t.unit_id = un.id
    WHERE t.id = :id
");
$stmt->execute([':id' => $id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header("Location: tickets.php?status=error&message=" . urlencode("Ticket tidak ditemukan."));
    exit;
}

// --- Handle POST update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $problem_type   = $_POST['problem_type'] ?? '';
        $problem_detail = $_POST['problem_detail'] ?? '';
        $phone          = $_POST['phone_number'] ?? '';
        $status         = $_POST['status'] ?? '';
        $action_taken   = $_POST['action_taken'] ?? null;
        $close_remarks  = $_POST['close_remarks'] ?? null;

        $allowedTypes = ['software','hardware','internet','accessories'];
        $allowedStatus = ['open','waiting','confirmed','closed','cancelled'];

        if (!in_array($problem_type, $allowedTypes, true)) {
            throw new Exception("Problem type tidak valid!");
        }
        if (!in_array($status, $allowedStatus, true)) {
            throw new Exception("Status tidak valid!");
        }

        $closeDateSql = ($status === 'closed') ? ", close_date = NOW()" : "";

        $update = $pdo->prepare("
            UPDATE tickets SET
                problem_type = :ptype,
                problem_detail = :pdetail,
                phone_number = :phone,
                status = :status,
                action_taken = :action_taken,
                close_remarks = :close_remarks
                $closeDateSql,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        $update->execute([
            ':ptype'        => $problem_type,
            ':pdetail'      => $problem_detail,
            ':phone'        => $phone,
            ':status'       => $status,
            ':action_taken' => $action_taken,
            ':close_remarks'=> $close_remarks,
            ':id'           => $id
        ]);

        header("Location: tickets.php?status=success&message=" . urlencode("Ticket berhasil diperbarui."));
        exit;

    } catch (Exception $e) {
        $err = urlencode($e->getMessage());
        header("Location: tickets_edit.php?id=$id&status=error&message=$err");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Ticket</title>
    <link rel="icon" type="image/png" href="../assets/img/NIP.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        <h1 class="text-2xl font-semibold">Edit Ticket</h1>

        <div class="bg-white shadow-lg rounded-3xl p-6 space-y-4">

            <!-- Info ticket (readonly) -->
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-slate-500">Ticket No</p>
                    <p class="font-semibold"><?= htmlspecialchars($ticket['ticket_no']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Reporter</p>
                    <p class="font-semibold"><?= htmlspecialchars($ticket['reporter_name']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Entitas</p>
                    <p class="font-semibold"><?= htmlspecialchars($ticket['nama_entitas']); ?></p>
                </div>
            </div>

            <!-- FORM EDIT -->
            <form method="post" class="space-y-4">

                <div>
                    <label class="text-sm font-medium">Problem Type</label>
                    <select name="problem_type" class="w-full mt-1 rounded-xl border px-3 py-2">
                        <?php foreach (['software','hardware','internet','accessories'] as $pt): ?>
                            <option value="<?= $pt ?>" <?= $ticket['problem_type']==$pt?'selected':'' ?>>
                                <?= ucfirst($pt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium">Problem Detail</label>
                    <textarea name="problem_detail" rows="4"
                        class="w-full mt-1 border rounded-xl px-3 py-2"><?= htmlspecialchars($ticket['problem_detail']); ?></textarea>
                </div>

                <div>
                    <label class="text-sm font-medium">Phone Number</label>
                    <input name="phone_number" class="w-full mt-1 border rounded-xl px-3 py-2"
                           value="<?= htmlspecialchars($ticket['phone_number']); ?>">
                </div>

                <div>
                    <label class="text-sm font-medium">Status</label>
                    <select name="status" id="statusSelect"
                            class="w-full mt-1 rounded-xl border px-3 py-2">
                        <?php
                        $statuses = ['open','waiting','confirmed','closed','cancelled'];
                        foreach ($statuses as $st):
                        ?>
                            <option value="<?= $st ?>" <?= $ticket['status']==$st?'selected':'' ?>>
                                <?= ucfirst($st) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action taken (optional) -->
                <div id="actionTakenBox" class="<?= $ticket['status']=='closed'?'':'hidden' ?>">
                    <label class="text-sm font-medium">Action Taken</label>
                    <textarea name="action_taken" rows="3"
                        class="w-full mt-1 border rounded-xl px-3 py-2"><?= htmlspecialchars($ticket['action_taken']); ?></textarea>
                </div>

                <!-- Close remarks -->
                <div id="closeRemarksBox" class="<?= $ticket['status']=='closed'?'':'hidden' ?>">
                    <label class="text-sm font-medium">Close Remarks</label>
                    <textarea name="close_remarks" rows="3"
                        class="w-full mt-1 border rounded-xl px-3 py-2"><?= htmlspecialchars($ticket['close_remarks']); ?></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3 pt-3">
                    <button class="bg-indigo-500 text-white px-5 py-2 rounded-xl shadow hover:bg-indigo-400">
                        Simpan
                    </button>
                    <a href="tickets.php"
                       class="px-5 py-2 rounded-xl border bg-white hover:bg-slate-50">
                        Batal
                    </a>
                </div>

            </form>

        </div>

    </main>
</div>

<script>
// show/hide close fields
document.getElementById("statusSelect").addEventListener("change", function () {
    const isClosed = this.value === "closed";

    document.getElementById("actionTakenBox").classList.toggle("hidden", !isClosed);
    document.getElementById("closeRemarksBox").classList.toggle("hidden", !isClosed);
});
</script>

</body>
</html>
