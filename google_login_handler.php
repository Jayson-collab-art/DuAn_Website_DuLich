<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    $credential = $_POST['credential'];

    // 1. Giải mã chuỗi JWT từ Google
    $parts = explode('.', $credential);
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

    if ($payload && isset($payload['email'])) {
        $google_email = $payload['email'];
        // Lấy tên từ Google, bỏ dấu cách để tạo Username tạm (vì DB của bạn dùng Username)
        $google_name = $payload['name']; 
        $username_temp = strtolower(str_replace(' ', '', $google_name)) . rand(100,999);

        // 2. Kiểm tra xem email này đã có trong bảng Users chưa
        $stmt = $conn->prepare("SELECT * FROM Users WHERE Email = ?");
        $stmt->bind_param("s", $google_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // ĐÃ CÓ TÀI KHOẢN -> Cho đăng nhập luôn
            $user = $result->fetch_assoc();
            
            // Xóa đếm số lần sai mật khẩu (nếu có) vì đã đăng nhập thành công bằng Google
            mysqli_query($conn, "UPDATE Users SET FailedAttempts = 0, LockUntil = NULL WHERE UserID = " . $user['UserID']);

            $_SESSION['user_id'] = $user['UserID']; 
            $_SESSION['user'] = $user['Username']; // Lưu session giống với form thường
            
            echo "<script>window.location.href='index.php';</script>";
        } else {
            // CHƯA CÓ TÀI KHOẢN -> Tự động đăng ký mới
            // Tạo mật khẩu ngẫu nhiên băm (hash) giống chuẩn bảo mật của bạn
            $random_password = password_hash(uniqid(), PASSWORD_DEFAULT); 
            
            // Thêm user mới vào DB
            $insert_stmt = $conn->prepare("INSERT INTO Users (Username, Email, Password) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $username_temp, $google_email, $random_password);
            
            if ($insert_stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['user'] = $username_temp;
                echo "<script>alert('Chào mừng bạn mới! Tài khoản đã được tự động tạo qua Google.'); window.location.href='index.php';</script>";
            } else {
                echo "<script>alert('Lỗi tạo tài khoản: " . $conn->error . "'); window.location.href='login_user.php';</script>";
            }
        }
    } else {
        echo "<script>alert('Lỗi: Không lấy được thông tin từ Google!'); window.location.href='login_user.php';</script>";
    }
} else {
    header("Location: login_user.php");
    exit();
}
?>