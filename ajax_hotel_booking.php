<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_hotel') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Vui lòng đăng nhập để đặt phòng!']);
        exit();
    }

    $user_id = (int)$_SESSION['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $hotel_name = mysqli_real_escape_string($conn, $_POST['hotel_name']);
    $destination = mysqli_real_escape_string($conn, $_POST['destination']);
    $check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($conn, $_POST['check_out']);
    $rooms = (int)$_POST['rooms'];
    $guests = (int)$_POST['guests'];
    
    $price_str = $_POST['price'] ?? '0';
    $price = (float)preg_replace('/[^0-9]/', '', $price_str);

    // Tạo mã đặt phòng ngẫu nhiên (vd: HTL-A1B2C3)
    $booking_code = 'HTL-' . strtoupper(substr(md5(uniqid()), 0, 6));

    $sql_insert = "INSERT INTO hotel_bookings (UserID, CustomerName, Phone, HotelName, Destination, CheckInDate, CheckOutDate, Rooms, Guests, TotalPrice, BookingCode, PaymentStatus) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
                   
    $stmt = mysqli_prepare($conn, $sql_insert);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "issssssiids", $user_id, $name, $phone, $hotel_name, $destination, $check_in, $check_out, $rooms, $guests, $price, $booking_code);
        
        if (mysqli_stmt_execute($stmt)) {
            $hotel_id = mysqli_insert_id($conn);
            echo json_encode([
                'status' => 'success', 
                'redirect' => "vnpay_create.php?hotel_id=" . $hotel_id
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