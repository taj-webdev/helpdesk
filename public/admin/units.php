<?php
// public/admin/units.php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Simple auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();

// --- SweetAlert (notif dari redirect) ---
$swalStatus  = $_GET['status']  ?? null; // success | error | info
$swalMessage = $_GET['message'] ?? null;

// --- Card Data ---
$totalUnits = (int) $pdo->query("SELECT COUNT(*) FROM units")->fetchColumn();
$totalEntities = (int) $pdo->query("SELECT COALESCE(SUM(entity_count), 0) FROM units")->fetchColumn();

// --- Search & Pagination ---
$search   = trim($_GET['q'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;

// base FROM
$fromSql = "FROM units WHERE 1";
$searchParam = null;

// kalau ada keyword pencarian
if ($search !== '') {
    $fromSql .= " AND CONCAT(
        COALESCE(unit_id, ''),
        ' ',
        COALESCE(nama_unit, ''),
        ' ',
        COALESCE(kab_kota, ''),
        ' ',
        COALESCE(provinsi, '')
    ) LIKE :search";
    $searchParam = '%' . $search . '%';
}

/** COUNT DATA UNTUK PAGINATION **/
$countSql = "SELECT COUNT(*) " . $fromSql;
$countStmt = $pdo->prepare($countSql);

if ($searchParam !== null) {
    $countStmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
}

$countStmt->execute();
$totalRows = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

/** AMBIL DATA UNITS **/
$dataSql = "
    SELECT
        id,
        unit_id,
        nama_unit,
        alamat,
        kab_kota,
        provinsi,
        tat_target,
        entity_count,
        created_at
    " . $fromSql . "
    ORDER BY created_at ASC
    LIMIT :limit OFFSET :offset
";

$dataStmt = $pdo->prepare($dataSql);

// bind search kalau ada
if ($searchParam !== null) {
    $dataStmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
}

$dataStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$dataStmt->execute();

$units = $dataStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Units - Helpdesk NIP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="../assets/img/NIP.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <div class="flex-1 flex flex-col">

        <?php include __DIR__ . '/header_admin.php'; ?>

        <!-- CONTENT -->
        <main class="flex-1 px-4 md:px-6 lg:px-8 py-6 space-y-6 fade-in-soft-delayed">
            <!-- Header title + actions -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-semibold">Units</h1>
                    <p class="text-xs md:text-sm text-slate-500">
                        Manajemen unit dan jumlah entitas pada sistem helpdesk Ninjas In Pyjamas.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- tombol tambah unit -->
                    <a href="units_create.php"
                       class="inline-flex items-center gap-2 rounded-2xl bg-emerald-500 px-4 py-2 text-xs md:text-sm
                              font-semibold text-white shadow-lg hover:bg-emerald-400 hover:shadow-xl
                              hover:-translate-y-[1px] active:translate-y-0 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-5 md:h-5" viewBox="0 0 24 24" fill="none">
                            <path d="M12 5V19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M5 12H19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                        <span>Tambah Unit</span>
                    </a>
                </div>
            </div>

            <!-- Cards -->
            <section class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                <!-- Total Units -->
                <div class="relative rounded-3xl bg-gradient-to-br from-indigo-500 via-indigo-400 to-sky-400 text-white shadow-xl overflow-hidden">
                    <div class="absolute inset-0 opacity-40 pointer-events-none">
                        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-xl"></div>
                        <div class="absolute -top-10 -right-10 w-44 h-44 bg-white/10 rounded-full blur-xl"></div>
                    </div>
                    <div class="relative p-4 md:p-5 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-medium text-indigo-50/80">Total Units</p>
                                <p class="mt-1 text-2xl md:text-3xl font-semibold"><?= $totalUnits; ?></p>
                            </div>
                            <div class="inline-flex items-center justify-center w-9 h-9 rounded-2xl bg-white/15">
                                <!-- icon building -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                    <rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M9 9H11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    <path d="M9 13H11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    <path d="M13 9H15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    <path d="M13 13H15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-[11px] md:text-xs text-indigo-50/90">
                            Ringkasan total semua unit yang terdaftar dalam sistem.
                        </p>
                        <div class="mt-2 h-10">
                            <svg viewBox="0 0 100 40" class="w-full h-full text-indigo-100/90">
                                <path d="M0 30 C 20 22, 40 26, 60 20 C 80 14, 90 16, 100 10"
                                      fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Entities -->
                <div class="relative rounded-3xl bg-gradient-to-br from-emerald-400 via-teal-400 to-lime-400 text-white shadow-xl overflow-hidden">
                    <div class="absolute inset-0 opacity-40 pointer-events-none">
                        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-xl"></div>
                        <div class="absolute -top-10 -left-10 w-44 h-44 bg-white/10 rounded-full blur-xl"></div>
                    </div>
                    <div class="relative p-4 md:p-5 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-medium text-emerald-50/80">Total Entities</p>
                                <p class="mt-1 text-2xl md:text-3xl font-semibold"><?= $totalEntities; ?></p>
                            </div>
                            <div class="inline-flex items-center justify-center w-9 h-9 rounded-2xl bg-white/15">
                                <!-- icon devices -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="7" width="13" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M9 17V19H6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    <rect x="16" y="9" width="5" height="8" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-[11px] md:text-xs text-emerald-50/90">
                            Total seluruh entitas yang terhubung ke unit, di-maintain otomatis oleh trigger.
                        </p>
                        <div class="mt-2 h-10">
                            <svg viewBox="0 0 100 40" class="w-full h-full text-emerald-100/90">
                                <path d="M0 28 C 25 22, 35 24, 55 16 C 75 10, 88 14, 100 8"
                                      fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Search + Table -->
            <section class="bg-white rounded-3xl shadow-lg border border-slate-200 p-4 md:p-5 space-y-4 fade-in-soft">
                <!-- search bar -->
                <form method="get" class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-center gap-2 w-full md:w-80">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                    <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M16 16L20 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input
                                type="text"
                                name="q"
                                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-9 py-2.5 text-xs md:text-sm
                                       outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                       placeholder:text-slate-400"
                                placeholder="Cari nama unit, kab/kota atau provinsi..."
                            >
                        </div>
                        <?php if ($search !== ''): ?>
                            <a href="units.php" class="text-[11px] text-slate-500 hover:text-slate-700">
                                Reset
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-2 text-[11px] text-slate-500">
                        <span>
                            Menampilkan
                            <span class="font-semibold">
                                <?= $totalRows > 0 ? ($offset + 1) : 0; ?>–<?= min($offset + $perPage, $totalRows); ?>
                            </span>
                            dari <span class="font-semibold"><?= $totalRows; ?></span> data
                        </span>
                    </div>
                </form>

                <!-- table -->
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-xs md:text-sm">
                        <thead class="bg-slate-50">
                        <tr class="text-left text-xs md:text-sm text-slate-500 uppercase tracking-wide">
                            <th class="px-4 py-3.5 text-center">Unit ID</th>
                            <th class="px-4 py-3.5 text-center">Nama Unit</th>
                            <th class="px-4 py-3.5 text-center">Lokasi</th>
                            <th class="px-4 py-3.5 text-center">TAT Target</th>
                            <th class="px-4 py-3.5 text-center">Entities</th>
                            <th class="px-4 py-3.5 text-center">Aksi</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
<?php if (empty($units)): ?>
    <tr>
        <td colspan="6" class="px-4 py-6 text-center text-xs md:text-sm text-slate-500">
            Belum ada data unit yang tersedia.
        </td>
    </tr>
<?php else: ?>
    <?php foreach ($units as $row): ?>
        <tr class="hover:bg-slate-50/80 transition">
            <!-- UNIT ID -->
            <td class="px-4 py-3 align-top text-center">
                <?php if (!empty($row['unit_id'])): ?>
                    <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-xs md:text-sm text-slate-700 font-semibold">
                        <?= htmlspecialchars($row['unit_id'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php else: ?>
                    <span class="text-xs md:text-sm text-slate-400">-</span>
                <?php endif; ?>
            </td>

            <!-- NAMA UNIT -->
            <td class="px-4 py-3 align-top text-center">
                <div class="font-semibold text-slate-800 text-sm md:text-base">
                    <?= htmlspecialchars($row['nama_unit'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </td>

            <!-- LOKASI (alamat + chips kab/kota & provinsi, chips di tengah) -->
            <td class="px-4 py-3 align-top">
                <?php if (!empty($row['alamat'])): ?>
                    <div class="text-xs md:text-sm text-slate-600 mb-1 md:text-center">
                        <?= htmlspecialchars($row['alamat'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="mt-1 flex flex-wrap items-center justify-start md:justify-center gap-1">
                    <?php if (!empty($row['kab_kota'])): ?>
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] md:text-xs text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                <path d="M12 3C8.68629 3 6 5.68629 6 9C6 13.5 12 21 12 21C12 21 18 13.5 18 9C18 5.68629 15.3137 3 12 3Z"
                                      stroke="currentColor" stroke-width="1.4"/>
                                <circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                            </svg>
                            <?= htmlspecialchars($row['kab_kota'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($row['provinsi'])): ?>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] md:text-xs text-slate-600">
                            <?= htmlspecialchars($row['provinsi'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </td>

            <!-- TAT TARGET -->
            <td class="px-4 py-3 align-top text-center">
                <?php if ($row['tat_target'] !== null): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl 
                                bg-amber-100 text-amber-700 text-[11px] md:text-xs font-semibold 
                                shadow-sm hover:shadow-md transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M12 9V12L14 14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                        <?= (int) $row['tat_target']; ?> jam
                    </span>
                <?php else: ?>
                    <span class="text-xs md:text-sm text-slate-400">-</span>
                <?php endif; ?>
            </td>

            <!-- ENTITIES -->
            <td class="px-4 py-3 align-top text-center">
                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl 
                            bg-emerald-100 text-emerald-700 text-[11px] md:text-xs font-semibold
                            hover:bg-emerald-200 shadow-sm hover:shadow-md transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <rect x="4" y="7" width="7" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
                        <rect x="13" y="7" width="7" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
                    </svg>
                    <?= (int) $row['entity_count']; ?> entities
                </button>
            </td>

            <!-- AKSI -->
            <td class="px-4 py-3 align-top text-center">
                <div class="inline-flex items-center gap-2">

                    <!-- EDIT BUTTON -->
                    <a href="units_edit.php?id=<?= (int)$row['id']; ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl 
                              bg-sky-100 text-sky-700 text-[11px] md:text-xs font-semibold
                              hover:bg-sky-200 shadow-sm hover:shadow-md transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <path d="M5 19L6 16L15 7L17 9L8 18L5 19Z" stroke="currentColor" stroke-width="1.6"/>
                        </svg>
                        Edit
                    </a>

                    <!-- DELETE BUTTON -->
                    <button type="button"
                       class="btn-delete inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl 
                              bg-rose-100 text-rose-700 text-[11px] md:text-xs font-semibold
                              hover:bg-rose-200 shadow-sm hover:shadow-md transition"
                       data-id="<?= (int)$row['id']; ?>"
                       data-name="<?= htmlspecialchars($row['nama_unit'], ENT_QUOTES, 'UTF-8'); ?>">
            
                       <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <path d="M6 7H18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M10 11V17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M14 11V17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M8 7L9 19H15L16 7" stroke="currentColor" stroke-width="1.6"/>
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

                <!-- pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] md:text-xs">
                        <div class="text-slate-500">
                            Halaman <span class="font-semibold"><?= $page; ?></span> dari <span class="font-semibold"><?= $totalPages; ?></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <?php
                            // helper untuk query string
                            $baseQuery = [];
                            if ($search !== '') { $baseQuery['q'] = $search; }
                            ?>
                            <!-- Previous -->
                            <?php if ($page > 1): ?>
                                <?php $baseQuery['page'] = $page - 1; ?>
                                <a href="?<?= http_build_query($baseQuery); ?>"
                                   class="px-2.5 py-1 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                                    Prev
                                </a>
                            <?php endif; ?>

                            <!-- Number -->
                            <?php
                            $start = max(1, $page - 2);
                            $end   = min($totalPages, $page + 2);
                            for ($p = $start; $p <= $end; $p++):
                                $baseQuery['page'] = $p;
                                $isCurrent = $p === $page;
                                ?>
                                <a href="?<?= http_build_query($baseQuery); ?>"
                                   class="px-2.5 py-1 rounded-xl border text-xs
                                          <?= $isCurrent
                                              ? 'border-indigo-500 bg-indigo-500 text-white'
                                              : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'; ?>">
                                    <?= $p; ?>
                                </a>
                            <?php endfor; ?>

                            <!-- Next -->
                            <?php if ($page < $totalPages): ?>
                                <?php $baseQuery['page'] = $page + 1; ?>
                                <a href="?<?= http_build_query($baseQuery); ?>"
                                   class="px-2.5 py-1 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <?php include __DIR__ . '/footer_admin.php'; ?>
    </div>
</div>

<script>
    // SweetAlert notifikasi dari ?status=&message=
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

    // SweetAlert konfirmasi hapus
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id   = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');

            Swal.fire({
                title: 'Hapus Unit?',
                text: 'Unit \"' + name + '\" akan dihapus dari sistem.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
            }).then((result) => {
                if (result.isConfirmed) {
                    // redirect ke file aksi delete (nanti kita buat)
                    window.location.href = 'units_delete.php?id=' + encodeURIComponent(id);
                }
            });
        });
    });
</script>

</body>
</html>
