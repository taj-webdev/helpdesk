<?php
// public/register.php
session_start();
require_once __DIR__ . '/../app/config/database.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $roleForm = $_POST['role'] ?? '';

    $allowedRoles = [
        'admin'    => 'Admin Helpdesk',
        'project'  => 'Project Helpdesk',
        'engineer' => 'Engineer On-Site',
    ];

    if ($fullname === '' || $username === '' || $password === '' || $roleForm === '') {
        $errors[] = 'Semua field wajib diisi.';
    } elseif (!array_key_exists($roleForm, $allowedRoles)) {
        $errors[] = 'Role tidak valid.';
    } else {
        try {
            $pdo = db();

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetch()) {
                $errors[] = 'Username sudah digunakan. Silakan pilih username lain.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $insert = $pdo->prepare("
                    INSERT INTO users (fullname, username, password, role, status)
                    VALUES (:fullname, :username, :password, :role, 1)
                ");
                $insert->execute([
                    ':fullname' => $fullname,
                    ':username' => $username,
                    ':password' => $hash,
                    ':role'     => $roleForm,
                ]);

                $success = true;
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
    <title>Register - Helpdesk System NIP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="assets/img/NIP.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }

        @keyframes fadeInUpSoft {
            0% { opacity: 0; transform: translateY(18px); filter: blur(2px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .fade-in-soft { animation: fadeInUpSoft .7s cubic-bezier(.22,.61,.36,1) forwards; }

        /* ---------- FULL SCREEN SPINNER ---------- */
        .overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15,23,42,0.38);
            backdrop-filter: blur(4px);
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s ease;
        }

        .orb {
            width: 120px;
            height: 120px;
            position: relative;
        }
        .orb .ring {
            position:absolute;
            inset:0;
            border-radius:9999px;
            filter: blur(10px);
            opacity:.8;
            animation: spin 3s linear infinite;
        }
        .orb .ring.r2 { animation-duration: 2.2s; transform: rotate(45deg); }
        .orb .ring.r3 { animation-duration: 3.6s; transform: rotate(90deg); }

        @keyframes spin { 0%{transform:rotate(0)}100%{transform:rotate(360deg)} }

        /* Button spinner */
        .btn-spinner {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            box-shadow: 0 0 12px rgba(16,185,129,.7) inset;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%{transform:scale(.9);opacity:.85}
            50%{transform:scale(1);opacity:1}
            100%{transform:scale(.9);opacity:.85}
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200 text-slate-900">

<?php include __DIR__ . '/header_front.php'; ?>

<!-- FULLSCREEN SPINNER -->
<div id="overlay" class="overlay">
    <div class="text-center">
        <div class="orb mx-auto">
            <div class="ring r1" style="background: conic-gradient(from 0deg, rgba(16,185,129,.9), transparent 50%);"></div>
            <div class="ring r2" style="background: conic-gradient(from 120deg, rgba(52,211,153,.9), transparent 50%);"></div>
            <div class="ring r3" style="background: conic-gradient(from 240deg, rgba(110,231,183,.9), transparent 50%);"></div>
            <div class="absolute inset-6 rounded-full bg-white shadow-xl flex items-center justify-center">
                <img src="assets/img/NIP.png" class="w-14 h-14 object-contain">
            </div>
        </div>
        <div class="mt-4 text-slate-800 font-semibold text-sm">Memproses pendaftaran...</div>
    </div>
</div>

<main class="flex-1 flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full fade-in-soft">

        <div class="bg-white/95 rounded-3xl shadow-xl border border-slate-200 px-7 py-8 md:px-8">

            <!-- HEADER -->
            <div class="flex items-center gap-3 mb-7">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-emerald-600" viewBox="0 0 24 24" fill="none">
                        <circle cx="10" cy="8" r="3" stroke="currentColor" stroke-width="1.6"/>
                        <path d="M5 18C5.8 16.2 7.7 15 10 15C12.3 15 14.2 16.2 15 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        <path d="M18 8V12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        <path d="M16 10H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-semibold">Registrasi Akun</h1>
                    <p class="text-sm text-slate-500">Buat akun baru untuk Helpdesk NIP</p>
                </div>
            </div>

            <!-- SUCCESS ALERT -->
            <?php if ($success && empty($errors)): ?>
                <script>
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Akun Berhasil Dibuat!',
                            text: 'Silakan login menggunakan akun baru Anda.',
                            timer: 2000,
                            showConfirmButton: false,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.href = "login.php";
                        });
                    }, 200);
                </script>
            <?php endif; ?>

            <!-- ERROR ALERT -->
            <?php if (!empty($errors)): ?>
                <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- FORM -->
            <form id="frmReg" action="" method="post" class="space-y-4">

                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-slate-700">Nama Lengkap</label>
                    <input name="fullname" required
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base outline-none
                               focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                        value="<?= htmlspecialchars($_POST['fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>

                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-slate-700">Username</label>
                    <input name="username" required
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base outline-none
                               focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                        value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>

                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" required
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base outline-none
                               focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                    >
                </div>

                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-slate-700">Role</label>
                    <select required name="role"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base outline-none
                                   focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500">
                        <option value="">Pilih role</option>
                        <option value="admin"    <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin Helpdesk</option>
                        <option value="project"  <?= (($_POST['role'] ?? '') === 'project') ? 'selected' : ''; ?>>Project Helpdesk</option>
                        <option value="engineer" <?= (($_POST['role'] ?? '') === 'engineer') ? 'selected' : ''; ?>>Engineer On-Site</option>
                    </select>
                </div>

                <!-- BUTTON -->
                <button id="btnReg" type="submit"
                    class="mt-3 w-full inline-flex items-center justify-center gap-3 rounded-2xl bg-emerald-500 px-4 py-3
                           text-base font-semibold text-white shadow-lg hover:bg-emerald-400 hover:shadow-xl
                           transition">
                    <span id="btnText" class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                            <circle cx="10" cy="8" r="3" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M5 18C5.8 16.2 7.7 15 10 15C12.3 15 14.2 16.2 15 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M18 8V12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M16 10H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                        Buat Akun
                    </span>
                    <span id="btnSpinner" class="hidden btn-spinner"></span>
                </button>

            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Sudah punya akun?
                <a href="login.php" class="font-semibold text-blue-600 hover:text-blue-500">
                    Login di sini
                </a>
            </p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/footer_front.php'; ?>

<!-- JS HANDLER -->
<script>
    const frm = document.getElementById('frmReg');
    const btn = document.getElementById('btnReg');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    const overlay = document.getElementById('overlay');

    frm.addEventListener('submit', function() {
        overlay.style.opacity = "1";
        overlay.style.pointerEvents = "auto";

        btnSpinner.classList.remove('hidden');
        btnText.style.opacity = "0.3";
        btn.disabled = true;

        setTimeout(() => {
            btn.disabled = false;
            btnSpinner.classList.add('hidden');
            btnText.style.opacity = "1";
        }, 8000);
    });
</script>

</body>
</html>
