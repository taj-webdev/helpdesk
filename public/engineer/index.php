<?php
// public/engineer/index.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// akses hanya untuk engineer
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'engineer') {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

// Logo lokal (developer: file upload path; server mapping may be adjusted)
$logoLocalPath = '/mnt/data/c0a94293-66a6-4e4c-8400-001b40460d81.png';

// --- Card counts ---
$totalTickets = (int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$totalOpen = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
$totalWaiting = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'waiting'")->fetchColumn();
$totalClosed = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'closed'")->fetchColumn();
$totalCancelled = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'cancelled'")->fetchColumn();

// tickets by entity type (join)
$totalPc = (int)$pdo->query("
    SELECT COUNT(*) FROM tickets t JOIN entities e ON t.entity_id = e.id WHERE LOWER(e.tipe_entitas) = 'pc'
")->fetchColumn();
$totalMonitor = (int)$pdo->query("
    SELECT COUNT(*) FROM tickets t JOIN entities e ON t.entity_id = e.id WHERE LOWER(e.tipe_entitas) = 'monitor'
")->fetchColumn();
$totalLaptop = (int)$pdo->query("
    SELECT COUNT(*) FROM tickets t JOIN entities e ON t.entity_id = e.id WHERE LOWER(e.tipe_entitas) = 'laptop'
")->fetchColumn();
$totalPrinter = (int)$pdo->query("
    SELECT COUNT(*) FROM tickets t JOIN entities e ON t.entity_id = e.id WHERE LOWER(e.tipe_entitas) = 'printer'
")->fetchColumn();

// --- Monthly trend (last 6 months) for line chart ---
$months = [];
$labels = [];
$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
for ($i = 5; $i >= 0; $i--) {
    $dt = $now->modify("-{$i} months");
    $months[] = $dt->format('Y-m'); // e.g. 2025-06
    $labels[] = $dt->format('M Y'); // e.g. Jun 2025
}
$placeholders = implode(',', array_fill(0, count($months), '?'));
$sql = "
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM tickets
    WHERE DATE_FORMAT(created_at, '%Y-%m') IN ($placeholders)
    GROUP BY ym
";
$stmt = $pdo->prepare($sql);
$stmt->execute($months);
$rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ym => cnt

$lineData = [];
foreach ($months as $m) {
    $lineData[] = isset($rows[$m]) ? (int)$rows[$m] : 0;
}

// --- Tickets by type (pie) ---
$typeStmt = $pdo->prepare("
    SELECT e.tipe_entitas, COUNT(*) AS cnt
    FROM tickets t
    JOIN entities e ON t.entity_id = e.id
    GROUP BY LOWER(e.tipe_entitas)
");
$typeStmt->execute();
$typeRows = $typeStmt->fetchAll();

// build pie arrays
$pieLabels = [];
$pieData = [];
foreach ($typeRows as $r) {
    $label = $r['tipe_entitas'] ?: 'Unknown';
    $pieLabels[] = $label;
    $pieData[] = (int)$r['cnt'];
}

// --- Bar chart: tickets by status (open, waiting, confirmed, closed, cancelled) ---
$statusMap = ['open','waiting','confirmed','closed','cancelled'];
$statusCounts = [];
foreach ($statusMap as $s) {
    $statusCounts[] = (int)$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status = :s")->execute([':s' => $s]) ?: 0;
}
// Note: the above execute() returns boolean. Fix by doing proper prepare+fetch:
$statusCounts = [];
$ps = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status = :s");
foreach ($statusMap as $s) {
    $ps->execute([':s' => $s]);
    $statusCounts[] = (int)$ps->fetchColumn();
}

// JSON encode datasets for Chart.js
$chartLabelsLine = json_encode($labels);
$chartDataLine = json_encode($lineData);
$chartLabelsPie = json_encode($pieLabels);
$chartDataPie = json_encode($pieData);
$chartLabelsBar = json_encode(array_map('ucfirst', $statusMap));
$chartDataBar = json_encode($statusCounts);

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Dashboard - Engineer Panel</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/png" href="../assets/img/NIP.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        @keyframes fadeInUpSoft {
            0% { opacity: 0; transform: translateY(18px); filter: blur(2px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .fade-in-soft { animation: fadeInUpSoft 0.8s cubic-bezier(.22,.61,.36,1) forwards; }
        .fade-in-soft-delayed { animation: fadeInUpSoft 1s cubic-bezier(.22,.61,.36,1) 0.12s forwards; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">

<div class="min-h-screen flex">
    <?php include __DIR__ . '/sidebar_engineer.php'; ?>

    <div class="flex-1 flex flex-col">
        <?php include __DIR__ . '/header_engineer.php'; ?>

        <main class="flex-1 px-4 md:px-6 lg:px-8 py-6 space-y-6 fade-in-soft-delayed">
            <!-- Title -->
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-semibold">Dashboard Engineer</h1>
                    <p class="text-xs md:text-sm text-slate-500">Ringkasan cepat tugas on-site & tiket Anda.</p>
                </div>
            </div>

            <!-- Top cards: ticket counts -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 fade-in-soft">
                <div class="rounded-3xl bg-gradient-to-br from-indigo-600 to-sky-500 text-white p-4 shadow-lg">
                    <p class="text-xs font-medium uppercase tracking-wider">Total Tickets</p>
                    <p class="text-2xl mt-2 font-semibold"><?= $totalTickets; ?></p>
                    <p class="text-xs mt-2 opacity-80">Semua tiket</p>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-amber-400 to-amber-300 text-slate-900 p-4 shadow-lg">
                    <p class="text-xs font-medium uppercase tracking-wider">Open</p>
                    <p class="text-2xl mt-2 font-semibold"><?= $totalOpen; ?></p>
                    <p class="text-xs mt-2 opacity-80">Perlu tindakan</p>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-rose-400 to-pink-400 text-white p-4 shadow-lg">
                    <p class="text-xs font-medium uppercase tracking-wider">Waiting</p>
                    <p class="text-2xl mt-2 font-semibold"><?= $totalWaiting; ?></p>
                    <p class="text-xs mt-2 opacity-80">Menunggu respon</p>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-emerald-400 to-teal-400 text-white p-4 shadow-lg">
                    <p class="text-xs font-medium uppercase tracking-wider">Closed</p>
                    <p class="text-2xl mt-2 font-semibold"><?= $totalClosed; ?></p>
                    <p class="text-xs mt-2 opacity-80">Selesai</p>
                </div>
            </section>

            <!-- Secondary cards: entity type tickets -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 fade-in-soft">
                <div class="rounded-3xl bg-gradient-to-br from-indigo-500 to-blue-400 text-white p-4 shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs uppercase">Tickets - PC</p>
                            <p class="text-2xl font-semibold"><?= $totalPc; ?></p>
                        </div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <!-- icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 20H16" stroke="currentColor" stroke-width="1.6"/></svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-cyan-400 to-emerald-400 text-white p-4 shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs uppercase">Tickets - Monitor</p>
                            <p class="text-2xl font-semibold"><?= $totalMonitor; ?></p>
                        </div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M10 19H14" stroke="currentColor" stroke-width="1.6"/></svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-fuchsia-500 to-pink-400 text-white p-4 shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs uppercase">Tickets - Laptop</p>
                            <p class="text-2xl font-semibold"><?= $totalLaptop; ?></p>
                        </div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"><rect x="4" y="6" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M2 18H22" stroke="currentColor" stroke-width="1.6"/></svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-lime-400 to-teal-400 text-white p-4 shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs uppercase">Tickets - Printer</p>
                            <p class="text-2xl font-semibold"><?= $totalPrinter; ?></p>
                        </div>
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"><rect x="6" y="3" width="12" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="4" y="9" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/></svg>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Charts: Line (monthly), Bar (status), Pie (type) -->
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 fade-in-soft">
                <div class="bg-white rounded-3xl p-4 shadow col-span-2">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold">Trend Tickets (6 bulan)</h3>
                        <p class="text-xs text-slate-500">Per bulan</p>
                    </div>
                    <canvas id="lineChart" height="120"></canvas>
                </div>

                <div class="bg-white rounded-3xl p-4 shadow">
                    <h3 class="text-sm font-semibold mb-3">Distribusi Tipe Entitas</h3>
                    <canvas id="pieChart" height="220"></canvas>
                </div>

                <div class="bg-white rounded-3xl p-4 shadow lg:col-span-3">
                    <h3 class="text-sm font-semibold mb-3">Tickets berdasarkan Status</h3>
                    <canvas id="barChart" height="80"></canvas>
                </div>
            </section>

        </main>

        <?php include __DIR__ . '/footer_engineer.php'; ?>
    </div>
</div>

<script>
    // Chart datasets from PHP
    const lineLabels = <?= $chartLabelsLine; ?>;
    const lineData = <?= $chartDataLine; ?>;
    const pieLabels = <?= $chartLabelsPie; ?>;
    const pieData = <?= $chartDataPie; ?>;
    const barLabels = <?= $chartLabelsBar; ?>;
    const barData = <?= $chartDataBar; ?>;

    // Line chart
    const ctxLine = document.getElementById('lineChart').getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: lineLabels,
            datasets: [{
                label: 'Tickets',
                data: lineData,
                tension: 0.35,
                fill: true,
                backgroundColor: 'rgba(59,130,246,0.08)',
                borderColor: 'rgba(59,130,246,0.95)',
                pointBackgroundColor: 'white',
                pointBorderColor: 'rgba(59,130,246,0.95)',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision:0 } }
            }
        }
    });

    // Pie chart
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: ['#6366F1','#06B6D4','#EC4899','#84CC16','#F97316','#F43F5E']
            }]
        },
        options: { responsive: true }
    });

    // Bar chart (status)
    const ctxBar = document.getElementById('barChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: barLabels,
            datasets: [{
                label: 'Jumlah',
                data: barData,
                backgroundColor: ['#6366F1','#F59E0B','#38BDF8','#10B981','#F43F5E']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision:0 } }
            }
        }
    });
</script>

</body>
</html>
