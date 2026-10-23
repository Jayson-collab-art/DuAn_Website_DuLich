<?php 
session_start();
include 'db.php'; 

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id <= 0) { header("Location: index.php"); exit(); }
    
    // Đã bao gồm gọi bảng Tours (Tours.* tự động lấy cả cột IsFullPackage)
    $sql = "SELECT Tours.*, Categories.CategoryName FROM Tours 
            LEFT JOIN Categories ON Tours.CategoryID = Categories.CategoryID WHERE TourID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tour = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if(!$tour) {
        echo "<script>alert('Tour không tồn tại hoặc đã bị xóa!'); window.location.href='index.php';</script>"; exit();
    }
    
    $fs_sql = "SELECT DiscountPrice, SaleEnd, Quota, Sold FROM flash_sales WHERE TourID = ? AND SaleEnd > NOW() AND SaleStart <= NOW() AND Sold < Quota AND status = 'Active' LIMIT 1";
    $fs_stmt = mysqli_prepare($conn, $fs_sql);
    mysqli_stmt_bind_param($fs_stmt, "i", $id);
    mysqli_stmt_execute($fs_stmt);
    $fs_res = mysqli_stmt_get_result($fs_stmt);
    if ($fs_res && mysqli_num_rows($fs_res) > 0) {
        $fs_row = mysqli_fetch_assoc($fs_res);
        $tour['OriginalPrice'] = $tour['Price'];
        $tour['Price'] = $fs_row['DiscountPrice'];
        $tour['is_flash_sale'] = true;
        $tour['FlashSaleEnd'] = $fs_row['SaleEnd'];
        $tour['FlashQuota'] = $fs_row['Quota'];
        $tour['FlashSold'] = $fs_row['Sold'];
    }
} else { header("Location: index.php"); exit(); }

$is_fixed_date = false;
$fixed_date_value = "";
$schedule = trim($tour['DepartureSchedule']); 
$is_expired = false; 

