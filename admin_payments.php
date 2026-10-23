<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// BẢO VỆ PHÍA MÁY CHỦ: Chỉ tài khoản Admin đăng nhập mới được truy cập
if (!isset($_SESSION['admin_id'])) {
    echo "<script>
        alert('CẢNH BÁO: Bạn chưa đăng nhập trang Quản trị!');
        window.location.href = 'login_admin.php';
    </script>";
    exit();
}

include 'db.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- XỬ LÝ CẬP NHẬT TRẠNG THÁI THANH TOÁN (Cho Tiền mặt/Chuyển khoản) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_payment') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("<script>alert('Lỗi bảo mật!'); window.location.href='admin_payments.php';</script>");
    }

    $payment_id = intval($_POST['payment_id']);
    
    $sql_update_payment = "UPDATE payments SET PaymentStatus = 'Paid', PaidAt = NOW() WHERE PaymentID = ?";
    $stmt_pay = mysqli_prepare($conn, $sql_update_payment);
    mysqli_stmt_bind_param($stmt_pay, "i", $payment_id);
    
    if (mysqli_stmt_execute($stmt_pay)) {
        $sql_get = "SELECT BookingID, FlightBookingID, HotelBookingID FROM payments WHERE PaymentID = ?";
        $stmt_get = mysqli_prepare($conn, $sql_get);
        mysqli_stmt_bind_param($stmt_get, "i", $payment_id);
        mysqli_stmt_execute($stmt_get);
        $res_get = mysqli_stmt_get_result($stmt_get);
        
        if ($data = mysqli_fetch_assoc($res_get)) {
            if (!empty($data['BookingID'])) {
                mysqli_query($conn, "UPDATE bookings SET PaymentStatus = 'Đã thanh toán', Status = 'Đã duyệt' WHERE BookingID = " . $data['BookingID']);
            } elseif (!empty($data['FlightBookingID'])) {
                mysqli_query($conn, "UPDATE flight_bookings SET PaymentStatus = 'Paid' WHERE FlightBookingID = " . $data['FlightBookingID']);
            } elseif (!empty($data['HotelBookingID'])) {
                mysqli_query($conn, "UPDATE hotel_bookings SET PaymentStatus = 'Paid' WHERE HotelBookingID = " . $data['HotelBookingID']);
            }
        }
        header("Location: admin_payments.php?msg=payment_confirmed");
        exit();
    }
}

