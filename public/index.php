<?php
// public/index.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Helpdesk System - Ninjas In Pyjamas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/NIP.png">

    <!-- Google Font (Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root{
            --nip-amber: #f59e0b;
            --nip-amber-2: #ffb84d;
            --nip-dark: #0f172a;
        }

        html, body {
            height: 100%;
            font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }

        /* ============================================================
           NEW: Background Image + Blur + Dark Overlay
        ============================================================ */
        body {
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -3;
            background: url('assets/img/helpdesk.png') center/cover no-repeat;
            filter: blur(6px) brightness(0.90);
            transform: scale(1.05);
        }

        /* Soft gradient overlay */
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -2;
            backdrop-filter: blur(2px);
            background: linear-gradient(180deg, rgba(248,250,252,0.92) 0%, rgba(238,242,255,0.96) 40%, rgba(238,242,255,0.85) 100%);
        }

        /* Original Hybrid Glow Layers */
        .hero-bg {
            background: radial-gradient(1200px 400px at 10% 10%, rgba(99,102,241,0.08), transparent 8%),
                        radial-gradient(900px 300px at 90% 30%, rgba(16,185,129,0.06), transparent 6%);
        }

        /* ============================================================
           LOGO + GLOW
        ============================================================ */
        .logo-wrap {
            position: relative;
            display: inline-block;
            border-radius: 9999px;
            padding: 0.35rem;
            background: linear-gradient(180deg, rgba(255,243,199,0.85), rgba(255,250,240,0.9));
            box-shadow: 0 12px 40px rgba(245,158,11,0.18), 0 6px 18px rgba(14,165,233,0.04);
        }

        .logo-glow {
            position: absolute;
            inset: -18px;
            border-radius: 9999px;
            filter: blur(22px);
            opacity: 0.85;
            z-index: -1;
            background: radial-gradient(circle at 30% 30%, rgba(245,158,11,0.28), rgba(99,102,241,0.12) 40%, rgba(16,185,129,0.06) 70%);
        }

        @keyframes floatY {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }
        .logo-float { animation: floatY 4.2s ease-in-out infinite; }

        /* Fade anim */
        @keyframes fadeInUpSoft {
            0% { opacity: 0; transform: translateY(18px); filter: blur(2px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .fade-in-soft { animation: fadeInUpSoft 0.9s cubic-bezier(.22,.61,.36,1) forwards; }
        .fade-in-soft-delayed { animation: fadeInUpSoft 1.05s cubic-bezier(.22,.61,.36,1) 0.12s forwards; }

        .glass-badge {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,0.6);
        }

        /* Glowing Buttons */
        .btn-glow {
            position: relative;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .btn-glow:before {
            content: "";
            position: absolute;
            inset: -4px;
            border-radius: .75rem;
            filter: blur(14px);
            opacity: 0.55;
            z-index: -1;
        }
        .btn-blue:before {
            background: linear-gradient(90deg, rgba(37,99,235,0.28), rgba(99,102,241,0.20));
        }
        .btn-green:before {
            background: linear-gradient(90deg, rgba(16,185,129,0.28), rgba(34,197,94,0.18));
        }
        .btn-glow:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 50px rgba(15,23,42,0.14);
        }

        .hero-title { letter-spacing: -0.02em; }
    </style>
</head>

<body class="min-h-screen hero-bg flex flex-col">

<?php if (file_exists(__DIR__ . '/header_front.php')) include __DIR__ . '/header_front.php'; ?>

<main class="flex-1 flex items-center justify-center px-6 py-12">
    <div class="max-w-4xl w-full text-center space-y-8">

        <!-- LOGO -->
        <div class="flex justify-center">
            <div class="logo-wrap logo-float fade-in-soft-delayed">
                <div class="logo-glow"></div>
                <div class="rounded-full bg-white p-4 md:p-5 shadow-2xl" style="width:110px; height:110px; display:flex; align-items:center; justify-content:center;">
                    <img src="assets/img/NIP.png" class="w-20 h-20 md:w-24 md:h-24 object-contain">
                </div>
            </div>
        </div>

        <!-- BADGE -->
        <div class="fade-in-soft">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full glass-badge text-emerald-700 text-sm font-semibold">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none">
                    <path d="M12 3L5 6V11C5 15.4 8.6 19.5 12 21C15.4 19.5 19 15.4 19 11V6L12 3Z"
                          stroke="currentColor" stroke-width="1.4"/>
                    <path d="M10 11L12 13L15 9" stroke="currentColor" stroke-width="1.4"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                SECURE HELPDESK ACCESS
            </span>
        </div>

        <!-- TITLE -->
        <div class="space-y-3 fade-in-soft-delayed">
            <h1 class="hero-title text-3xl md:text-5xl lg:text-6xl font-extrabold text-slate-900 drop-shadow">
                SELAMAT DATANG PADA  
                <div class="mt-2 text-amber-500 drop-shadow-md">
                    SYSTEM HELPDESK NINJAS IN PYJAMAS
                </div>
            </h1>

            <p class="hero-sub text-slate-700 max-w-3xl mx-auto md:text-lg font-medium">
                Kelola tiket, unit & entitas dengan tampilan futuristik dan nuansa Ninjas In Pyjamas yang premium.
            </p>
        </div>

        <!-- BUTTONS -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 fade-in-soft mt-6">
            <a href="login.php"
               class="btn-glow btn-blue inline-flex items-center gap-3 px-10 py-3.5 rounded-2xl bg-blue-600 text-white text-lg font-semibold shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none">
                    <path d="M11 16L7 12L11 8" stroke="currentColor" stroke-width="1.6"
                          stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 12H20" stroke="currentColor" stroke-width="1.6"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Login
            </a>

            <a href="register.php"
               class="btn-glow btn-green inline-flex items-center gap-3 px-10 py-3.5 rounded-2xl bg-emerald-500 text-white text-lg font-semibold shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none">
                    <circle cx="10" cy="8" r="3" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M5 18C5.8 16.2 7.7 15 10 15C12.3 15 14.2 16.2 15 18"
                          stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    <path d="M18 8V12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    <path d="M16 10H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
                Register
            </a>
        </div>

    </div>
</main>

<?php if (file_exists(__DIR__ . '/footer_front.php')) include __DIR__ . '/footer_front.php'; ?>

</body>
</html>
