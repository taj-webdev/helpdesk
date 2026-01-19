<?php
// public/engineer/tickets_create.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// auth: hanya engineer/project/admin (engineer panel)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['engineer','project','admin'], true)) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

// helper: generate ticket number TKT/NIP/0001/25 (reset tiap tahun)
function generateTicketNumber(PDO $pdo) {
    $year = date('y'); // dua digit, misal 25
    // cari ticket terakhir untuk tahun ini
    $stmt = $pdo->prepare("SELECT ticket_no FROM tickets WHERE ticket_no LIKE :pattern ORDER BY id DESC LIMIT 1");
    $stmt->execute([':pattern' => '%/' . $year]);
    $last = $stmt->fetchColumn();

    if ($last) {
        $parts = explode('/', $last);
        // expected parts: [TKT, NIP, 0001, 25]
        $lastNum = isset($parts[2]) ? intval($parts[2]) : 0;
        $newNum = $lastNum + 1;
    } else {
        $newNum = 1;
    }

    $num4 = str_pad($newNum, 4, '0', STR_PAD_LEFT);
    return "TKT/NIP/{$num4}/{$year}";
}

// fetch units & entities & authors for select
$unitsStmt = $pdo->query("SELECT id, unit_id, nama_unit, alamat, kab_kota, provinsi FROM units ORDER BY nama_unit ASC");
$units = $unitsStmt->fetchAll(PDO::FETCH_ASSOC);

$entitiesStmt = $pdo->query("SELECT id, unit_id, nama_entitas, nama_pengguna, serial_number, tipe_entitas, brand FROM entities ORDER BY nama_entitas ASC");
$entities = $entitiesStmt->fetchAll(PDO::FETCH_ASSOC);

// reporter (current user)
$reporterId = (int) $_SESSION['user_id'];
$reporterName = $_SESSION['fullname'] ?? ($_SESSION['username'] ?? 'Unknown');

