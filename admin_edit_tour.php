<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// LÍNH GÁC MỚI: Đẩy về đúng cửa login_admin.php
if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('CẢNH BÁO: Bạn chưa đăng nhập trang Quản trị!'); window.location.href='login_admin.php';</script>";
    exit(); 
}

include 'db.php'; 

// 1. LẤY DỮ LIỆU CŨ CỦA TOUR
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); 
    
    $sql_get = "SELECT * FROM Tours WHERE TourID = $id";
    $result = mysqli_query($conn, $sql_get);
    $tour = mysqli_fetch_assoc($result);
    
    if (!$tour) {
        die("Không tìm thấy tour này!");
    }
} else {
    header("Location: admin_list_tours.php");
    exit();
}

// 2. XỬ LÝ LƯU CẬP NHẬT
if (isset($_POST['btnUpdate'])) {
    $cat_id = intval($_POST['category_id'] ?? 0);
    $price = intval($_POST['price'] ?? 0);
    $max_people = intval($_POST['max_people'] ?? 20); 
    // THÊM BIẾN LẤY LOẠI HÌNH TOUR
    $is_full_package = intval($_POST['is_full_package'] ?? 1); 
    
    $name = trim($_POST['tour_name'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $image = trim($_POST['image_url'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $departure_schedule = trim($_POST['departure_schedule'] ?? '');
    $departure_location = trim($_POST['departure_location'] ?? '');
    
    // Lấy dữ liệu các trường mới
    $vehicle = trim($_POST['vehicle'] ?? '');
    $accommodation = trim($_POST['accommodation'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $includes = trim($_POST['includes'] ?? '');
    $excludes = trim($_POST['excludes'] ?? '');
    $highlights = trim($_POST['highlights'] ?? '');
    $gallery_urls = trim($_POST['gallery_urls'] ?? '');

    // CẬP NHẬT THÊM CỘT IsFullPackage VÀO LỆNH SQL
    $stmt = mysqli_prepare($conn, "UPDATE Tours SET TourName = ?, CategoryID = ?, Price = ?, MaxPeople = ?, IsFullPackage = ?, Duration = ?, ImageURL = ?, Description = ?, DepartureSchedule = ?, DepartureLocation = ?, Vehicle = ?, Accommodation = ?, Destination = ?, Includes = ?, Excludes = ?, Highlights = ?, GalleryURLs = ? WHERE TourID = ?");
    if ($stmt) {
        // Cập nhật chuỗi định dạng (thêm 1 chữ 'i' và biến $is_full_package)
        mysqli_stmt_bind_param($stmt, "siiiissssssssssssi", $name, $cat_id, $price, $max_people, $is_full_package, $duration, $image, $desc, $departure_schedule, $departure_location, $vehicle, $accommodation, $destination, $includes, $excludes, $highlights, $gallery_urls, $id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Cập nhật tour thành công!'); window.location='admin_list_tours.php';</script>";
        } else {
            echo "Lỗi: " . htmlspecialchars(mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Lỗi: " . htmlspecialchars(mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa Tour: <?php echo htmlspecialchars($tour['TourName']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        html { overflow-y: scroll; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .edit-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .navbar-admin { background: #00284D !important; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: #0d6efd; margin-top: 20px; margin-bottom: 15px; border-bottom: 2px solid #e9ecef; padding-bottom: 5px; text-transform: uppercase; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin mb-5 shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="admin_bookings.php">ADMIN DASHBOARD</a>
            <div class="navbar-nav ms-auto align-items-center">
                <a class="nav-link" href="admin_list_tours.php">Danh sách Tour</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card edit-card p-4">
                    <h2 class="text-center fw-bold text-primary mb-4">CHỈNH SỬA THÔNG TIN TOUR</h2>
                    
                    <form method="POST">
                        <div class="section-title">THÔNG TIN CƠ BẢN</div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên Tour:</label>
                            <input type="text" name="tour_name" class="form-control" value="<?php echo htmlspecialchars($tour['TourName']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Danh mục:</label>
                                <select name="category_id" class="form-select">
                                    <?php 
                                    $res_cat = mysqli_query($conn, "SELECT * FROM Categories");
                                    while($cat = mysqli_fetch_assoc($res_cat)) {
                                        $selected = ($cat['CategoryID'] == $tour['CategoryID']) ? "selected" : "";
                                        echo "<option value='".$cat['CategoryID']."' $selected>".htmlspecialchars($cat['CategoryName'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <!-- BỔ SUNG Ô CHỌN LOẠI HÌNH TOUR -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold text-danger">Loại hình Tour:</label>
                                <select name="is_full_package" class="form-select border-danger">
                                    <option value="1" <?php if(!isset($tour['IsFullPackage']) || $tour['IsFullPackage'] == 1) echo 'selected'; ?>>Trọn gói (Bao gồm Vé bay/KS)</option>
                                    <option value="0" <?php if(isset($tour['IsFullPackage']) && $tour['IsFullPackage'] == 0) echo 'selected'; ?>>Tự túc (Chỉ Land Tour)</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Giá tiền (VNĐ):</label>
                                <input type="number" name="price" class="form-control" value="<?php echo $tour['Price']; ?>" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold">Khách tối đa:</label>
                                <input type="number" name="max_people" class="form-control" value="<?php echo isset($tour['MaxPeople']) ? $tour['MaxPeople'] : 10; ?>" min="1" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Thời lượng:</label>
                                <input type="text" name="duration" class="form-control" value="<?php echo htmlspecialchars($tour['Duration']); ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Lịch khởi hành:</label>
                                <input type="text" name="departure_schedule" class="form-control" value="<?php echo htmlspecialchars($tour['DepartureSchedule']); ?>" placeholder="Ví dụ: Thứ 7 hàng tuần">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Điểm khởi hành:</label>
                                <input type="text" name="departure_location" class="form-control" value="<?php echo htmlspecialchars($tour['DepartureLocation']); ?>" placeholder="Ví dụ: TP.HCM">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Điểm tham quan (Đích đến):</label>
                                <input type="text" name="destination" class="form-control" value="<?php echo htmlspecialchars($tour['Destination'] ?? ''); ?>" placeholder="Ví dụ: Cần Thơ, Cà Mau">
                            </div>
                        </div>

                        <div class="section-title">HÌNH ẢNH & ĐIỂM NHẤN</div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Đường dẫn ảnh đại diện (URL):</label>
                                <input type="text" name="image_url" class="form-control" value="<?php echo htmlspecialchars($tour['ImageURL']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Album ảnh phụ (Cách nhau bằng dấu phẩy):</label>
                                <input type="text" name="gallery_urls" class="form-control" value="<?php echo htmlspecialchars($tour['GalleryURLs'] ?? ''); ?>" placeholder="https://anh1.jpg, https://anh2.jpg">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Điểm nhấn hành trình (Mỗi điểm 1 dòng):</label>
                            <textarea name="highlights" class="form-control" rows="3"><?php echo htmlspecialchars($tour['Highlights'] ?? ''); ?></textarea>
                        </div>

                        <div class="section-title">DỊCH VỤ BAO GỒM / KHÔNG BAO GỒM</div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Phương tiện di chuyển:</label>
                                <select name="vehicle" class="form-select">
                                    <option value="">-- Chọn phương tiện --</option>
                                    <option value="Máy bay khứ hồi + Xe ô tô du lịch" <?php if(($tour['Vehicle']??'') == 'Máy bay khứ hồi + Xe ô tô du lịch') echo 'selected'; ?>>Máy bay khứ hồi + Xe ô tô du lịch</option>
                                    <option value="Xe ô tô giường nằm cao cấp" <?php if(($tour['Vehicle']??'') == 'Xe ô tô giường nằm cao cấp') echo 'selected'; ?>>Xe ô tô giường nằm cao cấp</option>
                                    <option value="Xe ô tô du lịch đời mới" <?php if(($tour['Vehicle']??'') == 'Xe ô tô du lịch đời mới') echo 'selected'; ?>>Xe ô tô du lịch đời mới</option>
                                    <option value="Tàu cao tốc + Xe ô tô" <?php if(($tour['Vehicle']??'') == 'Tàu cao tốc + Xe ô tô') echo 'selected'; ?>>Tàu cao tốc + Xe ô tô</option>
                                    <option value="Tự túc phương tiện" <?php if(($tour['Vehicle']??'') == 'Tự túc phương tiện') echo 'selected'; ?>>Tự túc phương tiện</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tiêu chuẩn lưu trú:</label>
                                <select name="accommodation" class="form-select">
                                    <option value="">-- Chọn tiêu chuẩn --</option>
                                    <option value="Khách sạn 5 sao" <?php if(($tour['Accommodation']??'') == 'Khách sạn 5 sao') echo 'selected'; ?>>Khách sạn 5 sao</option>
                                    <option value="Khách sạn 4 sao" <?php if(($tour['Accommodation']??'') == 'Khách sạn 4 sao') echo 'selected'; ?>>Khách sạn 4 sao</option>
                                    <option value="Khách sạn 3 sao" <?php if(($tour['Accommodation']??'') == 'Khách sạn 3 sao') echo 'selected'; ?>>Khách sạn 3 sao</option>
                                    <option value="Resort cao cấp" <?php if(($tour['Accommodation']??'') == 'Resort cao cấp') echo 'selected'; ?>>Resort cao cấp</option>
                                    <option value="Homestay bản địa" <?php if(($tour['Accommodation']??'') == 'Homestay bản địa') echo 'selected'; ?>>Homestay bản địa</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Dịch vụ Bao gồm:</label>
                                <div class="mb-2 d-flex flex-wrap gap-2">
                                    <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Vé máy bay khứ hồi\n')">+ Máy bay</span>
                                    <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Khách sạn tiêu chuẩn\n')">+ Khách sạn</span>
                                    <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Các bữa ăn theo chương trình\n')">+ Ăn uống</span>
                                    <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Vé tham quan các điểm\n')">+ Vé tham quan</span>
                                    <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Hướng dẫn viên suốt tuyến\n')">+ HDV</span>
                                    <span class="badge border border-primary text-primary bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('includes', '- Bảo hiểm du lịch\n')">+ Bảo hiểm</span>
                                </div>
                                <textarea id="includes" name="includes" class="form-control" rows="4"><?php echo htmlspecialchars($tour['Includes'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Dịch vụ Không bao gồm:</label>
                                <div class="mb-2 d-flex flex-wrap gap-2">
                                    <span class="badge border border-danger text-danger bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('excludes', '- Chi phí cá nhân ngoài chương trình\n')">+ Chi phí cá nhân</span>
                                    <span class="badge border border-danger text-danger bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('excludes', '- Thuế VAT\n')">+ VAT</span>
                                    <span class="badge border border-danger text-danger bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('excludes', '- Tiền Tip cho HDV và tài xế\n')">+ Tiền Tip</span>
                                    <span class="badge border border-danger text-danger bg-white" style="cursor:pointer; padding: 6px 10px;" onclick="addText('excludes', '- Phụ thu phòng đơn\n')">+ Phụ thu phòng</span>
                                </div>
                                <textarea id="excludes" name="excludes" class="form-control" rows="4"><?php echo htmlspecialchars($tour['Excludes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="section-title">CHƯƠNG TRÌNH CHI TIẾT</div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Mô tả & Lịch trình:</label>
                            <textarea name="description" class="form-control" rows="6"><?php echo htmlspecialchars($tour['Description']); ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="btnUpdate" class="btn btn-primary btn-lg fw-bold rounded-pill shadow-sm">
                                <i class="bi bi-save me-2"></i>LƯU THAY ĐỔI
                            </button>
                            <a href="admin_list_tours.php" class="btn btn-outline-secondary rounded-pill">Hủy bỏ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
    function addText(fieldId, text) {
        let field = document.getElementById(fieldId);
        field.value += text;
        field.focus();
    }
</script>

</body>
</html>