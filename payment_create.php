<?php

session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

if ($booking_id <= 0) {
    die("Mã đơn hàng không hợp lệ.");
}

/*
|--------------------------------------------------------------------------
| 1. LẤY THÔNG TIN ĐƠN HÀNG
|--------------------------------------------------------------------------
*/

$sql_booking = "
    SELECT
        b.BookingID,
        b.UserID,
        b.TourID,
        b.Quantity,
        b.TotalPrice,
        b.Status,
        b.PaymentMethod,
        b.PaymentStatus,
        b.DepartureDate,
        b.CustomerName,
        b.Phone,
        t.TourName
    FROM bookings b
    INNER JOIN tours t
        ON b.TourID = t.TourID
    WHERE b.BookingID = ?
      AND b.UserID = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql_booking);

if (!$stmt) {
    die("Không thể chuẩn bị truy vấn.");
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $booking_id,
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$booking) {
    die("Không tìm thấy đơn hàng.");
}

/*
|--------------------------------------------------------------------------
| 2. CHỈ CHO PHÉP THANH TOÁN CHUYỂN KHOẢN
|--------------------------------------------------------------------------
*/

if (
    $booking['PaymentMethod'] !== 'Chuyển khoản'
    && $booking['PaymentMethod'] !== 'BANK_TRANSFER'
) {
    die("Đơn hàng này không sử dụng thanh toán chuyển khoản.");
}

/*
|--------------------------------------------------------------------------
| 3. KHÔNG CHO THANH TOÁN ĐƠN ĐÃ HỦY
|--------------------------------------------------------------------------
*/

if ($booking['Status'] === 'Đã hủy') {
    die("Đơn hàng đã bị hủy.");
}

if ($booking['PaymentStatus'] === 'Đã thanh toán') {
    header('Location: my_bookings.php');
    exit();
}

/*
|--------------------------------------------------------------------------
| 4. LẤY TÀI KHOẢN NGÂN HÀNG
|--------------------------------------------------------------------------
*/

$sql_account = "
    SELECT
        pa.PaymentAccountID,
        pa.AccountName,
        pa.AccountNumber,
        pa.BankName,
        pa.BankCode
    FROM payment_accounts pa
    INNER JOIN payment_methods pm
        ON pa.PaymentMethodID = pm.PaymentMethodID
    WHERE pm.Code = 'BANK_TRANSFER'
      AND pm.IsActive = 1
      AND pa.IsActive = 1
      AND pa.IsDefault = 1
    LIMIT 1
";

$result_account = mysqli_query($conn, $sql_account);

if (!$result_account) {
    die("Không thể lấy thông tin tài khoản thanh toán.");
}

$account = mysqli_fetch_assoc($result_account);

if (!$account) {
    die("Chưa cấu hình tài khoản ngân hàng nhận tiền.");
}

/*
|--------------------------------------------------------------------------
| 5. LẤY SỐ TIỀN
|--------------------------------------------------------------------------
*/

$amount = (float) $booking['TotalPrice'];

if ($amount <= 0) {
    die("Số tiền thanh toán không hợp lệ.");
}

/*
|--------------------------------------------------------------------------
| 6. TÌM PAYMENT PENDING ĐÃ TẠO
|--------------------------------------------------------------------------
*/

$payment_method_id = 2;

$sql_existing = "
    SELECT
        PaymentID,
        OrderCode,
        Amount,
        PaymentStatus,
        QRContent
    FROM payments
    WHERE BookingID = ?
      AND PaymentMethodID = ?
      AND PaymentStatus = 'Pending'
    ORDER BY PaymentID DESC
    LIMIT 1
";

$stmt_existing = mysqli_prepare($conn, $sql_existing);

if (!$stmt_existing) {
    die("Không thể kiểm tra giao dịch.");
}

mysqli_stmt_bind_param(
    $stmt_existing,
    "ii",
    $booking_id,
    $payment_method_id
);

mysqli_stmt_execute($stmt_existing);

$result_existing = mysqli_stmt_get_result($stmt_existing);
$existing_payment = mysqli_fetch_assoc($result_existing);

mysqli_stmt_close($stmt_existing);

/*
|--------------------------------------------------------------------------
| 7. NẾU ĐÃ CÓ PAYMENT THÌ DÙNG LẠI
|--------------------------------------------------------------------------
*/

