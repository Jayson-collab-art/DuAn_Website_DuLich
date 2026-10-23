<?php
session_start();
include 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Xử lý hủy đơn Tour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("<script>alert('Lỗi bảo mật!'); window.location.href='my_bookings.php';</script>");
    }
    $booking_id = intval($_POST['booking_id']);
    $sql_cancel = "UPDATE bookings SET Status = 'Đã hủy' WHERE BookingID = ? AND UserID = ? AND Status = 'Chờ xác nhận'";
    $stmt = mysqli_prepare($conn, $sql_cancel);
    mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_query($conn, "UPDATE payments SET PaymentStatus = 'Cancelled', UpdatedAt = NOW() WHERE BookingID = $booking_id AND PaymentStatus = 'Pending'");
        header("Location: my_bookings.php?msg=cancelled");
        exit();
    }
}

// =========================================================
// LẤY VÀ ĐỒNG BỘ HÓA DỮ LIỆU TỪ CƠ SỞ DỮ LIỆU
// =========================================================
$pending_items = [];
$confirmed_items = [];
$total_spent = 0;

// 1. Lấy dữ liệu Tour (Bổ sung thêm cột IsFullPackage)
$sql_tours = "SELECT b.*, t.TourName, t.ImageURL, t.DepartureLocation, t.Price as OriginalTourPrice, t.IsFullPackage,
              p.PaymentStatus AS GatewayPayStatus, p.BankCode, pm.MethodName as GatewayMethodName
              FROM bookings b 
              JOIN tours t ON b.TourID = t.TourID 
              LEFT JOIN payments p ON p.PaymentID = (SELECT MAX(PaymentID) FROM payments WHERE BookingID = b.BookingID)
              LEFT JOIN payment_methods pm ON p.PaymentMethodID = pm.PaymentMethodID
              WHERE b.UserID = $user_id ORDER BY b.BookingDate DESC";
$res_tours = mysqli_query($conn, $sql_tours);
if ($res_tours) {
    while ($row = mysqli_fetch_assoc($res_tours)) {
        $gateway_status = $row['GatewayPayStatus'] ?? '';
        $tour_status = $row['Status']; 
        
        $is_completed = ($tour_status === 'Đã duyệt');
        $is_cancelled = ($tour_status === 'Đã hủy');

        $raw_method = $row['PaymentMethod'] ?? 'Tiền mặt';
        if (!empty($row['GatewayMethodName'])) {
            $display_method = $row['GatewayMethodName'];
            if (!empty($row['BankCode'])) {
                $display_method .= " (" . $row['BankCode'] . ")";
            }
        } else {
            $display_method = $raw_method;
        }

        if ($is_cancelled) {
            $display_status = 'Đã hủy';
        } elseif ($is_completed) {
            $display_status = 'Đã duyệt';
        } elseif ($gateway_status === 'Paid') {
            $display_status = 'Đã thanh toán (Chờ duyệt)';
        } else {
            $display_status = 'Chờ xác nhận';
        }

        // TÍNH TOÁN XEM ĐƠN NÀY ÁP DỤNG SALE HAY MÃ GIẢM GIÁ
        $original_total = ($row['OriginalTourPrice'] ?? 0) * ($row['Quantity'] ?? 1);
        $coupon_code = $row['CouponCode'] ?? '';
        $has_coupon = !empty($coupon_code);

        $is_flash_sale = false;
        if (isset($row['IsFlashSale']) && $row['IsFlashSale'] == 1) {
            $is_flash_sale = true;
        } elseif (isset($row['FlashSaleID']) && $row['FlashSaleID'] > 0) {
            $is_flash_sale = true;
        } elseif ($row['TotalPrice'] < $original_total && !$has_coupon) {
            $is_flash_sale = true;
        } elseif ($row['TotalPrice'] < $original_total && $has_coupon) {
            $is_flash_sale = isset($row['IsFlashSale']) ? ($row['IsFlashSale'] ? true : false) : true;
        }

        $item = [
            'type_id'       => 'tour',
            'icon'          => 'bi-compass',
            'type_name'     => 'Tour Du Lịch',
            'code'          => '#' . $row['BookingID'],
            'title'         => $row['TourName'],
            'is_full_package'=> isset($row['IsFullPackage']) ? $row['IsFullPackage'] : 1, // LƯU CỜ VÀO ARRAY
            'image'         => !empty($row['ImageURL']) ? $row['ImageURL'] : 'https://placehold.co/600x400?text=Tour',
            'meta_1_lbl'    => 'Khởi hành:',
            'meta_1_val'    => !empty($row['DepartureDate']) ? date('d/m/Y', strtotime($row['DepartureDate'])) : 'Liên hệ',
            'meta_2_lbl'    => 'Số lượng:',
            'meta_2_val'    => $row['Quantity'] . ' khách',
            'price'         => $row['TotalPrice'],
            'status'        => $display_status,
            'payment'       => $display_method,
            'cancel_id'     => $row['BookingID'],
            'booking_id'    => $row['BookingID'],
            'pay_method'    => $raw_method,
            'is_flash_sale' => $is_flash_sale,
            'has_coupon'    => $has_coupon,
            'coupon_code'   => $coupon_code,
            'original_total' => $original_total
        ];
        
        if ($is_completed || $is_cancelled) {
            $confirmed_items[] = $item;
            if(!$is_cancelled) $total_spent += $row['TotalPrice'];
        } else {
            $pending_items[] = $item;
        }
    }
}

