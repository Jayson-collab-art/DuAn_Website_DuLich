<?php

include 'db.php';
require_once 'config_vnpay.php';

header('Content-Type: application/json; charset=utf-8');

function ipn_response(string $code, string $message): void
{
    echo json_encode([
        'RspCode' => $code,
        'Message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!vnpay_is_configured()) {
    ipn_response('99', 'VNPAY chưa được cấu hình');
}

$params = $_GET;

/* 1. Kiểm tra chữ ký ngay từ đầu */
if (!vnpay_verify_response($params)) {
    ipn_response('97', 'Fail checksum');
}

$order_code = trim($params['vnp_TxnRef'] ?? '');
$response_code = $params['vnp_ResponseCode'] ?? '';
$transaction_status = $params['vnp_TransactionStatus'] ?? '';
$vnp_amount = isset($params['vnp_Amount'])
    ? (float)$params['vnp_Amount'] / 100
    : -1;
$vnp_transaction_no = $params['vnp_TransactionNo'] ?? '';
$vnpay_bank_code = $params['vnp_BankCode'] ?? '';

if ($order_code === '') {
    ipn_response('01', 'Order not found');
}

/* 2. Lấy payment */
$sql = "
    SELECT
        p.PaymentID,
        p.BookingID,
        p.Amount,
        p.PaymentStatus
    FROM payments p
    WHERE p.OrderCode = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    ipn_response('99', 'Database error');
}

mysqli_stmt_bind_param($stmt, 's', $order_code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$payment) {
    ipn_response('01', 'Order not found');
}

/* 3. Kiểm tra số tiền */
if (abs((float)$payment['Amount'] - $vnp_amount) > 0.01) {
    ipn_response('04', 'Invalid amount');
}

/* 4. Idempotency: đã Paid thì xác nhận thành công ngay */
if ($payment['PaymentStatus'] === 'Paid') {
    ipn_response('00', 'Confirm Success');
}

$is_success = ($response_code === '00' && $transaction_status === '00');
$new_payment_status = $is_success ? 'Paid' : 'Failed';
$new_booking_payment_status = $is_success ? 'Đã thanh toán' : 'Chưa thanh toán';
$paid_at = null;
if ($is_success) {
    $vnp_pay_date = $params['vnp_PayDate'] ?? null;
    if (!empty($vnp_pay_date) && strlen($vnp_pay_date) === 14) {
        $y = substr($vnp_pay_date, 0, 4);
        $m = substr($vnp_pay_date, 4, 2);
        $d = substr($vnp_pay_date, 6, 2);
        $h = substr($vnp_pay_date, 8, 2);
        $i = substr($vnp_pay_date, 10, 2);
        $s = substr($vnp_pay_date, 12, 2);
        $paid_at = "$y-$m-$d $h:$i:$s";
    } else {
        $paid_at = date('Y-m-d H:i:s');
    }
}
$vnp_order_info = $params['vnp_OrderInfo'] ?? '';

mysqli_begin_transaction($conn);

try {
    $sql_update_payment = "
        UPDATE payments
        SET
            PaymentStatus = ?,
            TransactionCode = ?,
            ProviderTransactionID = ?,
            BankCode = ?,
            TransactionInfo = ?,
            PaidAt = ?,
            UpdatedAt = NOW()
        WHERE PaymentID = ?
          AND PaymentStatus <> 'Paid'
    ";

    $stmt = mysqli_prepare($conn, $sql_update_payment);
    mysqli_stmt_bind_param(
        $stmt,
        'ssssssi',
        $new_payment_status,
        $vnp_transaction_no,
        $vnp_transaction_no,
        $vnpay_bank_code,
        $vnp_order_info,
        $paid_at,
        $payment['PaymentID']
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($is_success) {
        $sql_update_booking = "
            UPDATE bookings
            SET PaymentStatus = 'Đã thanh toán'
            WHERE BookingID = ?
              AND PaymentStatus <> 'Đã thanh toán'
        ";

        $stmt = mysqli_prepare($conn, $sql_update_booking);
        mysqli_stmt_bind_param($stmt, 'i', $payment['BookingID']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    mysqli_commit($conn);
    ipn_response('00', 'Confirm Success');

} catch (Throwable $e) {
    mysqli_rollback($conn);
    ipn_response('99', 'Database error');
}