if ($existing_payment) {

    $payment_id = (int) $existing_payment['PaymentID'];
    $order_code = $existing_payment['OrderCode'];
    $qr_url = $existing_payment['QRContent'];

} else {

    /*
    |--------------------------------------------------------------------------
    | 8. TẠO MÃ ĐƠN
    |--------------------------------------------------------------------------
    */

    $order_code =
        'MT'
        . date('YmdHis')
        . str_pad(
            (string)$booking_id,
            6,
            '0',
            STR_PAD_LEFT
        );

    /*
    |--------------------------------------------------------------------------
    | 9. TẠO QR VIETQR
    |--------------------------------------------------------------------------
    */

    $qr_url =
        "https://img.vietqr.io/image/"
        . urlencode($account['BankCode'])
        . "-"
        . urlencode($account['AccountNumber'])
        . "-compact2.png"
        . "?amount="
        . (int)$amount
        . "&addInfo="
        . urlencode($order_code)
        . "&accountName="
        . urlencode($account['AccountName']);

    /*
    |--------------------------------------------------------------------------
    | 10. LƯU GIAO DỊCH
    |--------------------------------------------------------------------------
    */

    $sql_payment = "
        INSERT INTO payments
        (
            BookingID,
            PaymentMethodID,
            PaymentAccountID,
            OrderCode,
            Amount,
            PaymentStatus,
            QRContent,
            CreatedAt,
            UpdatedAt
        )
        VALUES
        (?, ?, ?, ?, ?, 'Pending', ?, NOW(), NOW())
    ";

    $stmt_payment = mysqli_prepare($conn, $sql_payment);

    if (!$stmt_payment) {
        die("Không thể tạo giao dịch thanh toán.");
    }

    $payment_account_id = (int)$account['PaymentAccountID'];

    mysqli_stmt_bind_param(
        $stmt_payment,
        "iiisds",
        $booking_id,
        $payment_method_id,
        $payment_account_id,
        $order_code,
        $amount,
        $qr_url
    );

    if (!mysqli_stmt_execute($stmt_payment)) {
        mysqli_stmt_close($stmt_payment);
        die("Không thể lưu giao dịch thanh toán.");
    }

    $payment_id = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt_payment);
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

    <title>Thanh toán - Miền Tây Travel</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    >

    <style>

        body {
            background-color: #f4f7f9;
            font-family: Arial, sans-serif;
        }

        .payment-card {
            max-width: 650px;
            margin: 50px auto;
            background: #ffffff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .qr-image {
            width: 350px;
            max-width: 100%;
        }

        .amount {
            color: #dc3545;
            font-size: 30px;
            font-weight: bold;
        }

        .bank-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
        }

    </style>

</head>

<body>

<div class="payment-card">

    <h3 class="text-center fw-bold mb-4">
        <i class="bi bi-credit-card text-primary"></i>
        Thanh toán đơn hàng
    </h3>

    <div class="text-center mb-4">

        <div class="text-muted">
            Mã đơn hàng
        </div>

        <div class="fw-bold fs-5">
            <?= htmlspecialchars($order_code) ?>
        </div>

        <div class="amount mt-2">
            <?= number_format($amount, 0, ',', '.') ?>đ
        </div>

    </div>

    <div class="text-center mb-4">

        <img
            src="<?= htmlspecialchars($qr_url) ?>"
            alt="QR thanh toán"
            class="qr-image"
        >

    </div>

    <div class="bank-info">

        <h5 class="fw-bold mb-3">
            Thông tin nhận tiền
        </h5>

        <p class="mb-2">
            <strong>Ngân hàng:</strong>
            <?= htmlspecialchars($account['BankName']) ?>
        </p>

        <p class="mb-2">
            <strong>Số tài khoản:</strong>
            <?= htmlspecialchars($account['AccountNumber']) ?>
        </p>

        <p class="mb-2">
            <strong>Chủ tài khoản:</strong>
            <?= htmlspecialchars($account['AccountName']) ?>
        </p>

        <p class="mb-0">
            <strong>Nội dung chuyển khoản:</strong>

            <span class="text-primary fw-bold">
                <?= htmlspecialchars($order_code) ?>
            </span>
        </p>

    </div>

    <div class="alert alert-warning mt-4">

        <i class="bi bi-info-circle me-1"></i>

        Vui lòng chuyển đúng số tiền và đúng nội dung chuyển khoản.

    </div>

    <a
        href="my_bookings.php"
        class="btn btn-primary w-100 rounded-pill"
    >
        Tôi đã chuyển khoản
    </a>

</div>

</body>
</html>