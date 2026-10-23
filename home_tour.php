<?php
// Kiểm tra dữ liệu Flash Sale
$sql_flash = "SELECT fs.*, t.TourName, t.Price as OriginalPrice, t.DepartureLocation, t.Duration, t.DepartureSchedule, t.ImageURL 
              FROM flash_sales fs JOIN tours t ON fs.TourID = t.TourID 
              WHERE fs.SaleEnd > NOW() AND fs.SaleStart <= NOW() AND fs.Sold < fs.Quota AND fs.status = 'Active'
              ORDER BY fs.SaleEnd ASC, fs.FlashSaleID DESC LIMIT 3";
$res_flash = mysqli_query($conn, $sql_flash);
$has_flash = ($res_flash && mysqli_num_rows($res_flash) > 0);

if ($has_flash): 
    $earliest_end = null; 
    $flash_items = [];
    while($item = mysqli_fetch_assoc($res_flash)) {
        $flash_items[] = $item;
        if (!$earliest_end || strtotime($item['SaleEnd']) < strtotime($earliest_end)) { 
            $earliest_end = $item['SaleEnd']; 
        }
    }
?>
<!-- KHU VỰC SIÊU ƯU ĐÃI -->
<section class="flash-sale-section mt-5">
    <div class="container position-relative" style="z-index: 2;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-lightning-charge-fill text-warning fs-3"></i>
                    <h2 class="text-white fw-bold m-0" style="text-transform: uppercase;">SIÊU ƯU ĐÃI</h2>
                </div>
                <p class="text-white-50 mb-0">Các deal du lịch hấp dẫn với mức giá tốt nhất đang chờ bạn!</p>
            </div>
            <?php if (!empty($earliest_end)): ?>
            <div class="countdown-box mt-3 mt-md-0" id="flash-countdown" data-end="<?php echo htmlspecialchars($earliest_end); ?>">
                <div class="countdown-item"><div class="countdown-number" id="cd-days">00</div><div class="countdown-label">Ngày</div></div>
                <div class="countdown-item"><div class="countdown-number" id="cd-hours">00</div><div class="countdown-label">Giờ</div></div>
                <div class="countdown-item"><div class="countdown-number" id="cd-mins">00</div><div class="countdown-label">Phút</div></div>
                <div class="countdown-item"><div class="countdown-number text-warning" id="cd-secs">00</div><div class="countdown-label text-warning">Giây</div></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <?php foreach($flash_items as $fs):
                    $percent = round((($fs['OriginalPrice'] - $fs['DiscountPrice']) / $fs['OriginalPrice']) * 100);
                    $slots_left = max(0, $fs['Quota'] - $fs['Sold']);
                    $progress = ($fs['Quota'] > 0) ? min(100, ($fs['Sold'] / $fs['Quota']) * 100) : 0;
                    $img_url = !empty($fs['ImageURL']) ? $fs['ImageURL'] : "https://images.unsplash.com/photo-1528127269322?q=80&w=600";
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="mtt-tour-card">
                    <div class="mtt-card-img">
                        <div class="mtt-badge-top">
                            <span class="mtt-badge-hot" style="background:#00558F;box-shadow:none;"><i class="bi bi-tag-fill"></i> ƯU ĐÃI</span>
                            <span class="mtt-badge-hot"><i class="bi bi-lightning-charge-fill"></i> -<?php echo $percent; ?>%</span>
                        </div>
                        <img src="<?php echo htmlspecialchars($img_url); ?>" alt="" loading="lazy">
                        <div class="mtt-card-overlay"></div>
                        <div class="mtt-price-tag">
                            <div class="mtt-price-label" style="text-decoration: line-through; color: #cbd5e1;"><?php echo number_format($fs['OriginalPrice']); ?>đ</div>
                            <div class="mtt-price-val" style="color: #ef4444;"><?php echo number_format($fs['DiscountPrice']); ?>đ</div>
                        </div>
                    </div>
                    <div class="mtt-card-body">
                        <h3 class="mtt-tour-name">
                            <a href="detail.php?id=<?php echo $fs['TourID']; ?>"><?php echo htmlspecialchars($fs['TourName']); ?></a>
                        </h3>
                        <div class="mtt-info-grid mb-2">
                            <div class="mtt-info-item"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($fs['DepartureLocation'] ?? 'TP.HCM'); ?></div>
                            <div class="mtt-info-item"><i class="bi bi-clock-fill"></i> <?php echo htmlspecialchars($fs['Duration']); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small fw-bold mb-1">
                                <span class="text-muted">Đã bán: <?php echo $fs['Sold']; ?></span>
                                <span class="text-danger">Còn <?php echo $slots_left; ?> suất</span>
                            </div>
                            <div class="progress" style="height: 6px; border-radius: 10px;">
                                <div class="progress-bar bg-danger progress-bar-striped progress-bar-animated" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        <div class="mtt-card-footer mt-auto">
                            <a href="detail.php?id=<?php echo $fs['TourID']; ?>" class="mtt-btn-detail">Chi tiết <i class="bi bi-arrow-right-short fs-5"></i></a>
                            <a href="detail.php?id=<?php echo $fs['TourID']; ?>" class="mtt-btn-book">Săn Deal</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- KHU VỰC DANH SÁCH TOUR -->