// 2. Lấy dữ liệu Máy Bay
$sql_flights = "SELECT * FROM flight_bookings WHERE UserID = $user_id ORDER BY DepartDate DESC";
$res_flights = mysqli_query($conn, $sql_flights);
if ($res_flights) {
    while ($row = mysqli_fetch_assoc($res_flights)) {
        $pay_status = $row['PaymentStatus'] ?? 'Chưa thanh toán';
        $is_paid = ($pay_status === 'Paid' || $pay_status === 'Đã thanh toán');
        $is_cancelled = ($pay_status === 'Cancelled' || $pay_status === 'Đã hủy');
        
        $status_text = $is_cancelled ? 'Đã hủy' : ($is_paid ? 'Đã duyệt' : 'Chờ thanh toán');
        
        $item = [
            'type_id'       => 'flight',
            'icon'          => 'bi-airplane-engines',
            'type_name'     => 'Vé Máy Bay',
            'code'          => 'PNR: ' . ($row['PNR_Code'] ?? 'N/A'),
            'title'         => 'Chuyến bay: ' . ($row['Airline'] ?? 'Hãng bay'),
            'image'         => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?q=80&w=400',
            'meta_1_lbl'    => 'Ngày bay:',
            'meta_1_val'    => !empty($row['DepartDate']) ? date('d/m/Y', strtotime($row['DepartDate'])) : 'N/A',
            'meta_2_lbl'    => 'Hành khách:',
            'meta_2_val'    => ($row['CustomerName'] ?? 'Khách hàng'),
            'price'         => $row['TotalPrice'] ?? 0,
            'status'        => $status_text,
            'payment'       => 'VNPay',
            'cancel_id'     => null,
            'booking_id'    => $row['FlightBookingID'] ?? $row['id'] ?? 0,
            'is_flash_sale' => false,
            'has_coupon'    => false
        ];
        
        if ($is_paid || $is_cancelled) {
            $confirmed_items[] = $item;
            if(!$is_cancelled) $total_spent += $row['TotalPrice'];
        } else {
            $pending_items[] = $item;
        }
    }
}

