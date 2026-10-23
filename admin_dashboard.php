<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    echo "<script>
            alert('CẢNH BÁO: Bạn chưa đăng nhập trang Quản trị!');
            window.location.href='login_admin.php';
          </script>";
    exit();
}

include 'db.php';

// ==========================================
// 0. XỬ LÝ LỌC THEO THÁNG / NĂM
// ==========================================
$filter_month = $_GET['month'] ?? 'all';
$filter_year = $_GET['year'] ?? 'all';

$filter_sql_pay = "";
$filter_sql_book = "";
$filter_sql_flight = "";
$filter_sql_hotel = "";

if ($filter_year !== 'all') {
    $filter_sql_pay .= " AND YEAR(CreatedAt) = " . intval($filter_year);
    $filter_sql_book .= " AND YEAR(BookingDate) = " . intval($filter_year); 
    $filter_sql_flight .= " AND YEAR(CreatedAt) = " . intval($filter_year);
    $filter_sql_hotel .= " AND YEAR(CreatedAt) = " . intval($filter_year);

    if ($filter_month !== 'all') {
        $filter_sql_pay .= " AND MONTH(CreatedAt) = " . intval($filter_month);
        $filter_sql_book .= " AND MONTH(BookingDate) = " . intval($filter_month);
        $filter_sql_flight .= " AND MONTH(CreatedAt) = " . intval($filter_month);
        $filter_sql_hotel .= " AND MONTH(CreatedAt) = " . intval($filter_month);
    }
}

// ==========================================
// 1. THỐNG KÊ DOANH THU CÓ LỌC
// ==========================================
$sql_revenue = "SELECT COALESCE(SUM(Amount), 0) AS Revenue FROM payments WHERE PaymentStatus = 'Paid' $filter_sql_pay";
$res_revenue = mysqli_query($conn, $sql_revenue);
$total_revenue = $res_revenue ? (float)(mysqli_fetch_assoc($res_revenue)['Revenue'] ?? 0) : 0;

// Doanh thu chia 3 nguồn
$sql_rev_tour = "SELECT COALESCE(SUM(Amount), 0) AS Rev FROM payments WHERE PaymentStatus = 'Paid' AND BookingID IS NOT NULL $filter_sql_pay";
$total_rev_tour = (float)(mysqli_fetch_assoc(mysqli_query($conn, $sql_rev_tour))['Rev'] ?? 0);

$sql_rev_flight = "SELECT COALESCE(SUM(Amount), 0) AS Rev FROM payments WHERE PaymentStatus = 'Paid' AND FlightBookingID IS NOT NULL $filter_sql_pay";
$total_rev_flight = (float)(mysqli_fetch_assoc(mysqli_query($conn, $sql_rev_flight))['Rev'] ?? 0);

$sql_rev_hotel = "SELECT COALESCE(SUM(Amount), 0) AS Rev FROM payments WHERE PaymentStatus = 'Paid' AND HotelBookingID IS NOT NULL $filter_sql_pay";
$total_rev_hotel = (float)(mysqli_fetch_assoc(mysqli_query($conn, $sql_rev_hotel))['Rev'] ?? 0);

// ==========================================
// 2. THỐNG KÊ TỔNG ĐƠN HÀNG (TÁCH THÀNH 3 LOẠI)
// ==========================================
// Tổng Đơn Tour
$sql_orders = "SELECT COUNT(BookingID) AS TotalOrders FROM Bookings WHERE 1=1 $filter_sql_book";
$total_orders = mysqli_query($conn, $sql_orders) ? (int)(mysqli_fetch_assoc(mysqli_query($conn, $sql_orders))['TotalOrders'] ?? 0) : 0;

// Tổng Đơn Vé Máy Bay
$sql_total_flight = "SELECT COUNT(FlightBookingID) AS Total FROM flight_bookings WHERE 1=1 $filter_sql_flight";
$total_flights = mysqli_query($conn, $sql_total_flight) ? (int)(mysqli_fetch_assoc(mysqli_query($conn, $sql_total_flight))['Total'] ?? 0) : 0;

// Tổng Đơn Khách Sạn
$sql_total_hotel = "SELECT COUNT(HotelBookingID) AS Total FROM hotel_bookings WHERE 1=1 $filter_sql_hotel";
$total_hotels = mysqli_query($conn, $sql_total_hotel) ? (int)(mysqli_fetch_assoc(mysqli_query($conn, $sql_total_hotel))['Total'] ?? 0) : 0;

