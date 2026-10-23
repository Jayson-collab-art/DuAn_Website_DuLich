<?php

session_start();
include 'db.php';

// Nếu đã đăng nhập Admin rồi thì chuyển thẳng vào Dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = mysqli_real_escape_string(
        $conn,
        $_POST['username'] ?? ''
    );

    $password = $_POST['password'] ?? '';

    // Truy vấn tài khoản Admin
    $sql = "SELECT * FROM admins WHERE Username='$username'";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {

        $lock_until = $row['LockUntil'];

        // Kiểm tra tài khoản có đang bị khóa không
        if ($lock_until && strtotime($lock_until) > time()) {

            $remaining_minutes = ceil(
                (strtotime($lock_until) - time()) / 60
            );

            $error = "Tài khoản Admin đang bị khóa do nhập sai quá 5 lần. Thử lại sau $remaining_minutes phút!";

        } else {

            // Kiểm tra mật khẩu
            if (password_verify($password, $row['Password'])) {

                // Đăng nhập thành công -> reset số lần sai
                mysqli_query(
                    $conn,
                    "UPDATE admins
                     SET FailedAttempts = 0,
                         LockUntil = NULL
                     WHERE AdminID = " . (int)$row['AdminID']
                );

                session_regenerate_id(true);

                $_SESSION['admin_id'] = $row['AdminID'];
                $_SESSION['admin_user'] = $row['Username'];

                header("Location: admin_dashboard.php");
                exit();

            } else {

                // Sai mật khẩu -> tăng số lần sai
                $attempts = (int)$row['FailedAttempts'] + 1;

                if ($attempts >= 5) {

                    // Khóa tài khoản 10 phút
                    $lock_time = date(
                        'Y-m-d H:i:s',
                        time() + 600
                    );

                    mysqli_query(
                        $conn,
                        "UPDATE admins
                         SET FailedAttempts = $attempts,
                             LockUntil = '$lock_time'
                         WHERE AdminID = " . (int)$row['AdminID']
                    );

                    $error = "Bạn đã nhập sai 5 lần liên tiếp. Tài khoản Admin đã bị khóa trong 10 phút!";

                } else {

                    mysqli_query(
                        $conn,
                        "UPDATE admins
                         SET FailedAttempts = $attempts
                         WHERE AdminID = " . (int)$row['AdminID']
                    );

                    $remaining_turns = 5 - $attempts;

                    $error = "Sai mật khẩu quản trị! Bạn còn $remaining_turns lần thử trước khi bị khóa.";
                }
            }
        }

    } else {

        $error = "Tài khoản quản trị không tồn tại!";
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login - Miền Tây Travel</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Google Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Google Identity Services -->
    <script
        src="https://accounts.google.com/gsi/client"
        async
        defer
    ></script>

    <style>

        body {
            background-color: #f4f7f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Lexend', sans-serif;
            padding: 20px;
        }

        .login-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 520px;
        }

        .btn-primary {
            background-color: #0d6efd;
            border: none;
            font-weight: bold;
            padding: 10px;
            height: 46px;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
        }

        .form-control {
            background-color: #F8FAFC;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 50rem !important;
            height: 46px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            background-color: #ffffff;
        }

        .password-container {
            position: relative;
        }

        .password-container .form-control {
            padding-right: 45px;
        }

        #togglePassword {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            font-size: 1.1rem;
            z-index: 10;
        }

        #togglePassword:hover {
            color: #0d6efd;
        }

        .divider {
            display: flex;
            align-items: center;
            width: 100%;
            margin: 14px 0;
        }

        .divider hr {
            flex: 1;
            margin: 0;
            border: 0;
            border-top: 1px solid #ced4da;
        }

        .divider span {
            margin: 0 18px;
            color: #6c757d;
            font-size: 14px;
            white-space: nowrap;
        }

        .google-login-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 46px;
        }

        #googleButton {
            width: 400px;
            max-width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #googleButton > div {
            width: 400px !important;
            max-width: 100% !important;
            margin: 0 auto !important;
        }

        #googleButton iframe {
            max-width: 100% !important;
        }

        .home-link {
            transition: 0.2s;
        }

        .home-link:hover {
            color: #0d6efd !important;
        }

        @media (max-width: 576px) {

            .login-card {
                padding: 30px 24px;
                max-width: 100%;
            }

            #googleButton {
                width: 100%;
            }

            #googleButton > div {
                width: 100% !important;
            }
        }

    </style>

