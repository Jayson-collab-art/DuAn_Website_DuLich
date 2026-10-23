<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('CẢNH BÁO: Chưa đăng nhập!'); window.location.href='login_admin.php';</script>";
    exit();
}

$base_path = __DIR__ . '/PHPMailer/';
require $base_path . 'Exception.php';
require $base_path . 'PHPMailer.php';
require $base_path . 'SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;

include 'db.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// 1. XỬ LÝ ACTIONS (DUYỆT / XÓA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("Lỗi bảo mật CSRF!");
    $id = (int)$_POST['booking_id'];
    
    if ($_POST['action'] === 'approve') {
        $res = mysqli_query($conn, "SELECT b.*, t.TourName, u.Email FROM Bookings b JOIN Tours t ON b.TourID = t.TourID LEFT JOIN Users u ON b.UserID = u.UserID WHERE b.BookingID = $id");
        if ($data = mysqli_fetch_assoc($res)) {
            mysqli_query($conn, "UPDATE Bookings SET Status = 'Đã duyệt' WHERE BookingID = $id");
            if (!empty($data['Email'])) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
                    $mail->Username = 'joji19982@gmail.com'; $mail->Password = 'rnvw xhrv ypit dqua';
                    $mail->SMTPSecure = 'tls'; $mail->Port = 587; $mail->CharSet = 'UTF-8';
                    $mail->setFrom('no-reply@mientaytravel.com', 'MIỀNTÂY TRAVEL');
                    $mail->addAddress($data['Email']);
                    $mail->isHTML(true); $mail->Subject = 'XÁC NHẬN ĐẶT TOUR THÀNH CÔNG - ĐƠN #' . $id;
                    $mail->Body = "<h2>Chào {$data['CustomerName']},</h2><p>Tour <strong>{$data['TourName']}</strong> đã được xác nhận!</p>";
                    $mail->send();
                } catch (Exception $e) {}
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $tab_type = $_POST['tab_type'] ?? 'tour';
        if ($tab_type === 'flight') {
            mysqli_query($conn, "DELETE FROM flight_bookings WHERE FlightBookingID = $id");
        } elseif ($tab_type === 'hotel') {
            mysqli_query($conn, "DELETE FROM hotel_bookings WHERE HotelBookingID = $id");
        } else {
            mysqli_query($conn, "DELETE FROM Payments WHERE BookingID = $id");
            mysqli_query($conn, "DELETE FROM Bookings WHERE BookingID = $id");
        }
    }
    header("Location: admin_bookings.php?" . http_build_query($_GET)); 
    exit();
}

// 2. LẤY BIẾN ĐIỀU KIỆN
$tab = $_GET['tab'] ?? 'tour';
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$date = $_GET['date'] ?? '';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10; 
$offset = ($page - 1) * $limit;

// Xây dựng điều kiện lọc cơ bản
$where = "1=1";
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= is_numeric($s) ? " AND Phone LIKE '%$s%'" : " AND CustomerName LIKE N'%$s%'";
}
if ($date !== '') {
    $d = mysqli_real_escape_string($conn, $date);
    $where .= ($tab === 'tour') ? " AND DATE(BookingDate) = '$d'" : " AND DATE(CreatedAt) = '$d'";
}

// 3. ĐẾM SỐ LƯỢNG CHO CÁC TAB TRẠNG THÁI
$count_all = 0; $count_pending = 0; $count_approved = 0; $count_cancelled = 0;

if ($tab === 'tour') {
    $where_base = str_replace(['Phone', 'CustomerName', 'BookingDate'], ['b.Phone', 'b.CustomerName', 'b.BookingDate'], $where);
    $q_count = mysqli_query($conn, "SELECT Status, COUNT(*) as c FROM Bookings b WHERE $where_base GROUP BY Status");
    if($q_count) {
        while($r = mysqli_fetch_assoc($q_count)) {
            if($r['Status'] === 'Chờ xác nhận') $count_pending += $r['c'];
            elseif($r['Status'] === 'Đã duyệt') $count_approved += $r['c'];
            elseif($r['Status'] === 'Đã hủy') $count_cancelled += $r['c'];
            $count_all += $r['c'];
        }
    }
} else {
    $table = ($tab === 'flight') ? 'flight_bookings' : 'hotel_bookings';
    $q_count = mysqli_query($conn, "SELECT PaymentStatus, COUNT(*) as c FROM $table WHERE $where GROUP BY PaymentStatus");
    if($q_count) {
        while($r = mysqli_fetch_assoc($q_count)) {
            if(in_array($r['PaymentStatus'], ['Pending', 'Chưa thanh toán'])) $count_pending += $r['c'];
            elseif(in_array($r['PaymentStatus'], ['Paid', 'Đã thanh toán'])) $count_approved += $r['c'];
            elseif(in_array($r['PaymentStatus'], ['Cancelled', 'Đã hủy'])) $count_cancelled += $r['c'];
            $count_all += $r['c'];
        }
    }
}