// 3. Lấy dữ liệu Khách sạn
$sql_hotels = "SELECT * FROM hotel_bookings WHERE UserID = $user_id";
$res_hotels = mysqli_query($conn, $sql_hotels);
if ($res_hotels) {
    while ($row = mysqli_fetch_assoc($res_hotels)) {
        $pay_status = $row['PaymentStatus'] ?? 'Chưa thanh toán';
        $is_paid = ($pay_status === 'Paid' || $pay_status === 'Đã thanh toán');
        $is_cancelled = ($pay_status === 'Cancelled' || $pay_status === 'Đã hủy');
        
        $status_text = $is_cancelled ? 'Đã hủy' : ($is_paid ? 'Đã duyệt' : 'Chờ thanh toán');

        $item = [
            'type_id'       => 'hotel',
            'icon'          => 'bi-building',
            'type_name'     => 'Khách Sạn',
            'code'          => 'Mã Đặt: HT' . ($row['id'] ?? rand(100,999)),
            'title'         => $row['HotelName'] ?? 'Khách sạn',
            'image'         => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=400',
            'meta_1_lbl'    => 'Check-in:',
            'meta_1_val'    => !empty($row['CheckIn']) ? date('d/m/Y', strtotime($row['CheckIn'])) : 'N/A',
            'meta_2_lbl'    => 'Check-out:',
            'meta_2_val'    => !empty($row['CheckOut']) ? date('d/m/Y', strtotime($row['CheckOut'])) : 'N/A',
            'price'         => $row['TotalPrice'] ?? 0,
            'status'        => $status_text,
            'payment'       => 'VNPay',
            'cancel_id'     => null,
            'booking_id'    => $row['HotelBookingID'] ?? $row['id'] ?? 0,
            'is_flash_sale' => false,
            'has_coupon'    => false
        ];
        
        if ($is_paid || $is_cancelled) {
            $confirmed_items[] = $item;
            if(!$is_cancelled) $total_spent += $row['TotalPrice'];
        } else {
            $pending_items[] = $item;
        }
    }
}

