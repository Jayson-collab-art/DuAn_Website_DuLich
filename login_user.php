<?php
session_start();
include 'db.php';

$error = '';


if (empty($_SESSION['google_login_csrf'])) {
    $_SESSION['google_login_csrf'] = bin2hex(random_bytes(32));
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'])) {
    $username_or_email = trim($_POST['username']);
    $password = $_POST['password'];

    // SỬ DỤNG PREPARED STATEMENT ĐỂ CHỐNG SQL INJECTION
    $sql = "SELECT * FROM Users WHERE Username = ? OR Email = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $username_or_email, $username_or_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $has_lock_column = array_key_exists('LockUntil', $row);
        $lock_until = $has_lock_column ? $row['LockUntil'] : null;
        
        if ($lock_until && strtotime($lock_until) > time()) {
            $remaining_minutes = ceil((strtotime($lock_until) - time()) / 60);
            $error = "Tài khoản đang bị khóa do nhập sai quá 5 lần. Vui lòng thử lại sau $remaining_minutes phút!";
        } else {
            if (password_verify($password, $row['Password'])) {
                if ($has_lock_column) {
                    $stmt_reset = mysqli_prepare($conn, "UPDATE Users SET FailedAttempts = 0, LockUntil = NULL WHERE UserID = ?");
                    mysqli_stmt_bind_param($stmt_reset, "i", $row['UserID']);
                    mysqli_stmt_execute($stmt_reset);
                    mysqli_stmt_close($stmt_reset);
                }
                
                // Tái tạo session ID chống Session Fixation
                session_regenerate_id(true);
                
                $_SESSION['user'] = $row['Username'];
                $_SESSION['user_id'] = (int)$row['UserID'];
                $_SESSION['user_name'] = !empty($row['FullName']) ? $row['FullName'] : $row['Username'];
                
                // Hỗ trợ redirect sau đăng nhập (ví dụ: từ trang đặt tour)
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                // Chỉ cho phép redirect nội bộ (chống Open Redirect)
                if (!preg_match('/^[a-zA-Z0-9_\-]+\.php/', $redirect)) {
                    $redirect = 'index.php';
                }
                header("Location: " . $redirect);
                exit();
            } else {
                if ($has_lock_column && array_key_exists('FailedAttempts', $row)) {
                    $attempts = (int)$row['FailedAttempts'] + 1;
                    $uid = (int)$row['UserID'];
                    if ($attempts >= 5) {
                        $lock_time = date('Y-m-d H:i:s', time() + 600);
                        $stmt_lock = mysqli_prepare($conn, "UPDATE Users SET FailedAttempts = ?, LockUntil = ? WHERE UserID = ?");
                        mysqli_stmt_bind_param($stmt_lock, "isi", $attempts, $lock_time, $uid);
                        mysqli_stmt_execute($stmt_lock);
                        mysqli_stmt_close($stmt_lock);
                        $error = "Bạn đã nhập sai 5 lần liên tiếp. Tài khoản đã bị khóa trong 10 phút!";
                    } else {
                        $stmt_fail = mysqli_prepare($conn, "UPDATE Users SET FailedAttempts = ? WHERE UserID = ?");
                        mysqli_stmt_bind_param($stmt_fail, "ii", $attempts, $uid);
                        mysqli_stmt_execute($stmt_fail);
                        mysqli_stmt_close($stmt_fail);
                        $remaining_turns = 5 - $attempts;
                        $error = "Mật khẩu không chính xác! Bạn còn $remaining_turns lần thử trước khi bị khóa.";
                    }
                } else {
                    $error = "Mật khẩu không chính xác!";
                }
            }
        }
    } else {
        $error = "Tên đăng nhập hoặc Email không tồn tại!";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Miền Tây Travel</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 450px;
        }
        .text-primary-custom {
            color: #0d6efd !important;
        }
        .btn-primary-custom {
            background-color: #0d6efd;
            border: none;
            font-weight: bold;
            padding: 10px;
        }
        .btn-primary-custom:hover {
            background-color: #0b5ed7;
        }
        .form-control {
            background-color: #f8f9fa;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 50rem !important;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            background-color: #fff;
        }
        /* Tinh chỉnh vị trí icon con mắt chìm trong ô input */
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
    </style>
</head>
<body>

<div class="login-card">
    <h3 class="text-center fw-bold text-primary-custom mb-4 text-uppercase">ĐĂNG NHẬP</h3>
    
    <?php if($error != ''): ?>
        <div class="alert alert-danger py-2 small text-center fw-bold"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- FORM ĐĂNG NHẬP THƯỜNG -->
    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label small fw-bold">Tên đăng nhập hoặc gmail</label>
            <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold">Mật khẩu</label>
            <div class="password-container">
                <input type="password" name="password" id="passwordInput" class="form-control" required>
                <i class="bi bi-eye-slash" id="togglePassword" title="Hiện/Ẩn mật khẩu"></i>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 small">
            <div></div>
            <a href="forgot_password.php" class="text-primary-custom text-decoration-none">Quên mật khẩu?</a>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 rounded-pill mb-3 text-white">ĐĂNG NHẬP</button>
    </form>

    <!-- ĐƯỜNG KẺ NGANG -->
    <div class="d-flex align-items-center my-3">
        <hr class="flex-grow-1">
        <span class="px-3 text-muted small">Hoặc đăng nhập bằng</span>
        <hr class="flex-grow-1">
    </div>

    <!-- KHU VỰC NÚT ĐĂNG NHẬP GOOGLE -->
    <div class="d-flex justify-content-center mb-3">
        <div id="g_id_onload"
             data-client_id="309156140127-3q48ipe4b089jtt06nockp76jhj8859n.apps.googleusercontent.com"
             data-context="signin"
             data-ux_mode="popup"
             data-callback="handleGoogleLogin"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-shape="pill"
             data-theme="outline"
             data-text="signin_with"
             data-size="large"
             data-logo_alignment="left">
        </div>
    </div>

    <!-- FORM ẨN ĐỂ CHUYỂN DỮ LIỆU GOOGLE VỀ PHP -->
    <form id="google-login-form" action="google_login_handler.php" method="POST" style="display: none;">
        <input type="hidden" name="credential" id="google-credential">
    </form>

    <div class="text-center small mt-4">
        Chưa có tài khoản? <a href="register.php" class="text-primary-custom text-decoration-none fw-bold">Đăng ký ngay</a>
    </div>
</div>

<!-- Thư viện API của Google -->
<script src="https://accounts.google.com/gsi/client" async defer></script>

<!-- Script xử lý chức năng -->
<script>
    // Xử lý icon con mắt
    const togglePassword = document.querySelector('#togglePassword');
    const passwordInput = document.querySelector('#passwordInput');

    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.classList.toggle('bi-eye');
        this.classList.toggle('bi-eye-slash');
    });

    // Hàm đón dữ liệu khi khách chọn tài khoản Google xong
    function handleGoogleLogin(response) {
        document.getElementById('google-credential').value = response.credential;
        document.getElementById('google-login-form').submit(); // Tự động submit qua file google_login_handler.php
    }
</script>

</body>
</html>