</head>

<body>

<div class="login-card">

    <h3 class="text-center fw-bold text-primary mb-4">
        ADMIN LOGIN
    </h3>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger py-2 small text-center fw-bold">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>


    <!-- Đăng nhập Admin -->
    <form method="POST" action="">

        <div class="mb-3">

            <label class="form-label small fw-bold">
                Tên đăng nhập
            </label>

            <input
                type="text"
                name="username"
                class="form-control"
                autocomplete="username"
                required
            >

        </div>


        <div class="mb-4">

            <label class="form-label small fw-bold">
                Mật khẩu
            </label>

            <div class="password-container">

                <input
                    type="password"
                    name="password"
                    id="passwordInput"
                    class="form-control"
                    autocomplete="current-password"
                    required
                >

                <i
                    class="bi bi-eye-slash"
                    id="togglePassword"
                    title="Hiện/Ẩn mật khẩu"
                ></i>

            </div>

        </div>


        <button
            type="submit"
            class="btn btn-primary w-100 rounded-pill mb-3"
        >
            ĐĂNG NHẬP
        </button>

    </form>


    <!-- Đăng nhập Google -->
    <div class="divider">

        <hr>

        <span>Hoặc đăng nhập bằng</span>

        <hr>

    </div>


    <div class="google-login-wrapper mb-3">

        <div id="googleButton"></div>

    </div>


    <!-- Form nhận credential từ Google -->
    <form
        id="google-admin-login-form"
        action="google_admin_login_handle.php"
        method="POST"
        style="display: none;"
    >

        <input
            type="hidden"
            name="credential"
            id="google-admin-credential"
        >

    </form>


    <div class="text-center small mt-3">

        <a
            href="index.php"
            class="text-muted text-decoration-none home-link"
        >
            &larr; Quay về trang chủ
        </a>

    </div>

</div>


<script>

    // Hiện / ẩn mật khẩu
    const togglePassword =
        document.getElementById('togglePassword');

    const passwordInput =
        document.getElementById('passwordInput');

    if (togglePassword && passwordInput) {

        togglePassword.addEventListener('click', function () {

            const type =
                passwordInput.getAttribute('type') === 'password'
                    ? 'text'
                    : 'password';

            passwordInput.setAttribute('type', type);

            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

    }


    // Khởi tạo Google Login
    function setupGoogleLogin() {

        if (
            !window.google ||
            !google.accounts ||
            !google.accounts.id
        ) {
            return;
        }

        const googleButton =
            document.getElementById('googleButton');

        const loginForm =
            document.getElementById(
                'google-admin-login-form'
            );

        const credentialInput =
            document.getElementById(
                'google-admin-credential'
            );

        if (!googleButton || !loginForm || !credentialInput) {
            return;
        }

        google.accounts.id.initialize({

            client_id:
                '564772374968-t4knfo6heucsmv84pno2vfk7lcfovq13.apps.googleusercontent.com',

            context: 'signin',

            ux_mode: 'popup',

            auto_select: false,

            callback: function(response) {

                if (
                    !response ||
                    !response.credential
                ) {

                    alert(
                        'Không nhận được thông tin xác thực từ Google.'
                    );

                    return;
                }

                credentialInput.value =
                    response.credential;

                loginForm.submit();
            }

        });


        // Google cho phép nút chuẩn tối đa 400px
        google.accounts.id.renderButton(
            googleButton,
            {
                type: 'standard',
                theme: 'outline',
                size: 'large',
                shape: 'pill',
                text: 'signin_with',
                logo_alignment: 'left',
                width: 400
            }
        );

    }


    // Chờ Google Identity Services tải xong
    function waitForGoogle() {

        if (
            window.google &&
            google.accounts &&
            google.accounts.id
        ) {

            setupGoogleLogin();

        } else {

            setTimeout(waitForGoogle, 100);

        }

    }

    waitForGoogle();

</script>

</body>

</html>