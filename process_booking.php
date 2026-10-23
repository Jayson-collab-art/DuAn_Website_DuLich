<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['btnBooking'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    $tour_id_for_redirect = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
    $redirect_url = $tour_id_for_redirect > 0 ? "login_user.php?redirect=" . urlencode("detail.php?id=" . $tour_id_for_redirect) : "login_user.php";
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='{$redirect_url}';</script>";
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$tour_id = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;
$cust_name = trim($_POST['customer_name'] ?? '');
$cust_phone = trim($_POST['customer_phone'] ?? '');
$dep_date = trim($_POST['departure_date'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? 'Tiền mặt');

if ($tour_id <= 0 || $quantity <= 0 || $cust_name === '' || !preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $cust_phone)) {
    echo "<script>alert('Thông tin không hợp lệ!'); window.history.back();</script>";
    exit();
}

$allowed_payment_methods = ['Tiền mặt', 'Chuyển khoản', 'VNPay'];
if (!in_array($payment_method, $allowed_payment_methods, true)) {
    echo "<script>alert('Phương thức thanh toán không hợp lệ!'); window.history.back();</script>";
    exit();
}

$date_obj = DateTime::createFromFormat('Y-m-d', $dep_date);
$today = new DateTime('today');
if (!$date_obj || $date_obj->format('Y-m-d') !== $dep_date || $date_obj < $today) {
    echo "<script>alert('Ngày khởi hành không hợp lệ hoặc đã qua!'); window.history.back();</script>";
    exit();
}

// BỔ SUNG: Lấy thêm cột IsFullPackage và Destination từ CSDL
$sql_tour = "SELECT TourID, Price, TourName, MaxPeople, IsFullPackage, Destination FROM tours WHERE TourID = ? LIMIT 1";
$stmt_tour = mysqli_prepare($conn, $sql_tour);
mysqli_stmt_bind_param($stmt_tour, "i", $tour_id);
mysqli_stmt_execute($stmt_tour);
$row_tour = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_tour));
mysqli_stmt_close($stmt_tour);

if (!$row_tour) {
    echo "<script>alert('Không tìm thấy tour!'); window.history.back();</script>";
    exit();
}

// Lấy thông tin phân loại Tour để dùng cho luồng Upsell
$is_full_package = isset($row_tour['IsFullPackage']) ? (int)$row_tour['IsFullPackage'] : 1;
$destination = $row_tour['Destination'] ?? '';

// Chống click đúp 2 lần
$submit_hash = md5($user_id . '_' . $tour_id . '_' . $quantity . '_' . $dep_date . '_' . $payment_method);
$now_time = microtime(true);
if (isset($_SESSION['last_booking_hash']) && $_SESSION['last_booking_hash'] === $submit_hash && ($now_time - ($_SESSION['last_booking_time'] ?? 0)) < 2.5) {
    echo "<script>alert('Đơn của bạn đang được xử lý!'); window.location.href = 'my_bookings.php';</script>";
    exit();
}
$_SESSION['last_booking_hash'] = $submit_hash;
$_SESSION['last_booking_time'] = $now_time;

$limit = (isset($row_tour['MaxPeople']) && (int)$row_tour['MaxPeople'] > 0) ? (int)$row_tour['MaxPeople'] : 10;
mysqli_begin_transaction($conn);

// Flash Sale
$fs_sql = "SELECT FlashSaleID, DiscountPrice, Quota, Sold FROM flash_sales WHERE TourID = ? AND SaleEnd > NOW() AND SaleStart <= NOW() AND status = 'Active' FOR UPDATE";
$stmt_fs = mysqli_prepare($conn, $fs_sql);
mysqli_stmt_bind_param($stmt_fs, "i", $tour_id);
mysqli_stmt_execute($stmt_fs);
$fs_res = mysqli_stmt_get_result($stmt_fs);

$price = (float)$row_tour['Price'];
$is_flash_sale = false;
$flash_sale_id = null;

if ($fs_res && mysqli_num_rows($fs_res) > 0) {
    $fs_row = mysqli_fetch_assoc($fs_res);
    $available_fs = (int)$fs_row['Quota'] - (int)$fs_row['Sold'];
    if ($quantity > $available_fs) {
        mysqli_rollback($conn);
        echo "<script>alert('Tour Giờ Chót chỉ còn {$available_fs} suất.'); window.history.back();</script>";
        exit();
    }
    $price = (float)$fs_row['DiscountPrice'];
    $is_flash_sale = true;
    $flash_sale_id = (int)$fs_row['FlashSaleID'];
}
mysqli_stmt_close($stmt_fs);

// Kiểm tra cộng dồn đơn chưa thanh toán
$sql_exist = "SELECT BookingID, Quantity FROM bookings WHERE UserID = ? AND TourID = ? AND DepartureDate = ? AND PaymentStatus = 'Chưa thanh toán' AND Status = 'Chờ xác nhận' LIMIT 1 FOR UPDATE";
$stmt_exist = mysqli_prepare($conn, $sql_exist);
mysqli_stmt_bind_param($stmt_exist, "iis", $user_id, $tour_id, $dep_date);
mysqli_stmt_execute($stmt_exist);
$existing_booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_exist));
mysqli_stmt_close($stmt_exist);

$is_merged = false;
$booking_id = 0;

