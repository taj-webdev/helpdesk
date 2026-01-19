<?php
// public/engineer/units.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Auth: hanya engineer / project / admin helper
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['engineer','project'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

// search & pagination
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$params = [];

$sqlBase = "FROM units WHERE 1=1";

// search
if ($search !== '') {
    $sqlBase .= " AND CONCAT(
        COALESCE(unit_id,''),' ',
        COALESCE(nama_unit,''),' ',
        COALESCE(kab_kota,''),' ',
        COALESCE(provinsi,'')
    ) LIKE :search";
    $params[':search'] = "%$search%";
}

// count rows
$stmtCount = $pdo->prepare("SELECT COUNT(*) " . $sqlBase);
foreach ($params as $k => $v) {
    $stmtCount->bindValue($k, $v);
}
$stmtCount->execute();
$totalRows = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

// fetch rows
$sqlData = "SELECT * " . $sqlBase . " ORDER BY created_at ASC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sqlData);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$units = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Units - Engineer Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="../assets/img/NIP.png">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @keyframes fadeInSoft {
            from { opacity:0; transform:translateY(10px); filter:blur(1px); }
            to   { opacity:1; transform:translateY(0); filter:blur(0); }
        }
        .fade { animation: fadeInSoft .7s ease forwards; }
    </style>
</head>

