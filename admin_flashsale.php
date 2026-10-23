<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('CẢNH BÁO: Bạn chưa đăng nhập trang Quản trị!'); window.location.href='login_admin.php';</script>";
    exit(); 
}
include 'db.php';

// --- THÊM TOUR GIỜ CHÓT ---
if (isset($_POST['btnAdd'])) {
    $tour_id = intval($_POST['tour_id']);
    $discount_type = $_POST['discount_type']; 
    $discount_value = floatval($_POST['discount_value']);
    $sale_start = $_POST['sale_start'];
    $sale_end = $_POST['sale_end'];
    $quota = intval($_POST['quota']);
    
    if ($discount_value <= 0 || $quota <= 0 || strtotime($sale_start) >= strtotime($sale_end) || strtotime($sale_end) <= time()) {
        echo "<script>alert('Dữ liệu không hợp lệ! Lưu ý: Thời gian kết thúc phải ở tương lai và lớn hơn thời gian bắt đầu.');</script>";
    } else {
        $stmt_check = mysqli_prepare($conn, "SELECT Price FROM tours WHERE TourID = ?");
        mysqli_stmt_bind_param($stmt_check, "i", $tour_id);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);
        if ($row_check = mysqli_fetch_assoc($res_check)) {
            $price = $row_check['Price'];
            
            if ($discount_type === 'percent') {
                if ($discount_value >= 100) {
                    echo "<script>alert('Phần trăm giảm giá phải nhỏ hơn 100%');</script>";
                    exit;
                }
                $discount_price = $price - ($price * ($discount_value / 100));
            } else {
                if ($discount_value >= $price) {
                    echo "<script>alert('Số tiền giảm không được lớn hơn hoặc bằng giá tour ban đầu!');</script>";
                    exit;
                }
                $discount_price = $price - $discount_value;
            }
            
            $stmt = mysqli_prepare($conn, "INSERT INTO flash_sales (TourID, DiscountPrice, SaleStart, SaleEnd, Quota, Sold, status) VALUES (?, ?, ?, ?, ?, 0, 'Active')");
            mysqli_stmt_bind_param($stmt, "idssi", $tour_id, $discount_price, $sale_start, $sale_end, $quota);
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>alert('Đã thêm Tour Giờ Chót thành công!'); window.location.href='admin_flashsale.php';</script>";
            } else {
                echo "<script>alert('Lỗi: " . mysqli_error($conn) . "');</script>";
            }
        } else {
            echo "<script>alert('Tour không tồn tại!');</script>";
        }
    }
}

// --- SỬA TOUR GIỜ CHÓT ---
if (isset($_POST['btnEdit'])) {
    $edit_id = intval($_POST['edit_id']);
    $tour_id = intval($_POST['edit_tour_id']);
    $discount_type = $_POST['edit_discount_type'];
    $discount_value = floatval($_POST['edit_discount_value']);
    $sale_start = $_POST['edit_sale_start'];
    $sale_end = $_POST['edit_sale_end'];
    $quota = intval($_POST['edit_quota']);
    
    if ($quota <= 0 || strtotime($sale_start) >= strtotime($sale_end) || strtotime($sale_end) <= time()) {
        echo "<script>alert('Dữ liệu không hợp lệ! Lưu ý: Thời gian kết thúc phải ở tương lai.');</script>";
    } else {
        $stmt_check = mysqli_prepare($conn, "SELECT Price FROM tours WHERE TourID = ?");
        mysqli_stmt_bind_param($stmt_check, "i", $tour_id);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);
        if ($row_check = mysqli_fetch_assoc($res_check)) {
            $price = $row_check['Price'];
            
            if ($discount_value > 0) {
                if ($discount_type === 'percent') {
                    $discount_price = $price - ($price * ($discount_value / 100));
                } else {
                    $discount_price = $price - $discount_value;
                }
            } else {
                $stmt_old = mysqli_prepare($conn, "SELECT DiscountPrice FROM flash_sales WHERE FlashSaleID = ?");
                mysqli_stmt_bind_param($stmt_old, "i", $edit_id);
                mysqli_stmt_execute($stmt_old);
                $res_old = mysqli_stmt_get_result($stmt_old);
                $old_row = mysqli_fetch_assoc($res_old);
                $discount_price = $old_row['DiscountPrice'];
            }
            
            $stmt = mysqli_prepare($conn, "UPDATE flash_sales SET TourID=?, DiscountPrice=?, SaleStart=?, SaleEnd=?, Quota=? WHERE FlashSaleID=?");
            mysqli_stmt_bind_param($stmt, "idssii", $tour_id, $discount_price, $sale_start, $sale_end, $quota, $edit_id);
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>alert('Đã cập nhật Tour Giờ Chót thành công!'); window.location.href='admin_flashsale.php';</script>";
            } else {
                echo "<script>alert('Lỗi: " . mysqli_error($conn) . "');</script>";
            }
        }
    }
}

