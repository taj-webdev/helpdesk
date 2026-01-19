<?php
// public/admin/header_admin.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$fullname = $_SESSION['fullname'] ?? 'Guest';
$roleCode = $_SESSION['role'] ?? 'engineer';

$roleMap = [
    'admin'    => 'Admin Helpdesk',
    'project'  => 'Project Helpdesk',
    'engineer' => 'Engineer On-Site',
];
$roleLabel = $roleMap[$roleCode] ?? ucfirst($roleCode);
?>
<header class="border-b border-slate-200 bg-white/90 backdrop-blur-md">
    <div class="px-4 md:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
        <!-- Salam -->
        <div class="flex items-center gap-3">
            <div class="relative">
                <div class="inline-flex items-center justify-center w-9 h-9 rounded-2xl bg-amber-100 border border-amber-200">
                    <span class="text-xl animate-[wave_1.4s_ease-in-out_infinite] origin-bottom">
                        👋
                    </span>
                </div>
            </div>
            <div class="leading-tight">
                <p class="text-xs text-slate-500">Hai,</p>
                <p class="text-sm md:text-base font-semibold text-slate-800">
                    <?= htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>
                    <span class="text-[11px] md:text-xs font-normal text-slate-500">
                        (<?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?>)
                    </span>
                </p>
            </div>
        </div>

        <!-- Jam aktual -->
        <div class="flex items-center gap-2 text-xs md:text-sm text-slate-600 font-mono">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-5 md:h-5" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.4"/>
                <path d="M12 8V12L14.5 13.5" stroke="currentColor" stroke-width="1.4"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span id="admin-clock"></span>
        </div>
    </div>
</header>

<style>
@keyframes wave {
    0% { transform: rotate(0deg); }
    15% { transform: rotate(14deg); }
    30% { transform: rotate(-8deg); }
    45% { transform: rotate(14deg); }
    60% { transform: rotate(-4deg); }
    75% { transform: rotate(10deg); }
    100% { transform: rotate(0deg); }
}
</style>

<script>
(function () {
    const el = document.getElementById('admin-clock');
    if (!el) return;

    function updateClock() {
        const now = new Date();
        const optionsTime = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
        const optionsDate = { day: '2-digit', month: 'short', year: 'numeric' };
        el.textContent = now.toLocaleDateString('id-ID', optionsDate) + ' • ' +
            now.toLocaleTimeString('id-ID', optionsTime);
    }

    updateClock();
    setInterval(updateClock, 1000);
})();
</script>