// --- LẤY BỘ LỌC TÌM KIẾM ---
$tab = $_GET['tab'] ?? 'tour';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$method_filter = isset($_GET['method']) ? trim($_GET['method']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

$where_clauses = ["1=1"];
if ($tab === 'tour') {
    $where_clauses[] = "p.BookingID IS NOT NULL";
} elseif ($tab === 'flight') {
    $where_clauses[] = "p.FlightBookingID IS NOT NULL";
} elseif ($tab === 'hotel') {
    $where_clauses[] = "p.HotelBookingID IS NOT NULL";
}

$params = [];
$types = "";

if ($search !== '') {
    if ($tab === 'tour') {
        $where_clauses[] = "(p.OrderCode LIKE ? OR p.TransactionCode LIKE ? OR b.CustomerName LIKE ? OR b.Phone LIKE ? OR CAST(p.BookingID AS CHAR) = ?)";
        $s_like = "%$search%";
        $params = array_merge($params, [$s_like, $s_like, $s_like, $s_like, $search]);
        $types .= "sssss";
    } elseif ($tab === 'flight') {
        $where_clauses[] = "(p.OrderCode LIKE ? OR p.TransactionCode LIKE ? OR fb.CustomerName LIKE ? OR fb.Phone LIKE ? OR CAST(p.FlightBookingID AS CHAR) = ?)";
        $s_like = "%$search%";
        $params = array_merge($params, [$s_like, $s_like, $s_like, $s_like, $search]);
        $types .= "sssss";
    } else {
        $where_clauses[] = "(p.OrderCode LIKE ? OR p.TransactionCode LIKE ? OR hb.CustomerName LIKE ? OR hb.Phone LIKE ? OR CAST(p.HotelBookingID AS CHAR) = ?)";
        $s_like = "%$search%";
        $params = array_merge($params, [$s_like, $s_like, $s_like, $s_like, $search]);
        $types .= "sssss";
    }
}

if ($method_filter !== '') {
    $where_clauses[] = "pm.Code = ?";
    $params[] = $method_filter;
    $types .= "s";
}

if ($status_filter !== '') {
    $where_clauses[] = "p.PaymentStatus = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($start_date !== '') {
    $where_clauses[] = "DATE(COALESCE(p.PaidAt, p.CreatedAt)) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if ($end_date !== '') {
    $where_clauses[] = "DATE(COALESCE(p.PaidAt, p.CreatedAt)) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Thống kê
$stat_sql = "
    SELECT 
        COUNT(CASE WHEN p.PaymentStatus = 'Paid' THEN 1 END) as paid_count,
        COALESCE(SUM(CASE WHEN p.PaymentStatus = 'Paid' THEN p.Amount ELSE 0 END), 0) as paid_revenue,
        COUNT(CASE WHEN p.PaymentStatus = 'Failed' THEN 1 END) as failed_count,
        COUNT(CASE WHEN p.PaymentStatus = 'Pending' THEN 1 END) as pending_count,
        COUNT(*) as total_records
    FROM payments p
    LEFT JOIN payment_methods pm ON p.PaymentMethodID = pm.PaymentMethodID
    LEFT JOIN bookings b ON p.BookingID = b.BookingID
    LEFT JOIN flight_bookings fb ON p.FlightBookingID = fb.FlightBookingID
    LEFT JOIN hotel_bookings hb ON p.HotelBookingID = hb.HotelBookingID
    WHERE $where_sql
";

$stmt_stat = mysqli_prepare($conn, $stat_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_stat, $types, ...$params);
}
mysqli_stmt_execute($stmt_stat);
$stat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stat));
mysqli_stmt_close($stmt_stat);

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$total_rows = (int)($stat['total_records'] ?? 0);
$total_pages = max(1, (int)ceil($total_rows / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit; // Đã sửa lỗi mất dấu trừ

// Lấy danh sách giao dịch kèm theo chi tiết Đơn hàng (Đã bổ sung Quantity, Date, IsFullPackage)
$list_sql = "
    SELECT 
        p.PaymentID, p.BookingID, p.FlightBookingID, p.HotelBookingID,
        p.OrderCode, p.Amount, p.TransactionCode, p.PaymentStatus, p.PaidAt, p.CreatedAt, p.BankCode, p.TransactionInfo,
        pm.MethodName, pm.Code as MethodCode,
        b.PaymentMethod AS BookingPaymentMethod,
        COALESCE(b.CustomerName, fb.CustomerName, hb.CustomerName) as CustomerName,
        COALESCE(b.Phone, fb.Phone, hb.Phone) as Phone,
        t.TourName, t.IsFullPackage, b.Quantity as TourQty, b.DepartureDate as TourDepDate,
        fb.Airline, hb.HotelName
    FROM payments p
    LEFT JOIN payment_methods pm ON p.PaymentMethodID = pm.PaymentMethodID
    LEFT JOIN bookings b ON p.BookingID = b.BookingID
    LEFT JOIN flight_bookings fb ON p.FlightBookingID = fb.FlightBookingID
    LEFT JOIN hotel_bookings hb ON p.HotelBookingID = hb.HotelBookingID
    LEFT JOIN tours t ON b.TourID = t.TourID
    WHERE $where_sql
    ORDER BY COALESCE(p.PaidAt, p.CreatedAt) DESC, p.PaymentID DESC
    LIMIT ? OFFSET ?
";

$stmt_list = mysqli_prepare($conn, $list_sql);
$list_params = $params;
$list_params[] = $limit;
$list_params[] = $offset;
$list_types = $types . "ii";

mysqli_stmt_bind_param($stmt_list, $list_types, ...$list_params);
mysqli_stmt_execute($stmt_list);
$result_list = mysqli_stmt_get_result($stmt_list);

$methods_res = mysqli_query($conn, "SELECT Code, MethodName FROM payment_methods WHERE IsActive = 1");
$active_methods = [];
if ($methods_res) {
    while ($m = mysqli_fetch_assoc($methods_res)) {
        $active_methods[] = $m;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Lịch sử Thanh toán - Admin Miền Tây Travel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Lexend:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
        body { background-color: #F8FAFC; font-family: 'Inter', sans-serif; color: #1E293B; }
        .nav-custom { gap: 10px; border-bottom: 2px solid #E2E8F0; padding-bottom: 10px; margin-bottom: 25px; }
        .nav-custom .nav-link { color: #64748B; font-weight: 600; border-radius: 50px; padding: 10px 24px; transition: 0.3s; border: 1px solid transparent; }
        .nav-custom .nav-link:hover { background: #F1F5F9; color: #0F172A; }
        .nav-custom .nav-link.active { background: #00558F; color: #fff; box-shadow: 0 4px 12px rgba(0, 85, 143, 0.3); }

        .stat-card { background: #FFFFFF; border-radius: 16px; padding: 22px 24px; border: 1px solid #E2E8F0; box-shadow: 0 4px 15px rgba(0, 40, 77, 0.04); display: flex; align-items: center; gap: 18px; height: 100%; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0, 40, 77, 0.08); }
        .stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0; }
        .stat-icon.success { background: #ECFDF5; color: #059669; }
        .stat-icon.primary { background: #EFF6FF; color: #0284C7; }
        .stat-icon.warning { background: #FFF7ED; color: #EA580C; }
        .stat-icon.danger  { background: #FEF2F2; color: #DC2626; }
        .stat-title { font-size: 0.82rem; color: #64748B; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .stat-value { font-family: 'Lexend', sans-serif; font-size: 1.45rem; font-weight: 700; color: #0F172A; line-height: 1.2; margin-bottom: 0; }
        
        .filter-panel { background: #FFFFFF; border-radius: 16px; padding: 22px; border: 1px solid #E2E8F0; box-shadow: 0 4px 15px rgba(0, 40, 77, 0.04); margin-bottom: 25px; }
        .table-card { background: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; box-shadow: 0 4px 20px rgba(0, 40, 77, 0.05); overflow: hidden; }
        .table-custom th { background: #F1F5F9; color: #334155; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 1.5px solid #CBD5E1; white-space: nowrap; }
        .table-custom td { padding: 15px 16px; vertical-align: middle; border-bottom: 1px solid #F1F5F9; font-size: 0.88rem; }
        .table-custom tbody tr:hover { background-color: #F8FAFC; }
        
        .badge-status { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 50px; font-size: 0.76rem; font-weight: 700; letter-spacing: 0.3px; }
        .badge-status.paid { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
        .badge-status.pending { background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A; }
        .badge-status.failed { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
        .badge-status.cancelled { background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; }
        
        .method-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 8px; font-size: 0.78rem; font-weight: 600; background: #F1F5F9; color: #334155; border: 1px solid #E2E8F0; }
        .method-badge.vnpay { background: #EFF6FF; color: #0369A1; border-color: #BAE6FD; }
        .method-badge.bank { background: #FAF5FF; color: #7E22CE; border-color: #E9D5FF; }
        
        .money-text { font-family: 'Lexend', sans-serif; font-weight: 700; color: #0F172A; font-size: 1rem; }
        .order-code-badge { font-family: monospace; background: #F8FAFC; color: #00558F; padding: 3px 8px; border-radius: 6px; border: 1px solid #E2E8F0; font-size: 0.85rem; font-weight: 600; }
        
        .modal-detail-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px dashed #E2E8F0; font-size: 0.95rem; }
        .modal-detail-row:last-child { border-bottom: none; }
        .modal-detail-label { color: #64748B; font-weight: 500; }
        .modal-detail-val { color: #0F172A; font-weight: 600; text-align: right; }
        .invoice-header { background: #F8FAFC; padding: 15px; border-radius: 12px; margin-bottom: 15px; border: 1px solid #E2E8F0; }
    </style>
</head>
<body>

    <?php include 'navbar_admin.php'; ?>

    <div class="container-fluid px-4 py-4">

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'payment_confirmed'): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i>Đã xác nhận thanh toán thành công!</div>
        <?php endif; ?>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2" style="font-family:'Lexend',sans-serif;">
                    <i class="bi bi-wallet2 text-primary"></i> Quản Lý Lịch Sử Thanh Toán
                </h3>
                <p class="text-muted small mb-0">Theo dõi dòng tiền, chi tiết hóa đơn khách hàng.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="admin_bookings.php" class="btn btn-outline-primary rounded-pill px-3 py-2 small fw-bold">
                    <i class="bi bi-list-check me-1"></i> Quản lý Đơn
                </a>
                <a href="admin_payments.php?tab=<?= $tab ?>" class="btn btn-primary rounded-pill px-3 py-2 small fw-bold" style="background:#00558F; border-color:#00558F;">
                    <i class="bi bi-arrow-clockwise me-1"></i> Làm mới
                </a>
            </div>
        </div>

        <!-- THỐNG KÊ -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon success"><i class="bi bi-cash-stack"></i></div>
                    <div>
                        <div class="stat-title">Doanh thu đã thu</div>
                        <h4 class="stat-value text-success"><?= number_format($stat['paid_revenue'], 0, ',', '.') ?> ₫</h4>
                        <span class="text-muted small">Từ <strong><?= $stat['paid_count'] ?></strong> GD thành công</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <div class="stat-title">Giao dịch thành công</div>
                        <h4 class="stat-value text-primary"><?= number_format($stat['paid_count']) ?></h4>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-title">Đang chờ xử lý</div>
                        <h4 class="stat-value text-warning"><?= number_format($stat['pending_count']) ?></h4>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon danger"><i class="bi bi-x-circle-fill"></i></div>
                    <div>
                        <div class="stat-title">Thất bại / Hủy</div>
                        <h4 class="stat-value text-danger"><?= number_format($stat['failed_count']) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- BỘ LỌC -->
        <div class="filter-panel">
            <form action="admin_payments.php" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-bold text-muted mb-1">Tìm kiếm</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Mã đơn, tên khách, SĐT..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-bold text-muted mb-1">Phương thức</label>
                    <select name="method" class="form-select">
                        <option value="">-- Tất cả phương thức --</option>
                        <?php foreach ($active_methods as $m): ?>
                            <option value="<?= htmlspecialchars($m['Code']) ?>" <?= ($method_filter === $m['Code']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['MethodName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-bold text-muted mb-1">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="Paid" <?= ($status_filter === 'Paid') ? 'selected' : '' ?>>Thành công</option>
                        <option value="Pending" <?= ($status_filter === 'Pending') ? 'selected' : '' ?>>Đang xử lý</option>
                        <option value="Failed" <?= ($status_filter === 'Failed') ? 'selected' : '' ?>>Thất bại</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Từ ngày</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                </div>

                <div class="col-lg-2 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Đến ngày</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                </div>

                <div class="col-lg-1 col-md-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold" style="background:#00558F; border-color:#00558F;" title="Lọc dữ liệu">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- TABS -->
        <ul class="nav nav-pills nav-custom">
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'tour' ? 'active' : '' ?>" href="admin_payments.php?tab=tour"><i class="bi bi-compass"></i> Tour Du Lịch</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'flight' ? 'active' : '' ?>" href="admin_payments.php?tab=flight"><i class="bi bi-airplane"></i> Vé Máy Bay</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'hotel' ? 'active' : '' ?>" href="admin_payments.php?tab=hotel"><i class="bi bi-building"></i> Khách Sạn</a>
            </li>
        </ul>

        <!-- BẢNG DỮ LIỆU ĐÃ NÂNG CẤP CHI TIẾT -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">STT</th>
                            <th>Mã Giao Dịch</th>
                            <th>Mã Đơn</th>
                            <th>Khách Hàng & Liên Hệ</th>
                            <th width="25%">Thông Tin Đặt Dịch Vụ</th>
                            <th>Phương Thức</th>
                            <th class="text-end">Tổng Tiền</th>
                            <th>Ngày / Giờ TT</th>
                            <th class="text-center">Trạng Thái</th>
                            <th class="text-center" style="width: 120px;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_list && mysqli_num_rows($result_list) > 0):
                            $idx = $offset;
                            while ($row = mysqli_fetch_assoc($result_list)):
                                $idx++;

                                $has_paid_time = !empty($row['PaidAt']);
                                $payment_timestamp = $has_paid_time ? strtotime($row['PaidAt']) : strtotime($row['CreatedAt']);
                                $pay_datetime = $payment_timestamp ? date('d/m/Y H:i', $payment_timestamp) : '—';

                                $cust_name = !empty($row['CustomerName']) ? $row['CustomerName'] : 'Khách vãng lai';
                                $cust_phone = !empty($row['Phone']) ? $row['Phone'] : 'Không có SĐT';
                                
                                $ref_id = ($tab === 'tour') ? $row['BookingID'] : (($tab === 'flight') ? $row['FlightBookingID'] : $row['HotelBookingID']);
                                $service_title = ($tab === 'tour') ? ($row['TourName'] ?? 'Tour #'.$ref_id) : (($tab === 'flight') ? ('Chuyến bay: '.($row['Airline']??'')) : ($row['HotelName']??''));

                                // Dữ liệu bổ sung cho Tour
                                $tour_qty = $row['TourQty'] ?? 1;
                                $tour_dep_date = !empty($row['TourDepDate']) ? date('d/m/Y', strtotime($row['TourDepDate'])) : 'Chưa rõ';
                                $is_full_pkg = isset($row['IsFullPackage']) ? $row['IsFullPackage'] : 1;

                                // XỬ LÝ HIỂN THỊ PHƯƠNG THỨC
                                $raw_method = $row['BookingPaymentMethod'] ?? $row['MethodName'] ?? 'Tiền mặt';
                                $is_vnpay = (stripos($raw_method, 'VNPay') !== false || $row['MethodCode'] === 'VNPAY');
                                $is_cash = (stripos($raw_method, 'Tiền mặt') !== false || stripos($raw_method, 'trực tiếp') !== false);
                                
                                if ($is_cash) {
                                    $method_class = 'bank';
                                    $method_display = 'Tiền mặt / Trực tiếp';
                                    $method_icon = '<i class="bi bi-cash-coin text-success"></i>';
                                } elseif ($is_vnpay) {
                                    $method_class = 'vnpay';
                                    $method_display = 'VNPay';
                                    $method_icon = '<i class="bi bi-qr-code text-primary"></i>';
                                } else {
                                    $method_class = 'bank';
                                    $method_display = $raw_method;
                                    $method_icon = '<i class="bi bi-bank text-success"></i>';
                                }

                                $st = $row['PaymentStatus'];
                                $badge_class = 'pending'; $badge_text = 'Đang xử lý'; $badge_icon = 'bi-hourglass-split';
                                if ($st === 'Paid') { $badge_class = 'paid'; $badge_text = 'Thành công'; $badge_icon = 'bi-check-circle-fill'; }
                                elseif ($st === 'Failed') { $badge_class = 'failed'; $badge_text = 'Thất bại'; $badge_icon = 'bi-x-circle-fill'; }
                                elseif ($st === 'Cancelled') { $badge_class = 'cancelled'; $badge_text = 'Đã hủy'; $badge_icon = 'bi-slash-circle'; }
                        ?>
                        <tr>
                            <td class="text-center text-muted fw-bold"><?= $idx ?></td>
                            <td>
                                <?php if (!empty($row['TransactionCode'])): ?>
                                    <span class="order-code-badge"><?= htmlspecialchars($row['TransactionCode']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><strong class="text-dark">#<?= $ref_id ?></strong></td>
                            
                            <!-- ĐÃ NÂNG CẤP: Hiện tên và SĐT -->
                            <td>
                                <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($cust_name) ?></div>
                                <div class="small text-muted"><i class="bi bi-telephone text-success"></i> <?= htmlspecialchars($cust_phone) ?></div>
                            </td>
                            
                            <!-- ĐÃ NÂNG CẤP: Hiện Chi tiết loại Tour, Ngày đi, Số lượng -->
                            <td>
                                <div class="fw-bold text-dark mb-1 text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($service_title) ?>">
                                    <?= htmlspecialchars($service_title) ?>
                                </div>
                                <?php if($tab === 'tour'): ?>
                                    <div class="small text-muted d-flex align-items-center gap-2 mb-1">
                                        <span><i class="bi bi-people text-primary"></i> SL: <?= $tour_qty ?> vé</span>
                                        <span><i class="bi bi-calendar-event text-danger"></i> Đi: <?= $tour_dep_date ?></span>
                                    </div>
                                    <div>
                                        <?php if($is_full_pkg == 0): ?>
                                            <span class="badge bg-warning text-dark" style="font-size: 0.65rem;"><i class="bi bi-person-walking"></i> Tự túc</span>
                                        <?php else: ?>
                                            <span class="badge bg-success" style="font-size: 0.65rem;"><i class="bi bi-box-seam"></i> Trọn gói</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="method-badge <?= $method_class ?>">
                                    <?= $method_icon ?> <?= htmlspecialchars($method_display) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="money-text <?= ($st === 'Paid') ? 'text-success' : 'text-dark' ?>">
                                    <?= number_format($row['Amount'], 0, ',', '.') ?> ₫
                                </span>
                            </td>
                            <td><span class="small fw-semibold text-secondary"><?= $pay_datetime ?></span></td>
                            <td class="text-center">
                                <span class="badge-status <?= $badge_class ?>">
                                    <i class="bi <?= $badge_icon ?>"></i> <?= $badge_text ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <?php if ($st !== 'Paid' && $is_cash): ?>
                                        <form method="POST" action="admin_payments.php?tab=<?= $tab ?>" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="action" value="confirm_payment">
                                            <input type="hidden" name="payment_id" value="<?= $row['PaymentID'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success rounded-circle" style="width: 32px; height: 32px; padding: 0;" title="Xác nhận đã nhận tiền mặt" onclick="return confirm('Xác nhận khách đã thanh toán tiền mặt trực tiếp?');">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" style="width: 32px; height: 32px; padding: 0;" data-bs-toggle="modal" data-bs-target="#paymentModal<?= $row['PaymentID'] ?>" title="Xem Hóa Đơn (Invoice)">
                                        <i class="bi bi-receipt"></i>
                                    </button>
                                </div>

                                <!-- MODAL HÓA ĐƠN ĐÃ ĐƯỢC THIẾT KẾ LẠI -->
                                <div class="modal fade text-start" id="paymentModal<?= $row['PaymentID'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                                            <div class="modal-header text-white" style="background: linear-gradient(135deg, #00284D 0%, #00558F 100%); border-top-left-radius: 18px; border-top-right-radius: 18px;">
                                                <h5 class="modal-title fw-bold fs-6">
                                                    <i class="bi bi-receipt-cutoff me-2"></i> BIÊN LAI THANH TOÁN #<?= $row['PaymentID'] ?>
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                
                                                <div class="invoice-header text-center">
                                                    <h3 class="fw-bold <?= ($st === 'Paid') ? 'text-success' : 'text-danger' ?> mb-1">
                                                        <?= number_format($row['Amount'], 0, ',', '.') ?> ₫
                                                    </h3>
                                                    <span class="badge-status <?= $badge_class ?> mt-2">
                                                        <i class="bi <?= $badge_icon ?>"></i> <?= $badge_text ?>
                                                    </span>
                                                </div>

                                                <h6 class="fw-bold text-dark mt-4 mb-3 border-bottom pb-2">THÔNG TIN KHÁCH HÀNG</h6>
                                                <div class="modal-detail-row">
                                                    <span class="modal-detail-label">Họ và Tên:</span>
                                                    <span class="modal-detail-val"><?= htmlspecialchars($cust_name) ?></span>
                                                </div>
                                                <div class="modal-detail-row">
                                                    <span class="modal-detail-label">Số điện thoại:</span>
                                                    <span class="modal-detail-val"><?= htmlspecialchars($cust_phone) ?></span>
                                                </div>

                                                <h6 class="fw-bold text-dark mt-4 mb-3 border-bottom pb-2">CHI TIẾT DỊCH VỤ (#<?= $ref_id ?>)</h6>
                                                <div class="modal-detail-row">
                                                    <span class="modal-detail-label">Tên Dịch Vụ:</span>
                                                    <span class="modal-detail-val text-primary" style="max-width: 60%; text-align:right;">
                                                        <?= htmlspecialchars($service_title) ?>
                                                    </span>
                                                </div>
                                                <?php if($tab === 'tour'): ?>
                                                <div class="modal-detail-row">
                                                    <span class="modal-detail-label">Số lượng vé:</span>
                                                    <span class="modal-detail-val"><?= $tour_qty ?> Người</span>
                                                </div>
                                                <div class="modal-detail-row">
                                                    <span class="modal-detail-label">Ngày khởi hành:</span>
                                                    <span class="modal-detail-val"><?= $tour_dep_date ?></span>
                                                </div>
                                                <?php endif; ?>

                                                <h6 class="fw-bold text-dark mt-4 mb-3 border-bottom pb-2">CHI TIẾT THANH TOÁN</h6>
                                                <div class="modal-detail-row">
                                                    <span class="modal-detail-label">Phương thức:</span>
                                                    <span class="modal-detail-val"><?= htmlspecialchars($method_display) ?></span>
                                                </div>
                                                <div class="modal-detail-row">
                                                    <span class="modal-detail-label">Mã Giao Dịch Ngân Hàng:</span>
                                                    <span class="modal-detail-val"><?= !empty($row['TransactionCode']) ? htmlspecialchars($row['TransactionCode']) : '—' ?></span>
                                                </div>
                                                <?php if(!empty($row['BankCode'])): ?>
                                                <div class="modal-detail-row">
                                                    <span class="modal-detail-label">Ngân Hàng (Bank Code):</span>
                                                    <span class="modal-detail-val fw-bold text-info"><?= htmlspecialchars($row['BankCode']) ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <div class="modal-detail-row">
                                                    <span class="modal-detail-label">Lúc thanh toán:</span>
                                                    <span class="modal-detail-val"><?= $pay_datetime ?></span>
                                                </div>
                                                <?php if(!empty($row['TransactionInfo'])): ?>
                                                <div class="modal-detail-row align-items-start">
                                                    <span class="modal-detail-label mt-1">Nội dung chuyển khoản:</span>
                                                    <span class="modal-detail-val text-muted small" style="max-width: 60%; text-align:right; font-style:italic;">
                                                        "<?= htmlspecialchars($row['TransactionInfo']) ?>"
                                                    </span>
                                                </div>
                                                <?php endif; ?>

                                            </div>
                                            <div class="modal-footer bg-light border-0 py-3">
                                                <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Đóng Biên Lai</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block text-secondary mb-2"></i>
                                Không tìm thấy lịch sử giao dịch nào phù hợp trong danh mục này.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PHÂN TRANG -->
            <?php if ($total_pages > 1): ?>
            <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-muted small">
                    Trang <strong><?= $page ?></strong> / <strong><?= $total_pages ?></strong> (Tổng <strong><?= $total_rows ?></strong> giao dịch)
                </span>
                <nav aria-label="Phân trang lịch sử thanh toán">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="admin_payments.php?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                            <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="admin_payments.php?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" <?= ($p === $page) ? 'style="background:#00558F; border-color:#00558F;"' : '' ?>>
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="admin_payments.php?<?= http_build_query(array_merge($_GET, ['page' => min($total_pages, $page + 1)])) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>