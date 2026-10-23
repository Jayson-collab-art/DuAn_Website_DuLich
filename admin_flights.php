<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='login_admin.php';</script>";
    exit();
}

include 'db.php';

// Xử lý Xóa Đơn Vé Máy Bay
if (isset($_GET['delete_flight'])) {
    $del_id = (int)$_GET['delete_flight'];
    $sql_del = "DELETE FROM flight_bookings WHERE FlightBookingID = ?";
    $stmt_del = mysqli_prepare($conn, $sql_del);
    mysqli_stmt_bind_param($stmt_del, "i", $del_id);
    if(mysqli_stmt_execute($stmt_del)) {
        echo "<script>alert('Đã xóa đơn vé máy bay thành công!'); window.location.href='admin_flights.php';</script>";
    }
}

// Lấy danh sách vé máy bay, sắp xếp mới nhất lên đầu
$sql = "SELECT * FROM flight_bookings ORDER BY CreatedAt DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn Vé Máy Bay - Admin</title>
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
        <h3 class="fw-bold text-dark m-0"><i class="bi bi-airplane-engines text-primary me-2"></i> Quản Lý Đơn Vé Máy Bay</h3>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left me-1"></i> Trở về Dashboard</a>
    </div>

    <div class="table-responsive table-custom">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mã PNR</th>
                    <th>Khách Hàng</th>
                    <th>Chặng Bay</th>
                    <th>Hãng Bay</th>
                    <th>Ngày Bay</th>
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
                        <td class="fw-bold text-muted">#<?= $row['FlightBookingID'] ?></td>
                        <td><span class="badge bg-light border text-dark fs-6 letter-spacing-1"><?= htmlspecialchars($row['PNR_Code']) ?></span></td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['CustomerName']) ?></div>
                            <div class="small text-muted"><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($row['Phone']) ?></div>
                        </td>
                        <td><strong class="text-primary"><?= htmlspecialchars($row['FlightRoute']) ?></strong></td>
                        <td><?= htmlspecialchars($row['Airline']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['DepartDate'])) ?></td>
                        <td class="fw-bold text-danger"><?= number_format($row['TotalPrice'], 0, ',', '.') ?>đ</td>
                        <td><span class="badge <?= $statusClass ?> px-3 py-2 rounded-pill"><?= $statusText ?></span></td>
                        <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($row['CreatedAt'])) ?></td>
                        <td class="text-center text-nowrap">
                            <!-- NÚT XEM CHI TIẾT ĐÃ ĐƯỢC GẮN LỆNH JAVASCRIPT -->
                            <button class="btn btn-outline-primary btn-action me-1" title="Xem chi tiết"
                                onclick="viewFlightDetail(
                                    '<?= $row['FlightBookingID'] ?>', 
                                    '<?= htmlspecialchars($row['PNR_Code'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['CustomerName'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['Phone'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['Airline'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['FlightRoute'], ENT_QUOTES) ?>',
                                    '<?= date('d/m/Y', strtotime($row['DepartDate'])) ?>',
                                    '<?= number_format($row['TotalPrice'], 0, ',', '.') ?>đ',
                                    '<?= $statusText ?>',
                                    '<?= $statusClass ?>',
                                    '<?= date('d/m/Y H:i', strtotime($row['CreatedAt'])) ?>'
                                )">
                                <i class="bi bi-eye"></i>
                            </button>
                            <!-- Nút Xóa có cảnh báo -->
                            <a href="admin_flights.php?delete_flight=<?= $row['FlightBookingID'] ?>" 
                               class="btn btn-outline-danger btn-action" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa đơn đặt vé máy bay này không?');" title="Xóa đơn">
                               <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center py-5 text-muted">Chưa có đơn đặt vé máy bay nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL XEM CHI TIẾT VÉ MÁY BAY -->
<div class="modal fade" id="viewFlightModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold text-primary"><i class="bi bi-ticket-detailed"></i> Chi Tiết Vé Máy Bay <span id="v-flight-id" class="text-muted ms-1"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card bg-light border-0 rounded-3 mb-3">
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">Mã PNR (Giữ chỗ):</div>
                            <div class="col-7 fw-bold text-dark fs-5 letter-spacing-1" id="v-flight-pnr"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">Trạng thái:</div>
                            <div class="col-7"><span id="v-flight-status"></span></div>
                        </div>
                        <div class="row">
                            <div class="col-5 text-muted small">Ngày đặt vé:</div>
                            <div class="col-7 fw-medium" id="v-flight-created"></div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 border-bottom pb-2">Thông tin khách hàng & Chuyến bay</h6>
                <div class="row mb-2">
                    <div class="col-5 text-muted small">Khách hàng:</div>
                    <div class="col-7 fw-bold" id="v-flight-name"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted small">Số điện thoại:</div>
                    <div class="col-7 fw-medium" id="v-flight-phone"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted small">Hãng hàng không:</div>
                    <div class="col-7 fw-medium text-primary" id="v-flight-airline"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted small">Chặng bay / Giờ:</div>
                    <div class="col-7 fw-medium" id="v-flight-route"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted small">Ngày khởi hành:</div>
                    <div class="col-7 fw-medium" id="v-flight-date"></div>
                </div>
                <div class="row mt-3 pt-3 border-top">
                    <div class="col-5 text-muted fw-bold">Tổng thanh toán:</div>
                    <div class="col-7 fw-bold text-danger fs-5" id="v-flight-price"></div>
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
    function viewFlightDetail(id, pnr, name, phone, airline, route, date, price, statusText, statusClass, created) {
        document.getElementById('v-flight-id').innerText = '#' + id;
        document.getElementById('v-flight-pnr').innerText = pnr;
        document.getElementById('v-flight-name').innerText = name;
        document.getElementById('v-flight-phone').innerText = phone;
        document.getElementById('v-flight-airline').innerText = airline;
        document.getElementById('v-flight-route').innerText = route;
        document.getElementById('v-flight-date').innerText = date;
        document.getElementById('v-flight-price').innerText = price;
        document.getElementById('v-flight-created').innerText = created;
        
        let statusBadge = document.getElementById('v-flight-status');
        statusBadge.className = 'badge px-3 py-1 rounded-pill ' + statusClass;
        statusBadge.innerText = statusText;

        new bootstrap.Modal(document.getElementById('viewFlightModal')).show();
    }
</script>
</body>
</html>