if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $schedule)) {
    $date_parts = explode('/', $schedule);
    $fixed_date_value = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
    $is_fixed_date = true;
    if (strtotime($fixed_date_value) < strtotime(date('Y-m-d'))) { $is_expired = true; }
}
$max_allowed = isset($tour['MaxPeople']) ? $tour['MaxPeople'] : 10;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết: <?php echo htmlspecialchars($tour['TourName']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #F8FAFC; font-family: 'Inter', sans-serif; }
        .tour-img { width: 100%; height: 500px; object-fit: cover; border-radius: 20px; }
        .price-tag { color: #ff4757; font-size: 35px; font-weight: 800; }
        .sticky-form { position: sticky; top: 100px; }
        .review-card { border-left: 5px solid #0d6efd; background: #fff; margin-bottom: 15px; border-radius: 15px !important; }
        .btn-quantity { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px !important; }
        input[readonly] { background-color: #f1f3f5 !important; cursor: not-allowed; }
        .payment-info-box { border: 1px solid #dce7f5; background: #f7fbff; }
        .payment-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: #eaf3ff; color: #0d6efd; }
        .vnpay-box { border: 1px solid #d8e4f4; background: linear-gradient(135deg, #f8fbff, #eef6ff); }
        .vnpay-logo { font-weight: 900; letter-spacing: .5px; color: #d71920; }
        .gallery-img { height: 100px; object-fit: cover; cursor: pointer; transition: 0.3s; border-radius: 10px; }
        .gallery-img:hover { opacity: 0.8; transform: scale(1.02); }
        
        .booking-card { border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .booking-header { background: linear-gradient(135deg, #FF862E, #FF4B2B); }
        .input-group-text { background-color: #fff; border-right: none; color: #a0aec0; }
        .form-control.booking-input { border-left: none; padding-left: 0; box-shadow: none !important; }
        .form-control.booking-input:focus { border-color: #dee2e6; }
        .input-group:focus-within { border-color: #FF862E; box-shadow: 0 0 0 0.25rem rgba(255, 134, 46, 0.25); border-radius: 0.375rem; }
        .input-group:focus-within .input-group-text, .input-group:focus-within .form-control { border-color: #FF862E; }
    </style>
</head>
<body>

    <div class="container mt-5 mb-5">
        <a href="index.php" class="btn btn-white shadow-sm mb-4 rounded-pill px-4 bg-white">
            <i class="bi bi-arrow-left"></i> Quay lại trang chủ
        </a>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="bg-white p-4 shadow-sm rounded-4 mb-4 border-0">
                    <?php
                    $gallery_images = [];
                    if (!empty($tour['ImageURL'])) $gallery_images[] = $tour['ImageURL'];
                    
                    $sql_extra_imgs = "SELECT * FROM tour_images WHERE TourID = $id ORDER BY ImageID ASC";
                    $res_extra_imgs = mysqli_query($conn, $sql_extra_imgs);
                    if ($res_extra_imgs) {
                        while ($ex_img = mysqli_fetch_assoc($res_extra_imgs)) {
                            if (!empty($ex_img['ImageURL']) && !in_array($ex_img['ImageURL'], $gallery_images)) {
                                $gallery_images[] = $ex_img['ImageURL'];
                            }
                        }
                    }

                    if (!empty($tour['GalleryURLs'])) {
                        $urls = explode(',', $tour['GalleryURLs']);
                        foreach($urls as $url) {
                            $url = trim($url);
                            if (!empty($url) && !in_array($url, $gallery_images)) {
                                $gallery_images[] = $url;
                            }
                        }
                    }

                    $total_images = count($gallery_images);
                    ?>

                    <div class="detail-gallery-container mb-4" id="detailGallery" data-total="<?php echo $total_images; ?>">
                        <div class="detail-main-stage position-relative overflow-hidden mb-3" id="detailMainStage">
                            <div class="detail-slides-track h-100 w-100 position-relative">
                                <?php foreach ($gallery_images as $idx => $img_url): ?>
                                    <div class="detail-slide <?php echo $idx === 0 ? 'active' : ''; ?>" data-slide-index="<?php echo $idx; ?>">
                                        <img src="<?php echo htmlspecialchars($img_url); ?>" class="detail-slide-img" onclick="showImageModal(this.src)">
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($total_images > 1): ?>
                                <button type="button" class="detail-gallery-nav prev-btn" id="galleryPrevBtn"><i class="bi bi-chevron-left"></i></button>
                                <button type="button" class="detail-gallery-nav next-btn" id="galleryNextBtn"><i class="bi bi-chevron-right"></i></button>
                                <div class="detail-counter-badge"><i class="bi bi-images me-1"></i> <span id="galleryCurrentNum">1</span> / <?php echo $total_images; ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if ($total_images > 1): ?>
                            <div class="detail-thumbs-wrapper">
                                <div class="d-flex flex-wrap gap-2" id="detailThumbTrack">
                                    <?php foreach ($gallery_images as $idx => $img_url): ?>
                                        <div class="detail-thumb-item <?php echo $idx === 0 ? 'active' : ''; ?>" data-thumb-index="<?php echo $idx; ?>">
                                            <img src="<?php echo htmlspecialchars($img_url); ?>" class="detail-thumb-img">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- TIÊU ĐỀ TOUR KÈM NHÃN PHÂN LOẠI TRỌN GÓI / TỰ TÚC -->
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 mt-2">
                        <h1 class="fw-bold m-0"><?php echo htmlspecialchars($tour['TourName']); ?></h1>
                        <?php if (isset($tour['IsFullPackage']) &&$tour['IsFullPackage'] == 0): ?>
                            <span class="badge bg-warning text-dark fs-6 px-3 py-2 shadow-sm"><i class="bi bi-person-walking"></i> Tour Tự Túc (Không bao gồm VMB/KS)</span>
                        <?php else: ?>
                            <span class="badge bg-success fs-6 px-3 py-2 shadow-sm"><i class="bi bi-box-seam"></i> Tour Trọn Gói</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- THÔNG TIN CƠ BẢN & DỊCH VỤ GẮN KÈM -->
                    <div class="d-flex flex-wrap gap-4 mb-4 text-muted border-bottom pb-3">
                        <span><i class="bi bi-clock text-primary me-1"></i> <?= htmlspecialchars($tour['Duration'] ?? 'Đang cập nhật') ?></span>
                        <span><i class="bi bi-geo-alt-fill text-primary me-1"></i> Khởi hành: <?= htmlspecialchars($tour['DepartureLocation'] ?? 'Đang cập nhật') ?></span>
                        
                        <?php if(!empty($tour['Destination'])): ?>
                            <span><i class="bi bi-geo-fill text-danger me-1"></i> Điểm đến: <?= htmlspecialchars($tour['Destination']) ?></span>
                        <?php endif; ?>
                        
                        <span><i class="bi bi-calendar-check text-primary me-1"></i> Lịch: <?= (!empty($tour['DepartureSchedule'])) ? htmlspecialchars($tour['DepartureSchedule']) : "Liên hệ" ?></span>
                        
                        <?php if(!empty($tour['Transport'])): ?>
                            <span><i class="bi bi-car-front-fill text-success me-1"></i> Phương tiện: <?= htmlspecialchars($tour['Transport']) ?></span>
                        <?php endif; ?>
                        
                        <?php if(!empty($tour['Accommodation'])): ?>
                            <span><i class="bi bi-house-door-fill text-info me-1"></i> Lưu trú: <?= htmlspecialchars($tour['Accommodation']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- ĐIỂM NHẤN HÀNH TRÌNH -->
                    <?php if(!empty($tour['Highlights'])): ?>
                    <div class="bg-light p-3 rounded-3 mb-4 border-start border-4 border-warning shadow-sm">
                        <h5 class="fw-bold mb-2"><i class="bi bi-star-fill text-warning me-2"></i>Điểm nhấn hành trình</h5>
                        <div class="text-secondary" style="line-height: 1.8;">
                            <?= nl2br(htmlspecialchars($tour['Highlights'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- BAO GỒM / KHÔNG BAO GỒM -->
                    <?php if(!empty($tour['Includes']) || !empty($tour['Excludes'])): ?>
                    <div class="row mb-5 border-bottom pb-4">
                        <?php if(!empty($tour['Includes'])): ?>
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h5 class="fw-bold text-success mb-3"><i class="bi bi-check-circle-fill me-2"></i>Dịch vụ bao gồm</h5>
                            <div class="text-secondary" style="line-height: 1.8;">
                                <?= nl2br(htmlspecialchars($tour['Includes'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($tour['Excludes'])): ?>
                        <div class="col-md-6">
                            <h5 class="fw-bold text-danger mb-3"><i class="bi bi-x-circle-fill me-2"></i>Không bao gồm</h5>
                            <div class="text-secondary" style="line-height: 1.8;">
                                <?= nl2br(htmlspecialchars($tour['Excludes'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <h4 class="fw-bold mb-3 text-dark"><i class="bi bi-journal-text me-2"></i>Lịch trình chi tiết</h4>
                    <div class="lh-lg text-secondary mb-5" style="text-align: justify;">
                        <?php echo nl2br(htmlspecialchars($tour['Description'])); ?>
                    </div>
                </div>

                <!-- ĐÁNH GIÁ KHÁCH HÀNG -->
                <div class="bg-white p-4 shadow-sm rounded-4 border-0" id="review-section">
                    <h4 class="fw-bold mb-4 text-primary"><i class="bi bi-chat-left-heart me-2 text-danger"></i>Đánh giá từ khách hàng</h4>
                    
                    <?php if(isset($_GET['msg']) &&$_GET['msg'] == 'review_sent'): ?>
                        <div class="alert alert-success rounded-pill fw-bold small text-center mb-4"><i class="bi bi-check-circle-fill me-1"></i> Đánh giá đã được gửi.</div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form action="process_review.php" method="POST" class="mb-5 p-4 border-0 rounded-4 bg-light shadow-sm">
                            <input type="hidden" name="tour_id" value="<?php echo $id; ?>">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Xếp hạng của bạn:</label>
                                <select name="rating" class="form-select border-0 shadow-sm rounded-3 w-50">
                                    <option value="5">⭐⭐⭐⭐⭐ Tuyệt vời</option>
                                    <option value="4">⭐⭐⭐⭐ Rất tốt</option>
                                    <option value="3">⭐⭐⭐ Bình thường</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <textarea name="comment" class="form-control border-0 shadow-sm rounded-4" rows="3" placeholder="Chia sẻ trải nghiệm..." required></textarea>
                            </div>
                            <button type="submit" name="btnReview" class="btn btn-primary px-5 rounded-pill fw-bold">Gửi đánh giá</button>
                        </form>
                    <?php endif; ?>

                    <div class="review-list">
                        <?php
                        $sql_review = "SELECT Reviews.*, Users.FullName FROM Reviews JOIN Users ON Reviews.UserID = Users.UserID WHERE TourID = $id AND Status = 1 ORDER BY ReviewDate DESC";
                        $res_review = mysqli_query($conn,$sql_review);
                        if(mysqli_num_rows($res_review) > 0) {
                            while($rev = mysqli_fetch_assoc($res_review)) {
                                echo "<div class='card review-card border-0 shadow-sm p-4 mb-3'>
                                        <div class='d-flex justify-content-between align-items-center mb-2'>
                                            <strong>".htmlspecialchars($rev['FullName'])."</strong>
                                            <span class='text-warning small'>".str_repeat("⭐", $rev['Rating'])."</span>
                                        </div>
                                        <p class='mb-1 text-secondary small'>".htmlspecialchars($rev['Comment'])."</p>
                                      </div>";
                            }
                        } else { 
                            echo "<p class='text-center py-4 text-muted'>Chưa có đánh giá nào.</p>"; 
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- CỘT FORM ĐẶT TOUR -->
            <div class="col-lg-4">
                <div class="sticky-form">
                    <div class="card booking-card rounded-4 overflow-hidden bg-white">
                        <div class="card-header text-white text-center py-3 border-0 <?php echo isset($tour['is_flash_sale']) ? '' : 'booking-header'; ?>" 
                             <?php if(isset($tour['is_flash_sale'])) echo 'style="background: linear-gradient(135deg, #ff416c, #ff4b2b);"'; ?>>
                            <h5 class="fw-bold mb-0 text-white text-uppercase letter-spacing-1">
                                <?php if(isset($tour['is_flash_sale'])): ?>
                                    <i class="bi bi-lightning-charge-fill me-2" style="color: #FFD700 !important;"></i>DEAL SỐC CÒN <span id="fs-timer" data-end="<?php echo $tour['FlashSaleEnd']; ?>"></span>
                                <?php else: ?>
                                    <i class="bi bi-cart-check me-2"></i>Đặt Tour Ngay
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            
                            <div class="text-center mb-4">
                                <?php if(isset($tour['is_flash_sale'])): ?>
                                    <div class="d-flex justify-content-center align-items-center gap-2 mb-1">
                                        <span class="badge bg-danger rounded-pill px-2 py-1 shadow-sm"><i class="bi bi-tags-fill"></i> FLASH SALE</span>
                                        <span class="text-decoration-line-through text-muted" style="font-size: 1.1rem;"><?php echo number_format($tour['OriginalPrice']); ?>đ</span>
                                    </div>
                                    <p class="price-tag mb-1 text-danger" id="display-total" style="font-size: 2.2rem;"><?php echo number_format($tour['Price']); ?>đ</p>
                                    <div class="progress mt-3 mx-auto" style="height: 8px; max-width: 250px; border-radius: 10px;">
                                        <?php $prog = ($tour['FlashQuota'] > 0) ? min(100, ($tour['FlashSold'] /$tour['FlashQuota']) * 100) : 0; ?>
                                        <div class="progress-bar bg-danger progress-bar-striped progress-bar-animated" style="width: <?php echo $prog; ?>%"></div>
                                    </div>
                                    <small class="text-danger fw-bold mt-1 d-block">Chỉ còn <?php echo max(0, $tour['FlashQuota'] -$tour['FlashSold']); ?> suất giá sốc!</small>
                                <?php else: ?>
                                    <p class="price-tag mb-1" id="display-total"><?php echo number_format($tour['Price']); ?>đ</p>
                                    <span class="badge bg-light text-dark border small rounded-pill px-3 py-2 shadow-sm">Giá cho <span id="people-count">1</span> khách</span>
                                <?php endif; ?>
                            </div>
                            
                            <form action="process_booking.php" method="POST" id="bookingForm">
                                <input type="hidden" name="tour_id" value="<?php echo $tour['TourID']; ?>">
                                <input type="hidden" id="base-price" value="<?php echo $tour['Price']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Họ tên người đặt:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" name="customer_name" id="custName" class="form-control booking-input" value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>" placeholder="Nhập họ tên" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Số điện thoại :</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                        <input type="tel" name="customer_phone" id="custPhone" class="form-control booking-input" pattern="^(03|05|07|08|09)[0-9]{8}$" placeholder="Ví dụ: 0912345678" required>
                                    </div>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="rememberInfo" style="cursor: pointer;">
                                    <label class="form-check-label small text-muted user-select-none" for="rememberInfo" style="cursor: pointer;">
                                        Ghi nhớ thông tin cho lần đặt sau
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Ngày khởi hành:</label>
                                    <?php if($is_fixed_date): ?>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                            <input type="date" class="form-control booking-input" value="<?php echo $fixed_date_value; ?>" readonly>
                                        </div>
                                        <input type="hidden" name="departure_date" value="<?php echo $fixed_date_value; ?>">
                                        <?php if($is_expired): ?>
                                            <small class="text-danger fw-bold d-block mt-2"><i class="bi bi-exclamation-triangle-fill"></i> Đã qua ngày khởi hành!</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                            <input type="date" name="departure_date" class="form-control booking-input" required min="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted d-block text-center">Số người (Tối đa <?php echo $max_allowed; ?>):</label>
                                    <div class="input-group justify-content-center bg-light p-2 rounded-4 shadow-sm border">
                                        <button class="btn btn-white btn-quantity border shadow-sm" type="button" onclick="updateQty(-1)">-</button>
                                        <input type="number" id="qty" name="quantity" class="form-control text-center fw-bold border-0 bg-transparent fs-5" value="1" min="1" max="<?php echo $max_allowed; ?>" readonly>
                                        <button class="btn btn-white btn-quantity border shadow-sm" type="button" onclick="updateQty(1)">+</button>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted">Phương thức thanh toán:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-wallet2"></i></span>
                                        <select name="payment_method" id="payment_method" class="form-select booking-input" onchange="togglePaymentInfo()">
                                            <option value="Tiền mặt">Thanh toán tại văn phòng</option>
                                            <option value="VNPay">Thanh toán qua VNPAY</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="vnpay-payment-info" class="payment-info-box vnpay-box p-3 rounded-4 mb-4" style="display:none;">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="payment-icon me-3"><i class="bi bi-credit-card-2-front-fill"></i></div>
                                        <div><div class="fw-bold vnpay-logo">VNPAY</div><small class="text-muted">Thanh toán qua cổng trực tuyến</small></div>
                                    </div>
                                    <div class="small text-dark mb-1">Hệ thống sẽ tự động chuyển hướng sang VNPAY sau khi xác nhận.</div>
                                </div>

                                <div class="mb-4 p-3 rounded-4" style="background-color: #f8fafc; border: 1px dashed #cbd5e1;">
                                    <label class="form-label small fw-bold text-dark mb-2"><i class="bi bi-ticket-perforated text-warning me-1"></i>Mã giảm giá (Nếu có):</label>
                                    <div class="input-group shadow-sm">
                                        <input type="text" name="coupon_code" id="coupon_code" class="form-control text-uppercase" placeholder="Nhập mã voucher...">
                                        <button class="btn btn-dark fw-bold px-3" type="button" onclick="applyCoupon()">Áp dụng</button>
                                    </div>
                                    <small id="coupon_msg" class="d-block mt-2" style="font-size: 0.8rem; display: none;"></small>
                                </div>

                                <div class="d-grid mt-4">
                                    <?php if($is_expired): ?>
                                        <button type="button" class="btn btn-secondary btn-lg fw-bold rounded-pill py-3" disabled>ĐÃ HẾT HẠN ĐẶT</button>
                                    <?php elseif(isset($_SESSION['user_id'])): ?>
                                        <button type="submit" name="btnBooking" class="btn btn-danger btn-lg fw-bold rounded-pill shadow-sm py-3" style="background: linear-gradient(135deg, #FF862E, #FF4B2B); border: none;">XÁC NHẬN ĐẶT TOUR</button>
                                    <?php else: ?>
                                        <a href="login_user.php" class="btn btn-outline-danger btn-lg fw-bold rounded-pill py-3">ĐĂNG NHẬP ĐỂ ĐẶT</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL ẢNH -->
    <div class="modal fade" id="imageLightbox" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-header border-0 pb-0 justify-content-end">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="lightboxImg" src="" class="img-fluid shadow-lg rounded" style="max-height: 85vh;">
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('custName');
            const phoneInput = document.getElementById('custPhone');
            const rememberCheckbox = document.getElementById('rememberInfo');
            const bookingForm = document.getElementById('bookingForm');

            if (localStorage.getItem('rememberTourInfo') === 'true') {
                rememberCheckbox.checked = true;
                if (localStorage.getItem('tourCustName')) {
                    nameInput.value = localStorage.getItem('tourCustName');
                }
                if (localStorage.getItem('tourCustPhone')) {
                    phoneInput.value = localStorage.getItem('tourCustPhone');
                }
            }

            if(bookingForm) {
                bookingForm.addEventListener('submit', function() {
                    if (rememberCheckbox.checked) {
                        localStorage.setItem('rememberTourInfo', 'true');
                        localStorage.setItem('tourCustName', nameInput.value);
                        localStorage.setItem('tourCustPhone', phoneInput.value);
                    } else {
                        localStorage.removeItem('rememberTourInfo');
                        localStorage.removeItem('tourCustName');
                        localStorage.removeItem('tourCustPhone');
                    }
                });
            }
        });

        function updateQty(val) {
            let qtyInput = document.getElementById('qty');
            let max = parseInt(qtyInput.getAttribute('max')); 
            let newQty = parseInt(qtyInput.value) + val;
            if (newQty >= 1 && newQty <= max) {
                qtyInput.value = newQty;
                document.getElementById('people-count').innerText = newQty;
                document.getElementById('display-total').innerText = (parseInt(document.getElementById('base-price').value) * newQty).toLocaleString('vi-VN') + 'đ';
            }
        }

        function togglePaymentInfo() {
            document.getElementById('vnpay-payment-info').style.display = (document.getElementById('payment_method').value === 'VNPay') ? 'block' : 'none';
        }
        document.addEventListener('DOMContentLoaded', togglePaymentInfo);

        function showImageModal(src) {
            document.getElementById('lightboxImg').src = src;
            new bootstrap.Modal(document.getElementById('imageLightbox')).show();
        }

        (function() {
            const galleryEl = document.getElementById('detailGallery');
            if (!galleryEl) return;
            const slides = Array.from(galleryEl.querySelectorAll('.detail-slide'));
            const thumbs = Array.from(galleryEl.querySelectorAll('.detail-thumb-item'));
            let currentIndex = 0; let slideTimer = null; let isPaused = false;

            function goToSlide(targetIndex) {
                if (targetIndex === currentIndex) return;
                targetIndex = (targetIndex + slides.length) % slides.length;
                slides.forEach((s, i) => s.classList.toggle('active', i === targetIndex));
                thumbs.forEach((th, i) => th.classList.toggle('active', i === targetIndex));
                let countEl = document.getElementById('galleryCurrentNum');
                if (countEl) countEl.textContent = targetIndex + 1;
                currentIndex = targetIndex;
                startTimer();
            }

            function startTimer() {
                if(slideTimer) clearInterval(slideTimer);
                slideTimer = setInterval(() => { if (!isPaused) goToSlide(currentIndex + 1); }, 4000);
            }
            
            galleryEl.addEventListener('mouseenter', () => isPaused = true);
            galleryEl.addEventListener('mouseleave', () => isPaused = false);
            
            thumbs.forEach(thumb => {
                thumb.addEventListener('click', (e) => {
                    e.preventDefault(); goToSlide(parseInt(thumb.getAttribute('data-thumb-index'), 10));
                });
            });

            document.getElementById('galleryPrevBtn')?.addEventListener('click', () => goToSlide(currentIndex - 1));
            document.getElementById('galleryNextBtn')?.addEventListener('click', () => goToSlide(currentIndex + 1));

            startTimer();
        })();

        document.addEventListener('DOMContentLoaded', function() {
            const fsTimerEl = document.getElementById('fs-timer');
            if (fsTimerEl && fsTimerEl.dataset.end) {
                const rawEnd = fsTimerEl.dataset.end.replace(' ', 'T');
                const flashEnd = new Date(rawEnd).getTime();
                
                const timerInterval = setInterval(function() {
                    const now = new Date().getTime();
                    const distance = flashEnd - now;
                    if (distance <= 0) {
                        clearInterval(timerInterval);
                        fsTimerEl.textContent = "ĐÃ KẾT THÚC";
                        return;
                    }
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const secs = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    if (days > 0) {
                        fsTimerEl.textContent = `${days} Ngày ${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                    } else {
                        fsTimerEl.textContent = `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                    }
                }, 1000);
            }
        });
        
        function applyCoupon() {
            const code = document.getElementById('coupon_code').value.trim();
            const msgEl = document.getElementById('coupon_msg');
            if(code === '') {
                msgEl.className = 'd-block mt-2 text-danger fw-bold';
                msgEl.textContent = 'Vui lòng nhập mã giảm giá!';
                return;
            }
            msgEl.className = 'd-block mt-2 text-danger fw-bold';
            msgEl.textContent = 'Mã giảm giá không hợp lệ hoặc đã hết hạn!';
        }
    </script>
</body>
</html>