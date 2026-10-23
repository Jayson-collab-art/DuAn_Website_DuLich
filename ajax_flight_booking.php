<?php
session_start();
include 'db.php'; // Đảm bảo gọi đúng file kết nối CSDL của bạn

// Báo cho trình duyệt biết file này chỉ trả về dữ liệu chuẩn JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_flight') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Vui lòng đăng nhập để đặt vé!']);
        exit();
    }

    $user_id = (int)$_SESSION['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $airline = mysqli_real_escape_string($conn, $_POST['airline']);
    $pnr = mysqli_real_escape_string($conn, $_POST['pnr']);
    $details = mysqli_real_escape_string($conn, $_POST['details']); // Chứa chặng bay, ngày giờ
    
    // Xử lý tiền: Lọc số (VD: 2.800.000đ -> 2800000)
    $price_str = $_POST['price'] ?? '0';
    $price = (float)preg_replace('/[^0-9]/', '', $price_str);

    // Lưu vào bảng flight_bookings (Đã thêm Passengers và SeatClass để không bị lỗi MySQL)
    $sql_insert = "INSERT INTO flight_bookings (UserID, CustomerName, Phone, Airline, FlightRoute, DepartDate, FlightTime, Passengers, SeatClass, TotalPrice, PNR_Code, PaymentStatus) 
                   VALUES (?, ?, ?, ?, ?, NOW(), '00:00', 1, 'Economy', ?, ?, 'Pending')";
                   
    $stmt = mysqli_prepare($conn, $sql_insert);
    
    if ($stmt) {
        // Tham số: 1 số nguyên (i), 4 chuỗi (s), 1 số thập phân (d), 1 chuỗi (s) -> 'issssds'
        mysqli_stmt_bind_param($stmt, "issssds", $user_id, $name, $phone, $airline, $details, $price, $pnr);
        
        if (mysqli_stmt_execute($stmt)) {
            $flight_id = mysqli_insert_id($conn);
            // Trả JSON chuẩn thông báo thành công và kèm link chuyển hướng VNPay
            echo json_encode([
                'status' => 'success', 
                'redirect' => "vnpay_create.php?flight_id=" . $flight_id
            ]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Lỗi DB: ' . mysqli_error($conn)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi truy vấn SQL']);
    }
    exit();
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Yêu cầu không hợp lệ']);
}
?>