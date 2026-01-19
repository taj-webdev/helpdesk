<?php
// public/admin/sidebar_admin.php
?>
<!-- SweetAlert & Tailwind -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    /* Fullscreen spinner overlay */
    #logoutOverlay {
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,0.45);
        backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        opacity: 0;
        pointer-events: none;
        transition: opacity .25s ease;
    }

    .orb {
        width: 130px;
        height: 130px;
        position: relative;
    }
    .orb .ring {
        position: absolute;
        inset: 0;
        border-radius: 9999px;
        filter: blur(12px);
        opacity: .75;
        animation: spin 3.2s linear infinite;
    }
    .orb .r2 { animation-duration: 2.4s; transform: rotate(60deg); }
    .orb .r3 { animation-duration: 4s; transform: rotate(120deg); }

    @keyframes spin { 
        0% { transform: rotate(0deg); } 
        100% { transform: rotate(360deg); } 
    }
</style>

<!-- FULL SCREEN LOGOUT SPINNER -->
<div id="logoutOverlay">
    <div class="text-center">
        <div class="orb mx-auto">
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

<aside class="hidden md:flex md:flex-col w-60 xl:w-64 bg-white border-r border-slate-200 shadow-sm">
    <div class="px-4 py-4 border-b border-slate-200 flex items-center gap-3">
        <div class="relative">
            <div class="w-11 h-11 rounded-2xl bg-amber-100 border border-amber-200 flex items-center justify-center overflow-hidden">
                <img src="../assets/img/NIP.png" alt="Logo NIP" class="w-9 h-9 object-contain rounded-xl">
            </div>
        </div>
        <div class="leading-tight">
            <p class="text-[11px] uppercase tracking-[0.18em] text-amber-500 font-semibold">
                Helpdesk System
            </p>
            <p class="text-sm font-semibold text-slate-800">
                Ninjas In Pyjamas
            </p>
        </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1 text-sm">

        <!-- Dashboard -->
        <a href="index.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-2xl text-slate-800 font-medium
                  hover:bg-slate-100 hover:text-slate-900 transition">
            <span class="w-7 h-7 inline-flex items-center justify-center rounded-xl bg-indigo-50 text-indigo-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none">
                    <path d="M4 11H11V4H4V11Z" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M13 4H20V9H13V4Z" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M13 11H20V20H13V11Z" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M4 13H11V20H4V13Z" stroke="currentColor" stroke-width="1.4"/>
                </svg>
            </span>
            <span>Dashboard</span>
        </a>

        <!-- Units -->
        <a href="units.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-2xl text-slate-600 hover:text-slate-900
                  hover:bg-slate-100 transition">
            <span class="w-7 h-7 inline-flex items-center justify-center rounded-xl bg-sky-50 text-sky-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none">
                    <rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M9 9H11" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M9 13H11" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M13 9H15" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M13 13H15" stroke="currentColor" stroke-width="1.4"/>
                </svg>
            </span>
            <span>Units</span>
        </a>

        <!-- Entities -->
        <a href="entities.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-2xl text-slate-600 hover:text-slate-900
                  hover:bg-slate-100 transition">
            <span class="w-7 h-7 inline-flex items-center justify-center rounded-xl bg-emerald-50 text-emerald-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none">
                    <rect x="5" y="6" width="14" height="10" rx="2" 
                          stroke="currentColor" stroke-width="1.4"/>
                    <path d="M9 18H15" stroke="currentColor" stroke-width="1.4"/>
                </svg>
            </span>
            <span>Entities</span>
        </a>

        <!-- Tickets -->
        <a href="tickets.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-2xl text-slate-600 hover:text-slate-900
                  hover:bg-slate-100 transition">
            <span class="w-7 h-7 inline-flex items-center justify-center rounded-xl bg-fuchsia-50 text-fuchsia-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none">
                    <path d="M5 7H19V10C18.1716 10 17.5 10.6716 17.5 11.5C17.5 12.3284 18.1716 13 19 13V16H5V13C5.82843 13 6.5 12.3284 6.5 11.5C6.5 10.6716 5.82843 10 5 10V7Z"
                          stroke="currentColor" stroke-width="1.4"/>
                </svg>
            </span>
            <span>Tickets</span>
        </a>

        <!-- LOGOUT BUTTON -->
        <button id="btnLogout"
            class="w-full text-left mt-2 flex items-center gap-3 px-3 py-2.5 rounded-2xl text-rose-600 hover:text-rose-700
                   hover:bg-rose-50 transition cursor-pointer">
            <span class="w-7 h-7 inline-flex items-center justify-center rounded-xl bg-rose-50 text-rose-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none">
                    <path d="M11 16L7 12L11 8" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M7 12H16" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M16 5V4C16 3 15 3 15 3H6C5 3 5 3 5 4V20C5 21 5 21 6 21H15C15 21 16 21 16 20V19"
                          stroke="currentColor" stroke-width="1.6"/>
                </svg>
            </span>
            <span>Log Out</span>
        </button>

    </nav>
</aside>

<!-- MOBILE -->
<div class="md:hidden border-b border-slate-200 bg-white px-4 py-2 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <img src="../assets/img/NIP.png" class="w-8 h-8 rounded-xl">
        <div class="leading-tight">
            <p class="text-[10px] uppercase tracking-[0.18em] text-amber-500 font-semibold">Helpdesk System</p>
            <p class="text-xs font-semibold text-slate-800">Ninjas In Pyjamas</p>
        </div>
    </div>

    <button id="btnLogoutMobile" class="text-[11px] text-rose-600 font-medium">
        Logout
    </button>
</div>

<script>
    const overlay = document.getElementById("logoutOverlay");

    function startLogout() {
        overlay.style.opacity = "1";
        overlay.style.pointerEvents = "auto";

        setTimeout(() => {
            window.location.href = "../logout.php";
        }, 1500);
    }

    function confirmLogout() {
        Swal.fire({
            title: "Konfirmasi",
            text: "Apakah Anda yakin ingin logout?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Logout",
            cancelButtonText: "Batal",
            backdrop: true,
        }).then((result) => {
            if (result.isConfirmed) {
                startLogout();
            }
        });
    }

    document.getElementById("btnLogout").onclick = confirmLogout;
    document.getElementById("btnLogoutMobile").onclick = confirmLogout;
</script>
