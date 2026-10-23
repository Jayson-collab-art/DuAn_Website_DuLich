<?php
session_start();
include 'db.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Điều hành Tour - Miền Tây Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif;}
        .accordion-button:not(.collapsed) {
            background-color: #e7f1ff;
            color: #0c63e4;
            font-weight: bold;
        }
        .tour-header-info {
            display: flex;
            justify-content: space-between;
            width: 100%;
            padding-right: 15px;
            align-items: center;
        }
        .tour-thumbnail {
            width: 70px;
            height: 45px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .tour-title-wrap {
            display: flex;
            align-items: center;
        }
        .service-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
            display: inline-block;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<?php include 'navbar_admin.php'; ?>

<div class="container-fluid px-4 py-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="bi bi-bus-front me-2 text-primary"></i> ĐIỀU HÀNH ĐOÀN THEO TOUR</h3>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="accordion" id="tourAccordion">

            <?php
            // Lấy danh sách các Tour sắp khởi hành (>= Hôm nay) VÀ ĐÃ THANH TOÁN
            $sql_groups = "
                SELECT 
                    b.TourID, 
                    t.TourName, 
                    t.ImageURL, 
                    b.DepartureDate, 
                    COUNT(b.BookingID) as TongSoDon, 
                    SUM(b.Quantity) as TongSoKhach 
                FROM bookings b
                JOIN tours t ON b.TourID = t.TourID
                WHERE b.DepartureDate >= CURDATE() 
                AND b.Status <> 'Đã hủy'
                AND (b.PaymentStatus = 'Đã thanh toán' OR b.Status = 'Đã duyệt') 
                GROUP BY b.TourID, t.TourName, t.ImageURL, b.DepartureDate 
                ORDER BY b.DepartureDate ASC
            ";
            
            $result_groups = mysqli_query($conn, $sql_groups);
            $index = 0;

            if ($result_groups && mysqli_num_rows($result_groups) > 0) {
                while ($group = mysqli_fetch_assoc($result_groups)) {
                    $index++;
                    $tourID = $group['TourID'];
                    $tourName = $group['TourName'];
                    $tourImage = $group['ImageURL'];
                    $ngayDi = $group['DepartureDate'];
                    $tongDon = $group['TongSoDon'];
                    $tongKhach = $group['TongSoKhach'];
                    $collapseId = "collapse_" . $index;
            ?>
                    <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>">
                                <div class="tour-header-info">
                                    <div class="tour-title-wrap">
                                        <?php if (!empty($tourImage)): ?>
                                            <img src="<?php echo $tourImage; ?>" alt="Tour Image" class="tour-thumbnail">
                                        <?php else: ?>
                                            <img src="images/default-tour.jpg" alt="Default" class="tour-thumbnail">
                                        <?php endif; ?>
                                        <span><strong class="fs-5"><?php echo $tourName; ?></strong></span>
                                    </div>

                                    <span class="d-flex align-items-center">
                                        <i class="bi bi-calendar-event me-1 text-primary"></i> Khởi hành: <strong class="me-3 text-danger"><?php echo date('d/m/Y', strtotime($ngayDi)); ?></strong>
                                        <span class="badge bg-secondary rounded-pill me-1"><?php echo $tongDon; ?> Đơn</span>
                                        <span class="badge bg-success rounded-pill">Tổng: <?php echo $tongKhach; ?> Khách</span>
                                    </span>
                                </div>
                            </button>
                        </h2>
                        
                        <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse" data-bs-parent="#tourAccordion">
                            <div class="accordion-body bg-light p-3">
                                <table class="table table-hover table-bordered bg-white mb-0 align-middle">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th width="8%">Mã Đơn</th>
                                            <th width="20%">Tên Khách Hàng</th>
                                            <th width="12%">SĐT</th>
                                            <th width="22%">✈️ Vé Máy Bay (Kèm theo)</th>
                                            <th width="22%">🏨 Khách Sạn (Kèm theo)</th>
                                            <th width="6%">Khách</th>
                                            <th width="10%">Trạng Thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Truy xuất danh sách khách ĐÃ THANH TOÁN trong 1 Tour cụ thể
                                        $sql_details = "
                                            SELECT * FROM bookings 
                                            WHERE TourID = '$tourID' 
                                            AND DepartureDate = '$ngayDi' 
                                            AND (PaymentStatus = 'Đã thanh toán' OR Status = 'Đã duyệt') 
                                            ORDER BY BookingID DESC
                                        ";
                                        $result_details = mysqli_query($conn, $sql_details);

                                        while ($detail = mysqli_fetch_assoc($result_details)) {
                                            $badgeClass = 'bg-success'; // Đã lọc nên 100% sẽ là màu xanh
                                            $uPhone = mysqli_real_escape_string($conn, $detail['Phone']);
                                            
                                            // THUẬT TOÁN DÒ TÌM VÉ MÁY BAY: Tìm chuyến bay của khách này trong vòng +/- 3 ngày so với ngày đi Tour
                                            $sql_flight = "SELECT Airline, FlightRoute, DepartDate FROM flight_bookings 
                                                           WHERE Phone = '$uPhone' AND PaymentStatus = 'Paid'
                                                           AND DepartDate BETWEEN DATE_SUB('$ngayDi', INTERVAL 3 DAY) AND DATE_ADD('$ngayDi', INTERVAL 3 DAY) 
                                                           LIMIT 1";
                                            $flight_res = mysqli_query($conn, $sql_flight);
                                            $flight = mysqli_fetch_assoc($flight_res);

                                            // THUẬT TOÁN DÒ TÌM KHÁCH SẠN: Tìm khách sạn của khách này trong vòng +/- 3 ngày so với ngày đi Tour
                                            $sql_hotel = "SELECT HotelName, CheckInDate FROM hotel_bookings 
                                                          WHERE Phone = '$uPhone' AND PaymentStatus = 'Paid'
                                                          AND CheckInDate BETWEEN DATE_SUB('$ngayDi', INTERVAL 3 DAY) AND DATE_ADD('$ngayDi', INTERVAL 3 DAY) 
                                                          LIMIT 1";
                                            $hotel_res = mysqli_query($conn, $sql_hotel);
                                            $hotel = mysqli_fetch_assoc($hotel_res);
                                        ?>
                                            <tr>
                                                <td class="text-center fw-medium text-secondary">#<?php echo $detail['BookingID']; ?></td>
                                                <td class="fw-bold text-dark"><?php echo $detail['CustomerName']; ?></td>
                                                <td class="text-center"><i class="bi bi-telephone-fill text-muted me-1 small"></i> <?php echo $detail['Phone']; ?></td>
                                                
                                                <!-- CỘT HIỂN THỊ VÉ MÁY BAY -->
                                                <td>
                                                    <?php if($flight): ?>
                                                        <span class="badge bg-info text-dark w-100 text-start service-badge border border-info">
                                                            <i class="bi bi-airplane-fill me-1"></i> <?= htmlspecialchars($flight['Airline']) ?><br>
                                                            <span class="fw-normal"><?= htmlspecialchars($flight['FlightRoute']) ?> (<?= date('d/m', strtotime($flight['DepartDate'])) ?>)</span>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted small fst-italic">Không đặt qua hệ thống</span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- CỘT HIỂN THỊ KHÁCH SẠN -->
                                                <td>
                                                    <?php if($hotel): ?>
                                                        <span class="badge bg-warning text-dark w-100 text-start service-badge border border-warning">
                                                            <i class="bi bi-building-fill me-1"></i> <?= htmlspecialchars($hotel['HotelName']) ?><br>
                                                            <span class="fw-normal">Nhận phòng: <?= date('d/m', strtotime($hotel['CheckInDate'])) ?></span>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted small fst-italic">Không đặt qua hệ thống</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td class="text-center fw-bold text-danger fs-5"><?php echo $detail['Quantity']; ?></td>
                                                <td class="text-center"><span class="badge <?php echo $badgeClass; ?> px-3 py-2 w-100"><i class="bi bi-check-circle-fill me-1"></i><?php echo $detail['Status']; ?></span></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
            <?php 
                }
            } else {
                echo '<div class="alert alert-info text-center py-4 fs-5"><i class="bi bi-info-circle me-2"></i> Hiện tại không có đoàn tour nào SẮP KHỞI HÀNH và ĐÃ THANH TOÁN.</div>';
            }
            ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>