// 4. LỌC STATUS VÀ TRUY VẤN DỮ LIỆU
$items = [];
$total_records = 0;

if ($tab === 'tour') {
    $where_tour = $where_base;
    if ($status !== '') $where_tour .= " AND b.Status = '" . mysqli_real_escape_string($conn, $status) . "'";
    
    $c_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM Bookings b WHERE $where_tour");
    $total_records = $c_res ? mysqli_fetch_assoc($c_res)['c'] : 0;
    
    $sql = "SELECT b.*, t.TourName, t.ImageURL, t.IsFullPackage, u.Email, 
                   p.PaymentStatus AS GatewayPaymentStatus, p.PaidAt, p.TransactionCode 
            FROM Bookings b 
            JOIN Tours t ON b.TourID = t.TourID 
            LEFT JOIN Users u ON b.UserID = u.UserID
            LEFT JOIN Payments p ON p.PaymentID = (SELECT MAX(PaymentID) FROM Payments WHERE BookingID = b.BookingID)
            WHERE $where_tour 
            ORDER BY b.BookingDate DESC LIMIT $limit OFFSET $offset";
            
    $res = mysqli_query($conn, $sql);
    if($res) while($r = mysqli_fetch_assoc($res)) $items[] = $r;

} else {
    $table = ($tab === 'flight') ? 'flight_bookings' : 'hotel_bookings';
    if ($status === 'Đã duyệt') $where .= " AND PaymentStatus IN ('Paid', 'Đã thanh toán')";
    elseif ($status === 'Chờ xác nhận') $where .= " AND PaymentStatus IN ('Pending', 'Chưa thanh toán')";
    elseif ($status === 'Đã hủy') $where .= " AND PaymentStatus IN ('Cancelled', 'Đã hủy')";
    
    $c_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM $table WHERE $where");
    $total_records = $c_res ? mysqli_fetch_assoc($c_res)['c'] : 0;
    
    $sql = "SELECT * FROM $table WHERE $where ORDER BY CreatedAt DESC LIMIT $limit OFFSET $offset";
    $res = mysqli_query($conn, $sql);
    if($res) while($r = mysqli_fetch_assoc($res)) $items[] = $r;
}

$total_pages = max(1, ceil($total_records / $limit));
$q = $_GET; unset($q['page']); 
$qs = !empty($q) ? '&' . http_build_query($q) : '';

