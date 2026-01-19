<?php
// public/engineer/entities.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

// stats (cards)
$totalEntities = (int) $pdo->query("SELECT COUNT(*) FROM entities")->fetchColumn();
$totalTickets = (int) $pdo->query("SELECT COALESCE(SUM(jumlah_ticket),0) FROM entities")->fetchColumn();
$totalPc = (int) $pdo->query("SELECT COUNT(*) FROM entities WHERE LOWER(tipe_entitas) = 'pc'")->fetchColumn();
$totalMonitor = (int) $pdo->query("SELECT COUNT(*) FROM entities WHERE LOWER(tipe_entitas) = 'monitor'")->fetchColumn();
$totalLaptop = (int) $pdo->query("SELECT COUNT(*) FROM entities WHERE LOWER(tipe_entitas) = 'laptop'")->fetchColumn();
$totalPrinter = (int) $pdo->query("SELECT COUNT(*) FROM entities WHERE LOWER(tipe_entitas) = 'printer'")->fetchColumn();

// search & pagination with join to units
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$searchParam = null;

$fromSql = "FROM entities e LEFT JOIN units u ON e.unit_id = u.id WHERE 1";
if ($search !== '') {
    $fromSql .= " AND CONCAT(COALESCE(e.nama_entitas,''),' ',COALESCE(e.nama_pengguna,''),' ',COALESCE(e.serial_number,''),' ',COALESCE(e.brand,''),' ',COALESCE(u.nama_unit,'')) LIKE :search";
    $searchParam = '%' . $search . '%';
}