// handle POST (create ticket)
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // read inputs (ids)
    $unit_id = (int) ($_POST['unit_id'] ?? 0);
    $entity_id = (int) ($_POST['entity_id'] ?? 0);
    $problem_type = trim($_POST['problem_type'] ?? '');
    $problem_detail = trim($_POST['problem_detail'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');

    // simple validation
    if ($unit_id <= 0) $errors[] = 'Pilih Unit.';
    if ($entity_id <= 0) $errors[] = 'Pilih Entitas / Perangkat.';
    if ($problem_type === '' || !in_array($problem_type, ['software','hardware','internet','accessories'], true)) $errors[] = 'Pilih tipe masalah.';
    if ($problem_detail === '') $errors[] = 'Jelaskan masalah pada kolom detail.';

    if (empty($errors)) {
        try {
            // generate ticket number (unique)
            $ticket_no = generateTicketNumber($pdo);

            $ins = $pdo->prepare("INSERT INTO tickets 
                (ticket_no, reporter_id, unit_id, entity_id, problem_type, problem_detail, phone_number, status, created_at, updated_at)
                VALUES
                (:ticket_no, :reporter_id, :unit_id, :entity_id, :problem_type, :problem_detail, :phone_number, 'open', NOW(), NOW())
            ");

            $ins->execute([
                ':ticket_no' => $ticket_no,
                ':reporter_id' => $reporterId,
                ':unit_id' => $unit_id,
                ':entity_id' => $entity_id,
                ':problem_type' => $problem_type,
                ':problem_detail' => $problem_detail,
                ':phone_number' => $phone_number !== '' ? $phone_number : null,
            ]);

            // redirect with success
            header('Location: tickets.php?status=success&message=' . urlencode('Ticket berhasil dibuat: ' . $ticket_no));
            exit;
        } catch (Exception $e) {
            $errors[] = 'Gagal membuat ticket: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tambah Ticket - Engineer Panel</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/png" href="../assets/img/NIP.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        @keyframes fadeInUpSoft {
            0% { opacity: 0; transform: translateY(14px); filter: blur(2px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .fade-in { animation: fadeInUpSoft 0.7s cubic-bezier(.22,.61,.36,1) forwards; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
<div class="min-h-screen flex">
    <?php include __DIR__ . '/sidebar_engineer.php'; ?>
    <div class="flex-1 flex flex-col">
        <?php
        // prepare header variables (used in header_engineer.php)
        $fullname = $_SESSION['fullname'] ?? ($_SESSION['username'] ?? 'Engineer');
        $roleLabel = ucfirst($_SESSION['role'] ?? 'engineer');
        include __DIR__ . '/header_engineer.php';
        ?>

        <main class="flex-1 px-4 md:px-6 lg:px-8 py-6 space-y-6 fade-in">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-semibold">Tambah Ticket</h1>
                    <p class="text-xs md:text-sm text-slate-500">Buat tiket baru (reporter otomatis: kamu).</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="tickets.php" class="inline-flex items-center gap-2 rounded-2xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                        ← Kembali ke Tickets
                    </a>
                </div>
            </div>

            <!-- form card -->
            <div class="bg-white rounded-3xl shadow-lg border border-slate-200 p-5 md:p-6 fade-in">
                <?php if (!empty($errors)): ?>
                    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form id="frm-create" method="post" class="space-y-4">
                    <!-- Reporter (readonly) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-xs font-medium text-slate-700">Reporter</label>
                            <div class="mt-1 inline-flex items-center gap-3">
                                <div class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-sky-50 text-sky-600">
                                    <!-- user icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.6"/>
                                        <path d="M5 20c1.5-3 4-4.5 7-4.5s5.5 1.5 7 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($reporterName, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-xs text-slate-500">Reporter ID: <?= $reporterId; ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- generated ticket no display -->
                        <div>
                            <label class="text-xs font-medium text-slate-700">Ticket No (otomatis)</label>
                            <div class="mt-1">
                                <input type="text" readonly
                                       value="<?= htmlspecialchars(generateTicketNumber($pdo), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                            </div>
                        </div>

                        <!-- phone -->
                        <div>
                            <label class="text-xs font-medium text-slate-700">Nomor Telepon (opsional)</label>
                            <input type="text" name="phone_number" value="<?= htmlspecialchars($_POST['phone_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm" placeholder="Contoh: 0812xxxx">
                        </div>
                    </div>

                    <!-- unit & entity -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-slate-700">Pilih Unit</label>
                            <select id="unit-select" name="unit_id" required
                                    class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">-- Pilih unit --</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= (int)$u['id']; ?>"
                                            data-code="<?= htmlspecialchars($u['unit_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars(($u['unit_id'] ? ($u['unit_id'] . ' - ') : '') . $u['nama_unit'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-700">Pilih Entitas / Perangkat</label>
                            <select id="entity-select" name="entity_id" required
                                    class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">-- Pilih entitas --</option>
                                <?php foreach ($entities as $e): ?>
                                    <option value="<?= (int)$e['id']; ?>"
                                            data-unit="<?= (int)$e['unit_id']; ?>"
                                            data-tipe="<?= htmlspecialchars($e['tipe_entitas'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($e['nama_entitas'] . ($e['nama_pengguna'] ? ' — ' . $e['nama_pengguna'] : ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- problem type & detail -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-xs font-medium text-slate-700">Tipe Problem</label>
                            <select name="problem_type" required class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">-- Pilih tipe --</option>
                                <option value="software" <?= (($_POST['problem_type'] ?? '') === 'software') ? 'selected' : ''; ?>>Software</option>
                                <option value="hardware" <?= (($_POST['problem_type'] ?? '') === 'hardware') ? 'selected' : ''; ?>>Hardware</option>
                                <option value="internet" <?= (($_POST['problem_type'] ?? '') === 'internet') ? 'selected' : ''; ?>>Internet</option>
                                <option value="accessories" <?= (($_POST['problem_type'] ?? '') === 'accessories') ? 'selected' : ''; ?>>Accessories</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Detail Problem</label>
                            <textarea name="problem_detail" required rows="5"
                                      class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm"><?= htmlspecialchars($_POST['problem_detail'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 justify-end">
                        <a href="tickets.php" class="px-4 py-2 rounded-2xl border bg-white text-sm">Batal</a>
                        <button id="btn-submit" type="button" class="px-4 py-2 rounded-2xl bg-indigo-600 text-white text-sm font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block w-4 h-4 mr-1 -mt-0.5" viewBox="0 0 24 24" fill="none">
                                <path d="M12 5v14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                <path d="M5 12h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                            Simpan Ticket
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?php include __DIR__ . '/footer_engineer.php'; ?>
    </div>
</div>

<script>
    // prepare entities mapping (filter entity select when unit change)
    (function () {
        const entitySelect = document.getElementById('entity-select');
        const unitSelect = document.getElementById('unit-select');

        // when unit changes, show only entities with matching data-unit
        unitSelect.addEventListener('change', function () {
            const selectedUnit = this.value;
            for (let i = 0; i < entitySelect.options.length; i++) {
                const opt = entitySelect.options[i];
                const unit = opt.getAttribute('data-unit');
                // keep the placeholder option
                if (!opt.value) {
                    opt.style.display = '';
                    continue;
                }
                if (selectedUnit === '' || unit === selectedUnit) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            }
            // if currently selected entity doesn't belong to chosen unit, reset
            if (entitySelect.value) {
                const cur = entitySelect.options[entitySelect.selectedIndex];
                if (cur && cur.style.display === 'none') {
                    entitySelect.value = '';
                }
            }
        });

        // submit with confirmation (SweetAlert)
        document.getElementById('btn-submit').addEventListener('click', function () {
            // basic client-side validation
            const unit = unitSelect.value;
            const entity = entitySelect.value;
            const problemType = document.querySelector('select[name="problem_type"]').value;
            const detail = document.querySelector('textarea[name="problem_detail"]').value.trim();

            if (!unit || !entity || !problemType || !detail) {
                Swal.fire({
                    icon: 'error',
                    title: 'Form belum lengkap',
                    text: 'Pastikan Unit, Entitas, Tipe Problem, dan Detail Problem sudah diisi.'
                });
                return;
            }

            Swal.fire({
                title: 'Buat ticket baru?',
                html: 'Ticket akan dibuat dan nomornya di-generate otomatis.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, buat',
                cancelButtonText: 'Batal'
            }).then((res) => {
                if (res.isConfirmed) {
                    // submit native form
                    document.getElementById('frm-create').submit();
                }
            });
        });

        // clock in header (if header_engineer uses #admin-clock)
        (function clock() {
            const el = document.getElementById('admin-clock');
            if (!el) return;
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            el.textContent = `${now.toLocaleDateString()} • ${hh}:${mm}:${ss}`;
            setTimeout(clock, 1000);
        })();
    })();
</script>
</body>
</html>