$q_status = $_GET; unset($q_status['status'], $q_status['page']);
$qs_status = !empty($q_status) ? '&' . http_build_query($q_status) : '';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn Đặt Dịch Vụ - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #F8FAFC; font-family: 'Inter', sans-serif; }
        
        .nav-custom { gap: 10px; border-bottom: 2px solid #E2E8F0; padding-bottom: 10px; margin-bottom: 25px; }
        .nav-custom .nav-link { color: #64748B; font-weight: 600; border-radius: 50px; padding: 10px 24px; transition: 0.3s; border: 1px solid transparent; }
        .nav-custom .nav-link:hover { background: #F1F5F9; color: #0F172A; }
        .nav-custom .nav-link.active { background: #FF862E; color: #fff; box-shadow: 0 4px 12px rgba(255, 134, 46, 0.3); }

        .unified-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 20px; padding: 24px; margin-bottom: 20px; transition: 0.3s; position: relative; overflow: hidden; }
        .unified-card:hover { transform: translateY(-3px); box-shadow: 0 12px 24px rgba(0,40,77,0.06); border-color: #CBD5E1; }
        
        .type-badge { position: absolute; top: 16px; left: -8px; padding: 6px 16px 6px 20px; color: #fff; font-size: 0.75rem; font-weight: 700; border-radius: 0 50px 50px 0; box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 2; }
        .bg-tour { background: #FF862E; } .bg-flight { background: #0284C7; } .bg-hotel { background: #10B981; }

        .unified-img { width: 100%; height: 100%; min-height: 180px; object-fit: cover; border-radius: 14px; }
        .unified-title { font-size: 1.2rem; font-weight: 800; color: #0F172A; margin-bottom: 12px; line-height: 1.4; }
        
        .status-pill { padding: 8px 18px; border-radius: 50px; font-size: 0.85rem; font-weight: 700; border: 1px solid transparent; display: inline-block;}
        .status-wait { background: #FFF7ED; color: #EA580C; border-color: #FED7AA; }
        .status-done { background: #ECFDF5; color: #10B981; border-color: #A7F3D0; }
        .status-cancel { background: #F1F5F9; color: #64748B; border-color: #E2E8F0; }

        .price-text { font-size: 1.6rem; font-weight: 800; color: #E11D48; margin-bottom: 0;}
        .filter-box { background: #fff; padding: 20px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #E2E8F0; margin-bottom: 30px; }
        
        .info-box { background: #F8FAFC; border-radius: 12px; padding: 12px; border: 1px solid #EEF2F6; margin-bottom: 15px;}
        
        /* Hiệu ứng link cho Upsell Badge */
        .upsell-badge { background: #E0F2FE; color: #0284C7; border: 1px dashed #BAE6FD; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; transition: 0.2s; }
        .upsell-badge.is-link:hover { background: #BAE6FD; color: #0369A1; transform: translateY(-2px); box-shadow: 0 2px 5px rgba(2,132,199,0.15);}
    </style>
</head>
<body>

    <?php include 'navbar_admin.php'; ?>

    <main class="container my-5" style="min-height: 65vh;">
        
        <div class="d-flex align-items-center mb-4">
            <h2 class="fw-bold m-0 text-dark"><i class="bi bi-ui-checks-grid text-primary me-2"></i>Quản Lý Đơn Đặt Dịch Vụ</h2>
        </div>

        <div class="filter-box">
            <form method="GET" action="admin_bookings.php" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">Tìm khách hàng / SĐT:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Nhập tên hoặc số điện thoại..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Lọc theo ngày đặt:</label>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Lọc</button>
                    <a href="admin_bookings.php?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>

        <ul class="nav nav-pills nav-custom">
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'tour' ? 'active' : '' ?>" href="?tab=tour"><i class="bi bi-compass"></i> Tour Du Lịch</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'flight' ? 'active' : '' ?>" href="?tab=flight"><i class="bi bi-airplane"></i> Vé Máy Bay</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'hotel' ? 'active' : '' ?>" href="?tab=hotel"><i class="bi bi-building"></i> Khách Sạn</a>
            </li>
        </ul>

        <!-- TABS TRẠNG THÁI -->
        <div class="d-flex gap-2 mb-4 overflow-auto pb-2 border-bottom">
            <a href="?status=<?= $qs_status ?>" class="btn <?= $status === '' ? 'btn-dark' : 'btn-outline-dark' ?> rounded-pill fw-bold text-nowrap">
                Tất cả <span class="badge bg-light text-dark ms-1 rounded-circle"><?= $count_all ?></span>
            </a>
            <a href="?status=Chờ xác nhận<?= $qs_status ?>" class="btn <?= $status === 'Chờ xác nhận' ? 'btn-warning' : 'btn-outline-warning text-dark' ?> rounded-pill fw-bold text-nowrap">
                Chờ xác nhận <span class="badge bg-light text-dark ms-1 rounded-circle"><?= $count_pending ?></span>
            </a>
            <a href="?status=Đã duyệt<?= $qs_status ?>" class="btn <?= $status === 'Đã duyệt' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill fw-bold text-nowrap">
                Đã duyệt <span class="badge bg-light text-dark ms-1 rounded-circle"><?= $count_approved ?></span>
            </a>
            <a href="?status=Đã hủy<?= $qs_status ?>" class="btn <?= $status === 'Đã hủy' ? 'btn-secondary' : 'btn-outline-secondary' ?> rounded-pill fw-bold text-nowrap">
                Đã hủy <span class="badge bg-light text-dark ms-1 rounded-circle"><?= $count_cancelled ?></span>
            </a>
        </div>

        <div id="bookingList">
            <?php if (empty($items)): ?>
                <div class="text-center p-5 bg-white rounded-4 border shadow-sm my-4">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 fw-bold">Không có dữ liệu</h5>
                    <p class="text-muted">Chưa có đơn hàng nào khớp với bộ lọc.</p>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): 
                    $bg_class = ($tab === 'tour') ? 'bg-tour' : (($tab === 'flight') ? 'bg-flight' : 'bg-hotel');
                    $type_name = ($tab === 'tour') ? 'Tour Du Lịch' : (($tab === 'flight') ? 'Vé Máy Bay' : 'Khách Sạn');
                    $icon = ($tab === 'tour') ? 'bi-compass' : (($tab === 'flight') ? 'bi-airplane-engines' : 'bi-building');
                    
                    $code = ($tab === 'tour') ? '#'.$item['BookingID'] : (($tab === 'flight') ? 'PNR: '.($item['PNR_Code']??'N/A') : 'HT-'.($item['id']??'N/A'));
                    $title = ($tab === 'tour') ? $item['TourName'] : (($tab === 'flight') ? 'Chuyến bay: '.($item['Airline']??'') : ($item['HotelName']??''));
                    
                    // --- BÓC TÁCH GHI CHÚ COMBO UPSELL KHỎI TÊN KHÁCH HÀNG ---
                    $raw_customer_name = $item['CustomerName'] ?? 'N/A';
                    $clean_customer_name = $raw_customer_name;
                    $upsell_items = [];
                    
                    if (preg_match('/\((Gói LandTour[^\)]+)\)/', $raw_customer_name, $matches)) {
                        $clean_customer_name = trim(str_replace($matches[0], '', $raw_customer_name));
                        $upsell_string = $matches[1];
                        
                        if (strpos($upsell_string, 'VMB') !== false) {
                            $upsell_items[] = "Vé Máy Bay";
                        }
                        if (strpos($upsell_string, 'Khách sạn') !== false || strpos($upsell_string, 'Lưu trú') !== false) {
                            $upsell_items[] = "Khách Sạn";
                        }
                    }

                    // --- KIỂM TRA DB XEM KHÁCH CÓ ĐƠN MÁY BAY / KHÁCH SẠN RỜI HAY KHÔNG ---
                    $has_flight_db = false;
                    $has_hotel_db = false;
                    if (!empty($item['Phone'])) {
                        $p_safe = mysqli_real_escape_string($conn, $item['Phone']);
                        // Tra vé máy bay
                        $chk_f = mysqli_query($conn, "SELECT 1 FROM flight_bookings WHERE Phone = '$p_safe' LIMIT 1");
                        if ($chk_f && mysqli_num_rows($chk_f) > 0) $has_flight_db = true;
                        
                        // Tra khách sạn
                        $chk_h = mysqli_query($conn, "SELECT 1 FROM hotel_bookings WHERE Phone = '$p_safe' LIMIT 1");
                        if ($chk_h && mysqli_num_rows($chk_h) > 0) $has_hotel_db = true;
                    }

                    if ($tab === 'tour') {
                        $item_status = $item['Status'];
                        $pay_method = $item['PaymentMethod'] ?? 'Tiền mặt';
                        $pay_status_raw = $item['GatewayPaymentStatus'] ?? $item['PaymentStatus'] ?? 'Chưa thanh toán';
                    } else {
                        $pay_status_raw = $item['PaymentStatus'] ?? 'Chưa thanh toán';
                        $item_status = (in_array($pay_status_raw, ['Pending', 'Chưa thanh toán'])) ? 'Chờ xác nhận' : ((in_array($pay_status_raw, ['Cancelled', 'Đã hủy'])) ? 'Đã hủy' : 'Đã duyệt');
                        $pay_method = 'Thanh toán Online';
                    }

                    $status_class = 'status-wait';
                    if ($item_status === 'Đã duyệt') $status_class = 'status-done';
                    elseif ($item_status === 'Đã hủy') $status_class = 'status-cancel';
                    
                    $pay_badge = in_array($pay_status_raw, ['Paid', 'Đã thanh toán']) ? 'bg-success-subtle text-success border-success' : 'bg-warning-subtle text-warning-emphasis border-warning';
                    $pay_text = in_array($pay_status_raw, ['Paid', 'Đã thanh toán']) ? 'Đã thanh toán' : 'Chưa TT';

                    $img = 'https://placehold.co/600x400?text=' . urlencode($type_name);
                    if ($tab === 'tour' && !empty($item['ImageURL'])) $img = $item['ImageURL'];
                    elseif ($tab === 'flight') $img = 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?q=80&w=400';
                    elseif ($tab === 'hotel') $img = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=400';
                ?>
                    <div class="unified-card">
                        <div class="type-badge <?= $bg_class ?>"><i class="<?= $icon ?> me-1"></i> <?= $type_name ?></div>
                        
                        <div class="row g-4 mt-2">
                            <div class="col-md-3">
                                <img src="<?= htmlspecialchars($img) ?>" class="unified-img" alt="image" onerror="this.src='https://placehold.co/600x400';">
                            </div>
                            
                            <div class="col-md-6 d-flex flex-column border-end pe-4">
                                <h4 class="unified-title d-flex align-items-center flex-wrap gap-2">
                                    <?= htmlspecialchars($title) ?>
                                    <?php if ($tab === 'tour'): ?>
                                        <?php if (isset($item['IsFullPackage']) && $item['IsFullPackage'] == 0): ?>
                                            <span class="badge bg-warning text-dark align-middle" style="font-size: 0.75rem;"><i class="bi bi-person-walking"></i> Tự túc</span>
                                        <?php else: ?>
                                            <span class="badge bg-success align-middle" style="font-size: 0.75rem;"><i class="bi bi-box-seam"></i> Trọn gói</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </h4>
                                
                                <!-- HIỂN THỊ BADGE UPSELL THÔNG MINH -->
                                <?php if (!empty($upsell_items)): ?>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                        <span class="badge bg-dark text-white fw-normal"><i class="bi bi-plus-circle me-1"></i> Mua kèm:</span>
                                        <?php foreach($upsell_items as $up): ?>
                                            <?php if ($up == 'Vé Máy Bay'): ?>
                                                <?php if ($has_flight_db): ?>
                                                    <a href="?tab=flight&search=<?= urlencode($item['Phone'] ?? '') ?>" class="upsell-badge is-link" title="Đã tìm thấy đơn rời. Bấm để chuyển sang Vé Máy Bay">
                                                        <i class="bi bi-airplane"></i> <?= $up ?> <i class="bi bi-link-45deg ms-1 fs-6"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="upsell-badge text-secondary" style="background: #f1f5f9; border-color: #e2e8f0; cursor: not-allowed;" title="Nằm trọn trong gói Tour, không có đơn rời">
                                                        <i class="bi bi-airplane"></i> <?= $up ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if ($has_hotel_db): ?>
                                                    <a href="?tab=hotel&search=<?= urlencode($item['Phone'] ?? '') ?>" class="upsell-badge is-link" title="Đã tìm thấy đơn rời. Bấm để chuyển sang Khách sạn">
                                                        <i class="bi bi-building"></i> <?= $up ?> <i class="bi bi-link-45deg ms-1 fs-6"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="upsell-badge text-secondary" style="background: #f1f5f9; border-color: #e2e8f0; cursor: not-allowed;" title="Nằm trọn trong gói Tour, không có đơn rời">
                                                        <i class="bi bi-building"></i> <?= $up ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="info-box">
                                    <div class="fw-bold text-dark mb-1"><i class="bi bi-person-circle text-primary me-2"></i><?= htmlspecialchars($clean_customer_name) ?></div>
                                    <div class="d-flex flex-wrap gap-3 text-muted small">
                                        <span><i class="bi bi-telephone-fill me-1"></i> <?= htmlspecialchars($item['Phone'] ?? 'N/A') ?></span>
                                        <?php if(!empty($item['Email'])): ?>
                                            <span><i class="bi bi-envelope-fill me-1"></i> <?= htmlspecialchars($item['Email']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($tab === 'tour'): ?>
                                    <div class="text-muted small mb-2 d-flex flex-wrap gap-3">
                                        <span><i class="bi bi-people text-primary"></i> SL: <strong class="text-dark"><?= $item['Quantity'] ?? 1 ?></strong> khách</span>
                                        <span><i class="bi bi-calendar-event text-danger"></i> Khởi hành: <strong class="text-dark"><?= !empty($item['DepartureDate']) ? date('d/m/Y', strtotime($item['DepartureDate'])) : 'N/A' ?></strong></span>
                                    </div>
                                <?php elseif ($tab === 'flight'): ?>
                                    <div class="text-muted small mb-2 d-flex flex-wrap gap-3">
                                        <span><i class="bi bi-calendar-event text-danger"></i> Ngày bay: <strong class="text-dark"><?= !empty($item['DepartDate']) ? date('d/m/Y', strtotime($item['DepartDate'])) : 'N/A' ?></strong></span>
                                        <span><i class="bi bi-people text-primary"></i> SL: <strong class="text-dark"><?= ($item['Adults'] ?? 0) + ($item['Children'] ?? 0) ?></strong> vé</span>
                                    </div>
                                <?php elseif ($tab === 'hotel'): ?>
                                    <div class="text-muted small mb-2 d-flex flex-wrap gap-3">
                                        <span><i class="bi bi-calendar-event text-danger"></i> Check-in: <strong class="text-dark"><?= !empty($item['CheckInDate']) ? date('d/m/Y', strtotime($item['CheckInDate'])) : 'N/A' ?></strong></span>
                                        <span><i class="bi bi-calendar-minus text-secondary"></i> Check-out: <strong class="text-dark"><?= !empty($item['CheckOutDate']) ? date('d/m/Y', strtotime($item['CheckOutDate'])) : 'N/A' ?></strong></span>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex flex-wrap align-items-center gap-2 mt-auto">
                                    <span class="badge bg-light text-dark border"><i class="bi bi-wallet2 text-primary me-1"></i><?= htmlspecialchars($pay_method) ?></span>
                                    <span class="badge border <?= $pay_badge ?>"><i class="bi bi-cash-coin me-1"></i><?= $pay_text ?></span>
                                    
                                    <?php if(!empty($item['TransactionCode'])): ?>
                                        <span class="small text-muted fst-italic ms-2"><i class="bi bi-upc-scan me-1"></i>GD: <?= htmlspecialchars($item['TransactionCode']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-3 d-flex flex-column justify-content-between text-end">
                                <div>
                                    <div class="text-muted small fw-bold mb-2">MÃ ĐƠN: <span class="badge bg-secondary fs-6"><?= $code ?></span></div>
                                    <div class="text-muted small mb-1">Tổng tiền thanh toán</div>
                                    <p class="price-tag mb-3" style="font-size: 1.8rem; font-weight: 800; color: #E11D48;"><?= number_format($item['TotalPrice'] ?? 0, 0, ',', '.') ?> ₫</p>
                                    <div class="mb-4">
                                        <span class="status-pill <?= $status_class ?> shadow-sm"><?= htmlspecialchars($item_status) ?></span>
                                    </div>
                                </div>

                                <div class="d-flex flex-column gap-2 mt-auto">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <?php if ($tab === 'tour'): ?>
                                            <a href="export_pdf.php?id=<?= $item['BookingID'] ?>" target="_blank" class="btn btn-outline-info rounded-pill px-3 shadow-sm"><i class="bi bi-printer"></i> In vé</a>
                                        <?php endif; ?>

                                        <?php if ($tab === 'tour' && $item_status === 'Chờ xác nhận'): ?>
                                            <form method="POST" action="admin_bookings.php" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="booking_id" value="<?= $item['BookingID'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-success fw-bold rounded-pill shadow-sm" onclick="return confirm('Duyệt đơn này?');"><i class="bi bi-check-lg"></i> Duyệt đơn</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <form method="POST" action="admin_bookings.php" class="m-0 text-end">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="tab_type" value="<?= $tab ?>">
                                        <?php $del_id = $item['BookingID'] ?? $item['FlightBookingID'] ?? $item['HotelBookingID'] ?? $item['id']; ?>
                                        <input type="hidden" name="booking_id" value="<?= $del_id ?>">
                                        <button type="submit" name="action" value="delete" class="btn btn-outline-danger btn-sm rounded-pill px-4 shadow-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn đơn này?');"><i class="bi bi-trash"></i> Xóa Đơn</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center shadow-sm">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link rounded-start-pill px-3" href="?page=<?= max(1, $page - 1) ?><?= $qs ?>"><i class="bi bi-chevron-left"></i> Trước</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page === $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $qs ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link rounded-end-pill px-3" href="?page=<?= min($total_pages, $page + 1) ?><?= $qs ?>">Sau <i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </main>

    <footer class="text-center mt-5 py-4 text-muted small bg-white border-top">
        © 2026 Quản trị MiềnTây Travel - Giải pháp Vận hành Du lịch
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>