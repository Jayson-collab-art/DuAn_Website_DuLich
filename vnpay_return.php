<?php
session_start();
include 'db.php';
require_once 'config_vnpay.php';

$params = $_GET;
if (!vnpay_is_configured()) die('VNPAY chưa cấu hình.');

$signature_valid = vnpay_verify_response($params);
$code = $params['vnp_ResponseCode'] ?? '99';
$txn_ref = $params['vnp_TxnRef'] ?? '';
$vnp_amount = isset($params['vnp_Amount']) ? (float)$params['vnp_Amount'] / 100 : -1;
$vnp_transaction_no = $params['vnp_TransactionNo'] ?? '';
$vnp_bank_code = $params['vnp_BankCode'] ?? '';

$payment = null;

if ($signature_valid && $txn_ref !== '') {
    // Lấy thông tin có bao gồm cột HotelBookingID
    $sql = "SELECT PaymentID, BookingID, FlightBookingID, HotelBookingID, Amount, PaymentStatus FROM payments WHERE OrderCode = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $txn_ref);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $payment = mysqli_fetch_assoc($result);
    
    if ($payment) {
        $is_success = ($code === '00');
        $payment_status = $is_success ? 'Paid' : 'Failed';
        $paid_at = $is_success ? date('Y-m-d H:i:s') : null;

        // Cập nhật trạng thái payment
        $sql_update_payment = "UPDATE payments SET PaymentStatus = ?, TransactionCode = ?, BankCode = ?, PaidAt = ?, UpdatedAt = NOW() WHERE PaymentID = ? AND PaymentStatus <> 'Paid'";
        $stmt_u = mysqli_prepare($conn, $sql_update_payment);
        mysqli_stmt_bind_param($stmt_u, 'ssssi', $payment_status, $vnp_transaction_no, $vnp_bank_code, $paid_at, $payment['PaymentID']);
        mysqli_stmt_execute($stmt_u);

        // Cập nhật trạng thái vào đúng bảng gốc
        if ($is_success) {
            if (!empty($payment['HotelBookingID'])) {
                // Đơn Khách sạn
                $sql_hotel = "UPDATE hotel_bookings SET PaymentStatus = 'Paid' WHERE HotelBookingID = ?";
                $stmt_h = mysqli_prepare($conn, $sql_hotel);
                mysqli_stmt_bind_param($stmt_h, 'i', $payment['HotelBookingID']);
                mysqli_stmt_execute($stmt_h);
            } elseif (!empty($payment['FlightBookingID'])) {
                // Đơn Máy bay
                $sql_flight = "UPDATE flight_bookings SET PaymentStatus = 'Paid' WHERE FlightBookingID = ?";
                $stmt_f = mysqli_prepare($conn, $sql_flight);
                mysqli_stmt_bind_param($stmt_f, 'i', $payment['FlightBookingID']);
                mysqli_stmt_execute($stmt_f);
            } elseif (!empty($payment['BookingID'])) {
                // Đơn Tour
                $sql_booking = "UPDATE bookings SET PaymentStatus = 'Đã thanh toán' WHERE BookingID = ?";
                $stmt_b = mysqli_prepare($conn, $sql_booking);
                mysqli_stmt_bind_param($stmt_b, 'i', $payment['BookingID']);
                mysqli_stmt_execute($stmt_b);
            }
        }
    }
}
$is_success = ($signature_valid && $payment && $code === '00');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả thanh toán - Miền Tây Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card border-0 shadow-sm rounded-4 mx-auto p-4 text-center" style="max-width:650px;">
        <?php if ($is_success): ?>
            <h2 class="text-success fw-bold">✓ Giao dịch thành công</h2>
            <p>Ngân hàng: <?= htmlspecialchars($vnp_bank_code) ?> - Số tiền: <?= number_format($vnp_amount, 0, ',', '.') ?>đ</p>
            <p class="text-muted">Đơn hàng của bạn đã được cập nhật trạng thái.</p>
        <?php else: ?>
            <h2 class="text-danger fw-bold">✕ Giao dịch thất bại</h2>
            <p>Mã lỗi: <?= htmlspecialchars($code) ?></p>
        <?php endif; ?>
        <a href="index.php" class="btn btn-primary mt-3 px-5 rounded-pill">Về trang chủ</a>
    </div>
</div>
</body>
</html>