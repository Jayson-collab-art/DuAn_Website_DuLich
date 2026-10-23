<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// LÍNH GÁC MỚI: Kiểm tra thẻ 'admin_id' và đẩy về đúng cửa login_admin.php
if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('CẢNH BÁO: Bạn chưa đăng nhập trang Quản trị!'); window.location.href='login_admin.php';</script>";
    exit(); 
}
include 'db.php';

if(isset($_POST['btnThem'])) {
    $cat_id = intval($_POST['category_id'] ?? 0);
    $price = intval($_POST['price'] ?? 0);
    $max_people = intval($_POST['max_people'] ?? 20); 
    // Lấy biến phân loại tour
    $is_full_package = intval($_POST['is_full_package'] ?? 1);
    
    $name = trim($_POST['tour_name'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $image = trim($_POST['image_url'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $departure_schedule = trim($_POST['departure_schedule'] ?? '');
    $departure_location = trim($_POST['departure_location'] ?? '');
    
    // Các trường mới thêm
    $vehicle = trim($_POST['vehicle'] ?? '');
    $accommodation = trim($_POST['accommodation'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $includes = trim($_POST['includes'] ?? '');
    $excludes = trim($_POST['excludes'] ?? '');
    $highlights = trim($_POST['highlights'] ?? '');
    $gallery_urls = trim($_POST['gallery_urls'] ?? '');

    // Đã thêm cột IsFullPackage vào câu lệnh INSERT
    $stmt = mysqli_prepare($conn, "INSERT INTO Tours (TourName, CategoryID, Price, MaxPeople, IsFullPackage, Duration, ImageURL, Description, DepartureSchedule, DepartureLocation, Vehicle, Accommodation, Destination, Includes, Excludes, Highlights, GalleryURLs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        // Cập nhật lại chuỗi định dạng (thêm 1 chữ 'i' và biến $is_full_package)
        mysqli_stmt_bind_param($stmt, "siiiissssssssssss", $name, $cat_id, $price, $max_people, $is_full_package, $duration, $image, $desc, $departure_schedule, $departure_location, $vehicle, $accommodation, $destination, $includes, $excludes, $highlights, $gallery_urls);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Thêm tour mới thành công! Giới hạn: $max_people khách.'); window.location='admin_list_tours.php';</script>";
        } else {
            echo "Lỗi hệ thống: " . htmlspecialchars(mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Lỗi truy vấn: " . htmlspecialchars(mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Thêm Tour Mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        html { overflow-y: scroll; }
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .admin-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn-add { border-radius: 10px; padding: 12px; font-weight: 600; }
        .form-label { font-weight: 600; color: #495057; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: #0d6efd; margin-top: 20px; margin-bottom: 15px; border-bottom: 2px solid #e9ecef; padding-bottom: 5px; text-transform: uppercase; }
    </style>
</head>
<body>

<?php include 'navbar_admin.php'; ?>

<div class="container mb-5 mt-4"> 
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card admin-card">
                <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                    <h2 class="fw-bold text-primary">ĐĂNG TOUR DU LỊCH MỚI</h2>
                    <p class="text-muted">Điền đầy đủ thông tin bên dưới để hiển thị lên trang chủ</p>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        
                        <div class="section-title">THÔNG TIN CƠ BẢN</div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tên Tour Du Lịch:</label>
                            <input type="text" name="tour_name" class="form-control rounded-3" placeholder="Ví dụ: Tour Miền Tây Sông Nước 2 Ngày" required>
                        </div>

                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label class="form-label">Danh mục loại hình:</label>
                                <div class="input-group">
                                    <select name="category_id" class="form-select" required>
                                        <option value="">-- Chọn danh mục --</option>
                                        <?php 
                                            $res_cat = mysqli_query($conn, "SELECT * FROM Categories");
                                            while($cat = mysqli_fetch_assoc($res_cat)) {
                                                echo "<option value='".$cat['CategoryID']."'>".$cat['CategoryName']."</option>";
                                            }
                                        ?>
                                    </select>
                                    <a href="admin_list_categories.php" class="btn btn-outline-primary" style="font-weight: 600;">
                                        <i class="bi bi-plus-circle"></i> Thêm Mới
                                    </a>
                                </div>
                            </div>
                            <!-- THÊM Ô CHỌN LOẠI HÌNH TOUR: TRỌN GÓI / TỰ TÚC -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-danger">Loại hình Tour:</label>
                                <select name="is_full_package" class="form-select rounded-3 border-danger" required>
                                    <option value="1">Trọn gói (Bao gồm Vé bay/KS)</option>
                                    <option value="0">Tự túc (Chỉ Land Tour)</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Giá Tour (VNĐ):</label>
                                <input type="number" name="price" class="form-control rounded-3" placeholder="3500000" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Khách tối đa:</label>
                                <input type="number" name="max_people" class="form-control rounded-3" value="10" min="1" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Thời gian:</label>
                                <input type="text" name="duration" class="form-control rounded-3" placeholder="Ví dụ: 3 ngày 2 đêm">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Ngày đi / Lịch khởi hành:</label>
                                <input type="text" name="departure_schedule" class="form-control rounded-3" placeholder="VD: 18/08/2026, Theo yêu cầu...">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Nơi xuất phát:</label>
                                <input type="text" name="departure_location" class="form-control rounded-3" placeholder="Ví dụ: Mỹ Tho, Tiền Giang">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Nơi đến (Điểm tham quan):</label>
                                <input type="text" name="destination" class="form-control rounded-3" placeholder="Ví dụ: Cần Thơ, Cà Mau">
                            </div>
                        </div>

                        <div class="section-title">HÌNH ẢNH & ĐIỂM NHẤN</div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Đường dẫn ảnh đại diện (URL):</label>
                                <input type="text" name="image_url" class="form-control rounded-3" placeholder="https://anh-dep.jpg">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Album ảnh phụ (Cách nhau bằng dấu phẩy):</label>
                                <input type="text" name="gallery_urls" class="form-control rounded-3" placeholder="https://anh1.jpg, https://anh2.jpg">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Điểm nhấn hành trình (Mỗi điểm 1 dòng):</label>
                            <textarea name="highlights" class="form-control rounded-3" rows="3" placeholder="- Check-in Cột cờ đất Mũi&#10;Trải nghiệm đi vỏ lãi..."></textarea>
                        </div>

                        <div class="section-title">DỊCH VỤ BAO GỒM / KHÔNG BAO GỒM</div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phương tiện di chuyển:</label>
                                <select name="vehicle" class="form-select rounded-3">
                                    <option value="">-- Chọn phương tiện --</option>
                                    <option value="Máy bay khứ hồi + Xe ô tô du lịch">Máy bay khứ hồi + Xe ô tô du lịch</option>
                                    <option value="Xe ô tô giường nằm cao cấp">Xe ô tô giường nằm cao cấp</option>
                                    <option value="Xe ô tô du lịch đời mới">Xe ô tô du lịch đời mới</option>
                                    <option value="Tàu cao tốc + Xe ô tô">Tàu cao tốc + Xe ô tô</option>
                                    <option value="Tự túc phương tiện">Tự túc phương tiện</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tiêu chuẩn lưu trú:</label>
                                <select name="accommodation" class="form-select rounded-3">
                                    <option value="">-- Chọn tiêu chuẩn --</option>
                                    <option value="Khách sạn 5 sao">Khách sạn 5 sao</option>
                                    <option value="Khách sạn 4 sao">Khách sạn 4 sao</option>
                                    <option value="Khách sạn 3 sao">Khách sạn 3 sao</option>
                                    <option value="Resort cao cấp">Resort cao cấp</option>
                                    <option value="Homestay bản địa">Homestay bản địa</option>
                                </select>
                            </div>
                        </div>

                      <div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Dịch vụ Bao gồm:</label>
        <div class="mb-2 d-flex flex-wrap gap-2">
            <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Vé máy bay khứ hồi\n')">+ Máy bay</span>
            <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Khách sạn tiêu chuẩn\n')">+ Khách sạn</span>
            <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Các bữa ăn theo chương trình\n')">+ Ăn uống</span>
            <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Vé tham quan các điểm\n')">+ Vé tham quan</span>
            <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Hướng dẫn viên suốt tuyến\n')">+ HDV</span>
            <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Bảo hiểm du lịch\n')">+ Bảo hiểm</span>
        </div>
        <textarea id="includes" name="includes" class="form-control rounded-3" rows="4" placeholder="Nhấn vào các nút bên trên để tự động điền hoặc gõ thêm..."></textarea>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Dịch vụ Không bao gồm:</label>
        <div class="mb-2 d-flex flex-wrap gap-2">
            <span class="badge border border-danger text-danger bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('excludes', '- Chi phí cá nhân ngoài chương trình\n')">+ Chi phí cá nhân</span>
            <span class="badge border border-danger text-danger bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('excludes', '- Thuế VAT\n')">+ VAT</span>
            <span class="badge border border-danger text-danger bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('excludes', '- Tiền Tip cho HDV và tài xế\n')">+ Tiền Tip</span>
            <span class="badge border border-danger text-danger bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('excludes', '- Phụ thu phòng đơn\n')">+ Phụ thu phòng</span>
        </div>
        <textarea id="excludes" name="excludes" class="form-control rounded-3" rows="4" placeholder="Nhấn vào các nút bên trên để tự động điền hoặc gõ thêm..."></textarea>
    </div>
</div>
                        <div class="section-title">CHƯƠNG TRÌNH CHI TIẾT</div>

                        <div class="mb-4">
                            <label class="form-label">Lịch trình & Mô tả chi tiết:</label>
                            <textarea name="description" class="form-control rounded-3" rows="6" placeholder="Nhập lịch trình từng ngày tại đây..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="btnThem" class="btn btn-primary btn-add shadow">
                                <i class="bi bi-cloud-upload me-2"></i>ĐĂNG TOUR LÊN WEBSITE
                            </button>
                            <a href="admin_list_tours.php" class="btn btn-outline-dark btn-add">
                                <i class="bi bi-list-task me-2"></i>QUAY LẠI DANH SÁCH TOUR
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center pb-4 text-muted small">
  © 2026 Hệ thống quản trị MienTay Travel
</footer>

<script>
    function addText(fieldId, text) {
        let field = document.getElementById(fieldId);
        field.value += text;
        field.focus();
    }
</script>

</body>
</html>