<div class="container mt-5 pt-4 pb-5 bg-subtle-blue rounded-4 shadow-sm" id="tour-section">
    
    <!-- TIÊU ĐỀ KHU VỰC TOUR -->
    <div class="section-title-wrap">
        <h2 class="m-0" style="font-size: 30px; font-weight: 800; color: #0F172A; text-transform: uppercase;">
            <?php echo isset($is_homepage) && $is_homepage ? 'TOUR NỔI BẬT' : 'KẾT QUẢ TÌM KIẾM'; ?>
        </h2>
        <?php if(isset($is_homepage) && $is_homepage): ?>
            <p class="mt-1 mb-0" style="font-size: 15px; color: #64748B;">Những hành trình được yêu thích và lựa chọn nhiều nhất</p>
        <?php endif; ?>
    </div>
    
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php
        if (isset($result) && mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $t_id = $row['TourID'];
                $sql_count = "SELECT SUM(Quantity) as booked FROM Bookings WHERE TourID = $t_id AND Status != N'Đã hủy'";
                $res_count = mysqli_query($conn, $sql_count);
                $row_count = mysqli_fetch_assoc($res_count);
                
                $max = isset($row['MaxPeople']) ? $row['MaxPeople'] : 10;
                $booked = ($row_count['booked']) ? $row_count['booked'] : 0;
                $remain = max(0, $max - $booked);

                $img_url = !empty($row['ImageURL']) ? $row['ImageURL'] : "https://images.unsplash.com/photo-1528127269322?q=80&w=600";
                $departure_schedule = !empty($row['DepartureSchedule']) ? htmlspecialchars($row['DepartureSchedule']) : "Theo yêu cầu";
                ?>
                <div class="col mb-4 reveal">
                    <div class="mtt-tour-card"> 
                        <div class="mtt-card-img">
                            <div class="mtt-badge-top">
                                <span class="mtt-badge-hot"><i class="bi bi-fire"></i> Bán chạy</span>
                            </div>
                            <img src="<?php echo htmlspecialchars($img_url); ?>" alt="<?php echo htmlspecialchars($row['TourName']); ?>" loading="lazy">
                            <div class="mtt-card-overlay"></div>
                            <div class="mtt-price-tag">
                                <div class="mtt-price-label">Giá trọn gói từ</div>
                                <div class="mtt-price-val"><?php echo number_format($row['Price']); ?>đ</div>
                            </div>
                        </div>
                        
                        <div class="mtt-card-body">
                            <h3 class="mtt-tour-name">
                                <a href="detail.php?id=<?php echo $row['TourID']; ?>"><?php echo htmlspecialchars($row['TourName']); ?></a>
                            </h3>
                            
                            <div class="mtt-info-grid">
                                <div class="mtt-info-item" title="Nơi khởi hành">
                                    <i class="bi bi-geo-alt-fill"></i> <?php echo (!empty($row['DepartureLocation'])) ? htmlspecialchars($row['DepartureLocation']) : "TP.HCM"; ?>
                                </div>
                                <div class="mtt-info-item" title="Thời gian">
                                    <i class="bi bi-clock-fill"></i> <?php echo htmlspecialchars($row['Duration']); ?>
                                </div>
                                <div class="mtt-info-item" style="grid-column: span 2;" title="Lịch khởi hành">
                                    <i class="bi bi-calendar-check-fill"></i> Khởi hành: <span class="text-dark fw-bold"><?php echo $departure_schedule; ?></span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <span class="mtt-slots-left">
                                    <i class="bi bi-person-check-fill"></i> Còn <?php echo $remain; ?> chỗ nhận khách
                                </span>
                            </div>

                            <div class="mtt-card-footer"> 
                                <a href="detail.php?id=<?php echo $row['TourID']; ?>" class="mtt-btn-detail">
                                    Xem chi tiết <i class="bi bi-arrow-right-short fs-5"></i>
                                </a>
                                <a href="detail.php?id=<?php echo $row['TourID']; ?>" class="mtt-btn-book">
                                    Đặt Ngay
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo "<div class='col-12 text-center py-5'><p class='fs-5 text-muted'>Không tìm thấy chuyến đi nào phù hợp.</p></div>";
        }
        ?>
    </div>

    <!-- HIỂN THỊ PHÂN TRANG KHI CÓ HƠN 9 TOUR -->
    <?php if (isset($total_pages) && $total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-5 pt-3">
            <ul class="pagination justify-content-center">
                <?php 
                $qs_array = $_GET; unset($qs_array['page']);
                $qs = http_build_query($qs_array);
                $prefix = (!empty($qs) ? "&" . $qs : "") . "#tour-section";
                ?>
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1 . $prefix; ?>"><i class="bi bi-chevron-left"></i> Trước</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i . $prefix; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1 . $prefix; ?>">Sau <i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>