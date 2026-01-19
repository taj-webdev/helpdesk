<?php
// public/header_front.php
?>
<header class="w-full border-b border-slate-800/70 bg-slate-950/70 backdrop-blur-md">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-4 text-sm md:text-base">

        <!-- Kiri: Tulisan Helpdesk System -->
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-amber-400/10 border border-amber-300/40 overflow-hidden">
                <img
                    src="assets/img/NIP.png"
                    alt="Logo NIP"
                    class="w-8 h-8 object-contain"
                >
            </div>
            <div class="leading-tight">
                <div class="uppercase tracking-[0.20em] text-[11px] md:text-xs text-amber-300 font-semibold">
                    Helpdesk System
                </div>
                <div class="text-slate-200 font-semibold text-sm md:text-base">
                    Ninjas In Pyjamas
                </div>
            </div>
        </div>

        <!-- Kanan: Jam aktual -->
        <div class="flex items-center gap-2 text-slate-300 font-mono">
            <!-- Icon jam -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 md:w-6 md:h-6" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.4"/>
                <path d="M12 8V12L14.5 13.5" stroke="currentColor" stroke-width="1.4"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span id="clock" class="text-xs md:text-sm lg:text-base"></span>
        </div>
    </div>
</header>

<script>
// Jam aktual (client-side, mengikuti waktu device user)
(function () {
    const clockEl = document.getElementById('clock');

    function updateClock() {
        const now = new Date();
        const options = {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        const dateOptions = {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        };

        const timeStr = now.toLocaleTimeString('id-ID', options);
        const dateStr = now.toLocaleDateString('id-ID', dateOptions);

        if (clockEl) {
            clockEl.textContent = `${dateStr} • ${timeStr}`;
        }
    }

    updateClock();
    setInterval(updateClock, 1000);
})();
</script>