<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
<div class="min-h-screen flex">

    <?php include __DIR__ . '/sidebar_engineer.php'; ?>

    <div class="flex-1 flex flex-col">
        <?php include __DIR__ . '/header_engineer.php'; ?>

        <main class="flex-1 px-4 md:px-6 lg:px-8 py-6 space-y-6 fade">

            <!-- TITLE -->
            <div>
                <h1 class="text-xl md:text-2xl font-semibold">Units</h1>
                <p class="text-xs md:text-sm text-slate-500">
                    Daftar unit (read only) berikut entitas terkait.
                </p>
            </div>

            <!-- CARD DATA -->
            <?php
            $totalUnits = (int)$pdo->query("SELECT COUNT(*) FROM units")->fetchColumn();
            $totalEntities = (int)$pdo->query("SELECT COUNT(*) FROM entities")->fetchColumn();
            ?>

            <section class="grid grid-cols-1 md:grid-cols-2 gap-4 fade">
    
                <!-- TOTAL UNITS -->
                <div class="rounded-3xl p-5 bg-gradient-to-br from-sky-500 to-indigo-500 text-white shadow-xl flex items-center justify-between">
                    <div>
                        <p class="text-xs tracking-widest uppercase">Total Units</p>
                        <p class="text-3xl mt-1 font-semibold"><?= $totalUnits ?></p>
                    </div>
                    <div class="w-12 h-12 flex items-center justify-center rounded-2xl bg-white/20">
                        <!-- Icon Unit -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M3 10H21" stroke="currentColor" stroke-width="1.6"/>
                        </svg>
                    </div>
                </div>

                <!-- TOTAL ENTITIES -->
                <div class="rounded-3xl p-5 bg-gradient-to-br from-emerald-400 to-teal-400 text-white shadow-xl flex items-center justify-between">
                    <div>
                        <p class="text-xs tracking-widest uppercase">Total Entities</p>
                        <p class="text-3xl mt-1 font-semibold"><?= $totalEntities ?></p>
                    </div>
                <div class="w-12 h-12 flex items-center justify-center rounded-2xl bg-white/20">
                        <!-- Icon Entities -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

            </section>


            <!-- Search -->
            <section class="bg-white rounded-3xl shadow-lg border border-slate-200 p-4 md:p-5 space-y-4 fade">
                <form method="get" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="relative w-full md:w-96">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                 viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="6"
                                        stroke="currentColor" stroke-width="1.6"/>
                                <path d="M16 16L20 20"
                                      stroke="currentColor" stroke-width="1.6"
                                      stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input type="text" name="q"
                               value="<?= htmlspecialchars($search) ?>"
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-9 py-2.5 text-sm outline-none"
                               placeholder="Cari unit, kab/kota, provinsi...">
                    </div>

                    <div class="flex gap-2">
                        <button class="px-4 py-2 rounded-2xl bg-indigo-500 text-white text-sm font-semibold">
                            Cari
                        </button>
                        <a href="units.php"
                           class="px-4 py-2 rounded-2xl bg-white border text-sm">
                            Reset
                        </a>
                    </div>
                </form>

                <!-- TABLE -->
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-sm md:text-base">
                        <thead class="bg-slate-50">
                        <tr class="text-left text-slate-500 uppercase tracking-wide">
                            <th class="px-4 py-3.5 text-center">Unit ID</th>
                            <th class="px-4 py-3.5">Nama Unit</th>
                            <th class="px-4 py-3.5">Lokasi</th>
                            <th class="px-4 py-3.5 text-center">TAT Target</th>
                            <th class="px-4 py-3.5 text-center">Entities</th>
                        </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                        <?php if (empty($units)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                                    Tidak ada data unit.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($units as $u): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <!-- UNIT ID -->
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">
                                            <?= htmlspecialchars($u['unit_id']) ?>
                                        </span>
                                    </td>

                                    <!-- NAMA -->
                                    <td class="px-4 py-3 font-semibold">
                                        <?= htmlspecialchars($u['nama_unit']) ?>
                                    </td>

                                    <!-- LOKASI -->
                                    <td class="px-4 py-3">
                                        <div><?= htmlspecialchars($u['alamat']) ?></div>
                                        <div class="flex gap-2 mt-1">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2 py-1 text-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                                     viewBox="0 0 24 24">
                                                    <path d="M12 2C8 2 5 5 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-4-3-7-7-7z"
                                                          stroke="currentColor" stroke-width="1.6"/>
                                                </svg>
                                                <?= htmlspecialchars($u['kab_kota'] ?? '-') ?>
                                            </span>

                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2 py-1 text-xs">
                                                <?= htmlspecialchars($u['provinsi'] ?? '-') ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- TAT -->
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full 
                                                     bg-gradient-to-br from-orange-50 to-orange-100 
                                                     text-orange-700 text-xs font-semibold shadow-sm border border-orange-200">
                                            
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/>
                                                <path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                            </svg>

                                            <?= $u['tat_target'] ?> jam
                                        </span>
                                    </td>

                                    <!-- ENTITIES -->
                                    <?php
                                    $count = $pdo->prepare("SELECT COUNT(*) FROM entities WHERE unit_id = ?");
                                    $count->execute([$u['id']]);
                                    $entityCount = $count->fetchColumn();
                                    ?>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full 
                                                     bg-gradient-to-br from-emerald-50 to-emerald-100 
                                                     text-emerald-700 text-xs font-semibold shadow-sm border border-emerald-200">
                                            
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/>
                                                <path d="M12 8v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                                <path d="M12 12l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                            </svg>

                                            <?= $entityCount ?> entities
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-between items-center mt-4">
                        <div class="text-slate-500 text-sm">
                            Halaman <b><?= $page ?></b> dari <b><?= $totalPages ?></b>
                        </div>

                        <div class="flex gap-1">
                            <?php
                            $query = [];
                            if ($search !== '') $query['q'] = $search;
                            ?>

                            <?php if ($page > 1):
                                $query['page'] = $page - 1;
                                ?>
                                <a href="?<?= http_build_query($query) ?>"
                                   class="px-3 py-1 bg-white border rounded">Prev</a>
                            <?php endif; ?>

                            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++):
                                $query['page'] = $p;
                                ?>
                                <a href="?<?= http_build_query($query) ?>"
                                   class="px-3 py-1 rounded <?= $p == $page ? 'bg-indigo-500 text-white' : 'bg-white border' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages):
                                $query['page'] = $page + 1;
                                ?>
                                <a href="?<?= http_build_query($query) ?>"
                                   class="px-3 py-1 bg-white border rounded">Next</a>
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
