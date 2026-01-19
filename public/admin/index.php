<?php
// public/admin/index.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Simple auth check (bisa kamu kembangkan)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

// Helper hitung growth %
function calc_growth($current, $previous): int
{
    if ($previous <= 0) {
        return $current > 0 ? 100 : 0;
    }
    return (int) round((($current - $previous) / $previous) * 100);
}

// Ambil total tiket
function ticket_count(PDO $pdo, ?string $status = null): int
{
    if ($status === null) {
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM tickets");
        return (int) $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM tickets WHERE status = :status");
    $stmt->execute([':status' => $status]);
    return (int) $stmt->fetchColumn();
}

// Ambil total tiket per bulan (untuk growth)
function ticket_count_month(PDO $pdo, ?string $status, int $year, int $month): int
{
    $sql = "SELECT COUNT(*) AS total FROM tickets 
            WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month";
    $params = [
        ':year'  => $year,
        ':month' => $month,
    ];

    if ($status !== null) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

// Waktu sekarang & bulan lalu
$now = new DateTime();
$currYear = (int) $now->format('Y');
$currMonth = (int) $now->format('m');

$prev = (clone $now)->modify('-1 month');
$prevYear = (int) $prev->format('Y');
$prevMonth = (int) $prev->format('m');

// Total tiket (all time)
$totalAll      = ticket_count($pdo, null);
$totalOpen     = ticket_count($pdo, 'open');
$totalClosed   = ticket_count($pdo, 'closed');
$totalWaiting  = ticket_count($pdo, 'waiting');

// Total tiket bulan ini & bulan lalu (per status)
$currAll      = ticket_count_month($pdo, null,     $currYear, $currMonth);
$prevAll      = ticket_count_month($pdo, null,     $prevYear, $prevMonth);

$currOpen     = ticket_count_month($pdo, 'open',   $currYear, $currMonth);
$prevOpen     = ticket_count_month($pdo, 'open',   $prevYear, $prevMonth);

$currClosed   = ticket_count_month($pdo, 'closed', $currYear, $currMonth);
$prevClosed   = ticket_count_month($pdo, 'closed', $prevYear, $prevMonth);

$currWaiting  = ticket_count_month($pdo, 'waiting',$currYear, $currMonth);
$prevWaiting  = ticket_count_month($pdo, 'waiting',$prevYear, $prevMonth);

// Growth %
$growthAll     = calc_growth($currAll,    $prevAll);
$growthOpen    = calc_growth($currOpen,   $prevOpen);
$growthClosed  = calc_growth($currClosed, $prevClosed);
$growthWaiting = calc_growth($currWaiting,$prevWaiting);

// DAILY, WEEKLY, MONTHLY - ALL TICKETS
function ticket_daily(PDO $pdo): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function ticket_weekly(PDO $pdo): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tickets
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function ticket_monthly(PDO $pdo): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tickets
        WHERE YEAR(created_at) = YEAR(CURDATE())
          AND MONTH(created_at) = MONTH(CURDATE())
    ");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

$dailyTickets   = ticket_daily($pdo);
$weeklyTickets  = ticket_weekly($pdo);
$monthlyTickets = ticket_monthly($pdo);

// Count Yesterday, Last Week, Last Month
function ticket_yesterday(PDO $pdo): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tickets
        WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    ");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function ticket_last_week(PDO $pdo): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tickets
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
          AND created_at <  DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function ticket_last_month(PDO $pdo): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tickets
        WHERE YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
          AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
    ");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

$yesterdayTickets = ticket_yesterday($pdo);
$lastWeekTickets  = ticket_last_week($pdo);
$lastMonthTickets = ticket_last_month($pdo);

// Growth %
$growthDaily   = calc_growth($dailyTickets,   $yesterdayTickets);
$growthWeekly  = calc_growth($weeklyTickets,  $lastWeekTickets);
$growthMonthly = calc_growth($monthlyTickets, $lastMonthTickets);

// Data chart: tiket per status (bar)
$statusStmt = $pdo->query("
    SELECT status, COUNT(*) AS total 
    FROM tickets 
    GROUP BY status
");
$statusRows = $statusStmt->fetchAll();

$statusLabels = [];
$statusData   = [];
foreach ($statusRows as $row) {
    $statusLabels[] = ucfirst($row['status']);
    $statusData[]   = (int) $row['total'];
}

// Data chart: tiket per problem_type (pie)
$typeStmt = $pdo->query("
    SELECT problem_type, COUNT(*) AS total 
    FROM tickets 
    GROUP BY problem_type
");
$typeRows = $typeStmt->fetchAll();

$typeLabels = [];
$typeData   = [];
foreach ($typeRows as $row) {
    $typeLabels[] = ucfirst($row['problem_type']);
    $typeData[]   = (int) $row['total'];
}

// Data chart: total tiket per bulan (6 bulan terakhir, line)
$monthlyStmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym,
           DATE_FORMAT(created_at, '%b %Y') AS label,
           COUNT(*) AS total
    FROM tickets
    WHERE created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
    GROUP BY ym, label
    ORDER BY ym
");
$monthlyRows = $monthlyStmt->fetchAll();

$monthLabels = [];
$monthData   = [];
foreach ($monthlyRows as $row) {
    $monthLabels[] = $row['label'];
    $monthData[]   = (int) $row['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Helpdesk NIP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="../assets/img/NIP.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        @keyframes fadeInUpSoft {
            0% { opacity: 0; transform: translateY(18px); filter: blur(2px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .fade-in-soft {
            animation: fadeInUpSoft 0.7s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
        }
        .fade-in-soft-delayed {
            animation: fadeInUpSoft 0.9s cubic-bezier(0.22, 0.61, 0.36, 1) 0.1s forwards;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">

<div class="min-h-screen flex">

    <?php include __DIR__ . '/../admin/sidebar_admin.php'; ?>

    <div class="flex-1 flex flex-col">

        <?php include __DIR__ . '/../admin/header_admin.php'; ?>

        <!-- CONTENT -->
        <main class="flex-1 px-4 md:px-6 lg:px-8 py-6 space-y-6 fade-in-soft-delayed">
            <!-- Judul -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                    <h1 class="text-xl md:text-2xl font-semibold">
                        Dashboard Helpdesk
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500">
                        Ringkasan aktivitas tiket dan performa helpdesk Ninjas In Pyjamas.
                    </p>
                </div>
            </div>

            <!-- Cards -->
            <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-5 fade-in-soft">

            <!-- OPEN -->
            <div class="relative rounded-3xl shadow-xl overflow-hidden bg-gradient-to-br from-indigo-500 via-indigo-400 to-sky-400 text-white p-5">
                <!-- Glow Orbs -->
                <div class="absolute inset-0 opacity-[0.25] pointer-events-none">
                    <div class="absolute -top-10 -left-10 w-32 h-32 bg-white/20 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 right-0 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                </div>

                <!-- Top -->
                <div class="relative flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium opacity-90">Open Tickets</p>
                        <p class="mt-1 text-3xl font-bold"><?= $totalOpen; ?></p>
                    </div>

                    <div class="p-2 rounded-xl bg-white/20 backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white"
                             viewBox="0 0 24 24" fill="none">
                            <rect x="4" y="5" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M9 9H15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M9 13H12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

                <!-- Growth -->
                <p class="relative mt-2 text-xs">
                    <span class="<?= $growthOpen >= 0 ? 'text-emerald-100' : 'text-red-100'; ?>">
                        <?= $growthOpen >= 0 ? '↑' : '↓'; ?> <?= abs($growthOpen); ?>%
                    </span>
                    <span class="opacity-90"> dari bulan lalu</span>
                </p>

                <!-- Mini Chart -->
                <div class="relative mt-4 h-10">
                    <svg viewBox="0 0 120 40" class="w-full h-full text-white/85">
                        <path d="M0 28 C 22 26, 38 26, 55 20 C 78 14, 90 18, 120 12"
                              fill="none" stroke="currentColor" stroke-width="2"
                              stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <!-- CLOSED -->
            <div class="relative rounded-3xl shadow-xl overflow-hidden bg-gradient-to-br from-sky-400 via-cyan-400 to-teal-400 text-white p-5">
                <div class="absolute inset-0 opacity-[0.25] pointer-events-none">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/20 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                </div>

                <div class="relative flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium opacity-90">Closed Tickets</p>
                        <p class="mt-1 text-3xl font-bold"><?= $totalClosed; ?></p>
                    </div>

                    <div class="p-2 rounded-xl bg-white/20 backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white"
                             viewBox="0 0 24 24" fill="none">
                            <path d="M8 4H14L18 8V20H8V4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                            <path d="M14 4V8H18" stroke="currentColor" stroke-width="1.6"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>

                <p class="relative mt-2 text-xs">
                    <span class="<?= $growthClosed >= 0 ? 'text-emerald-100' : 'text-red-100'; ?>">
                        <?= $growthClosed >= 0 ? '↑' : '↓'; ?> <?= abs($growthClosed); ?>%
                    </span>
                    <span class="opacity-90"> resolved bulan lalu</span>
                </p>

                <div class="relative mt-4 h-10">
                    <svg viewBox="0 0 120 40" class="w-full h-full text-white/85">
                        <path d="M0 30 C 20 27, 38 24, 55 18 C 75 14, 90 16, 120 10"
                              fill="none" stroke="currentColor" stroke-width="2"
                              stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <!-- WAITING -->
            <div class="relative rounded-3xl shadow-xl overflow-hidden bg-gradient-to-br from-pink-400 via-rose-400 to-orange-400 text-white p-5">
                <div class="absolute inset-0 opacity-[0.25] pointer-events-none">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/20 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                </div>

                <div class="relative flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium opacity-90">Waiting Tickets</p>
                        <p class="mt-1 text-3xl font-bold"><?= $totalWaiting; ?></p>
                    </div>

                    <div class="p-2 rounded-xl bg-white/20 backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white"
                             viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M12 9V12L14 14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

                <p class="relative mt-2 text-xs">
                    <span class="<?= $growthWaiting >= 0 ? 'text-red-100' : 'text-emerald-100'; ?>">
                        <?= $growthWaiting >= 0 ? '↑' : '↓'; ?> <?= abs($growthWaiting); ?>%
                    </span>
                    <span class="opacity-90"> dibanding bulan lalu</span>
                </p>

                <div class="relative mt-4 h-10">
                    <svg viewBox="0 0 120 40" class="w-full h-full text-white/85">
                        <path d="M0 26 C 18 22, 34 24, 55 28 C 72 32, 88 30, 120 22"
                              fill="none" stroke="currentColor" stroke-width="2"
                              stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <!-- ALL -->
            <div class="relative rounded-3xl shadow-xl overflow-hidden bg-gradient-to-br from-emerald-400 via-teal-400 to-lime-400 text-white p-5">
                <div class="absolute inset-0 opacity-[0.25] pointer-events-none">
                    <div class="absolute -top-10 -left-10 w-32 h-32 bg-white/25 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 right-0 w-40 h-40 bg-white/15 rounded-full blur-2xl"></div>
                </div>

                <div class="relative flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium opacity-90">All Tickets</p>
                        <p class="mt-1 text-3xl font-bold"><?= $totalAll; ?></p>
                    </div>

                    <div class="p-2 rounded-xl bg-white/20 backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white"
                             viewBox="0 0 24 24" fill="none">
                            <circle cx="9" cy="9" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                            <circle cx="16" cy="10" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M4.5 18C5.2 16.5 6.8 15.5 8.5 15.5C10.2 15.5 11.8 16.5 12.5 18" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            <path d="M13.5 18C14.2 16.5 15.8 15.5 17.5 15.5C19.2 15.5 20.8 16.5 21.5 18" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

                <p class="relative mt-2 text-xs">
                    <span class="<?= $growthAll >= 0 ? 'text-emerald-100' : 'text-red-100'; ?>">
                        <?= $growthAll >= 0 ? '↑' : '↓'; ?> <?= abs($growthAll); ?>%
                    </span>
                    <span class="opacity-90"> total tiket bulan lalu</span>
                </p>

                <div class="relative mt-4 h-10">
                    <svg viewBox="0 0 120 40" class="w-full h-full text-white/85">
                        <path d="M0 28 C 20 26, 38 24, 55 20 C 75 16, 90 18, 120 10"
                              fill="none" stroke="currentColor" stroke-width="2"
                              stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

        </section>

        <!-- EXTRA CARDS: DAILY / WEEKLY / MONTHLY -->
        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-5 fade-in-soft">

        <!-- DAILY -->
        <div class="relative rounded-3xl shadow-xl overflow-hidden bg-gradient-to-br from-violet-500 via-purple-400 to-fuchsia-400 text-white p-5">
            <div class="absolute inset-0 opacity-[0.28] pointer-events-none">
                <div class="absolute -top-10 -left-10 w-32 h-32 bg-white/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            </div>

            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium opacity-90">Daily Tickets</p>
                    <p class="mt-1 text-3xl font-bold"><?= $dailyTickets; ?></p>
                </div>

                <div class="p-2 rounded-xl bg-white/25 backdrop-blur-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor">
                        <circle cx="12" cy="12" r="8" stroke-width="1.7"/>
                        <path d="M12 8v4l2.5 1.5" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <!-- GROWTH DAILY -->
            <p class="relative mt-2 text-xs">
                <span class="<?= $growthDaily >= 0 ? 'text-emerald-100' : 'text-red-100'; ?>">
                    <?= $growthDaily >= 0 ? '↑' : '↓'; ?> <?= abs($growthDaily); ?>%
                </span>
                <span class="opacity-90"> dibanding kemarin</span>
            </p>

            <div class="relative mt-4 h-10">
                <svg viewBox="0 0 120 40" class="w-full h-full text-white/80">
                    <path d="M0 30 C 20 25, 40 28, 60 18 C 80 12, 100 15, 120 10"
                          fill="none" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>

        <!-- WEEKLY -->
        <div class="relative rounded-3xl shadow-xl overflow-hidden bg-gradient-to-br from-blue-500 via-sky-400 to-cyan-400 text-white p-5">
            <div class="absolute inset-0 opacity-[0.28] pointer-events-none">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/25 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            </div>

            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium opacity-90">Weekly Tickets</p>
                    <p class="mt-1 text-3xl font-bold"><?= $weeklyTickets; ?></p>
                </div>

                <div class="p-2 rounded-xl bg-white/25 backdrop-blur-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor">
                        <path d="M3 12h18M12 3v18" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <!-- GROWTH WEEKLY -->
            <p class="relative mt-2 text-xs">
                <span class="<?= $growthWeekly >= 0 ? 'text-emerald-100' : 'text-red-100'; ?>">
                    <?= $growthWeekly >= 0 ? '↑' : '↓'; ?> <?= abs($growthWeekly); ?>%
                </span>
                <span class="opacity-90"> dibanding minggu lalu</span>
            </p>

            <div class="relative mt-4 h-10">
                <svg viewBox="0 0 120 40" class="w-full h-full text-white/80">
                    <path d="M0 28 C 18 24, 38 26, 60 22 C 78 18, 98 20, 120 16"
                          fill="none" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>

        <!-- MONTHLY -->
        <div class="relative rounded-3xl shadow-xl overflow-hidden bg-gradient-to-br from-emerald-500 via-green-400 to-lime-400 text-white p-5">
            <div class="absolute inset-0 opacity-[0.28] pointer-events-none">
                <div class="absolute -top-10 -left-10 w-32 h-32 bg-white/25 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            </div>

            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium opacity-90">Monthly Tickets</p>
                    <p class="mt-1 text-3xl font-bold"><?= $monthlyTickets; ?></p>
                </div>

                <div class="p-2 rounded-xl bg-white/25 backdrop-blur-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor">
                        <rect x="4" y="4" width="16" height="16" rx="3" stroke-width="1.7"/>
                        <path d="M4 10h16" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <!-- GROWTH MONTHLY -->
            <p class="relative mt-2 text-xs">
                <span class="<?= $growthMonthly >= 0 ? 'text-emerald-100' : 'text-red-100'; ?>">
                    <?= $growthMonthly >= 0 ? '↑' : '↓'; ?> <?= abs($growthMonthly); ?>%
                </span>
                <span class="opacity-90"> dibanding bulan lalu</span>
            </p>

            <div class="relative mt-4 h-10">
                <svg viewBox="0 0 120 40" class="w-full h-full text-white/80">
                    <path d="M0 32 C 25 26, 42 22, 60 20 C 78 18, 100 16, 120 12"
                          fill="none" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>

    </section>

            <!-- Charts -->
            <section class="grid grid-cols-1 xl:grid-cols-3 gap-4 md:gap-5">
                <!-- Line chart -->
                <div class="bg-white rounded-3xl shadow-lg border border-slate-200 p-4 md:p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h2 class="text-sm md:text-base font-semibold">Trend Tiket 6 Bulan Terakhir</h2>
                            <p class="text-[11px] md:text-xs text-slate-500">Total tiket yang dibuat per bulan</p>
                        </div>
                    </div>
                    <div class="h-56">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>

                <!-- Bar chart -->
                <div class="bg-white rounded-3xl shadow-lg border border-slate-200 p-4 md:p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h2 class="text-sm md:text-base font-semibold">Tiket per Status</h2>
                            <p class="text-[11px] md:text-xs text-slate-500">Distribusi status tiket saat ini</p>
                        </div>
                    </div>
                    <div class="h-56">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>

                <!-- Pie chart -->
                <div class="bg-white rounded-3xl shadow-lg border border-slate-200 p-4 md:p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h2 class="text-sm md:text-base font-semibold">Tiket per Jenis Masalah</h2>
                            <p class="text-[11px] md:text-xs text-slate-500">Software, hardware, internet, dsb</p>
                        </div>
                    </div>
                    <div class="h-56">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
            </section>
        </main>

        <?php include __DIR__ . '/../admin/footer_admin.php'; ?>
    </div>
</div>

<script>
    const monthLabels = <?= json_encode($monthLabels, JSON_UNESCAPED_UNICODE); ?>;
    const monthData   = <?= json_encode($monthData, JSON_UNESCAPED_UNICODE); ?>;
    const statusLabels = <?= json_encode($statusLabels, JSON_UNESCAPED_UNICODE); ?>;
    const statusData   = <?= json_encode($statusData, JSON_UNESCAPED_UNICODE); ?>;
    const typeLabels   = <?= json_encode($typeLabels, JSON_UNESCAPED_UNICODE); ?>;
    const typeData     = <?= json_encode($typeData, JSON_UNESCAPED_UNICODE); ?>;

    // Line Chart
    const lineCtx = document.getElementById('lineChart');
    if (lineCtx) {
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Total Tickets',
                    data: monthData,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 3,
                    borderColor: 'rgba(37, 99, 235, 1)',
                    backgroundColor: 'rgba(129, 140, 248, 0.2)',
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' } }
                }
            }
        });
    }

    // Bar Chart
    const barCtx = document.getElementById('barChart');
    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    borderWidth: 1,
                    backgroundColor: [
                        'rgba(79, 70, 229, 0.9)',
                        'rgba(56, 189, 248, 0.9)',
                        'rgba(244, 114, 182, 0.9)',
                        'rgba(52, 211, 153, 0.9)',
                        'rgba(251, 191, 36, 0.9)',
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' } }
                }
            }
        });
    }

    // Pie Chart
    const pieCtx = document.getElementById('pieChart');
    if (pieCtx) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeData,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.9)',
                        'rgba(249, 115, 22, 0.9)',
                        'rgba(16, 185, 129, 0.9)',
                        'rgba(244, 63, 94, 0.9)',
                        'rgba(234, 179, 8, 0.9)',
                    ],
                    borderWidth: 1,
                    borderColor: 'white'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 14 } }
                },
                cutout: '60%'
            }
        });
    }
</script>

</body>
</html>