if ($existing_booking) {
    $is_merged = true;
    $booking_id = (int)$existing_booking['BookingID'];
    $old_quantity = (int)$existing_booking['Quantity'];
    $final_quantity = $old_quantity + $quantity;

    $sql_check_other = "SELECT COALESCE(SUM(Quantity), 0) AS OtherBooked FROM bookings WHERE TourID = ? AND Status <> 'Đã hủy' AND BookingID <> ?";
    $stmt_check_other = mysqli_prepare($conn, $sql_check_other);
    mysqli_stmt_bind_param($stmt_check_other, "ii", $tour_id, $booking_id);
    mysqli_stmt_execute($stmt_check_other);
    $other_booked = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_check_other))['OtherBooked'] ?? 0);
    mysqli_stmt_close($stmt_check_other);

    if (($other_booked + $final_quantity) > $limit) {
        $slots_can_add = max(0, $limit - $other_booked - $old_quantity);
        mysqli_rollback($conn);
        echo "<script>alert('Chỉ có thể thêm tối đa {$slots_can_add} khách.'); window.history.back();</script>";
        exit();
    }

    $final_total_price = $price * $final_quantity;
    $sql_update = "UPDATE bookings SET Quantity = ?, TotalPrice = ?, CustomerName = ?, Phone = ?, PaymentMethod = ?, BookingDate = NOW() WHERE BookingID = ?";
    $stmt_up = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_up, "idsssi", $final_quantity, $final_total_price, $cust_name, $cust_phone, $payment_method, $booking_id);
    mysqli_stmt_execute($stmt_up);
    mysqli_stmt_close($stmt_up);

    $sql_cancel = "UPDATE payments SET PaymentStatus = 'Cancelled', UpdatedAt = NOW() WHERE BookingID = ? AND PaymentStatus = 'Pending'";
    $stmt_c = mysqli_prepare($conn, $sql_cancel);
    mysqli_stmt_bind_param($stmt_c, "i", $booking_id);
    mysqli_stmt_execute($stmt_c);
    mysqli_stmt_close($stmt_c);
} else {
    $sql_check_all = "SELECT COALESCE(SUM(Quantity), 0) AS TotalBooked FROM bookings WHERE TourID = ? AND Status <> 'Đã hủy'";
    $stmt_check_all = mysqli_prepare($conn, $sql_check_all);
    mysqli_stmt_bind_param($stmt_check_all, "i", $tour_id);
    mysqli_stmt_execute($stmt_check_all);
    $total_booked = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_check_all))['TotalBooked'] ?? 0);
    mysqli_stmt_close($stmt_check_all);

    if (($total_booked + $quantity) > $limit) {
        $slots_left = max(0, $limit - $total_booked);
        mysqli_rollback($conn);
        echo "<script>alert('Tour chỉ còn trống {$slots_left} chỗ.'); window.history.back();</script>";
        exit();
    }

    $final_total_price = $price * $quantity;
    $sql_booking = "INSERT INTO bookings (UserID, TourID, Quantity, TotalPrice, Status, BookingDate, DepartureDate, CustomerName, Phone, PaymentMethod, PaymentStatus) VALUES (?, ?, ?, ?, 'Chờ xác nhận', NOW(), ?, ?, ?, ?, 'Chưa thanh toán')";
    $stmt_b = mysqli_prepare($conn, $sql_booking);
    mysqli_stmt_bind_param($stmt_b, "iiidssss", $user_id, $tour_id, $quantity, $final_total_price, $dep_date, $cust_name, $cust_phone, $payment_method);
    mysqli_stmt_execute($stmt_b);
    $booking_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_b);
}

if ($is_flash_sale && $flash_sale_id) {
    mysqli_query($conn, "UPDATE flash_sales SET Sold = Sold + $quantity WHERE FlashSaleID = $flash_sale_id");
}
mysqli_commit($conn);


// ==========================================
// BẺ LÁI SANG TRANG GỢI Ý (NẾU LÀ TOUR TỰ TÚC)
// ==========================================
if ($is_full_package === 0) {
    // Lưu các thông tin cần thiết vào Session để trang suggest_combo.php sử dụng
    $_SESSION['upsell_booking_id'] = $booking_id;
    $_SESSION['upsell_destination'] = $destination;
    $_SESSION['upsell_dep_date'] = $dep_date;
    $_SESSION['upsell_payment_method'] = $payment_method;
    
    // Redirect thẳng tới trang gợi ý
    header("Location: suggest_combo.php");
    exit();
}


// ==========================================
// CHUYỂN HƯỚNG BÌNH THƯỜNG (LUỒNG CŨ CHO TOUR TRỌN GÓI)
// ==========================================
if ($payment_method === 'VNPay') {
    // Chuyển thẳng sang VNPay (Không hiện alert để tăng tốc độ UI UX)
    header("Location: vnpay_create.php?booking_id=" . $booking_id);
    exit();
} elseif ($payment_method === 'Chuyển khoản') {
    header("Location: payment_create.php?booking_id=" . $booking_id);
    exit();
} else {
    $msg = $is_merged ? "Đơn hàng của bạn đã được cộng dồn thành công." : "Đặt tour thành công!";
    echo "<script>alert('{$msg} Vui lòng thanh toán tại văn phòng.'); window.location.href = 'my_bookings.php';</script>";
    exit();
}
?>