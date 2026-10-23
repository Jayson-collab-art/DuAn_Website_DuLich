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

// THÊM DANH MỤC
if(isset($_POST['btnAddCat'])) {
    $cat_name = trim($_POST['cat_name'] ?? '');
    
    if(!empty($cat_name)) {
        $stmt_chk = mysqli_prepare($conn, "SELECT CategoryID FROM Categories WHERE CategoryName = ?");
        mysqli_stmt_bind_param($stmt_chk, "s", $cat_name);
        mysqli_stmt_execute($stmt_chk);
        $res_chk = mysqli_stmt_get_result($stmt_chk);
        
        if (mysqli_num_rows($res_chk) > 0) {
            echo "<script>alert('Danh mục này đã tồn tại!');</script>";
        } else {
            $stmt_ins = mysqli_prepare($conn, "INSERT INTO Categories (CategoryName) VALUES (?)");
            mysqli_stmt_bind_param($stmt_ins, "s", $cat_name);
            if(mysqli_stmt_execute($stmt_ins)) {
                echo "<script>alert('Đã thêm danh mục: " . htmlspecialchars($cat_name, ENT_QUOTES, 'UTF-8') . "'); window.location.href='admin_categories.php';</script>";
            }
            mysqli_stmt_close($stmt_ins);
        }
        mysqli_stmt_close($stmt_chk);
    }
}

// XÓA DANH MỤC (Prepared Statement)
if(isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    
    $stmt_tour = mysqli_prepare($conn, "SELECT TourID FROM Tours WHERE CategoryID = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_tour, "i", $id);
    mysqli_stmt_execute($stmt_tour);
    $res_tour = mysqli_stmt_get_result($stmt_tour);
    
    if (mysqli_num_rows($res_tour) > 0) {
        echo "<script>alert('KHÔNG THỂ XÓA! Đang có Tour thuộc danh mục này. Hãy xóa Tour trước.'); window.location.href='admin_categories.php';</script>";
    } else {
        $stmt_del = mysqli_prepare($conn, "DELETE FROM Categories WHERE CategoryID = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $id);
        mysqli_stmt_execute($stmt_del);
        mysqli_stmt_close($stmt_del);
        header("Location: admin_categories.php");
        exit();
    }
    mysqli_stmt_close($stmt_tour);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý danh mục - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .table thead { background-color: #0d6efd; color: white; }
    </style>
</head>
<body>

<?php include 'navbar_admin.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="mb-3">
                <a href="admin_add_tour.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Quay lại trang Thêm Tour</a>
            </div>

            <div class="card p-4 mb-4">
                <h3 class="fw-bold text-primary mb-4 text-center">QUẢN LÝ DANH MỤC DU LỊCH</h3>
                
                <form method="POST" class="row g-2">
                    <div class="col-md-9">
                        <input type="text" name="cat_name" class="form-control" placeholder="Nhập tên (VD: Du lịch Hành Hương...)" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="btnAddCat" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Thêm mới
                        </button>
                    </div>
                </form>
            </div>

            <div class="card p-4">
                <h5 class="fw-bold mb-3">Danh sách danh mục hiện có:</h5>
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="15%">ID</th>
                            <th>Tên danh mục</th>
                            <th width="20%" class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = mysqli_query($conn, "SELECT * FROM Categories ORDER BY CategoryID DESC");
                        while($row = mysqli_fetch_assoc($res)) {
                            echo "<tr>
                                    <td>".$row['CategoryID']."</td>
                                    <td class='fw-semibold'>".$row['CategoryName']."</td>
                                    <td class='text-center'>
                                        <a href='admin_categories.php?delete_id=".$row['CategoryID']."' 
                                           onclick='return confirm(\"Xóa danh mục này có thể làm lỗi các tour đang thuộc danh mục đó. Bạn chắc chứ?\")' 
                                           class='btn btn-outline-danger btn-sm'>
                                            <i class='bi bi-trash'></i> Xóa
                                        </a>
                                    </td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>