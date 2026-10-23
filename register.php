<?php
session_start();
include 'db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // ── VALIDATE DỮ LIỆU ĐẦU VÀO ──
    if (empty($fullname) || empty($email) || empty($username) || empty($password)) {
        $error = "Vui lòng điền đầy đủ tất cả các trường!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Địa chỉ Email không hợp lệ!";
    } elseif (mb_strlen($username, 'UTF-8') < 3 || mb_strlen($username, 'UTF-8') > 50) {
        $error = "Tên đăng nhập phải từ 3 đến 50 ký tự!";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới!";
    } elseif (mb_strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự!";
    } else {
        // SỬ DỤNG PREPARED STATEMENT ĐỂ CHỐNG SQL INJECTION
        $sql_check = "SELECT UserID FROM Users WHERE Username = ? OR Email = ? LIMIT 1";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            $error = "Tên đăng nhập hoặc Email đã được sử dụng!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql_insert = "INSERT INTO Users (FullName, Email, Username, Password) VALUES (?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "ssss", $fullname, $email, $username, $hashed_password);

            if (mysqli_stmt_execute($stmt_insert)) {
                $success = "Đăng ký thành công! Vui lòng đăng nhập.";
            } else {
                // Không tiết lộ lỗi nội bộ MySQL cho người dùng
                $error = "Có lỗi xảy ra khi đăng ký. Vui lòng thử lại sau!";
            }
            mysqli_stmt_close($stmt_insert);
        }
        mysqli_stmt_close($stmt_check);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký - Miền Tây Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- Sử dụng phông chữ giống login_user.php -->
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6; /* Nền giống login_user.php */
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Lexend', sans-serif; /* Phông chữ đồng bộ */
            padding: 20px;
        }
        .register-card {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); /* Bóng đổ giống login_user.php */
            width: 100%;
            max-width: 450px;
        }
        /* Thay đổi màu sắc thành xanh dương giống login_user.php */
        .text-primary-custom {
            color: #0d6efd !important; /* Xanh dương Bootstrap mặc định */
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
        /* Style cho input giống login_user.php */
        .form-control {
            background-color: #F8FAFC;
            border: none;
            border-radius: 50rem !important; /* Bo tròn giống login_user.php */
            padding: 0.75rem 1.25rem;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            background-color: #fff;
        }
        .password-hint {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<div class="register-card">
    <h3 class="text-center fw-bold text-primary-custom mb-4 text-uppercase">ĐĂNG KÝ</h3>
    
    <?php if($error != ''): ?>
        <div class="alert alert-danger py-2 small text-center fw-bold"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if($success != ''): ?>
        <div class="alert alert-success py-2 small text-center fw-bold"><?php echo htmlspecialchars($success); ?></div>
        <!-- Chuyển hướng về trang đăng nhập sau 2 giây nếu thành công -->
        <script>
            setTimeout(function(){
                window.location.href = 'login_user.php';
            }, 2000);
        </script>
    <?php endif; ?>

    <form method="POST" action="" id="registerForm">
        <div class="mb-3">
            <label class="form-label small fw-bold">Họ và tên</label>
            <input type="text" name="fullname" class="form-control" required 
                   value="<?php echo htmlspecialchars($fullname ?? ''); ?>"
                   maxlength="100">
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold">Email</label>
            <input type="email" name="email" class="form-control" required
                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                   maxlength="100">
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold">Tên đăng nhập</label>
            <input type="text" name="username" class="form-control" required
                   value="<?php echo htmlspecialchars($username ?? ''); ?>"
                   pattern="[a-zA-Z0-9_]+" title="Chỉ chấp nhận chữ cái, số và dấu _"
                   minlength="3" maxlength="50">
        </div>

        <div class="mb-4">
            <label class="form-label small fw-bold">Mật khẩu</label>
            <input type="password" name="password" class="form-control" required minlength="6">
            <div class="password-hint">Tối thiểu 6 ký tự</div>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 rounded-pill mb-3 text-white">ĐĂNG KÝ</button>
        
        <div class="text-center small">
            Đã có tài khoản? <a href="login_user.php" class="text-primary-custom text-decoration-none fw-bold">Đăng nhập ngay</a>
        </div>
    </form>
</div>

</body>
</html>