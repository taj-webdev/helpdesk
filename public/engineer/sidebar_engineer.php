<?php if (!isset($_SESSION)) session_start(); ?>

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* FULLSCREEN ORBIT SPINNER */
    #logoutOverlay {
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,0.48);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        opacity: 0;
        pointer-events: none;
        transition: opacity .25s ease;
    }

    .orbit {
        width: 130px;
        height: 130px;
        position: relative;
    }

    .orbit .ring {
        position: absolute;
        inset: 0;
        border-radius: 9999px;
        filter: blur(12px);
        opacity: .75;
        animation: spin 3s linear infinite;
    }
    .orbit .r2 { animation-duration: 2s; transform: rotate(80deg); }
    .orbit .r3 { animation-duration: 4.2s; transform: rotate(160deg); }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<!-- FULLSCREEN LOGOUT SPINNER -->
<div id="logoutOverlay">
    <div class="text-center">
        <div class="orbit mx-auto">
            <div class="ring" style="background: conic-gradient(from 0deg, rgba(244,63,94,.9), transparent 60%);"></div>
            <div class="ring r2" style="background: conic-gradient(from 120deg, rgba(251,113,133,.9), transparent 60%);"></div>
            <div class="ring r3" style="background: conic-gradient(from 240deg, rgba(254,205,211,.9), transparent 60%);"></div>

            <div class="absolute inset-7 rounded-full bg-white shadow-xl flex items-center justify-center">
                <img src="../assets/img/NIP.png" class="w-14 h-14 object-contain">
            </div>
        </div>

        <p class="mt-4 text-white font-semibold text-sm tracking-wide">
            Logging Out...
        </p>
    </div>
</div>

<aside class="w-60 bg-white border-r border-slate-200 min-h-screen flex flex-col">

    <!-- Logo -->
    <div class="px-5 py-6 border-b border-slate-200">
        <div class="flex flex-col items-start">
            <img src="../assets/img/NIP.png" class="w-12 h-12 rounded-xl shadow" alt="logo">
            <p class="mt-2 text-[10px] tracking-[0.15em] text-amber-600 font-semibold">HELPDESK SYSTEM</p>
            <p class="text-sm font-semibold text-slate-700 -mt-1">Ninjas In Pyjamas</p>
        </div>
    </div>

    <!-- Menu -->
    <nav class="flex-1 px-4 py-4 space-y-1 text-sm">

        <!-- Dashboard -->
        <a href="index.php"
           class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-slate-100 text-slate-700 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-500" viewBox="0 0 24 24" fill="none">
                <path d="M4 4H10V10H4V4Z" stroke="currentColor" stroke-width="1.6"/>
                <path d="M14 4H20V10H14V4Z" stroke="currentColor" stroke-width="1.6"/>
                <path d="M4 14H10V20H4V14Z" stroke="currentColor" stroke-width="1.6"/>
                <path d="M14 14H20V20H14V14Z" stroke="currentColor" stroke-width="1.6"/>
            </svg>
            Dashboard
        </a>

        <!-- Units -->
        <a href="units.php"
           class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-slate-100 text-slate-700 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-sky-500" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="4" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/>
            </svg>
            Units
        </a>

        <!-- Entities -->
        <a href="entities.php"
           class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-slate-100 text-slate-700 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-500" fill="none">
                <rect x="3" y="7" width="18" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
            </svg>
            Entities
        </a>

        <!-- Tickets -->
        <a href="tickets.php"
           class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-slate-100 text-slate-700 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-pink-500" fill="none">
                <path d="M5 7H19V10C18 10 17.5 11 17.5 12C17.5 13 18 14 19 14V17H5V14C6 14 6.5 13 6.5 12C6.5 11 6 10 5 10V7Z"
                      stroke="currentColor" stroke-width="1.6"/>
            </svg>
            Tickets
        </a>

        <!-- Logout -->
        <button id="logoutBtn"
           class="flex items-center gap-3 px-3 py-2 mt-3 rounded-xl hover:bg-red-50 text-red-600 font-medium cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" stroke="currentColor" fill="none">
                <path d="M13 16L17 12L13 8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M17 12H7" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M4 4H12C13.657 4 15 5.343 15 7V17C15 18.657 13.657 20 12 20H4"
                      stroke-width="1.6" stroke-linecap="round"/>
            </svg>
            Log Out
        </button>

    </nav>
</aside>

<script>
    const overlay = document.getElementById("logoutOverlay");

    function doLogout() {
        overlay.style.opacity = "1";
        overlay.style.pointerEvents = "auto";

        // setelah 1.4 detik → redirect
        setTimeout(() => {
            window.location.href = "../logout.php";
        }, 1400);
    }

    function confirmLogout() {
        Swal.fire({
            title: "Keluar dari sistem?",
            text: "Anda yakin ingin logout?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Logout",
            cancelButtonText: "Batal",
            backdrop: true,
        }).then((res) => {
            if (res.isConfirmed) {
                doLogout();
            }
        });
    }

    document.getElementById("logoutBtn").onclick = confirmLogout;
</script>