// ==========================================
// 3. THỐNG KÊ ĐƠN CHỜ XỬ LÝ (PENDING)
// ==========================================
$sql_pending = "SELECT COUNT(BookingID) AS PendingOrders FROM Bookings WHERE Status = 'Chờ xác nhận' $filter_sql_book";
$pending_orders = mysqli_query($conn, $sql_pending) ? (int)(mysqli_fetch_assoc(mysqli_query($conn, $sql_pending))['PendingOrders'] ?? 0) : 0;

$sql_pending_flight = "SELECT COUNT(FlightBookingID) AS Total FROM flight_bookings WHERE PaymentStatus = 'Pending' $filter_sql_flight";
$pending_flights = mysqli_query($conn, $sql_pending_flight) ? (int)(mysqli_fetch_assoc(mysqli_query($conn, $sql_pending_flight))['Total'] ?? 0) : 0;

$sql_pending_hotel = "SELECT COUNT(HotelBookingID) AS Total FROM hotel_bookings WHERE PaymentStatus = 'Pending' $filter_sql_hotel";
$pending_hotels = mysqli_query($conn, $sql_pending_hotel) ? (int)(mysqli_fetch_assoc(mysqli_query($conn, $sql_pending_hotel))['Total'] ?? 0) : 0;

// Tour đang mở bán (Không bị ảnh hưởng bởi bộ lọc thời gian mua hàng)
$sql_tours = "SELECT COUNT(TourID) AS TotalTours FROM Tours";
$total_tours = mysqli_query($conn, $sql_tours) ? (int)(mysqli_fetch_assoc(mysqli_query($conn, $sql_tours))['TotalTours'] ?? 0) : 0;

// ==========================================
// 4. DỮ LIỆU BIỂU ĐỒ TRẠNG THÁI CÓ LỌC (Chỉ hiển thị cho Tour)
// ==========================================
$sql_chart_status = "SELECT Status, COUNT(BookingID) AS Count FROM Bookings WHERE 1=1 $filter_sql_book GROUP BY Status";
$res_chart_status = mysqli_query($conn, $sql_chart_status);

$status_labels = [];
$status_data = [];
$status_colors = [];

