<?php
if (!isset($_SESSION)) session_start();

$fullname  = $_SESSION['fullname'] ?? 'Engineer';
$role      = $_SESSION['role'] ?? 'engineer';

$roleLabel = match ($role) {
    'engineer' => 'Engineer On-Site',
    'project'  => 'Project Engineer',
    'admin'    => 'Admin Helpdesk',
    default    => ucfirst($role),
};
?>
<header class="border-b border-slate-200 bg-white/90 backdrop-blur-md">
    <div class="px-4 md:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">

        <!-- Salam -->
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-amber-100 border border-amber-200">
                <span class="text-2xl animate-[wave_1.4s_ease-in-out_infinite] origin-bottom">👋</span>
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
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.4"/>
                <path d="M12 8V12L14.5 13.5" stroke="currentColor" stroke-width="1.4"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span id="admin-clock"></span>
        </div>
    </div>
</header>

<script>
// Clock realtime
function updateClock() {
    const now = new Date();
    const tgl = now.toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' });
    const jam = now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    document.getElementById('admin-clock').innerText = `${tgl} • ${jam}`;
}
setInterval(updateClock, 1000);
updateClock();
</script>

<style>
@keyframes wave {
    0% { transform: rotate(0deg); }
    20% { transform: rotate(18deg); }
    40% { transform: rotate(-12deg); }
    60% { transform: rotate(16deg); }
    80% { transform: rotate(-6deg); }
    100% { transform: rotate(0deg); }
}
</style>
