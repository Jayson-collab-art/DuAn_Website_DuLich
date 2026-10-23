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

// 1. LẤY DỮ LIỆU CŨ CỦA BÀI VIẾT
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt_sel = mysqli_prepare($conn, "SELECT * FROM News WHERE NewsID = ?");
    mysqli_stmt_bind_param($stmt_sel, "i", $id);
    mysqli_stmt_execute($stmt_sel);
    $result = mysqli_stmt_get_result($stmt_sel);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_sel);
    if (!$row) {
        header("Location: admin_news.php");
        exit();
    }
} else {
    header("Location: admin_news.php");
    exit();
}

// 2. XỬ LÝ CẬP NHẬT KHI BẤM NÚT LƯU (Dùng Prepared Statement)
if (isset($_POST['btnUpdate'])) {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $source_url = trim($_POST['source_url'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $stmt_up = mysqli_prepare($conn, "UPDATE News SET Title = ?, Category = ?, ImageURL = ?, SourceURL = ?, Description = ? WHERE NewsID = ?");
    if ($stmt_up) {
        mysqli_stmt_bind_param($stmt_up, "sssssi", $title, $category, $image_url, $source_url, $description, $id);
        if (mysqli_stmt_execute($stmt_up)) {
            echo "<script>alert('Cập nhật tin tức thành công!'); window.location='admin_news.php';</script>";
        } else {
            echo "Lỗi: " . htmlspecialchars(mysqli_stmt_error($stmt_up));
        }
        mysqli_stmt_close($stmt_up);
    } else {
        echo "Lỗi truy vấn: " . htmlspecialchars(mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa Tin Tức - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #F8FAFC; font-family: 'Segoe UI', sans-serif; }
        .edit-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .form-label { font-weight: 600; color: #444; }
    </style>
</head>
<body>

    <?php include 'navbar_admin.php'; ?>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card edit-card p-4">
                    <h3 class="fw-bold text-center text-primary mb-4">
                        <i class="bi bi-pencil-square me-2"></i>CHỈNH SỬA TIN TỨC
                    </h3>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề bài viết:</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($row['Title']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Chuyên mục:</label>
                                <select name="category" class="form-select">
                                    <option value="Cẩm nang" <?php if($row['Category'] == 'Cẩm nang') echo 'selected'; ?>>Cẩm nang</option>
                                    <option value="Kinh nghiệm" <?php if($row['Category'] == 'Kinh nghiệm') echo 'selected'; ?>>Kinh nghiệm</option>
                                    <option value="Lễ hội" <?php if($row['Category'] == 'Lễ hội') echo 'selected'; ?>>Lễ hội</option>
                                    <option value="Ẩm thực" <?php if($row['Category'] == 'Ẩm thực') echo 'selected'; ?>>Ẩm thực</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Link ảnh đại diện (URL):</label>
                                <input type="text" name="image_url" class="form-control" value="<?php echo htmlspecialchars($row['ImageURL']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Link nguồn bài viết (URL báo ngoài):</label>
                            <input type="text" name="source_url" class="form-control" value="<?php echo htmlspecialchars($row['SourceURL']); ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Mô tả ngắn:</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($row['Description']); ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="btnUpdate" class="btn btn-primary fw-bold py-2">
                                <i class="bi bi-save me-2"></i>LƯU THAY ĐỔI
                            </button>
                            <a href="admin_news.php" class="btn btn-light fw-bold py-2">HỦY BỎ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>