// --- BẬT/TẮT ĐƠN LẺ ---
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $stmt = mysqli_prepare($conn, "UPDATE flash_sales SET status = IF(status='Active', 'Paused', 'Active') WHERE FlashSaleID = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_flashsale.php");
    exit();
}

// --- THAO TÁC HÀNG LOẠT ---
if (isset($_GET['bulk_action'])) {
    $action = $_GET['bulk_action'];
    if ($action === 'turn_off_all') {
        mysqli_query($conn, "UPDATE flash_sales SET status = 'Paused' WHERE status = 'Active'");
    } elseif ($action === 'turn_on_all') {
        mysqli_query($conn, "UPDATE flash_sales SET status = 'Active' WHERE status = 'Paused' AND SaleEnd > NOW()");
    }
    header("Location: admin_flashsale.php");
    exit();
}

// --- XÓA ---
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = mysqli_prepare($conn, "UPDATE flash_sales SET status = 'Deleted' WHERE FlashSaleID = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_flashsale.php");
    exit();
}

// Lấy danh sách Tour ĐÃ GOM NHÓM
$tours_grouped = [];
$sql_tours = "SELECT t.TourID, t.TourName, t.Price, c.CategoryName 
              FROM tours t 
              LEFT JOIN categories c ON t.CategoryID = c.CategoryID 
              ORDER BY c.CategoryName ASC, t.TourName ASC";