$all_pending_count = count($pending_items);
$all_confirmed_count = count($confirmed_items);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý hành trình tổng hợp - MIỀNTÂY TRAVEL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #F8FAFC; font-family: 'Inter', sans-serif; }
        .cart-hero { background: linear-gradient(135deg, #00284D 0%, #00558F 100%); padding: 50px 0; color: #fff; }
        
        .nav-custom { gap: 10px; border-bottom: 2px solid #E2E8F0; padding-bottom: 10px; margin-bottom: 30px; }
        .nav-custom .nav-link { color: #64748B; font-weight: 600; border-radius: 50px; padding: 10px 24px; transition: 0.3s; }
        .nav-custom .nav-link:hover { background: #F1F5F9; color: #0F172A; }
        .nav-custom .nav-link.active { background: #FF862E; color: #fff; box-shadow: 0 4px 12px rgba(255, 134, 46, 0.3); }

        .unified-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 20px; padding: 20px; margin-bottom: 20px; transition: 0.3s; position: relative; overflow: hidden; }
        .unified-card:hover { transform: translateY(-3px); box-shadow: 0 12px 24px rgba(0,40,77,0.06); border-color: #CBD5E1; }
        
        .type-badge { position: absolute; top: 16px; left: -8px; padding: 6px 16px 6px 20px; color: #fff; font-size: 0.75rem; font-weight: 700; border-radius: 0 50px 50px 0; box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 2; }
        .bg-tour { background: #FF862E; } .bg-flight { background: #0284C7; } .bg-hotel { background: #10B981; }

        .unified-img { width: 100%; height: 160px; object-fit: cover; border-radius: 14px; }
        .unified-title { font-size: 1.15rem; font-weight: 800; color: #0F172A; margin-bottom: 8px; line-height: 1.4; }
        
        .meta-box { background: #F8FAFC; border: 1px solid #EEF2F6; padding: 12px 16px; border-radius: 12px; font-size: 0.85rem; }
        .meta-box i { color: #0284C7; font-size: 1.1rem; }
        
        .status-pill { padding: 6px 14px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; border: 1px solid transparent; }
        .status-pending { background: #FFF7ED; color: #EA580C; border-color: #FED7AA; }
        .status-success { background: #ECFDF5; color: #10B981; border: 1px solid #A7F3D0; }
        .status-cancel { background: #F1F5F9; color: #64748B; border-color: #E2E8F0; }

        .price-text { font-size: 1.4rem; font-weight: 800; color: #E11D48; }
        .btn-outline-danger { border-radius: 50px; font-weight: 600; padding: 6px 20px; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <header class="cart-hero">
        <div class="container">
            <h1 class="fw-bold mb-2">Hành Trình Của Bạn</h1>
            <p class="mb-0 text-white-50">Tất cả Tour, Vé máy bay và Khách sạn được quản lý tập trung tại đây.</p>
        </div>
    </header>

    <main class="container my-5">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm"><i class="bi bi-check-circle-fill me-2"></i>Hủy đơn thành công!</div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <ul class="nav nav-pills nav-custom" id="bookingTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                            <i class="bi bi-hourglass-split me-1"></i> Chờ Xác Nhận / Thanh Toán
                            <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $all_pending_count ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="confirmed-tab" data-bs-toggle="tab" data-bs-target="#confirmed" type="button" role="tab">
                            <i class="bi bi-check-circle-fill me-1"></i> Đã Xử Lý / Lịch Sử
                            <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $all_confirmed_count ?></span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="bookingTabsContent">
                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                        <?php if (empty($pending_items)): ?>
                            <div class="text-center p-5 bg-white rounded-4 border shadow-sm">
                                <i class="bi bi-check2-all text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 fw-bold">Tuyệt vời!</h5>
                                <p class="text-muted">Bạn không có đơn nào đang chờ xử lý.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_items as $item): renderBookingCard($item); endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="confirmed" role="tabpanel">
                        <?php if (empty($confirmed_items)): ?>
                            <div class="text-center p-5 bg-white rounded-4 border shadow-sm">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 fw-bold">Chưa có lịch sử</h5>
                                <p class="text-muted">Các đơn hàng đã hoàn tất hoặc bị hủy sẽ xuất hiện tại đây.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($confirmed_items as $item): renderBookingCard($item); endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="bg-white p-4 rounded-4 border shadow-sm position-sticky" style="top: 100px;">
                    <h5 class="fw-bold mb-4 border-bottom pb-3"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Tổng Quan Chi Tiêu</h5>
                    
                    <div class="d-flex justify-content-between mb-3 text-muted">
                        <span>Đang chờ xử lý:</span>
                        <strong class="text-warning"><?= $all_pending_count ?> đơn</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 text-muted">
                        <span>Lịch sử / Đã duyệt:</span>
                        <strong class="text-success"><?= $all_confirmed_count ?> đơn</strong>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top text-center">
                        <span class="d-block text-muted small fw-bold text-uppercase mb-1">Tổng chi tiêu hợp lệ</span>
                        <span class="fs-2 fw-bold text-danger"><?= number_format($total_spent, 0, ',', '.') ?> ₫</span>
                    </div>

                    <a href="index.php" class="btn btn-primary w-100 mt-4 rounded-pill fw-bold py-2 shadow-sm">Khám phá thêm dịch vụ</a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function renderBookingCard($item) {
    $bg_class = ($item['type_id'] == 'tour') ? 'bg-tour' : (($item['type_id'] == 'flight') ? 'bg-flight' : 'bg-hotel');
    
    $status_class = 'status-pending';
    if (in_array($item['status'], ['Đã duyệt', 'Đã thanh toán', 'Đã thanh toán (Chờ duyệt)'])) $status_class = 'status-success';
    elseif (in_array($item['status'], ['Đã hủy', 'Cancelled'])) $status_class = 'status-cancel';
?>
    <div class="unified-card">
        <div class="type-badge <?= $bg_class ?>">
            <i class="<?= $item['icon'] ?> me-1"></i> <?= $item['type_name'] ?>
        </div>
        
        <div class="row g-3 mt-1">
            <div class="col-md-3">
                <img src="<?= $item['image'] ?>" class="unified-img" alt="image">
            </div>
            <div class="col-md-9 d-flex flex-column">
                <div class="d-flex flex-wrap justify-content-between align-items-start mb-2 gap-2">
                    <div class="d-flex flex-column gap-1">
                        <!-- TÍCH HỢP NHÃN LOẠI HÌNH VÀO TIÊU ĐỀ TOUR -->
                        <h5 class="unified-title m-0 pe-2 d-flex align-items-center flex-wrap gap-2">
                            <?= htmlspecialchars($item['title']) ?>
                            <?php if ($item['type_id'] === 'tour'): ?>
                                <?php if (isset($item['is_full_package']) && $item['is_full_package'] == 0): ?>
                                    <span class="badge bg-warning text-dark align-middle" style="font-size: 0.75rem;"><i class="bi bi-person-walking"></i> Tự túc</span>
                                <?php else: ?>
                                    <span class="badge bg-success align-middle" style="font-size: 0.75rem;"><i class="bi bi-box-seam"></i> Trọn gói</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </h5>
                        
                        <!-- CHIA RÕ BADGE ƯU ĐÃI (SALE & MÃ GIẢM GIÁ) -->
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <?php if (!empty($item['is_flash_sale'])): ?>
                                <span class="badge text-white rounded-pill px-2 py-1 shadow-sm" style="background: linear-gradient(135deg, #ef4444, #b91c1c); font-size: 0.75rem;">
                                    <i class="bi bi-lightning-charge-fill me-1" style="color: #FFD700 !important;"></i>FLASH SALE
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($item['has_coupon'])): ?>
                                <span class="badge text-white rounded-pill px-2 py-1 shadow-sm" style="background: linear-gradient(135deg, #0284c7, #0369a1); font-size: 0.75rem;">
                                    <i class="bi bi-ticket-perforated-fill me-1" style="color: #FFD700 !important;"></i>ÁP MÃ: <?= mb_strtoupper(htmlspecialchars($item['coupon_code']), 'UTF-8') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="status-pill <?= $status_class ?> text-nowrap mt-1"><?= $item['status'] ?></span>
                </div>
                
                <div class="text-muted small mb-2 fw-semibold"><?= $item['code'] ?></div>
                
                <div class="d-flex gap-2 mb-3">
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-wallet2 text-primary me-1"></i> Phương thức: <strong><?= htmlspecialchars($item['payment']) ?></strong>
                    </span>
                </div>
                
                <div class="meta-box row g-2 mb-3">
                    <div class="col-sm-6 d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-event"></i>
                        <div><span class="text-muted small d-block"><?= $item['meta_1_lbl'] ?></span><strong><?= $item['meta_1_val'] ?></strong></div>
                    </div>
                    <div class="col-sm-6 d-flex align-items-center gap-2">
                        <i class="bi bi-info-square"></i>
                        <div><span class="text-muted small d-block"><?= $item['meta_2_lbl'] ?></span><strong><?= $item['meta_2_val'] ?></strong></div>
                    </div>
                </div>

                <div class="mt-auto d-flex flex-wrap justify-content-between align-items-end border-top pt-3 gap-2">
                    <div>
                        <span class="text-muted small d-block mb-1">Tổng tiền</span>
                        
                        <!-- HIỂN THỊ GIÁ GỐC BỊ GẠCH NGANG NẾU CÓ ƯU ĐÃI -->
                        <?php if ((!empty($item['is_flash_sale']) || !empty($item['has_coupon'])) && !empty($item['original_total']) && $item['original_total'] > $item['price']): ?>
                            <span class="text-decoration-line-through text-secondary d-block" style="font-size: 0.9rem; margin-bottom: -5px;">
                                <?= number_format($item['original_total'], 0, ',', '.') ?> ₫
                            </span>
                        <?php endif; ?>
                        
                        <span class="price-text d-block"><?= number_format($item['price'], 0, ',', '.') ?> ₫</span>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <?php if ($item['type_id'] === 'tour' && $item['status'] === 'Chờ xác nhận' && (strpos($item['payment'], 'Tiền mặt') !== false || $item['pay_method'] === 'Tiền mặt')): ?>
                            <a href="vnpay_create.php?booking_id=<?= $item['booking_id'] ?>" class="btn btn-warning btn-sm fw-bold text-dark rounded-pill px-3">
                                <i class="bi bi-credit-card me-1"></i> Thanh toán ngay
                            </a>
                        <?php endif; ?>

                        <?php if ($item['cancel_id'] && $item['status'] === 'Chờ xác nhận'): ?>
                            <form method="POST" action="my_bookings.php" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="booking_id" value="<?= $item['cancel_id'] ?>">
                                <button type="submit" name="cancel_booking" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Hủy đơn Tour này?');"><i class="bi bi-x-circle me-1"></i> Hủy đơn</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>