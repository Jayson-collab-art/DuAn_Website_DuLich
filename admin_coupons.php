<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='login_admin.php';</script>";
    exit();
}
include 'db.php';

// 1. TỰ ĐỘNG TẠO BẢNG NẾU CHƯA CÓ
$sql_create_table = "CREATE TABLE IF NOT EXISTS coupons (
    CouponID INT AUTO_INCREMENT PRIMARY KEY,
    CouponCode VARCHAR(50) NOT NULL UNIQUE,
    DiscountType ENUM('Percent', 'Fixed') NOT NULL DEFAULT 'Fixed',
    DiscountValue INT NOT NULL,
    MinOrderValue INT DEFAULT 0,
    UsageLimit INT DEFAULT 0,
    UsedCount INT DEFAULT 0,
    ExpiryDate DATETIME NOT NULL,
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql_create_table);

// 2. XỬ LÝ THÊM MÃ GIẢM GIÁ
if (isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['type'];
    $value = intval($_POST['value']);
    $min_order = intval($_POST['min_order'] ?? 0);
    $limit = intval($_POST['limit'] ?? 0);
    $expiry = $_POST['expiry'];

    // Kiểm tra trùng lặp
    $check = mysqli_query($conn, "SELECT * FROM coupons WHERE CouponCode = '$code'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Lỗi: Mã giảm giá này đã tồn tại!'); window.location.href='admin_coupons.php';</script>";
    } else {
        $sql_insert = "INSERT INTO coupons (CouponCode, DiscountType, DiscountValue, MinOrderValue, UsageLimit, ExpiryDate) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql_insert);
        mysqli_stmt_bind_param($stmt, "ssiiis", $code, $type, $value, $min_order, $limit, $expiry);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Tạo mã giảm giá thành công!'); window.location.href='admin_coupons.php';</script>";
        } else {
            echo "<script>alert('Lỗi hệ thống!');</script>";
        }
    }
}

// 3. XỬ LÝ ĐỔI TRẠNG THÁI (BẬT/TẮT) & XÓA
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'toggle') {
        mysqli_query($conn, "UPDATE coupons SET Status = IF(Status='Active', 'Inactive', 'Active') WHERE CouponID = $id");
        header("Location: admin_coupons.php");
        exit();
    } elseif ($_GET['action'] == 'delete') {
        mysqli_query($conn, "DELETE FROM coupons WHERE CouponID = $id");
        header("Location: admin_coupons.php");
        exit();
    }
}

// Cập nhật trạng thái tự động: Hết hạn hoặc hết lượt -> Inactive
mysqli_query($conn, "UPDATE coupons SET Status = 'Inactive' WHERE ExpiryDate < NOW() OR (UsageLimit > 0 AND UsedCount >= UsageLimit)");