$res_tours = mysqli_query($conn, $sql_tours);
while ($t = mysqli_fetch_assoc($res_tours)) {
    $cat = $t['CategoryName'] ? $t['CategoryName'] : 'Chưa phân loại';
    $tours_grouped[$cat][] = $t;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Tour Giờ Chót - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            color: #495057;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
</head>
<body>
<?php include 'navbar_admin.php'; ?>
<div class="container mt-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary m-0"><i class="bi bi-alarm me-2"></i>QUẢN LÝ TOUR GIỜ CHÓT</h3>
        <div class="btn-group shadow-sm">
            <a href="admin_flashsale.php?bulk_action=turn_on_all" class="btn btn-success btn-sm fw-bold" onclick="return confirm('Kích hoạt lại tất cả các chiến dịch đang tạm dừng?');">
                <i class="bi bi-play-circle-fill me-1"></i> Bật tất cả
            </a>
            <a href="admin_flashsale.php?bulk_action=turn_off_all" class="btn btn-secondary btn-sm fw-bold" onclick="return confirm('Tạm dừng ngay lập tức toàn bộ chiến dịch?');">
                <i class="bi bi-pause-circle-fill me-1"></i> Tắt tất cả
            </a>
        </div>
    </div>

    <div class="alert alert-info d-flex align-items-center gap-2 py-2 mb-3" style="font-size:0.9rem">
        <i class="bi bi-clock-history"></i>
        <span>Thời gian máy chủ hiện tại: <strong><?php echo date('d/m/Y H:i:s'); ?></strong> — Nhập thời gian kết thúc Tour Giờ Chót <strong>sau</strong> thời điểm này.</span>
    </div>
    
    <!-- KHU VỰC THÊM MỚI -->
    <div class="card p-4 mb-4 shadow-sm border-0 bg-white">
        <h5 class="mb-3 text-dark fw-bold"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Thêm Chiến Dịch Giờ Chót</h5>
        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-semibold text-muted small mb-1">Tìm / Chọn Tour</label>
                <select name="tour_id" class="form-select select2-tour" required>
                    <option value=""></option>
                    <?php foreach($tours_grouped as $cat_name => $tours): ?>
                        <optgroup label="Danh mục: <?php echo htmlspecialchars($cat_name); ?>">
                            <?php foreach($tours as $t): ?>
                                <option value="<?php echo $t['TourID']; ?>">
                                    <?php echo htmlspecialchars($t['TourName']); ?> — (Gốc: <?php echo number_format($t['Price']); ?>đ)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold text-muted small mb-1">Kiểu giảm</label>
                <select name="discount_type" class="form-select">
                    <option value="percent">Phần Trăm (%)</option>
                    <option value="amount">Số Tiền (đ)</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold text-muted small mb-1">Mức giảm</label>
                <input type="number" name="discount_value" class="form-control" placeholder="VD: 20 hoặc 500000" min="1" required>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small mb-1">Số Lượng Vé Sale</label>
                <input type="number" name="quota" class="form-control" placeholder="Số chỗ tối đa" min="1" required>
            </div>

            <div class="col-md-4 mt-3">
                <label class="form-label fw-semibold text-muted small mb-1">Thời điểm Bắt đầu</label>
                <input type="datetime-local" name="sale_start" id="sale_start" class="form-control" required value="<?php echo date('Y-m-d\TH:i'); ?>">
            </div>
            
            <div class="col-md-4 mt-3">
                <label class="form-label fw-semibold text-muted small mb-1">Thời điểm Kết thúc <span class="text-danger">*</span></label>
                <input type="datetime-local" name="sale_end" id="sale_end" class="form-control" required min="<?php echo date('Y-m-d\TH:i', strtotime('+1 minute')); ?>">
            </div>
            
            <div class="col-md-4 mt-3 text-end">
                <button type="submit" name="btnAdd" class="btn btn-primary w-100 fw-bold py-2"><i class="bi bi-lightning-charge-fill me-1"></i> Khởi Tạo Chiến Dịch</button>
            </div>
        </form>
    </div>

    <!-- DANH SÁCH CHIẾN DỊCH -->
    <div class="card p-4 shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th width="5%">ID</th>
                        <th width="35%">Sản phẩm Tour</th>
                        <th width="15%">Giá sau giảm</th>
                        <th width="20%">Thời hạn</th>
                        <th width="10%">Đã bán</th>
                        <th width="5%">TT</th>
                        <th width="10%" class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT fs.*, t.TourName, t.Price as OriginalPrice FROM flash_sales fs JOIN tours t ON fs.TourID = t.TourID WHERE fs.status != 'Deleted' ORDER BY fs.FlashSaleID DESC";
                    $res = mysqli_query($conn, $sql);
                    while($row = mysqli_fetch_assoc($res)):
                        $is_active = ($row['status'] == 'Active' && strtotime($row['SaleEnd']) > time());
                        $badge = $is_active ? 'bg-success' : 'bg-secondary';
                        $status_text = $is_active ? 'Đang chạy' : ($row['status'] == 'Paused' ? 'Tạm dừng' : 'Hết hạn');
                        $pct = round((($row['OriginalPrice'] - $row['DiscountPrice']) / $row['OriginalPrice']) * 100);
                        
                        // Chuẩn hóa định dạng thời gian để đẩy vào Modal Sửa
                        $start_formatted = date('Y-m-d\TH:i', strtotime($row['SaleStart']));
                        $end_formatted = date('Y-m-d\TH:i', strtotime($row['SaleEnd']));
                    ?>
                    <tr class="<?php echo !$is_active ? 'opacity-75' : ''; ?>">
                        <td class="fw-bold text-muted">#<?php echo $row['FlashSaleID']; ?></td>
                        <td>
                            <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($row['TourName']); ?></div>
                            <small class="text-muted">Giá gốc: <span class="text-decoration-line-through"><?php echo number_format($row['OriginalPrice']); ?>đ</span></small>
                            <span class="badge bg-danger ms-2">-<?php echo $pct; ?>%</span>
                        </td>
                        <td>
                            <div class="text-danger fw-bold fs-6"><?php echo number_format($row['DiscountPrice']); ?>đ</div>
                        </td>
                        <td>
                            <div class="small text-muted mb-1"><i class="bi bi-play-circle text-success me-1"></i><?php echo date('H:i d/m/Y', strtotime($row['SaleStart'])); ?></div>
                            <div class="small text-muted"><i class="bi bi-stop-circle text-danger me-1"></i><?php echo date('H:i d/m/Y', strtotime($row['SaleEnd'])); ?></div>
                        </td>
                        <td>
                            <span class="fw-bold <?php echo ($row['Sold'] >= $row['Quota']) ? 'text-danger' : 'text-primary'; ?>">
                                <?php echo $row['Sold']; ?> / <?php echo $row['Quota']; ?>
                            </span>
                        </td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo $status_text; ?></span></td>
                        <td class="text-end">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-info" title="Sửa" onclick="openEditModal(<?php echo $row['FlashSaleID']; ?>, <?php echo $row['TourID']; ?>, '<?php echo $start_formatted; ?>', '<?php echo $end_formatted; ?>', <?php echo $row['Quota']; ?>)">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="admin_flashsale.php?toggle_id=<?php echo $row['FlashSaleID']; ?>" class="btn btn-sm btn-outline-warning" title="Bật/Tắt">
                                    <i class="bi bi-power"></i>
                                </a>
                                <a href="admin_flashsale.php?delete_id=<?php echo $row['FlashSaleID']; ?>" class="btn btn-sm btn-outline-danger" title="Xóa" onclick="return confirm('Xóa lịch sử Sale này?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-light">
          <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Điều Chỉnh Chiến Dịch</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_id" id="edit_id">
          
          <div class="mb-3">
              <label class="form-label fw-semibold small text-muted">Sản phẩm Tour đang chạy Sale</label>
              <select name="edit_tour_id" id="edit_tour_id" class="form-select select2-tour-edit" style="width: 100%;" required>
                  <?php foreach($tours_grouped as $cat_name => $tours): ?>
                        <optgroup label="<?php echo htmlspecialchars($cat_name); ?>">
                            <?php foreach($tours as $t): ?>
                                <option value="<?php echo $t['TourID']; ?>">
                                    <?php echo htmlspecialchars($t['TourName']); ?> (Gốc: <?php echo number_format($t['Price']); ?>đ)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                  <?php endforeach; ?>
              </select>
          </div>
          
          <div class="row g-3 mb-3">
              <div class="col-md-6">
                  <label class="form-label fw-semibold small text-muted">Kiểu giảm mới (Bỏ trống nếu giữ nguyên giá)</label>
                  <select name="edit_discount_type" class="form-select">
                      <option value="percent">Phần Trăm (%)</option>
                      <option value="amount">Số Tiền (đ)</option>
                  </select>
              </div>
              <div class="col-md-6">
                  <label class="form-label fw-semibold small text-muted">Mức giảm mới</label>
                  <input type="number" name="edit_discount_value" id="edit_discount_value" class="form-control" placeholder="Để trống nếu không đổi giá sale">
              </div>
          </div>

          <div class="mb-3">
              <label class="form-label fw-semibold small text-muted">Tổng số vé bán (Quota)</label>
              <input type="number" name="edit_quota" id="edit_quota" class="form-control" min="1" required>
          </div>
          
          <div class="row g-3">
              <div class="col-md-6">
                  <label class="form-label fw-semibold small text-muted">Thời điểm Bắt đầu</label>
                  <input type="datetime-local" name="edit_sale_start" id="edit_sale_start" class="form-control" required>
              </div>
              <div class="col-md-6">
                  <label class="form-label fw-semibold small text-muted">Thời điểm Kết thúc <span class="text-danger">*</span></label>
                  <input type="datetime-local" name="edit_sale_end" id="edit_sale_end" class="form-control" required>
              </div>
          </div>
        </div>
        <div class="modal-footer border-0 bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
          <button type="submit" name="btnEdit" class="btn btn-primary fw-bold px-4">Lưu Thay Đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// ĐÃ SỬA CHUẨN CÚ PHÁP JQUERY ($document).ready
$(document).ready(function() {
    $('.select2-tour').select2({
        placeholder: "Gõ tên tour để tìm kiếm...",
        allowClear: true
    });
    
    $('#editModal').on('shown.bs.modal', function () {
        $('.select2-tour-edit').select2({
            dropdownParent: $('#editModal')
        });
    });
});

function openEditModal(id, tour_id, start, end, quota) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_tour_id').value = tour_id;
    $('#edit_tour_id').trigger('change'); 
    
    document.getElementById('edit_sale_start').value = start;
    document.getElementById('edit_sale_end').value = end;
    document.getElementById('edit_quota').value = quota;
    document.getElementById('edit_discount_value').value = '';
    
    var myModal = new bootstrap.Modal(document.getElementById('editModal'));
    myModal.show();
}
</script>
</body>
</html>