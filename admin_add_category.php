<?php 
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('CẢNH BÁO: Bạn chưa đăng nhập trang Quản trị!'); window.location.href='login_admin.php';</script>";
    exit(); 
}
include 'db.php';

if(isset($_POST['btnThemDM'])) {
    $cat_name = trim($_POST['category_name'] ?? '');
    
    if($cat_name != '') {
        $stmt = mysqli_prepare($conn, "INSERT INTO Categories (CategoryName) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $cat_name);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Thêm danh mục mới thành công!'); window.location='admin_list_categories.php';</script>";
        } else {
            echo "Lỗi hệ thống: " . htmlspecialchars(mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Vui lòng nhập tên danh mục!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Thêm Danh Mục</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .admin-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<?php include 'navbar_admin.php'; ?>

<div class="container mb-5 mt-4"> 
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card admin-card">
                <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                    <h3 class="fw-bold text-primary">THÊM DANH MỤC MỚI</h3>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Tên Danh Mục:</label>
                            <input type="text" name="category_name" class="form-control rounded-3 py-2" placeholder="Ví dụ: Du lịch Miền Bắc" required autofocus>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="btnThemDM" class="btn btn-primary fw-bold py-2">
                                <i class="bi bi-save me-2"></i>LƯU DANH MỤC
                            </button>
                            <a href="admin_list_categories.php" class="btn btn-outline-secondary fw-bold py-2">
                                Hủy bỏ & Quay lại
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>