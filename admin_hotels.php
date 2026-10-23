<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='login_admin.php';</script>";
    exit();
}

include 'db.php';

// Xử lý Xóa Đơn Đặt Phòng
if (isset($_GET['delete_hotel'])) {
    $del_id = (int)$_GET['delete_hotel'];
    $sql_del = "DELETE FROM hotel_bookings WHERE HotelBookingID = ?";
    $stmt_del = mysqli_prepare($conn, $sql_del);
    mysqli_stmt_bind_param($stmt_del, "i", $del_id);
    if(mysqli_stmt_execute($stmt_del)) {
        echo "<script>alert('Đã xóa đơn đặt phòng thành công!'); window.location.href='admin_hotels.php';</script>";
    }
}

// Lấy danh sách đặt phòng khách sạn, sắp xếp mới nhất lên đầu
$sql = "SELECT * FROM hotel_bookings ORDER BY CreatedAt DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn Khách Sạn - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .table-custom { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table-custom th { background-color: #f8f9fa; font-weight: 600; color: #495057; border-bottom: 2px solid #e9ecef; }
        .table-custom td { vertical-align: middle; }
        .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; }
    </style>
</head>
<body>

<?php include 'navbar_admin.php'; ?>

<div class="container-fluid mt-4 mb-5 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark m-0"><i class="bi bi-building text-warning me-2"></i> Quản Lý Đơn Khách Sạn</h3>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left me-1"></i> Trở về Dashboard</a>
    </div>

    <div class="table-responsive table-custom">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mã Đặt Phòng</th>
                    <th>Khách Hàng</th>
                    <th>Khách Sạn</th>
                    <th>Nhận/Trả Phòng</th>
                    <th>Chi Tiết</th>
                    <th>Tổng Tiền</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Đặt</th>
                    <th class="text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): 
                        $statusClass = 'bg-secondary';
                        $statusText = $row['PaymentStatus'];
                        if($statusText === 'Paid') { $statusClass = 'bg-success'; $statusText = 'Đã thanh toán'; }
                        elseif($statusText === 'Pending') { $statusClass = 'bg-warning text-dark'; $statusText = 'Chờ thanh toán'; }
                        elseif($statusText === 'Failed') { $statusClass = 'bg-danger'; $statusText = 'Thất bại'; }
                    ?>
                    <tr>
                        <td class="fw-bold text-muted">#<?= $row['HotelBookingID'] ?></td>
                        <td><span class="badge bg-light border text-dark fs-6 letter-spacing-1"><?= htmlspecialchars($row['BookingCode']) ?></span></td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['CustomerName']) ?></div>
                            <div class="small text-muted"><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($row['Phone']) ?></div>
                        </td>
                        <td>
                            <div class="fw-bold text-primary"><?= htmlspecialchars($row['HotelName']) ?></div>
                            <div class="small text-muted"><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($row['Destination']) ?></div>
                        </td>
                        <td class="small">
                            <span class="text-success"><i class="bi bi-box-arrow-in-right"></i> <?= date('d/m/Y', strtotime($row['CheckInDate'])) ?></span><br>
                            <span class="text-danger"><i class="bi bi-box-arrow-right"></i> <?= date('d/m/Y', strtotime($row['CheckOutDate'])) ?></span>
                        </td>
                        <td class="text-muted small">
                            <i class="bi bi-door-open"></i> <?= $row['Rooms'] ?> phòng<br>
                            <i class="bi bi-people"></i> <?= $row['Guests'] ?> khách
                        </td>
                        <td class="fw-bold text-danger"><?= number_format($row['TotalPrice'], 0, ',', '.') ?>đ</td>
                        <td><span class="badge <?= $statusClass ?> px-3 py-2 rounded-pill"><?= $statusText ?></span></td>
                        <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($row['CreatedAt'])) ?></td>
                        <td class="text-center text-nowrap">
                            <!-- NÚT XEM CHI TIẾT KHÁCH SẠN -->
                            <button class="btn btn-outline-primary btn-action me-1" title="Xem chi tiết"
                                onclick="viewHotelDetail(
                                    '<?= $row['HotelBookingID'] ?>',
                                    '<?= htmlspecialchars($row['BookingCode'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['CustomerName'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['Phone'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['HotelName'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['Destination'], ENT_QUOTES) ?>',
                                    '<?= date('d/m/Y', strtotime($row['CheckInDate'])) ?>',
                                    '<?= date('d/m/Y', strtotime($row['CheckOutDate'])) ?>',
                                    '<?= $row['Rooms'] ?>',
                                    '<?= $row['Guests'] ?>',
                                    '<?= number_format($row['TotalPrice'], 0, ',', '.') ?>đ',
                                    '<?= $statusText ?>',
                                    '<?= $statusClass ?>',
                                    '<?= date('d/m/Y H:i', strtotime($row['CreatedAt'])) ?>'
                                )">
                                <i class="bi bi-eye"></i>
                            </button>
                            <!-- Nút Xóa có cảnh báo -->
                            <a href="admin_hotels.php?delete_hotel=<?= $row['HotelBookingID'] ?>" 
                               class="btn btn-outline-danger btn-action" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa đơn đặt phòng này không?');" title="Xóa đơn">
                               <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center py-5 text-muted">Chưa có đơn đặt phòng khách sạn nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL XEM CHI TIẾT KHÁCH SẠN -->
