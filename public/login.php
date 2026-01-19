<?php
// public/login.php
session_start();
require_once __DIR__ . '/../app/config/database.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Username dan password wajib diisi.';
    } else {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND status = 1 LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {

                // set session (same as before)
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['fullname']  = $user['fullname'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $user['role'];

                // compute redirect target
                switch ($user['role']) {
                    case 'admin':
                    case 'project':
                        $target = 'admin/index.php';
                        break;
                    case 'engineer':
                        $target = 'engineer/index.php';
                        break;
                    default:
                        $target = 'login.php?error=role_invalid';
                        break;
                }

                // Instead of immediate header redirect, render a small transition page
                // that shows spinner (1.2s), then SweetAlert success, then redirect.
                // This preserves authentication logic (sessions already set).
                ?>
                <!doctype html>
                <html lang="id">
                <head>
                    <meta charset="utf-8" />
                    <title>Login - Sukses</title>
                    <meta name="viewport" content="width=device-width,initial-scale=1" />
                    <link rel="icon" type="image/png" href="assets/img/NIP.png">
                    <script src="https://cdn.tailwindcss.com"></script>
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <style>
                        /* Full-screen centered orbit glow spinner (Spinner C) */
                        :root{
                            --accent1: #60A5FA;
                            --accent2: #34D399;
                            --accent3: #FBBF24;
                        }
                        html,body{height:100%;}
                        body{margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial;}
                        .fullscreen {
                            height:100vh;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            background: linear-gradient(180deg, rgba(15,23,42,0.03), rgba(248,250,252,0.9));
                        }
                        .orbiter {
                            position:relative;
                            width:120px;
                            height:120px;
                        }
                        .orbiter .ring {
                            position:absolute;
                            inset:0;
                            border-radius:9999px;
                            filter: blur(10px);
                            opacity:.7;
                            animation: spin 2.6s linear infinite;
                            box-shadow: 0 0 30px rgba(99,102,241,0.28);
                        }
                        .orbiter .ring.r2 { animation-duration: 2s; box-shadow: 0 0 30px rgba(16,185,129,0.22); transform: rotate(30deg); }
                        .orbiter .ring.r3 { animation-duration: 3.1s; box-shadow: 0 0 30px rgba(251,191,36,0.22); transform: rotate(60deg); }
                        @keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
                        .orbiter .center {
                            position:absolute;
                            left:50%; top:50%;
                            transform:translate(-50%,-50%);
                            width:72px; height:72px;
                            border-radius:9999px;
                            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(250,250,250,0.95));
                            display:flex;align-items:center;justify-content:center;
                            box-shadow: 0 10px 30px rgba(2,6,23,0.08);
                        }
                        .orbiter .center img { width:46px; height:46px; object-fit:contain; border-radius:12px; }
                        .loading-text { margin-top:18px; text-align:center; color:#0f172a; font-weight:600; }
                        /* small spinner on button */
                        .btn-spinner { display:inline-block; width:18px; height:18px; border-radius:50%; box-shadow: 0 0 12px rgba(96,165,250,0.8) inset; animation: pulse 1s linear infinite; }
                        @keyframes pulse { 0%{transform:scale(0.9);opacity:.85}50%{transform:scale(1);opacity:1}100%{transform:scale(0.9);opacity:.85} }
                    </style>
                </head>
                <body>
                    <div class="fullscreen">
                        <div class="text-center">
                            <div class="orbiter mx-auto" aria-hidden="true">
                                <div class="ring r1" style="background: conic-gradient(from 0deg, rgba(96,165,250,0.9), transparent 40%);"></div>
                                <div class="ring r2" style="background: conic-gradient(from 120deg, rgba(34,197,94,0.88), transparent 40%);"></div>
                                <div class="ring r3" style="background: conic-gradient(from 240deg, rgba(251,191,36,0.85), transparent 40%);"></div>
                                <div class="center">
                                    <img src="assets/img/NIP.png" alt="logo"/>
                                </div>
                            </div>

                            <div class="loading-text text-slate-700">Authenticating... Please wait</div>
                        </div>
                    </div>

                    <script>
                        // Show spinner 1.2s, then SweetAlert success, then redirect to target
                        (function(){
                            const delay = 1200; // ms
                            setTimeout(() => {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Login Berhasil',
                                    text: 'Selamat datang, <?= htmlspecialchars(addslashes($user['fullname'])); ?>',
                                    timer: 1400,
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    background: '#ffffff',
                                    backdrop: true
                                }).then(() => {
                                    // Redirect after SweetAlert finishes
                                    window.location.href = <?= json_encode($target); ?>;
                                });
                            }, delay);
                        })();
                    </script>
                </body>
                </html>
                <?php
                exit;
            } else {
                $errors[] = 'Username atau password salah, atau akun tidak aktif.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan pada server. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Helpdesk System NIP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="assets/img/NIP.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        body { font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
        @keyframes fadeInUpSoft {
            0% { opacity: 0; transform: translateY(18px); filter: blur(2px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .fade-in-soft { animation: fadeInUpSoft 0.7s cubic-bezier(0.22, 0.61, 0.36, 1) forwards; }
        .btn-loader { display:inline-flex; align-items:center; gap:0.6rem; justify-content:center; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200 text-slate-900 antialiased flex flex-col">

    <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    Swal.fire({
        icon: 'success',
        title: 'Logout Berhasil',
        text: 'Anda telah keluar dari sistem.',
        timer: 1600,
        showConfirmButton: false,
        timerProgressBar: true,
        background: '#ffffff',
        backdrop: true
    });
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/header_front.php'; ?>

<main class="flex-1 flex items-center justify-center px-4 py-10">
    <div class="max-w-md w-full fade-in-soft">
        <div class="bg-white/95 rounded-3xl shadow-2xl border border-slate-200 px-6 py-7 md:px-8 md:py-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200">
                    <!-- Icon shield login -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-blue-600" viewBox="0 0 24 24" fill="none">
                        <path d="M12 3L5 6V11C5 15.4183 8.5817 19.5 12 21C15.4183 19.5 19 15.4183 19 11V6L12 3Z"
                              stroke="currentColor" stroke-width="1.6"/>
                        <path d="M10 11.5L11.5 13L14.5 10" stroke="currentColor" stroke-width="1.6"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-2xl font-semibold">Login Helpdesk</h1>
                    <p class="text-sm text-slate-500">Masuk ke sistem Ninjas In Pyjamas</p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="frmLogin" action="" method="post" class="space-y-4" autocomplete="off" novalidate>
                <!-- Username -->
                <div class="space-y-1.5">
                    <label for="username" class="text-sm font-medium text-slate-700">
                        Username
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <!-- icon user -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.4"/>
                                <path d="M7 19C7.8 17.2 9.7 16 12 16C14.3 16 16.2 17.2 17 19" stroke="currentColor" stroke-width="1.4"
                                      stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-white px-10 py-3 text-base outline-none
                                   focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 placeholder:text-slate-400"
                            placeholder="Masukkan username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <label for="password" class="text-sm font-medium text-slate-700">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <!-- icon lock -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="1.4"/>
                                <path d="M9 11V9C9 7.34315 10.3431 6 12 6C13.6569 6 15 7.34315 15 9V11" stroke="currentColor" stroke-width="1.4"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-white px-10 py-3 text-base outline-none
                                   focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 placeholder:text-slate-400"
                            placeholder="Masukkan password"
                        >
                    </div>
                </div>

                <button
                    id="btnSubmit"
                    type="submit"
                    class="mt-3 w-full inline-flex items-center justify-center gap-3 rounded-2xl bg-blue-600 px-4 py-3
                           text-base font-semibold text-white shadow-lg hover:bg-blue-500 hover:shadow-xl
                           hover:-translate-y-[1px] active:translate-y-0 transition"
                >
                    <span id="btnIcon" class="inline-flex items-center gap-2">
                        <!-- icon login -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                            <path d="M11 16L7 12L11 8" stroke="currentColor" stroke-width="1.6"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 12H20" stroke="currentColor" stroke-width="1.6"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M4 5H13C14.6569 5 16 6.34315 16 8V9" stroke="currentColor"
                                  stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M16 15V16C16 17.6569 14.6569 19 13 19H4" stroke="currentColor"
                                  stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                        <span id="btnText">Masuk</span>
                    </span>
                    <!-- small glowing dot (hidden until loading) -->
                    <span id="btnSpinner" class="hidden btn-spinner"></span>
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-slate-500">
                Belum punya akun?
                <a href="register.php" class="font-semibold text-blue-600 hover:text-blue-500">
                    Daftar sekarang
                </a>
            </p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/footer_front.php'; ?>

<script>
    // enhance UX: show full-screen mini overlay spinner + button spinner BEFORE submitting,
    // then let native server-side logic continue (form POST). For security we DO NOT
    // show "success" notification until the server actually authenticates.
    (function(){
        const frm = document.getElementById('frmLogin');
        const btn = document.getElementById('btnSubmit');
        const btnSpinner = document.getElementById('btnSpinner');
        const btnIcon = document.getElementById('btnIcon');
        const btnText = document.getElementById('btnText');

        // create full-screen overlay element (hidden by default)
        const overlay = document.createElement('div');
        overlay.id = 'login-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,0.28);backdrop-filter:blur(4px);z-index:9999;opacity:0;pointer-events:none;transition:opacity .22s ease;';
        overlay.innerHTML = `
            <div style="text-align:center">
                <div style="width:120px;height:120px;margin:0 auto;" aria-hidden="true">
                    <div style="position:relative;width:100%;height:100%;">
                        <div style="position:absolute;inset:0;border-radius:9999px;filter:blur(10px);opacity:.85;background:conic-gradient(from 0deg, rgba(96,165,250,0.9), transparent 40%);animation:spin 2.6s linear infinite;"></div>
                        <div style="position:absolute;inset:6px;border-radius:9999px;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 12px 40px rgba(2,6,23,0.06);">
                            <img src="assets/img/NIP.png" alt="logo" style="width:56px;height:56px;object-fit:contain;border-radius:10px;">
                        </div>
                    </div>
                </div>
                <div style="margin-top:14px;color:#0f172a;font-weight:600">Memeriksa kredensial...</div>
            </div>
        `;
        document.body.appendChild(overlay);

        // keyframes for overlay spinner
        const styleEl = document.createElement('style');
        styleEl.innerHTML = '@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}';
        document.head.appendChild(styleEl);

        function showOverlay(){
            overlay.style.pointerEvents = 'auto';
            overlay.style.opacity = '1';
            // button spinner + disable button
            btnSpinner.classList.remove('hidden');
            btnIcon.style.opacity = '0';
            btn.setAttribute('disabled', 'disabled');
        }
        function hideOverlay(){
            overlay.style.opacity = '0';
            overlay.style.pointerEvents = 'none';
            btnSpinner.classList.add('hidden');
            btnIcon.style.opacity = '1';
            btn.removeAttribute('disabled');
        }

        frm.addEventListener('submit', function(e){
            // show overlay and small animation before actual submit
            showOverlay();
            // allow form to submit to server after a brief UI feedback (0ms to not interfere).
            // but to produce the desired "spinner then server" experience, we keep the submit immediate.
            // The transition page on successful login will show the spinner+SweetAlert as implemented server-side.
            // If credentials invalid, server will return login page with errors and overlay will disappear.
            // We'll set a timeout to hide overlay after 8s to avoid stuck overlay in weird cases (server error).
            setTimeout(() => {
                hideOverlay();
            }, 8000);
            // let the browser submit the form natively
        });

        // if page shows errors (server returned page with errors), ensure overlay hidden
        window.addEventListener('load', function(){
            hideOverlay();
        });
    })();
</script>

</body>
</html>
