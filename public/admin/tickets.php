<?php
// public/admin/tickets.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Simple auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();
$swalStatus  = $_GET['status']  ?? null; // success | error | info
$swalMessage = $_GET['message'] ?? null;

// ---------------------------
// Handle POST actions (status change / delete)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // CSRF: simple token could be added later
    try {
        if ($action === 'set_status') {
            $id = (int)($_POST['id'] ?? 0);
            $newStatus = $_POST['status'] ?? '';
            $allowed = ['open','waiting','confirmed','closed','cancelled'];
            if ($id <= 0 || !in_array($newStatus, $allowed, true)) {
                throw new Exception('Parameter tidak valid.');
            }
            $upd = $pdo->prepare("UPDATE tickets SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id LIMIT 1");
            $upd->bindValue(':status', $newStatus, PDO::PARAM_STR);
            $upd->bindValue(':id', $id, PDO::PARAM_INT);
            $upd->execute();
            // optional: set close_date when closed
            if ($newStatus === 'closed') {
                $pdo->prepare("UPDATE tickets SET close_date = NOW() WHERE id = :id LIMIT 1")->execute([':id' => $id]);
            }
            header('Location: tickets.php?status=success&message=' . urlencode('Status tiket berhasil diubah.'));
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID tidak valid.');
            // optional: check constraints
            $del = $pdo->prepare("DELETE FROM tickets WHERE id = :id LIMIT 1");
            $del->bindValue(':id', $id, PDO::PARAM_INT);
            $del->execute();
            header('Location: tickets.php?status=success&message=' . urlencode('Tiket berhasil dihapus.'));
            exit;
        }
    } catch (Exception $e) {
        header('Location: tickets.php?status=error&message=' . urlencode($e->getMessage()));
        exit;
    }
}

// ---------------------------
// Card Data (counts)
// ---------------------------
$totalTicketsAll = (int) $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

