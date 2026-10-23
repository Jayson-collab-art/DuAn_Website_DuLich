<?php
session_start();
include 'db.php';
require_once 'config_vnpay.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login_user.php');
    exit();
}

if (!vnpay_is_configured()) {
    die('VNPAY chưa được cấu hình. Hãy kiểm tra config_vnpay.php.');
}

$user_id = (int) $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
$flight_id = isset($_GET['flight_id']) ? (int) $_GET['flight_id'] : 0;
$hotel_id = isset($_GET['hotel_id']) ? (int) $_GET['hotel_id'] : 0;

if ($booking_id <= 0 && $flight_id <= 0 && $hotel_id <= 0) {
    die('Mã đơn hàng không hợp lệ.');
}

$amount = 0;
$order_info = '';

// Xử lý đơn Khách sạn
if ($hotel_id > 0) {
    $sql = "SELECT TotalPrice, PaymentStatus FROM hotel_bookings WHERE HotelBookingID = ? AND UserID = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $hotel_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($result);

    if (!$booking) die('Không tìm thấy đơn đặt phòng.');
    if ($booking['PaymentStatus'] === 'Paid') {
        header('Location: index.php'); exit(); 
    }
    $amount = (float)$booking['TotalPrice'];
    $order_info = 'Thanh toan Khach san ID: ' . $hotel_id;
}
// Xử lý đơn Vé máy bay
elseif ($flight_id > 0) {
    $sql = "SELECT TotalPrice, PaymentStatus FROM flight_bookings WHERE FlightBookingID = ? AND UserID = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $flight_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($result);

    if (!$booking) die('Không tìm thấy đơn Vé máy bay.');
    if ($booking['PaymentStatus'] === 'Paid') {
        header('Location: my_bookings.php'); exit();
    }
    $amount = (float)$booking['TotalPrice'];
    $order_info = 'Thanh toan Ve may bay ID: ' . $flight_id;
}
// Xử lý đơn Tour
else {
    $sql = "SELECT TotalPrice, PaymentStatus, Status FROM bookings WHERE BookingID = ? AND UserID = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $booking_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($result);
    
    if (!$booking) die('Không tìm thấy đơn Tour.');
    if ($booking['Status'] === 'Đã hủy') die('Đơn hàng đã bị hủy.');
    if ($booking['PaymentStatus'] === 'Đã thanh toán') {
        header('Location: my_bookings.php'); exit();
    }
    $amount = (float)$booking['TotalPrice'];
    $order_info = 'Thanh toan don Tour ID: ' . $booking_id;
}

// Lấy ID phương thức VNPAY từ DB
$sql_method = "SELECT PaymentMethodID FROM payment_methods WHERE Code = 'VNPAY' LIMIT 1";
$result_method = mysqli_query($conn, $sql_method);
$method = mysqli_fetch_assoc($result_method);
$payment_method_id = $method ? (int)$method['PaymentMethodID'] : 1; 

// Tạo mã tham chiếu duy nhất
$order_code = 'MT' . date('YmdHis') . rand(1000, 9999);
$create_date = date('YmdHis');
$expire_date = date('YmdHis', time() + 15 * 60);

// Tham số gửi sang VNPAY
$vnp_params = [
    'vnp_Version'    => '2.1.0',
    'vnp_Command'    => 'pay',
    'vnp_TmnCode'    => VNPAY_TMN_CODE,
    'vnp_Amount'     => (string)round($amount * 100),
    'vnp_CreateDate' => $create_date,
    'vnp_CurrCode'   => 'VND',
    'vnp_IpAddr'     => vnpay_get_client_ip(),
    'vnp_Locale'     => 'vn',
    'vnp_OrderInfo'  => $order_info,
    'vnp_OrderType'  => 'other',
    'vnp_ReturnUrl'  => VNPAY_RETURN_URL,
    'vnp_TxnRef'     => $order_code,
    'vnp_ExpireDate' => $expire_date,
];

$vnpay_url = vnpay_build_url($vnp_params);

// Lưu vào bảng payments hỗ trợ cả 3 ID (BookingID, FlightBookingID, HotelBookingID)
$sql_payment = "INSERT INTO payments (BookingID, FlightBookingID, HotelBookingID, PaymentMethodID, OrderCode, Amount, PaymentStatus, PaymentUrl, ExpiresAt, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?, NOW())";
$stmt_payment = mysqli_prepare($conn, $sql_payment);

$b_id = ($booking_id > 0) ? $booking_id : NULL;
$f_id = ($flight_id > 0) ? $flight_id : NULL;
$h_id = ($hotel_id > 0) ? $hotel_id : NULL;
$expire_db_date = date('Y-m-d H:i:s', time() + 15 * 60);

// iiiisdss tương ứng với: 4 int, 1 string, 1 double, 2 string
mysqli_stmt_bind_param($stmt_payment, 'iiiisdss', $b_id, $f_id, $h_id, $payment_method_id, $order_code, $amount, $vnpay_url, $expire_db_date);
mysqli_stmt_execute($stmt_payment);

header('Location: ' . $vnpay_url);
exit();