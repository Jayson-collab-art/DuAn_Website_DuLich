<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id <= 0) {
    header("Location: my_bookings.php");
    exit();
}

// 1. Kiểm tra quyền sở hữu đơn & trạng thái: chỉ cho sửa đơn 'Chờ xác nhận' và 'Chưa thanh toán'
$sql = "SELECT b.*, t.TourName, t.MaxPeople, t.Price 
        FROM bookings b 
        JOIN tours t ON b.TourID = t.TourID 
        WHERE b.BookingID = ? AND b.UserID = ? AND b.Status = 'Chờ xác nhận' 
        LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$data) {
    echo "<script>alert('Không tìm thấy đơn hàng hoặc đơn hàng đã được admin phê duyệt!'); window.location.href='my_bookings.php';</script>";
    exit();
}

if ($data['PaymentStatus'] === 'Đã thanh toán') {
    echo "<script>alert('Đơn hàng này đã thanh toán thành công, không thể thay đổi thông tin. Vui lòng liên hệ Hotline 0382 123 805 để được hỗ trợ điều chỉnh lịch!'); window.location.href='my_bookings.php';</script>";
    exit();
}

// 2. Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnUpdate'])) {
    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['customer_phone'] ?? '');
    $date = trim($_POST['departure_date'] ?? '');
    $qty = (int)($_POST['quantity'] ?? 0);

    if (empty($name) || empty($phone) || empty($date) || $qty <= 0) {
        echo "<script>alert('Vui lòng điền đầy đủ và chính xác các thông tin!'); window.history.back();</script>";
        exit();
    }

    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        echo "<script>alert('Lỗi: Ngày khởi hành không được ở quá khứ!'); window.history.back();</script>";
        exit();
    }

    if (!preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $phone)) {
        echo "<script>alert('Lỗi: Số điện thoại không hợp lệ! Vui lòng nhập đúng 10 số.'); window.history.back();</script>";
        exit();
    }

    // Kiểm tra sức chứa còn lại của tour
    $limit = (int)($data['MaxPeople'] > 0 ? $data['MaxPeople'] : 10);
    $tour_id = (int)$data['TourID'];

    $sql_check_other = "SELECT COALESCE(SUM(Quantity), 0) AS OtherBooked FROM bookings WHERE TourID = ? AND Status <> 'Đã hủy' AND BookingID <> ?";
    $stmt_co = mysqli_prepare($conn, $sql_check_other);
    mysqli_stmt_bind_param($stmt_co, "ii", $tour_id, $booking_id);
    mysqli_stmt_execute($stmt_co);
    $res_co = mysqli_stmt_get_result($stmt_co);
    $row_co = mysqli_fetch_assoc($res_co);
    mysqli_stmt_close($stmt_co);

    $other_booked = (int)($row_co['OtherBooked'] ?? 0);
    if (($other_booked + $qty) > $limit) {
        $slots_can_book = max(0, $limit - $other_booked);
        echo "<script>alert('Rất tiếc! Tour này chỉ còn trống tối đa {$slots_can_book} chỗ. Bạn không thể đổi thành {$qty} người.'); window.history.back();</script>";
        exit();
    }

    // Giá tour lấy từ DB
    $unit_price = (float)$data['Price'];
    $total_price = $unit_price * $qty;

    $sql_update = "UPDATE bookings SET 
                   CustomerName = ?, 
                   Phone = ?, 
                   DepartureDate = ?, 
                   Quantity = ?,
                   TotalPrice = ?
                   WHERE BookingID = ? AND UserID = ?";
    $stmt_up = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_up, "sssiiii", $name, $phone, $date, $qty, $total_price, $booking_id, $user_id);

    if (mysqli_stmt_execute($stmt_up)) {
        mysqli_stmt_close($stmt_up);
        // Hủy bất kỳ pending payment cũ vì số tiền có thể đã thay đổi
        mysqli_query($conn, "UPDATE payments SET PaymentStatus = 'Cancelled', UpdatedAt = NOW() WHERE BookingID = $booking_id AND PaymentStatus = 'Pending'");
        echo "<script>alert('Cập nhật đơn hàng thành công! Số tiền mới: " . number_format($total_price, 0, ',', '.') . "đ'); window.location.href='my_bookings.php';</script>";
        exit();
    } else {
        mysqli_stmt_close($stmt_up);
        echo "<script>alert('Lỗi cập nhật: " . mysqli_error($conn) . "'); window.history.back();</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa đơn hàng - Miền Tây Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Lexend:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #F8FAFC; font-family: 'Inter', sans-serif; }
        .edit-card { 
            border: 1px solid #E2E8F0; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0, 40, 77, 0.08); 
            background: #FFFFFF;
        }
        .form-label { font-weight: 600; color: #334155; }
        .form-control { border-radius: 10px; border: 1.5px solid #CBD5E1; padding: 10px 14px; }
        .form-control:focus { border-color: #0284C7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15); }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card edit-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-2">ĐƠN HÀNG #<?php echo $booking_id; ?></span>
                        <h3 class="fw-bold text-dark mb-1">Điều chỉnh thông tin đặt tour</h3>
                        <p class="text-muted small">Tour: <strong class="text-primary"><?php echo htmlspecialchars($data['TourName']); ?></strong></p>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small">Họ và tên người đặt: <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control" value="<?php echo htmlspecialchars($data['CustomerName']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Số điện thoại: <span class="text-danger">*</span></label>
                            <input type="tel" name="customer_phone" class="form-control" value="<?php echo htmlspecialchars($data['Phone']); ?>" pattern="^(03|05|07|08|09)[0-9]{8}$" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Ngày khởi hành dự kiến: <span class="text-danger">*</span></label>
                            <input type="date" name="departure_date" class="form-control" value="<?php echo htmlspecialchars($data['DepartureDate']); ?>" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small">Số người tham gia: <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" value="<?php echo (int)$data['Quantity']; ?>" min="1" max="<?php echo (int)($data['MaxPeople'] ?: 10); ?>" required>
                            <small class="text-muted fst-italic">Sức chứa tối đa của tour: <?php echo (int)($data['MaxPeople'] ?: 10); ?> khách.</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="btnUpdate" class="btn btn-primary rounded-pill fw-bold py-2" style="background:#00558F; border-color:#00558F;">
                                <i class="bi bi-check-lg me-1"></i> Lưu thay đổi
                            </button>
                            <a href="my_bookings.php" class="btn btn-light rounded-pill py-2 border text-muted">
                                Quay lại danh sách
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>