<div class="modal fade" id="viewHotelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold text-warning"><i class="bi bi-building-check"></i> Chi Tiết Đặt Phòng <span id="v-hotel-id" class="text-muted ms-1"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card bg-light border-0 rounded-3 mb-3">
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">Mã Đặt Phòng:</div>
                            <div class="col-7 fw-bold text-dark fs-5 letter-spacing-1" id="v-hotel-code"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">Trạng thái:</div>
                            <div class="col-7"><span id="v-hotel-status"></span></div>
                        </div>
                        <div class="row">
                            <div class="col-5 text-muted small">Ngày tạo đơn:</div>
                            <div class="col-7 fw-medium" id="v-hotel-created"></div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 border-bottom pb-2">Thông tin khách hàng & Dịch vụ</h6>
                <div class="row mb-2">
                    <div class="col-5 text-muted small">Khách hàng:</div>
                    <div class="col-7 fw-bold" id="v-hotel-name"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted small">Số điện thoại:</div>
                    <div class="col-7 fw-medium" id="v-hotel-phone"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted small">Tên Khách Sạn:</div>
                    <div class="col-7 fw-bold text-primary" id="v-hotel-hotelname"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted small">Địa điểm:</div>
                    <div class="col-7 fw-medium" id="v-hotel-destination"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted small">Thời gian lưu trú:</div>
                    <div class="col-7 fw-medium">
                        <span class="text-success"><i class="bi bi-box-arrow-in-right"></i> <span id="v-hotel-checkin"></span></span> <br>
                        <span class="text-danger"><i class="bi bi-box-arrow-right"></i> <span id="v-hotel-checkout"></span></span>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted small">Yêu cầu phòng:</div>
                    <div class="col-7 fw-medium"><span id="v-hotel-rooms"></span> phòng | <span id="v-hotel-guests"></span> khách</div>
                </div>
                <div class="row mt-3 pt-3 border-top">
                    <div class="col-5 text-muted fw-bold">Tổng thanh toán:</div>
                    <div class="col-7 fw-bold text-danger fs-5" id="v-hotel-price"></div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function viewHotelDetail(id, code, name, phone, hotelName, dest, checkIn, checkOut, rooms, guests, price, statusText, statusClass, created) {
        document.getElementById('v-hotel-id').innerText = '#' + id;
        document.getElementById('v-hotel-code').innerText = code;
        document.getElementById('v-hotel-name').innerText = name;
        document.getElementById('v-hotel-phone').innerText = phone;
        document.getElementById('v-hotel-hotelname').innerText = hotelName;
        document.getElementById('v-hotel-destination').innerText = dest;
        document.getElementById('v-hotel-checkin').innerText = checkIn;
        document.getElementById('v-hotel-checkout').innerText = checkOut;
        document.getElementById('v-hotel-rooms').innerText = rooms;
        document.getElementById('v-hotel-guests').innerText = guests;
        document.getElementById('v-hotel-price').innerText = price;
        document.getElementById('v-hotel-created').innerText = created;
        
        let statusBadge = document.getElementById('v-hotel-status');
        statusBadge.className = 'badge px-3 py-1 rounded-pill ' + statusClass;
        statusBadge.innerText = statusText;

        new bootstrap.Modal(document.getElementById('viewHotelModal')).show();
    }
</script>
</body>
</html>