if ($res_chart_status) {
    while ($row = mysqli_fetch_assoc($res_chart_status)) {
        $status_labels[] = $row['Status'];
        $status_data[] = (int)$row['Count'];
        if ($row['Status'] == 'Đã duyệt') {
            $status_colors[] = '#198754';
        } elseif ($row['Status'] == 'Chờ xác nhận') {
            $status_colors[] = '#ffc107';
        } else {
            $status_colors[] = '#dc3545';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê Tổng quan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .stat-card { border: none; border-radius: 15px; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-box { width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 24px; }
        .chart-container { background: #fff; border-radius: 15px; padding: 20px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); }
        .revenue-list { list-style: none; padding: 0; margin: 0; }
        .revenue-list li { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.95rem; }
    </style>
</head>
<body>

<?php include 'navbar_admin.php'; ?>

<div class="container mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <h2 class="fw-bold mb-0 text-dark">
            <i class="bi bi-speedometer2 text-primary me-2"></i> Dashboard Thống Kê
        </h2>
        
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <!-- BỘ LỌC THÁNG/NĂM -->
            <form method="GET" action="admin_dashboard.php" class="d-flex gap-2 bg-white p-1 rounded-pill shadow-sm border">
                <select name="month" class="form-select form-select-sm border-0 bg-transparent text-secondary fw-semibold" style="box-shadow: none; width: auto;">
                    <option value="all">Tất cả tháng</option>
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>" <?= $filter_month == $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                    <?php endfor; ?>
                </select>
                <div class="vr text-secondary my-1"></div>
                <select name="year" class="form-select form-select-sm border-0 bg-transparent text-secondary fw-semibold" style="box-shadow: none; width: auto;">
                    <option value="all">Tất cả năm</option>
                    <?php 
                    $current_year = date('Y');
                    for($y=$current_year; $y>=$current_year-3; $y--): ?>
                        <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>>Năm <?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold"><i class="bi bi-funnel-fill"></i> Lọc</button>
            </form>

            <a href="admin_payments.php" class="btn btn-success shadow-sm rounded-pill px-3 py-2 fw-semibold">
                <i class="bi bi-file-earmark-excel me-1"></i> Xuất Excel
            </a>
            <!-- Đã loại bỏ nút dropdown Quản Lý Đơn Hàng -->
        </div>
    </div>

    <!-- KHỐI 1: TỔNG DOANH THU -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card stat-card shadow-sm border-start border-success border-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-5 d-flex align-items-center border-end">
                            <div class="icon-box bg-success bg-opacity-10 text-success me-4" style="width: 80px; height: 80px; font-size: 36px;">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div>
                                <h5 class="text-muted mb-1 fw-bold">Tổng Doanh Thu Toàn Hệ Thống</h5>
                                <h2 class="fw-bold mb-0 text-success"><?= number_format($total_revenue, 0, ',', '.') ?>đ</h2>
                            </div>
                        </div>
                        <div class="col-md-7 ps-md-4 mt-3 mt-md-0">
                            <ul class="revenue-list">
                                <li>
                                    <span class="text-secondary"><i class="bi bi-geo-alt-fill text-primary me-2"></i> Tour trọn gói:</span>
                                    <strong class="text-dark fs-5"><?= number_format($total_rev_tour, 0, ',', '.') ?>đ</strong>
                                </li>
                                <li class="border-top mt-2 pt-2">
                                    <span class="text-secondary"><i class="bi bi-airplane-engines-fill text-info me-2"></i> Vé Máy Bay:</span>
                                    <strong class="text-dark fs-5"><?= number_format($total_rev_flight, 0, ',', '.') ?>đ</strong>
                                </li>
                                <li class="border-top mt-2 pt-2">
                                    <span class="text-secondary"><i class="bi bi-building-fill-check text-warning me-2"></i> Khách Sạn:</span>
                                    <strong class="text-dark fs-5"><?= number_format($total_rev_hotel, 0, ',', '.') ?>đ</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KHỐI 2: CÁC THẺ SỐ LƯỢNG ĐƠN HÀNG -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary mb-3">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h6 class="text-muted mb-2">Tổng Đơn Tour</h6>
                    <h2 class="fw-bold mb-0 text-dark"><?= $total_orders ?> <span class="fs-6 fw-normal text-muted">đơn</span></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card stat-card shadow-sm h-100 border-start border-info border-4">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                    <div class="icon-box bg-info bg-opacity-10 text-info mb-3">
                        <i class="bi bi-airplane-fill"></i>
                    </div>
                    <h6 class="text-muted mb-2">Tổng Đơn Máy Bay</h6>
                    <h2 class="fw-bold mb-0 text-dark"><?= $total_flights ?> <span class="fs-6 fw-normal text-muted">đơn</span></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card stat-card shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning mb-3">
                        <i class="bi bi-building-fill"></i>
                    </div>
                    <h6 class="text-muted mb-2">Tổng Đơn Khách Sạn</h6>
                    <h2 class="fw-bold mb-0 text-dark"><?= $total_hotels ?> <span class="fs-6 fw-normal text-muted">đơn</span></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card stat-card shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                    <div class="icon-box bg-danger bg-opacity-10 text-danger mb-3">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <h6 class="text-muted mb-2">Tất Cả Đơn Chờ Xử Lý</h6>
                    <h2 class="fw-bold mb-0 text-dark"><?= ($pending_orders + $pending_flights + $pending_hotels) ?> <span class="fs-6 fw-normal text-muted">đơn</span></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- KHỐI 3: BIỂU ĐỒ VÀ THÔNG BÁO -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="chart-container h-100">
                <h5 class="fw-bold mb-4 text-center">Tỷ Lệ Trạng Thái Đơn Hàng Tour</h5>
                <div style="position: relative; height: 300px; width: 100%; display: flex; justify-content: center;">
                    <?php if ($total_orders > 0): ?>
                        <canvas id="statusChart"></canvas>
                    <?php else: ?>
                        <div class="d-flex align-items-center text-muted fst-italic">Không có dữ liệu trong khoảng thời gian này</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="chart-container h-100 d-flex flex-column justify-content-center align-items-center text-center p-4">
                <i class="bi bi-rocket-takeoff text-primary opacity-50" style="font-size: 5rem;"></i>
                <h4 class="fw-bold mt-3">Hệ thống đang hoạt động ổn định</h4>
                <p class="text-muted">
                    Hiện có <b><?= $total_tours ?></b> tour đang mở bán. Bạn có <b><?= ($pending_orders + $pending_flights + $pending_hotels) ?></b> đơn hàng mới cần được kiểm tra và xác nhận.
                </p>
                <div class="d-flex gap-2 justify-content-center mt-2 flex-wrap">
                    <a href="admin_bookings.php" class="btn btn-outline-primary rounded-pill px-3">Quản lý Tour</a>
                    <a href="admin_flights.php" class="btn btn-outline-info rounded-pill px-3">Quản lý Vé Máy Bay</a>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    <?php if ($total_orders > 0): ?>
    const statusLabels = <?php echo json_encode($status_labels); ?>;
    const statusData = <?php echo json_encode($status_data); ?>;
    const statusColors = <?php echo json_encode($status_colors); ?>;

    const ctx = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: statusColors,
                borderWidth: 2,
                hoverOffset: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    <?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>