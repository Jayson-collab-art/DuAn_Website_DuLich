<?php
/**
 * export_pdf.php - Xuất hóa đơn / Vé điện tử (E-Voucher) trực tuyến
 * Miền Tây Travel
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';

// Kiểm tra quyền truy cập: Cho phép cả Admin đăng nhập HOẶC Khách hàng đăng nhập đều xem được
$is_admin = isset($_SESSION['admin_id']);
$user_id = $_SESSION['user_id'] ?? null;

if (!$is_admin && !$user_id) {
    echo "<script>alert('Vui lòng đăng nhập để xem hoặc tải hóa đơn đặt tour!'); window.location.href='login_user.php';</script>";
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("Mã đơn hàng không hợp lệ!");
}

// Truy vấn thông tin chi tiết đơn đặt tour
$sql = "SELECT Bookings.*, Tours.TourName, Tours.DepartureLocation, Tours.Price as BasePrice, Tours.Duration, Tours.ImageURL 
        FROM Bookings 
        JOIN Tours ON Bookings.TourID = Tours.TourID 
        WHERE Bookings.BookingID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$row) {
    die("Không tìm thấy thông tin đơn đặt tour #$id!");
}

// Kiểm tra phân quyền: Nếu không phải Admin thì User chỉ được xem vé của chính mình
if (!$is_admin && $row['UserID'] && intval($row['UserID']) !== intval($user_id)) {
    die("Bạn không có quyền truy cập vào hóa đơn này!");
}

$customer_name = htmlspecialchars($row['CustomerName'] ?? 'Quý khách');
$customer_phone = htmlspecialchars($row['Phone'] ?? 'Chưa cập nhật');
$customer_email = htmlspecialchars($row['Email'] ?? 'Chưa cập nhật');
$tour_name = htmlspecialchars($row['TourName'] ?? 'Tour Miền Tây');
$departure_loc = htmlspecialchars($row['DepartureLocation'] ?? 'TP. Hồ Chí Minh');
$departure_date = !empty($row['DepartureDate']) ? date('d/m/Y', strtotime($row['DepartureDate'])) : 'Liên hệ xác nhận';
$booking_date = !empty($row['BookingDate']) ? date('d/m/Y H:i', strtotime($row['BookingDate'])) : date('d/m/Y H:i');
$quantity = intval($row['Quantity'] ?? 1);
$payment_method = htmlspecialchars($row['PaymentMethod'] ?? 'Tiền mặt');
$payment_status = $row['PaymentStatus'] ?? 'Chờ xử lý';
$total_price = number_format($row['TotalPrice'] ?? 0, 0, ',', '.') . ' VNĐ';

$status_badge_text = ($payment_status === 'Đã thanh toán' || $payment_status === 'Thành công') ? 'ĐÃ THANH TOÁN THÀNH CÔNG' : 'XÁC NHẬN GIỮ CHỖ';
$status_color = ($payment_status === 'Đã thanh toán' || $payment_status === 'Thành công') ? '#10B981' : '#F59E0B';

// Giao diện hóa đơn / vé điện tử
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vé Điện Tử & Hóa Đơn #<?= $id ?> - Miền Tây Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAFC;
            color: #1E293B;
            padding: 40px 0;
        }
        .invoice-card {
            max-width: 700px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 40, 77, 0.08);
            border: 1px solid #E2E8F0;
            overflow: hidden;
            padding: 40px;
        }
        .invoice-header {
            border-bottom: 2px solid #00558F;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .brand-title {
            font-size: 24px;
            font-weight: 800;
            color: #00558F;
            letter-spacing: -0.5px;
        }
        .brand-slogan {
            font-size: 12px;
            color: #64748B;
            margin-top: 4px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
            background-color: <?= $status_color ?>;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #00558F;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 25px;
            margin-bottom: 12px;
            border-bottom: 1px dashed #CBD5E1;
            padding-bottom: 6px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
            border-bottom: 1px solid #F1F5F9;
        }
        .info-label {
            color: #64748B;
            font-weight: 500;
        }
        .info-value {
            color: #0F172A;
            font-weight: 600;
            text-align: right;
        }
        .price-box {
            background: linear-gradient(135deg, #FFF8F1 0%, #FFFFFF 100%);
            border: 1px dashed #FF862E;
            border-radius: 14px;
            padding: 20px;
            margin-top: 25px;
            text-align: right;
        }
        .price-total {
            font-size: 26px;
            font-weight: 800;
            color: #FF862E;
        }
        .action-bar {
            text-align: center;
            margin-top: 30px;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff; padding: 0; }
            .invoice-card { box-shadow: none; border: none; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print mb-4 text-center">
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold me-2" style="background:#00558F; border-color:#00558F;">
            <i class="bi bi-printer me-1"></i> In Vé Điện Tử / Lưu PDF
        </button>
        <a href="javascript:history.back()" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Quay lại
        </a>
    </div>

    <div class="invoice-card">
        <div class="invoice-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="brand-title">MIỀN TÂY TRAVEL</div>
                <div class="brand-slogan">Trải nghiệm nét đẹp mộc mạc sông nước miền Tây</div>
            </div>
            <div class="text-end">
                <div class="small text-muted">MÃ ĐƠN HÀNG</div>
                <div class="fw-bold fs-5 text-primary">#<?= $id ?></div>
                <div class="small text-muted mt-1">Ngày đặt: <?= $booking_date ?></div>
            </div>
        </div>

        <div class="text-center my-3">
            <h4 class="fw-bold text-dark text-uppercase mb-2" style="letter-spacing:-0.5px;">Xác Nhận Đặt Tour & Vé Điện Tử</h4>
            <div><span class="status-badge"><?= $status_badge_text ?></span></div>
        </div>

        <div class="section-title">Thông tin khách hàng</div>
        <div class="info-row">
            <span class="info-label">Họ và tên:</span>
            <span class="info-value"><?= $customer_name ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Số điện thoại:</span>
            <span class="info-value"><?= $customer_phone ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Email liên hệ:</span>
            <span class="info-value"><?= $customer_email ?></span>
        </div>

        <div class="section-title">Chi tiết chuyến đi</div>
        <div class="info-row">
            <span class="info-label">Chương trình tour:</span>
            <span class="info-value text-primary"><?= $tour_name ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Điểm khởi hành:</span>
            <span class="info-value"><?= $departure_loc ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Ngày khởi hành:</span>
            <span class="info-value"><?= $departure_date ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Số lượng khách:</span>
            <span class="info-value"><?= $quantity ?> khách</span>
        </div>
        <div class="info-row">
            <span class="info-label">Phương thức thanh toán:</span>
            <span class="info-value"><?= $payment_method ?></span>
        </div>

        <div class="price-box">
            <div class="small text-muted fw-bold text-uppercase mb-1">Tổng tiền thanh toán</div>
            <div class="price-total"><?= $total_price ?></div>
        </div>

        <div class="text-center mt-4 pt-3 border-top text-muted" style="font-size: 11px; line-height: 1.6;">
            <p class="mb-1"><strong>Hóa đơn điện tử có giá trị xác nhận giao dịch giữ chỗ thành công tại Miền Tây Travel.</strong></p>
            <p class="mb-1">Vui lòng xuất trình mã đơn hàng #<?= $id ?> khi tập trung vào ngày khởi hành.</p>
            <p class="mb-0">Hotline hỗ trợ: <strong>0382 123 805</strong> • Email: <strong>contact@mientaytravel.vn</strong></p>
        </div>
    </div>

</body>
</html>