// Lấy danh sách mã giảm giá
$res_coupons = mysqli_query($conn, "SELECT * FROM coupons ORDER BY CouponID DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Mã Giảm Giá - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #F8FAFC; font-family: 'Inter', sans-serif; }
        .card-custom { background: #fff; border: 1px solid #E2E8F0; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .coupon-badge { background: linear-gradient(135deg, #0284c7, #0369a1); color: white; padding: 5px 12px; border-radius: 6px; font-weight: 800; letter-spacing: 1px; border: 1px dashed rgba(255,255,255,0.5); }
        .table > :not(caption) > * > * { padding: 1rem 0.5rem; vertical-align: middle; }
    </style>
</head>
<body>

    <?php include 'navbar_admin.php'; ?>

    <div class="container-fluid px-4 py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark m-0"><i class="bi bi-ticket-perforated text-warning me-2"></i>Quản lý Mã Giảm Giá (Voucher)</h2>
        </div>

        <div class="row g-4">
            <!-- CỘT FORM TẠO MÃ MỚI -->
            <div class="col-lg-4">
                <div class="card-custom p-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-3"><i class="bi bi-plus-circle-fill text-success me-2"></i>Tạo Mã Mới</h5>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Mã Voucher (Code):</label>
                            <input type="text" name="code" class="form-control text-uppercase fw-bold" placeholder="VD: MIENTAY2024" required style="letter-spacing: 1px;">
                            <small class="text-muted" style="font-size: 0.75rem;">Viết liền không dấu, tự động in hoa.</small>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-5">
                                <label class="form-label small fw-bold">Kiểu giảm:</label>
                                <select name="type" class="form-select">
                                    <option value="Fixed">Tiền mặt (VNĐ)</option>
                                    <option value="Percent">Phần trăm (%)</option>
                                </select>
                            </div>
                            <div class="col-sm-7">
                                <label class="form-label small fw-bold">Mức giảm:</label>
                                <input type="number" name="value" class="form-control" placeholder="VD: 500000 hoặc 15" required min="1">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Đơn tối thiểu áp dụng (VNĐ):</label>
                            <input type="number" name="min_order" class="form-control" placeholder="Để 0 nếu áp dụng mọi đơn" value="0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Giới hạn số lượt dùng:</label>
                            <input type="number" name="limit" class="form-control" placeholder="Để 0 nếu không giới hạn" value="0">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Hạn sử dụng (Thời gian kết thúc):</label>
                            <input type="datetime-local" name="expiry" class="form-control" required>
                        </div>

                        <button type="submit" name="add_coupon" class="btn w-100 fw-bold py-2 text-white" style="background: linear-gradient(135deg, #0284c7, #0369a1);">
                            Khởi tạo Mã Giảm Giá
                        </button>
                    </form>
                </div>
            </div>

            <!-- CỘT DANH SÁCH MÃ -->
            <div class="col-lg-8">
                <div class="card-custom p-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-3"><i class="bi bi-list-task text-primary me-2"></i>Danh Sách Voucher</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã Voucher</th>
                                    <th>Mức Giảm</th>
                                    <th>Điều Kiện</th>
                                    <th>Đã Dùng</th>
                                    <th>Hạn Sử Dụng</th>
                                    <th>Trạng Thái</th>
                                    <th class="text-end">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($res_coupons) == 0): ?>
                                    <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có mã giảm giá nào.</td></tr>
                                <?php else: while($row = mysqli_fetch_assoc($res_coupons)): 
                                    $is_active = ($row['Status'] === 'Active');
                                ?>
                                    <tr>
                                        <td><span class="coupon-badge"><?= $row['CouponCode'] ?></span></td>
                                        
                                        <td class="fw-bold text-danger">
                                            <?= ($row['DiscountType'] == 'Fixed') ? number_format($row['DiscountValue']).'đ' : $row['DiscountValue'].'%' ?>
                                        </td>
                                        
                                        <td>
                                            <div class="small text-muted">
                                                Đơn tối thiểu:<br>
                                                <strong class="text-dark"><?= number_format($row['MinOrderValue']) ?>đ</strong>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <div class="fw-bold">
                                                <?= $row['UsedCount'] ?> / <?= ($row['UsageLimit'] > 0) ? $row['UsageLimit'] : '∞' ?>
                                            </div>
                                            <div class="progress mt-1" style="height: 4px;">
                                                <?php 
                                                    $pct = 0;
                                                    if($row['UsageLimit'] > 0) $pct = min(100, ($row['UsedCount'] / $row['UsageLimit']) * 100);
                                                ?>
                                                <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                                            </div>
                                        </td>
                                        
                                        <td class="small fw-semibold <?= strtotime($row['ExpiryDate']) < time() ? 'text-danger' : 'text-primary' ?>">
                                            <?= date('H:i d/m/Y', strtotime($row['ExpiryDate'])) ?>
                                        </td>
                                        
                                        <td>
                                            <?php if($is_active): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Đang chạy</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">Đã dừng</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="text-end">
                                            <a href="?action=toggle&id=<?= $row['CouponID'] ?>" class="btn btn-sm <?= $is_active ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="Bật/Tắt">
                                                <i class="bi bi-power"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $row['CouponID'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa mã này?');" title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>