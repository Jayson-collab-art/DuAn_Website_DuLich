<?php
$servername = "localhost"; // Nếu code cũ ghi là "db", "mysql" hoặc getenv(...) thì đổi lại thành "localhost"
$username = "root";        // Tài khoản mặc định của XAMPP
$password = "";            // Mật khẩu mặc định của XAMPP là rỗng (không có gì bên trong)
$dbname = "web_du_lich";   // Phải trùng khớp với tên CSDL trong phpMyAdmin của bạn

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>