$totalPc = (int) $pdo->query("
    SELECT COUNT(*)
    FROM tickets t
    JOIN entities e ON t.entity_id = e.id
    WHERE LOWER(e.tipe_entitas) = 'pc'
")->fetchColumn();

$totalMonitor = (int) $pdo->query("
    SELECT COUNT(*)
    FROM tickets t
    JOIN entities e ON t.entity_id = e.id
    WHERE LOWER(e.tipe_entitas) = 'monitor'
")->fetchColumn();

$totalLaptop = (int) $pdo->query("
    SELECT COUNT(*)
    FROM tickets t
    JOIN entities e ON t.entity_id = e.id
    WHERE LOWER(e.tipe_entitas) = 'laptop'
")->fetchColumn();

$totalPrinter = (int) $pdo->query("
    SELECT COUNT(*)
    FROM tickets t
    JOIN entities e ON t.entity_id = e.id
    WHERE LOWER(e.tipe_entitas) = 'printer'
")->fetchColumn();

// ---------------------------
// Filters, search & pagination
// ---------------------------
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status_filter'] ?? '';
$authorFilter = (int)($_GET['author_id'] ?? 0);
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$params = [];

// base FROM (join users reporter, units, entities)
$fromSql = "FROM tickets t
    LEFT JOIN users u_reporter ON t.reporter_id = u_reporter.id
    LEFT JOIN entities e ON t.entity_id = e.id
    LEFT JOIN units un ON t.unit_id = un.id
    WHERE 1=1
";

// search
if ($search !== '') {
    $fromSql .= " AND CONCAT(
        COALESCE(t.ticket_no,''),' ',
        COALESCE(t.problem_detail,''),' ',
        COALESCE(u_reporter.fullname,''),' ',
        COALESCE(e.nama_entitas,''),' ',
        COALESCE(e.serial_number,''),' ',
        COALESCE(e.brand,'')
    ) LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

// status
if (in_array($statusFilter, ['open','waiting','confirmed','closed','cancelled'], true)) {
    $fromSql .= " AND t.status = :status";
    $params[':status'] = $statusFilter;
}

// author
if ($authorFilter > 0) {
    $fromSql .= " AND t.reporter_id = :author";
    $params[':author'] = $authorFilter;
}

// date range (created_at)
if ($dateFrom !== '') {
    $fromSql .= " AND DATE(t.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $fromSql .= " AND DATE(t.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

// count total for pagination
$countSql = "SELECT COUNT(*) " . $fromSql;
$countStmt = $pdo->prepare($countSql);
foreach ($params as $k => $v) {
    $countStmt->bindValue($k, $v);
}
$countStmt->execute();
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// fetch ticket rows with necessary fields
$dataSql = "SELECT
    t.id, t.ticket_no, t.reporter_id, t.problem_type, t.problem_detail, t.phone_number,
    t.status, t.action_taken, t.close_remarks, t.close_date, t.created_at,
    u_reporter.fullname AS reporter_name,
    e.nama_entitas, e.tipe_entitas, e.brand, e.serial_number,
    un.unit_id AS unit_kode, un.nama_unit AS unit_nama
    " . $fromSql . "
    ORDER BY t.created_at DESC
    LIMIT :limit OFFSET :offset
";
$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $k => $v) {
    $dataStmt->bindValue($k, $v);
}
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$tickets = $dataStmt->fetchAll();

// fetch authors (engineers) for filter select
$authorsStmt = $pdo->prepare("SELECT id, fullname FROM users WHERE role = 'engineer' OR role = 'project' OR role = 'admin' ORDER BY fullname ASC");
$authorsStmt->execute();
$authors = $authorsStmt->fetchAll();

// ---------------------------------------------------------------------------------
// Ready UI
// ---------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tickets - Helpdesk NIP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

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

        /* small helpers for glossy badge */
        .glossy-border { box-shadow: inset 0 -6px 18px rgba(255,255,255,0.15); }
        .status-row-highlight { transition: background-color .18s ease; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">

<div class="min-h-screen flex">
    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <div class="flex-1 flex flex-col">
        <?php include __DIR__ . '/header_admin.php'; ?>

        <main class="flex-1 px-4 md:px-6 lg:px-8 py-6 space-y-6 fade-in-soft-delayed">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-semibold">Tickets</h1>
                    <p class="text-xs md:text-sm text-slate-500">Manajemen tiket (filter, ubah status, edit, hapus, export).</p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Export Buttons -->
                    <form action="tickets_export_pdf.php" method="get" class="inline-block">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-rose-500 px-3 py-2 text-xs md:text-sm font-semibold text-white shadow hover:opacity-95">
                            <!-- pdf icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="1.6"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.6"/></svg>
                            PDF
                        </button>
                    </form>
                    <form action="tickets_export_excel.php" method="get" class="inline-block">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-500 px-3 py-2 text-xs md:text-sm font-semibold text-white shadow hover:opacity-95">
                            <!-- excel icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 7v10" stroke="currentColor" stroke-width="1.6"/><path d="M16 7v10" stroke="currentColor" stroke-width="1.6"/></svg>
                            Excel
                        </button>
                    </form>
                </div>
            </div>

            <!-- Cards (tickets by entity type) -->
            <section class="grid grid-cols-1 md:grid-cols-4 gap-4 fade-in-soft">
                <div class="rounded-3xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white p-4 shadow">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs tracking-[0.14em] uppercase">Total Tickets - PC</p>
                            <p class="mt-1 text-2xl font-semibold"><?= $totalPc; ?></p>
                        </div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <!-- pc icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <rect x="2" y="4" width="20" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M8 20H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                <path d="M12 16V20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-cyan-400 to-emerald-400 text-white p-4 shadow">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs tracking-[0.14em] uppercase">Total Tickets - Monitor</p>
                            <p class="mt-1 text-2xl font-semibold"><?= $totalMonitor; ?></p>
                        </div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <!-- monitor icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="4" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M10 19H14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-fuchsia-500 to-pink-400 text-white p-4 shadow">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs tracking-[0.14em] uppercase">Total Tickets - Laptop</p>
                            <p class="mt-1 text-2xl font-semibold"><?= $totalLaptop; ?></p>
                        </div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <!-- laptop icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <rect x="4" y="6" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M2 18H22" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-lime-400 to-teal-400 text-white p-4 shadow">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs tracking-[0.14em] uppercase">Total Tickets - Printer</p>
                            <p class="mt-1 text-2xl font-semibold"><?= $totalPrinter; ?></p>
                        </div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <!-- printer icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <rect x="6" y="3" width="12" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
                                <rect x="4" y="9" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M8 13H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Search + Filters -->
            <section class="bg-white rounded-3xl shadow-lg border border-slate-200 p-4 md:p-5 space-y-4 fade-in-soft">
                <form method="get" class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-center gap-2 w-full md:w-96">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                    <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M16 16L20 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                                   class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-9 py-2.5 text-sm outline-none"
                                   placeholder="Cari ticket no, entitas, reporter, serial, brand...">
                        </div>
                        <button type="submit" class="px-3 py-2 rounded-2xl bg-indigo-500 text-white text-sm font-semibold">Cari</button>
                        <a href="tickets.php" class="px-3 py-2 rounded-2xl border bg-white text-sm">Reset</a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Status filter -->
                        <select name="status_filter" class="rounded-2xl border px-3 py-2 text-sm">
                            <option value="">Semua Status</option>
                            <?php foreach (['open','waiting','confirmed','closed','cancelled'] as $s): ?>
                                <option value="<?= $s; ?>" <?= $s === $statusFilter ? 'selected' : ''; ?>><?= ucfirst($s); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Date from/to -->
                        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-2xl border px-3 py-2 text-sm">
                        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-2xl border px-3 py-2 text-sm">

                        <!-- Author -->
                        <select name="author_id" class="rounded-2xl border px-3 py-2 text-sm">
                            <option value="">Semua Author</option>
                            <?php foreach ($authors as $a): ?>
                                <option value="<?= (int)$a['id']; ?>" <?= ((int)$a['id'] === $authorFilter) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($a['fullname'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                                <!-- Table -->
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-sm md:text-base">
                        <thead class="bg-slate-50">
                        <tr class="text-left text-slate-500 uppercase tracking-wide">
                            <th class="px-4 py-3.5 text-center">Ticket No</th>
                            <th class="px-4 py-3.5">Entitas / Unit</th>
                            <th class="px-4 py-3.5">Problem</th>
                            <th class="px-4 py-3.5 text-center">Reporter</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5 text-center">Created</th>
                            <th class="px-4 py-3.5 text-center">Aksi</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <?php if (empty($tickets)): ?>
                            <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Belum ada tiket yang sesuai filter.</td></tr>
                        <?php else: ?>

                            <?php
                            // helpers for status visuals
                            $iconMap = [
                                'open'      => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.0" stroke-opacity="0.22"/></svg>',
                                'waiting'   => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                                'confirmed' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                                'closed'    => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 18L18 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
                                'cancelled' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 18L18 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
                            ];

                            $glossyMap = [
                                'open'      => 'from-indigo-100 to-indigo-200 text-indigo-700 border-indigo-300',
                                'waiting'   => 'from-amber-100 to-amber-200 text-amber-700 border-amber-300',
                                'confirmed' => 'from-sky-100 to-sky-200 text-sky-700 border-sky-300',
                                'closed'    => 'from-emerald-100 to-emerald-200 text-emerald-700 border-emerald-300',
                                'cancelled' => 'from-rose-100 to-rose-200 text-rose-700 border-rose-300',
                            ];

                            $rowHighlight = [
                                'open'      => 'bg-amber-50 status-row-highlight',
                                'waiting'   => 'bg-amber-50 status-row-highlight',
                                'confirmed' => 'bg-sky-50 status-row-highlight',
                                'closed'    => 'bg-emerald-50 status-row-highlight',
                                'cancelled' => 'bg-rose-50 status-row-highlight',
                            ];
                            ?>

                            <?php foreach ($tickets as $row): ?>
                                <?php $status = $row['status'] ?? ''; ?>
                                <tr class="<?= htmlspecialchars($rowHighlight[$status] ?? '', ENT_QUOTES, 'UTF-8'); ?> hover:bg-white transition">
                                    <td class="px-4 py-3 text-center font-mono text-xs"><?= htmlspecialchars($row['ticket_no'], ENT_QUOTES, 'UTF-8'); ?></td>

                                    <td class="px-4 py-3">
                                        <div class="font-semibold"><?= htmlspecialchars($row['nama_entitas'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-xs text-slate-600">
                                            <?= htmlspecialchars($row['unit_nama'] ? $row['unit_nama'] . " ({$row['unit_kode']})" : '-', ENT_QUOTES, 'UTF-8'); ?>
                                            <?php if (!empty($row['serial_number'])): ?>
                                                <div class="inline-block mt-1 px-2 py-1 bg-slate-50 rounded-full text-xs text-slate-600">
                                                    SN: <?= htmlspecialchars($row['serial_number'], ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 max-w-md">
                                        <div class="text-sm text-slate-800 font-medium"><?= htmlspecialchars(ucfirst($row['problem_type']), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-xs text-slate-500 mt-1 line-clamp-3"><?= nl2br(htmlspecialchars(substr($row['problem_detail'],0,400), ENT_QUOTES, 'UTF-8')); ?></div>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <div class="text-sm font-medium"><?= htmlspecialchars($row['reporter_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>

                                    <!-- STATUS column with glossy badge and dropdown trigger -->
                                    <td class="px-4 py-3 text-center">
                                        <?php
                                            $cls = $glossyMap[$status] ?? 'from-slate-100 to-slate-200 text-slate-700 border-slate-300';
                                            $iconHtml = $iconMap[$status] ?? '';
                                        ?>
                                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gradient-to-br <?= $cls; ?> border glossy-border text-xs font-semibold">
                                            <span class="flex items-center"><?= $iconHtml; ?></span>
                                            <span><?= strtoupper(htmlspecialchars($status, ENT_QUOTES, 'UTF-8')); ?></span>
                                        </span>

                                        <!-- quick actions dropdown (premium) -->
                                        <div class="mt-2 inline-block relative">
                                            <button data-id="<?= (int)$row['id']; ?>" class="btn-change-status inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs bg-white shadow-sm hover:shadow-md transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.6"/></svg>
                                                <span>Ubah Status</span>
                                            </button>

                                            <div class="status-options hidden absolute right-0 mt-2 w-44 bg-white border rounded-xl shadow-lg py-1 z-50">
                                                <?php foreach (['open','waiting','confirmed','closed','cancelled'] as $sopt): ?>
                                                    <button data-id="<?= (int)$row['id']; ?>" data-status="<?= $sopt; ?>" class="set-status w-full text-left px-3 py-2 text-sm hover:bg-slate-50">
                                                        <?php
                                                            echo $iconMap[$sopt] . ' ' . ucfirst($sopt);
                                                        ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </td>

                                                                        <td class="px-4 py-3 text-center text-xs"><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>

                                    <td class="px-4 py-3 text-center">
                                        <div class="inline-flex items-center gap-2">

                                        <!-- DETAIL BUTTON -->
                                        <a href="tickets_detail.php?id=<?= (int)$row['id']; ?>"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl 
                                                  bg-indigo-100 text-indigo-700 text-xs font-semibold
                                                  hover:bg-indigo-200 shadow-sm hover:shadow-md transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4"
                                                 viewBox="0 0 24 24" fill="none">
                                                <path d="M12 5C7 5 2.7 9 2 12c.7 3 5 7 10 7s9.3-4 10-7c-.7-3-5-7-10-7Z"
                                                      stroke="currentColor" stroke-width="1.6"/>
                                                <circle cx="12" cy="12" r="3"
                                                        stroke="currentColor" stroke-width="1.6"/>
                                            </svg>
                                            Detail
                                        </a>

                                        <!-- EDIT BUTTON -->
                                        <a href="tickets_edit.php?id=<?= (int)$row['id']; ?>"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl 
                                                  bg-sky-100 text-sky-700 text-xs font-semibold
                                                  hover:bg-sky-200 shadow-sm hover:shadow-md transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4"
                                                 viewBox="0 0 24 24" fill="none">
                                                <path d="M5 19L6 16L15 7L17 9L8 18L5 19Z"
                                                      stroke="currentColor" stroke-width="1.6"/>
                                            </svg>
                                            Edit
                                        </a>

                                        <!-- DELETE BUTTON -->
                                        <button type="button"
                                            data-id="<?= (int)$row['id']; ?>"
                                            data-ticket="<?= htmlspecialchars($row['ticket_no'], ENT_QUOTES, 'UTF-8'); ?>"
                                            class="btn-delete inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl 
                                                   bg-rose-100 text-rose-700 text-xs font-semibold
                                                   hover:bg-rose-200 shadow-sm hover:shadow-md transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4"
                                                 viewBox="0 0 24 24" fill="none">
                                                <path d="M6 7H18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                                <path d="M10 11V17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                                <path d="M14 11V17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                                <path d="M8 7L9 19H15L16 7"
                                                      stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                            </svg>
                                            Hapus
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-between mt-3">
                        <div class="text-slate-500">Halaman <span class="font-semibold"><?= $page; ?></span> dari <span class="font-semibold"><?= $totalPages; ?></span></div>
                        <div class="flex items-center gap-1">
                            <?php
                                $baseQuery = [];
                                if ($search !== '') $baseQuery['q'] = $search;
                                if ($statusFilter !== '') $baseQuery['status_filter'] = $statusFilter;
                                if ($authorFilter > 0) $baseQuery['author_id'] = $authorFilter;
                                if ($dateFrom !== '') $baseQuery['date_from'] = $dateFrom;
                                if ($dateTo !== '') $baseQuery['date_to'] = $dateTo;
                            ?>
                            <?php if ($page > 1): $baseQuery['page'] = $page - 1; ?>
                                <a href="?<?= http_build_query($baseQuery); ?>" class="px-2 py-1 rounded border bg-white">Prev</a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end   = min($totalPages, $page + 2);
                            for ($p = $start; $p <= $end; $p++):
                                $baseQuery['page'] = $p;
                                $active = $p === $page;
                                ?>
                                <a href="?<?= http_build_query($baseQuery); ?>"
                                   class="px-2 py-1 rounded <?= $active ? 'bg-indigo-500 text-white' : 'bg-white border'; ?>"><?= $p; ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): $baseQuery['page'] = $page + 1; ?>
                                <a href="?<?= http_build_query($baseQuery); ?>" class="px-2 py-1 rounded border bg-white">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </main>

                <?php include __DIR__ . '/footer_admin.php'; ?>
    </div>
</div>

<!-- Hidden forms for actions (POST) -->
<form id="frm-action" method="post" style="display:none;">
    <input type="hidden" name="action" id="frm-action-action" value="">
    <input type="hidden" name="id" id="frm-action-id" value="">
    <input type="hidden" name="status" id="frm-action-status" value="">
</form>

<script>
    // SweetAlert show messages from redirect
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

    // Toggle status dropdown (each button)
    document.querySelectorAll('.btn-change-status').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            // close other dropdowns first
            document.querySelectorAll('.status-options').forEach(el => el.classList.add('hidden'));
            const wrapper = this.parentElement;
            const opts = wrapper.querySelector('.status-options');
            if (!opts) return;
            opts.classList.toggle('hidden');
        });
    });

    // Set status (click option)
    document.querySelectorAll('.set-status').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.stopPropagation();
            const id = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');

            Swal.fire({
                title: 'Ubah status tiket?',
                text: 'Status akan diubah menjadi ' + status.toUpperCase(),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, ubah',
                cancelButtonText: 'Batal'
            }).then(result => {
                if (result.isConfirmed) {
                    // submit hidden form
                    document.getElementById('frm-action-action').value = 'set_status';
                    document.getElementById('frm-action-id').value = id;
                    document.getElementById('frm-action-status').value = status;
                    document.getElementById('frm-action').submit();
                } else {
                    // hide opened dropdown
                    document.querySelectorAll('.status-options').forEach(el => el.classList.add('hidden'));
                }
            });
        });
    });

    // Delete ticket
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const id = this.getAttribute('data-id');
            const ticketNo = this.getAttribute('data-ticket');

            Swal.fire({
                title: 'Hapus tiket?',
                text: 'Tiket ' + ticketNo + ' akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(result => {
                if (result.isConfirmed) {
                    document.getElementById('frm-action-action').value = 'delete';
                    document.getElementById('frm-action-id').value = id;
                    document.getElementById('frm-action').submit();
                }
            });
        });
    });

    // Close any status dropdown when clicking outside
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.status-options').forEach(function (el) {
            if (!el.classList.contains('hidden')) {
                if (!el.parentElement.contains(e.target)) {
                    el.classList.add('hidden');
                }
            }
        });
    });

    // Accessibility: close dropdowns on ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.status-options').forEach(el => el.classList.add('hidden'));
        }
    });
</script>

</body>
</html>
