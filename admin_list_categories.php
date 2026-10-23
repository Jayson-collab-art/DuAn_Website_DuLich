<?php 
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('CẢNH BÁO: Bạn chưa đăng nhập trang Quản trị!'); window.location.href='login_admin.php';</script>";
    exit(); 
}
include 'db.php';

// Xử lý khi bấm nút Xóa
if(isset($_GET['del_id'])) {
    $del_id = intval($_GET['del_id']);
    $stmt = mysqli_prepare($conn, "DELETE FROM Categories WHERE CategoryID = ?");
    mysqli_stmt_bind_param($stmt, "i", $del_id);
    if(mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Đã xóa danh mục thành công!'); window.location='admin_list_categories.php';</script>";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Quản Lý Danh Mục</title>
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
        <div class="col-lg-8">
            <div class="card admin-card">
                <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h3 class="fw-bold text-primary mb-0">QUẢN LÝ DANH MỤC TOUR</h3>
                    <a href="admin_add_category.php" class="btn btn-success fw-bold">
                        <i class="bi bi-plus-circle me-1"></i> Thêm Danh Mục Mới
                    </a>
                </div>
                <div class="card-body p-4">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Tên Danh Mục</th>
                                <th scope="col" class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $res_cat = mysqli_query($conn, "SELECT * FROM Categories ORDER BY CategoryID DESC");
                                while($cat = mysqli_fetch_assoc($res_cat)) {
                            ?>
                            <tr>
                                <td>#<?= $cat['CategoryID'] ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($cat['CategoryName']) ?></td>
                                <td class="text-end">
                                    <a href="?del_id=<?= $cat['CategoryID'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                        <i class="bi bi-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>