$countSql = "SELECT COUNT(*) " . $fromSql;
$stmt = $pdo->prepare($countSql);
if ($searchParam !== null) $stmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
$stmt->execute();
$totalRows = (int) $stmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$dataSql = "SELECT e.id, e.unit_id, e.nama_pengguna, e.nama_entitas, e.serial_number, e.tipe_entitas, e.brand, e.jumlah_ticket, e.created_at, u.unit_id AS unit_kode, u.nama_unit AS unit_nama " . $fromSql . " ORDER BY e.created_at ASC LIMIT :limit OFFSET :offset";
$dataStmt = $pdo->prepare($dataSql);
if ($searchParam !== null) $dataStmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$entities = $dataStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Entities - Engineer Panel</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/png" href="../assets/img/NIP.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUpSoft {0%{opacity:0;transform:translateY(18px);filter:blur(2px)}100%{opacity:1;transform:translateY(0);filter:blur(0)}}
        .fade-in-soft{animation:fadeInUpSoft .7s cubic-bezier(.22,.61,.36,1) forwards}
        .fade-in-soft-delayed{animation:fadeInUpSoft .9s cubic-bezier(.22,.61,.36,1) .1s forwards}
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
                    <h1 class="text-xl md:text-2xl font-semibold">Entities</h1>
                    <p class="text-xs md:text-sm text-slate-500">Read-only: daftar entitas/perangkat yang terhubung ke unit.</p>
                </div>
            </div>

            <!-- stats cards (simple) -->
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 fade-in-soft">
                <div class="rounded-3xl bg-gradient-to-br from-sky-500 to-cyan-400 text-white p-4 shadow">
                    <div class="flex items-start justify-between">
                        <div><p class="text-xs uppercase">Total Entities</p><p class="mt-1 text-2xl font-semibold"><?= $totalEntities; ?></p></div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                                <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                                <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                                <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="rounded-3xl bg-gradient-to-br from-rose-400 to-amber-300 text-white p-4 shadow">
                    <div class="flex items-start justify-between">
                        <div><p class="text-xs uppercase">Total Tickets</p><p class="mt-1 text-2xl font-semibold"><?= $totalTickets; ?></p></div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <rect x="4" y="4" width="16" height="16" rx="3"/>
                                <path d="M8 10h8M8 14h5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- device type cards -->
                <div class="rounded-3xl bg-gradient-to-br from-indigo-500 to-blue-400 text-white p-4 shadow">
                    <div class="flex items-start justify-between">
                        <div><p class="text-xs uppercase">Total PC</p><p class="mt-1 text-2xl font-semibold"><?= $totalPc; ?></p></div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <rect x="2" y="4" width="20" height="12" rx="2"/>
                                <path d="M8 20h8M12 16v4" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-cyan-400 to-emerald-400 text-white p-4 shadow">
                    <div class="flex items-start justify-between">
                        <div><p class="text-xs uppercase">Total Monitor</p><p class="mt-1 text-2xl font-semibold"><?= $totalMonitor; ?></p></div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="4" width="18" height="13" rx="2"/>
                                <path d="M9 21h6" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-fuchsia-500 to-pink-400 text-white p-4 shadow">
                    <div class="flex items-start justify-between">
                        <div><p class="text-xs uppercase">Total Laptop</p><p class="mt-1 text-2xl font-semibold"><?= $totalLaptop; ?></p></div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <rect x="4" y="6" width="16" height="10" rx="2"/>
                                <path d="M2 18h20" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-lime-400 to-teal-400 text-white p-4 shadow">
                    <div class="flex items-start justify-between">
                        <div><p class="text-xs uppercase">Total Printer</p><p class="mt-1 text-2xl font-semibold"><?= $totalPrinter; ?></p></div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <rect x="6" y="3" width="12" height="5" rx="1.5"/>
                                <rect x="4" y="8" width="16" height="10" rx="2"/>
                                <path d="M8 13h8" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-3xl shadow-lg border border-slate-200 p-4 md:p-5 space-y-4 fade-in-soft">
                <form method="get" class="flex items-center gap-3">
                    <div class="relative flex-1 md:w-96">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="1.6"/><path d="M16 16L20 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </span>
                        <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari nama entitas, pengguna, serial, brand, atau unit..." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-9 py-2.5 text-sm outline-none">
                    </div>
                    <button class="px-3 py-2 rounded-2xl bg-indigo-500 text-white text-sm font-semibold">Cari</button>
                    <a href="entities.php" class="px-3 py-2 rounded-2xl border bg-white text-sm">Reset</a>
                </form>

                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-sm md:text-base">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-slate-500 uppercase tracking-wide">
                                <th class="px-4 py-3.5">Unit</th>
                                <th class="px-4 py-3.5">Entitas</th>
                                <th class="px-4 py-3.5">Pengguna</th>
                                <th class="px-4 py-3.5 text-center">Tipe</th>
                                <th class="px-4 py-3.5 text-center">Brand / Serial</th>
                                <th class="px-4 py-3.5 text-center">Tickets</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($entities)): ?>
                                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada entities yang sesuai.</td></tr>
                            <?php else: ?>
                                <?php foreach ($entities as $row): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="px-4 py-3">
                                            <div class="font-semibold"><?= htmlspecialchars($row['unit_nama'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php if (!empty($row['unit_kode'])): ?>
                                                <div class="text-xs text-slate-600 mt-1"><?= htmlspecialchars($row['unit_kode'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($row['nama_entitas'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3">
                                            <?php if (!empty($row['nama_pengguna'])): ?>
                                                <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1 text-[11px]">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <circle cx="12" cy="8" r="4"/>
                                                        <path d="M6 20c0-3 2.5-6 6-6s6 3 6 6"/>
                                                    </svg>
                                                    <?= htmlspecialchars($row['nama_pengguna'], ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <?php if (!empty($row['tipe_entitas'])): ?>
                                                <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-[11px]"><?= htmlspecialchars(strtoupper($row['tipe_entitas']), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <?php if (!empty($row['brand'])): ?><div class="text-xs font-medium">Brand: <?= htmlspecialchars($row['brand'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                                            <?php if (!empty($row['serial_number'])): ?><div class="inline-flex items-center mt-1 px-2 py-1 bg-slate-50 rounded-full text-xs">SN: <?= htmlspecialchars($row['serial_number'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center gap-1.5 justify-center rounded-full 
                                                         bg-gradient-to-br from-emerald-50 to-emerald-100 
                                                         text-emerald-700 px-3 py-1 text-[11px] font-semibold 
                                                         border border-emerald-200 shadow-sm">
                                                
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <rect x="4" y="4" width="16" height="16" rx="3"/>
                                                    <path d="M8 10h8M8 14h5" stroke-linecap="round"/>
                                                </svg>

                                                <?= (int)$row['jumlah_ticket']; ?> tickets
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- pagination same as admin -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-between mt-3">
                        <div class="text-slate-500">Menampilkan <span class="font-semibold"><?= $totalRows > 0 ? ($offset+1) : 0; ?></span>–<span class="font-semibold"><?= min($offset+$perPage, $totalRows); ?></span> dari <span class="font-semibold"><?= $totalRows; ?></span></div>
                        <div class="flex items-center gap-1">
                            <?php $baseQuery = []; if ($search !== '') $baseQuery['q'] = $search; ?>
                            <?php if ($page > 1): $baseQuery['page'] = $page - 1; ?>
                                <a href="?<?= http_build_query($baseQuery); ?>" class="px-2 py-1 rounded border bg-white">Prev</a>
                            <?php endif; ?>
                            <?php $start = max(1,$page-2); $end = min($totalPages,$page+2); for ($p=$start;$p<=$end;$p++): $baseQuery['page']=$p; $active=$p===$page; ?>
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
</body>
</html>
