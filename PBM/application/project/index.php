<?php
session_start();

$hasSession = !empty($_SESSION['code']) && !empty($_SESSION['r_email']);
$errorMessage = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | ระบบบริหารโครงการ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --cmu-purple: #7b6fc0;
            --cmu-purple-dark: #5c4fa3;
            --cmu-purple-deep: #4a3d8f;
            --cmu-gold: #f5b335;
            --cmu-gold-dark: #e09a18;
            --cmu-text: #2d2640;
            --cmu-muted: #6b6280;
        }

        * { box-sizing: border-box; }

        body {
            font-family: "Noto Sans Thai", sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(123, 111, 192, 0.18) 0%, transparent 55%),
                radial-gradient(ellipse 70% 50% at 85% 90%, rgba(245, 179, 53, 0.14) 0%, transparent 50%),
                linear-gradient(160deg, #f3f0fa 0%, #faf7ef 45%, #ede8f8 100%);
        }

        .login-scene {
            width: 100%;
            max-width: 440px;
            position: relative;
        }

        .login-scene::before {
            content: "";
            position: absolute;
            inset: -12px -8px auto;
            height: 6px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--cmu-purple) 0%, var(--cmu-purple) 48%, var(--cmu-gold) 52%, var(--cmu-gold) 100%);
            opacity: 0.85;
        }

        .login-card {
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow:
                0 24px 60px rgba(74, 61, 143, 0.14),
                0 8px 24px rgba(74, 61, 143, 0.08);
            border: 1px solid rgba(123, 111, 192, 0.12);
        }

        .login-header {
            position: relative;
            padding: 40px 32px 28px;
            text-align: center;
            background: linear-gradient(135deg, rgba(123, 111, 192, 0.06) 0%, rgba(245, 179, 53, 0.05) 100%);
        }

        .login-header::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--cmu-purple) 0%, var(--cmu-purple) 48%, var(--cmu-gold) 52%, var(--cmu-gold) 100%);
        }

        .logo-wrap {
            width: 240px;
            height: 195px;
            margin: 0 auto 20px;
            overflow: hidden;
            position: relative;
        }

        .logo-wrap img {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) scale(1.55);
            width: 100%;
            height: auto;
            max-width: none;
            display: block;
        }

        .login-header h1 {
            font-size: 1.28rem;
            font-weight: 700;
            color: var(--cmu-text);
            margin: 0 0 6px;
        }

        .login-header p {
            margin: 0;
            font-size: 0.88rem;
            color: var(--cmu-muted);
            font-weight: 500;
        }

        .faculty-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 14px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(123, 111, 192, 0.1);
            color: var(--cmu-purple-dark);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .login-body { padding: 32px 32px 28px; }

        .login-intro { text-align: center; margin-bottom: 22px; }

        .login-intro h2 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--cmu-text);
            margin: 0 0 4px;
        }

        .login-intro p {
            margin: 0;
            font-size: 0.82rem;
            color: var(--cmu-muted);
        }

        .login-choices { display: flex; flex-direction: column; gap: 12px; }

        .btn-cmu,
        .btn-plan {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            width: 100%;
            padding: 14px 18px;
            border-radius: 14px;
            font-size: 0.98rem;
            font-weight: 700;
            text-decoration: none;
            text-align: left;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .btn-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .btn-title {
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .btn-sub {
            font-size: 0.75rem;
            font-weight: 600;
            opacity: 0.85;
        }

        .btn-cmu {
            border: none;
            color: #fff;
            background: linear-gradient(135deg, var(--cmu-purple) 0%, var(--cmu-purple-dark) 100%);
            box-shadow: 0 10px 28px rgba(92, 79, 163, 0.35);
        }

        .btn-cmu:hover,
        .btn-cmu:focus {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 14px 36px rgba(92, 79, 163, 0.42);
            background: linear-gradient(135deg, var(--cmu-purple-dark) 0%, var(--cmu-purple-deep) 100%);
        }

        .btn-plan {
            color: var(--cmu-purple-deep);
            background: #fff;
            border: 2px solid rgba(123, 111, 192, 0.45);
        }

        .btn-plan:hover,
        .btn-plan:focus {
            color: var(--cmu-purple-deep);
            transform: translateY(-2px);
            border-color: var(--cmu-gold);
            box-shadow: 0 10px 24px rgba(224, 154, 24, 0.18);
        }

        .btn-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #fff;
            padding: 4px;
            flex-shrink: 0;
        }

        .btn-plan .btn-logo {
            background: rgba(123, 111, 192, 0.08);
        }

        .btn-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .login-footer {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid rgba(123, 111, 192, 0.12);
            text-align: center;
            font-size: 0.76rem;
            color: #9a93ad;
            line-height: 1.5;
        }

        .login-footer strong {
            color: var(--cmu-purple-dark);
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .login-header { padding: 32px 24px 24px; }
            .login-body { padding: 28px 24px 24px; }
            .logo-wrap { width: 210px; height: 170px; }
            .logo-wrap img { transform: translate(-50%, -50%) scale(1.45); }
            .login-header h1 { font-size: 1.15rem; }
        }
    </style>
</head>

<body>
    <div class="login-scene">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-wrap">
                    <img src="logo.php?f=phar" alt="คณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่">
                </div>
                <h1>ระบบบริหารโครงการ</h1>
                <p>คณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่</p>
                <span class="faculty-badge">
                    <i class="bi bi-shield-check"></i>
                    Single Sign-On · CMU Account
                </span>
            </div>

            <div class="login-body">
                <div class="login-intro">
                    <h2>เข้าสู่ระบบ</h2>
                    <p>ใช้บัญชี CMU Account ของมหาวิทยาลัยเชียงใหม่</p>
                </div>

                <div class="login-choices">
                    <a href="login_cmu.php?side=user" class="btn-cmu">
                        <span class="btn-logo">
                            <img src="logo.php?f=cmu" alt="CMU">
                        </span>
                        <span class="btn-text">
                            <span class="btn-title">เข้าสู่ระบบด้วย CMU Account</span>
                            <span class="btn-sub">สำหรับผู้ใช้งาน</span>
                        </span>
                    </a>
                    <a href="login_cmu.php?side=plan" class="btn-plan">
                        <span class="btn-logo">
                            <img src="logo.php?f=cmu" alt="CMU">
                        </span>
                        <span class="btn-text">
                            <span class="btn-title">เข้าสู่ระบบด้วย CMU Account</span>
                            <span class="btn-sub">สำหรับฝ่ายแผน</span>
                        </span>
                    </a>
                </div>

                <?php if ($hasSession) { ?>
                    <div class="login-footer">
                        <a href="User/page.php">ไปหน้าผู้ใช้งาน</a>
                        ·
                        <a href="Plan/page.php">ไปหน้าฝ่ายแผน</a>
                    </div>
                <?php } ?>

                <div class="login-footer">
                    &copy; <?php echo (int) date('Y') + 543; ?>
                    <strong>คณะเภสัชศาสตร์ มช.</strong><br>
                    Faculty of Pharmacy, Chiang Mai University
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var errorMessage = <?php echo json_encode($errorMessage, JSON_UNESCAPED_UNICODE); ?>;
            if (errorMessage) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: errorMessage,
                    confirmButtonColor: '#7b6fc0',
                });
            }
        });
    </script>
</body>

</html>
