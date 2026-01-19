<?php
// public/engineer/tickets.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Auth: minimal engineer or project or admin allowed
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['engineer','project','admin'], true)) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

// SweetAlert messages from redirects
$swalStatus  = $_GET['status']  ?? null; // success | error | info
$swalMessage = $_GET['message'] ?? null;

// ---------------------------
// Card counts (tickets grouped by entity type)
// ---------------------------
$totalPc = (int) $pdo->query("
    SELECT COUNT(*) FROM tickets t
    JOIN entities e ON t.entity_id = e.id
    WHERE LOWER(e.tipe_entitas) = 'pc'
")->fetchColumn();

$totalMonitor = (int) $pdo->query("
    SELECT COUNT(*) FROM tickets t
    JOIN entities e ON t.entity_id = e.id
    WHERE LOWER(e.tipe_entitas) = 'monitor'
")->fetchColumn();

$totalLaptop = (int) $pdo->query("
    SELECT COUNT(*) FROM tickets t
    JOIN entities e ON t.entity_id = e.id
    WHERE LOWER(e.tipe_entitas) = 'laptop'
")->fetchColumn();

$totalPrinter = (int) $pdo->query("
    SELECT COUNT(*) FROM tickets t
    JOIN entities e ON t.entity_id = e.id
    WHERE LOWER(e.tipe_entitas) = 'printer'
")->fetchColumn();

// ---------------------------
// Search / filters / pagination
// ---------------------------
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status_filter'] ?? '';
$authorFilter = (int)($_GET['author_id'] ?? 0);
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$params = [];

// base FROM (join reporter, entities, units)
$fromSql = "FROM tickets t
    LEFT JOIN users u_reporter ON t.reporter_id = u_reporter.id
    LEFT JOIN entities e ON t.entity_id = e.id
    LEFT JOIN units un ON t.unit_id = un.id
    WHERE 1=1
";

if ($search !== '') {
    $fromSql .= " AND CONCAT(
        COALESCE(t.ticket_no,''),' ',
        COALESCE(e.nama_entitas,''),' ',
        COALESCE(u_reporter.fullname,''),' ',
        COALESCE(e.serial_number,''),' ',
        COALESCE(e.brand,''),' ',
        COALESCE(t.problem_detail,'')
    ) LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if (in_array($statusFilter, ['open','waiting','confirmed','closed','cancelled'], true)) {
    $fromSql .= " AND t.status = :status";
    $params[':status'] = $statusFilter;
}

if ($authorFilter > 0) {
    $fromSql .= " AND t.reporter_id = :author";
    $params[':author'] = $authorFilter;
}

if ($dateFrom !== '') {
    // expect YYYY-MM-DD
    $fromSql .= " AND DATE(t.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $fromSql .= " AND DATE(t.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

// count
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

// fetch tickets
$dataSql = "SELECT
    t.id, t.ticket_no, t.problem_type, t.problem_detail, t.phone_number,
    t.status, t.created_at,
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

// authors list (for filter)
$authorsStmt = $pdo->prepare("SELECT id, fullname FROM users WHERE status = 1 ORDER BY fullname ASC");
$authorsStmt->execute();
$authors = $authorsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Tickets - Engineer Panel</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="icon" type="image/png" href="../assets/img/NIP.png" />
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
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
<div class="min-h-screen flex">
    <?php include __DIR__ . '/sidebar_engineer.php'; ?>

    <div class="flex-1 flex flex-col">
        <?php include __DIR__ . '/header_engineer.php'; ?>

        <main class="flex-1 px-4 md:px-6 lg:px-8 py-6 space-y-6 fade-in-soft-delayed">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-semibold">Tickets</h1>
                    <p class="text-xs md:text-sm text-slate-500">Manajemen tiket (buat, filter, lihat detail, edit).</p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="tickets_export_pdf.php" class="inline-flex items-center gap-2 rounded-2xl bg-rose-500 px-3 py-2 text-xs md:text-sm font-semibold text-white shadow">
                        <!-- pdf -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="1.6"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.6"/></svg>
                        PDF
                    </a>
                    <a href="tickets_export_excel.php" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-500 px-3 py-2 text-xs md:text-sm font-semibold text-white shadow">
                        <!-- excel -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 7v10"/><path d="M16 7v10"/></svg>
                        Excel
                    </a>

                    <a href="tickets_create.php" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-3 py-2 text-xs md:text-sm font-semibold text-white shadow">
                        <!-- plus -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        Tambah Ticket
                    </a>
                </div>
            </div>

            <!-- Cards -->
            <section class="grid grid-cols-1 md:grid-cols-4 gap-4 fade-in-soft">
                <div class="rounded-3xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white p-4 shadow">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs tracking-[0.14em] uppercase">Total Tickets - PC</p>
                            <p class="mt-1 text-2xl font-semibold"><?= $totalPc; ?></p>
                        </div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <!-- pc -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 20H16" stroke="currentColor" stroke-width="1.6"/><path d="M12 16V20" stroke="currentColor" stroke-width="1.6"/></svg>
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
                            <!-- monitor -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M10 19H14" stroke="currentColor" stroke-width="1.6"/></svg>
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
                            <!-- laptop -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"><rect x="4" y="6" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M2 18H22" stroke="currentColor" stroke-width="1.6"/></svg>
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
                            <!-- printer -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"><rect x="6" y="3" width="12" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="4" y="9" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 13H16" stroke="currentColor" stroke-width="1.6"/></svg>
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="1.6"/><path d="M16 16L20 20" stroke="currentColor" stroke-width="1.6"/></svg>
                            </span>
                            <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                                   class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-9 py-2.5 text-sm outline-none"
                                   placeholder="Cari ticket no, entitas, reporter, serial, brand...">
                        </div>
                        <button type="submit" class="px-3 py-2 rounded-2xl bg-indigo-500 text-white text-sm font-semibold">Cari</button>
                        <a href="tickets.php" class="px-3 py-2 rounded-2xl border bg-white text-sm">Reset</a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <select name="status_filter" class="rounded-2xl border px-3 py-2 text-sm">
                            <option value="">Semua Status</option>
                            <?php foreach (['open','waiting','confirmed','closed','cancelled'] as $s): ?>
                                <option value="<?= $s; ?>" <?= $s === $statusFilter ? 'selected' : ''; ?>><?= ucfirst($s); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-2xl border px-3 py-2 text-sm">
                        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-2xl border px-3 py-2 text-sm">

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
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">
                                Belum ada tiket yang sesuai filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $row): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                
                                <!-- TICKET NO -->
                                <td class="px-4 py-3 text-center font-mono text-xs">
                                    <?= htmlspecialchars($row['ticket_no'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <!-- ENTITAS -->
                                <td class="px-4 py-3">
                                    <div class="font-semibold">
                                        <?= htmlspecialchars($row['nama_entitas'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="text-xs text-slate-600">
                                        <?= htmlspecialchars($row['unit_nama'] ? $row['unit_nama']." ({$row['unit_kode']})" : '-', ENT_QUOTES, 'UTF-8'); ?>
                                        
                                        <?php if (!empty($row['serial_number'])): ?>
                                            <div class="inline-block mt-1 px-2 py-1 bg-slate-50 rounded-full text-xs text-slate-600">
                                                SN: <?= htmlspecialchars($row['serial_number'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- PROBLEM -->
                                <td class="px-4 py-3 max-w-md">
                                    <div class="text-sm text-slate-800 font-medium">
                                        <?= htmlspecialchars(ucfirst($row['problem_type']), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1">
                                        <?= nl2br(htmlspecialchars(substr($row['problem_detail'],0,400), ENT_QUOTES, 'UTF-8')); ?>
                                    </div>
                                </td>

                                <!-- REPORTER -->
                                <td class="px-4 py-3 text-center">
                                    <div class="text-sm font-medium">
                                        <?= htmlspecialchars($row['reporter_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>

                                <!-- STATUS BADGE PREMIUM -->
                                <td class="px-4 py-3 text-center">
                                    <?php $status = $row['status']; ?>

                                    <?php if ($status === 'open'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold">
                                            <svg class="w-3.5 h-3.5" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                                            OPEN
                                        </span>
                                    <?php elseif ($status === 'waiting'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                                 viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="9"></circle>
                                                <path d="M12 7v5l3 3" stroke-linecap="round"></path>
                                            </svg>
                                            WAITING
                                        </span>
                                    <?php elseif ($status === 'confirmed'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                                 viewBox="0 0 24 24">
                                                <path d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            CONFIRMED
                                        </span>
                                    <?php elseif ($status === 'closed'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                                 viewBox="0 0 24 24">
                                                <path d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            CLOSED
                                        </span>
                                    <?php elseif ($status === 'cancelled'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-xs font-semibold">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                                 viewBox="0 0 24 24">
                                                <path d="M6 6l12 12M18 6L6 18"></path>
                                            </svg>
                                            CANCELLED
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- CREATED -->
                                <td class="px-4 py-3 text-center text-xs">
                                    <?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <!-- BUTTON Aksi Dengan Ikon -->
                                <td class="px-4 py-3 text-center">
                                    <div class="inline-flex items-center gap-2">

                                        <!-- DETAIL (Icon Eye) -->
                                        <a href="tickets_detail.php?id=<?= (int)$row['id']; ?>"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl 
                                                  bg-sky-50 text-sky-700 text-xs font-semibold hover:bg-sky-100 transition">

                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                                 viewBox="0 0 24 24">
                                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>

                                            Detail
                                        </a>

                                        <!-- EDIT (Pencil) -->
                                        <a href="tickets_edit.php?id=<?= (int)$row['id']; ?>"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl 
                                                  bg-amber-50 text-amber-700 text-xs font-semibold hover:bg-amber-100 transition">

                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                                 viewBox="0 0 24 24">
                                                <path d="M15 6l3 3M4 20l4-1 11-11-3-3L5 16l-1 4"/>
                                            </svg>

                                            Edit
                                        </a>

                                        <!-- ACTION TAKEN (Checklist) -->
                                        <button type="button"
                                            class="btn-action-taken inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl 
                                                   bg-emerald-50 text-emerald-700 text-xs font-semibold hover:bg-emerald-100 transition"
                                            data-id="<?= (int)$row['id']; ?>"
                                            data-ticket="<?= htmlspecialchars($row['ticket_no'], ENT_QUOTES, 'UTF-8'); ?>">

                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                                 viewBox="0 0 24 24">
                                                <path d="M5 13l4 4L19 7"/>
                                                <rect x="3" y="4" width="14" height="17" rx="2"/>
                                            </svg>

                                            Action Taken
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
                                <a href="?<?= http_build_query($baseQuery); ?>" class="px-2 py-1 rounded <?= $active ? 'bg-indigo-500 text-white' : 'bg-white border'; ?>"><?= $p; ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): $baseQuery['page'] = $page + 1; ?>
                                <a href="?<?= http_build_query($baseQuery); ?>" class="px-2 py-1 rounded border bg-white">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <?php include __DIR__ . '/footer_engineer.php'; ?>
    </div>
</div>

<script>
    // show SweetAlert notifications from redirect
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

    /* Action Taken flow */
document.querySelectorAll('.btn-action-taken').forEach(function(btn){
    btn.addEventListener('click', function(){
        const id = this.getAttribute('data-id');
        const ticketNo = this.getAttribute('data-ticket');
        const now = new Date();
        const nowStr = now.toLocaleString(); // tampil di popup (lokal)

        Swal.fire({
            title: 'Action Taken untuk ' + ticketNo,
            html: `<div class="text-sm text-slate-600 mb-2">Timestamp: <strong>${nowStr}</strong></div>
                   <textarea id="swal-action-text" class="swal2-textarea" placeholder="Jelaskan tindakan yang sudah dilakukan..." style="min-height:120px;"></textarea>`,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const val = document.getElementById('swal-action-text').value.trim();
                if (!val) {
                    Swal.showValidationMessage('Tuliskan action yang dilakukan (tidak boleh kosong).');
                    return false;
                }
                return val;
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                const actionText = result.value;

                // post ke server menggunakan fetch
                const formData = new FormData();
                formData.append('action', 'action_taken');
                formData.append('id', id);
                formData.append('text', actionText);

                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch('tickets_action.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                }).then(res => res.json())
                  .then(data => {
                      if (data && data.success) {
                          Swal.fire({
                              icon: 'success',
                              title: 'Tersimpan',
                              text: data.message || 'Action saved.',
                              timer: 1500,
                              showConfirmButton: false
                          }).then(() => {
                              // reload halaman agar tabel & detail uptodate
                              location.reload();
                          });
                      } else {
                          Swal.fire({
                              icon: 'error',
                              title: 'Gagal',
                              text: (data && data.message) ? data.message : 'Gagal menyimpan action.',
                          });
                      }
                  }).catch(err => {
                      console.error(err);
                      Swal.fire({
                          icon: 'error',
                          title: 'Error',
                          text: 'Terjadi kesalahan saat berkomunikasi ke server.'
                      });
                  });
            }
        });
    });
});
</script>

</body>
</html>
