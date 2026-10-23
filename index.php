<?php 
session_start(); 
include 'db.php'; 

// --- XỬ LÝ LỌC TÌM KIẾM TOUR ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : "";
$price_range = isset($_GET['price_range']) ? $_GET['price_range'] : "";
$custom_price = isset($_GET['custom_price']) && $_GET['custom_price'] !== '' ? intval($_GET['custom_price']) : 0;
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

$conditions = "1=1"; 
if (!empty($search)) { $conditions .= " AND TourName LIKE '%$search%'"; }
if ($category_id > 0) { $conditions .= " AND CategoryID = $category_id"; }

if (!empty($price_range)) {
    if ($price_range == "0-1000000") $conditions .= " AND Price < 1000000";
    elseif ($price_range == "1000000-3000000") $conditions .= " AND Price BETWEEN 1000000 AND 3000000";
    elseif ($price_range == "3000000-up") $conditions .= " AND Price > 3000000";
} elseif ($custom_price > 0) { $conditions .= " AND Price <= $custom_price"; }

// --- THUẬT TOÁN PHÂN TRANG TOUR ---
$is_homepage = empty($search) && empty($price_range) && $custom_price == 0 && $category_id == 0 && !isset($_GET['all']);

$limit = 9; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit; // Đã sửa lại dòng bị đứt gãy

$sql_count = "SELECT COUNT(*) as total FROM Tours WHERE $conditions";
$res_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($res_count);
$total_records = $row_count['total'];
$total_pages = ceil($total_records / $limit);

$sql = "SELECT * FROM Tours WHERE $conditions ORDER BY TourID DESC LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);
$sql_cats = "SELECT * FROM Categories";
$res_cats = mysqli_query($conn, $sql_cats);

// --- XÁC ĐỊNH TAB HOẠT ĐỘNG ---
$active_tab = 'tour';
if (isset($_GET['tab']) && in_array($_GET['tab'], ['tour', 'flight', 'hotel'])) {
    $active_tab = $_GET['tab'];
} elseif (isset($_GET['search_flight'])) {
    $active_tab = 'flight';
} elseif (isset($_GET['search_hotel'])) {
    $active_tab = 'hotel';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIỀNTÂY Travel - Mạng bán tour trực tuyến</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ========================================================
           1. BỘ HIỆU ỨNG ANIMATION XUẤT HIỆN MƯỢT MÀ
           ======================================================== */
        .anim-up { opacity: 0; transform: translateY(40px); transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .anim-left { opacity: 0; transform: translateX(-40px); transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .anim-right { opacity: 0; transform: translateX(40px); transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .anim-visible { opacity: 1 !important; transform: translate(0) scale(1) !important; }
        .d1 { transition-delay: 0.1s; } .d2 { transition-delay: 0.2s; } .d3 { transition-delay: 0.3s; } .d4 { transition-delay: 0.4s; }

        @media (prefers-reduced-motion: reduce) {
            .anim-up, .anim-left, .anim-right { opacity: 1 !important; transform: none !important; }
        }

        /* ========================================================
           2. BANNER & STORY TOP 10 ĐIỂM ĐẾN
           ======================================================== */
        .ab-hero {
            position: relative; min-height: 90vh;
            display: flex; align-items: center; overflow: hidden;
            padding-bottom: 80px; padding-top: 60px;
        }
        .ab-hero-bg {
            position: absolute; inset: 0;
            background: url('https://images.unsplash.com/photo-1528127269322-539801943592?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;
            transform: scale(1.05); animation: heroBgZoom 25s infinite alternate linear; z-index: 1;
        }
        @keyframes heroBgZoom { from { transform: scale(1); } to { transform: scale(1.1); } }
        
        .ab-hero-grad {
            position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(0,40,77,.4) 0%, rgba(0,20,40,.8) 70%, rgba(0,10,25,.95) 100%);
            z-index: 2;
        }
        .ab-hero-content { position: relative; z-index: 10; width: 100%; }
        
        .hero-eyebrow { font-size: 0.9rem; font-weight: 700; letter-spacing: 4px; color: #FF862E; text-transform: uppercase; margin-bottom: 15px; display: block; }
        .hero-h1 { font-family: 'Inter', sans-serif; font-size: clamp(2.5rem, 5.5vw, 4.5rem); font-weight: 900; line-height: 1.1; color: #fff; letter-spacing: -1px; margin-bottom: 15px; text-transform: uppercase; }
        .hero-h1 .accent { color: #FF862E; }
        .hero-sub { font-size: clamp(1rem, 2vw, 1.15rem); color: rgba(255,255,255,0.85); line-height: 1.6; max-width: 550px; margin-bottom: 30px; }

        .ab-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 50px; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; text-decoration: none; transition: 0.3s; }
        .ab-btn-solid { background: #FF862E; color: #fff; border: 2px solid #FF862E; box-shadow: 0 8px 20px rgba(255,134,46,.3); }
        .ab-btn-solid:hover { background: #EA580C; border-color: #EA580C; color: #fff; transform: translateY(-2px); }

        /* KHỐI TOP 10 STORY CIRCLES (TRÒN) */
        .story-container { margin-top: 40px; }
        .story-title { color: #fff; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .story-track { display: flex; gap: 20px; overflow-x: auto; padding-bottom: 15px; scrollbar-width: none; -ms-overflow-style: none; }
        .story-track::-webkit-scrollbar { display: none; }
        
        .story-item { display: flex; flex-direction: column; align-items: center; text-decoration: none; transition: 0.3s; min-width: 85px; }
        .story-item:hover { transform: translateY(-5px); }
        .story-img-wrap { 
            width: 80px; height: 80px; border-radius: 50%; padding: 3px;
            background: linear-gradient(135deg, #FF862E, #FDE047); 
            position: relative; box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            transition: 0.3s;
        }
        .story-item:hover .story-img-wrap { box-shadow: 0 10px 25px rgba(255, 134, 46, 0.4); }
        .story-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid #001a33; }
        
        .story-badge { 
            position: absolute; top: -5px; right: -5px; background: #dc2626; color: #fff;
            width: 24px; height: 24px; border-radius: 50%; font-size: 0.7rem; font-weight: bold;
            display: flex; align-items: center; justify-content: center; border: 2px solid #001a33;
        }
        .story-name { color: #fff; font-size: 0.85rem; font-weight: 600; margin-top: 8px; text-shadow: 0 2px 4px rgba(0,0,0,0.6); text-align: center; white-space: nowrap; }

        /* --- CSS THẺ TOUR NỔI BẬT --- */
        #tour-section, #search-widget { scroll-margin-top: 110px; }
        .section-title-wrap { border-left: 6px solid var(--orange, #FF862E); padding-left: 15px; margin-bottom: 30px; }
        .mtt-tour-card { background: #ffffff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column; height: 100%; position: relative; transition: all 0.4s ease; }
        .mtt-tour-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0, 85, 143, 0.15); border-color: #bae6fd; }
        .mtt-card-img { position: relative; height: 230px; overflow: hidden; }
        .mtt-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .mtt-tour-card:hover .mtt-card-img img { transform: scale(1.12); }
        .mtt-card-overlay { position: absolute; bottom: 0; left: 0; right: 0; height: 60%; background: linear-gradient(to top, rgba(0,25,50,0.85) 0%, transparent 100%); pointer-events: none; }
        .mtt-badge-top { position: absolute; top: 12px; left: 12px; z-index: 2; display: flex; gap: 8px; }
        .mtt-badge-hot { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4); text-transform: uppercase; }
        .mtt-price-tag { position: absolute; bottom: 12px; right: 15px; z-index: 2; text-align: right; }
        .mtt-price-label { font-size: 0.75rem; color: rgba(255,255,255,0.9); margin-bottom: -2px; text-shadow: 0 1px 2px rgba(0,0,0,0.5); }
        .mtt-price-val { font-size: 1.5rem; font-weight: 800; color: #FDE047; text-shadow: 0 2px 4px rgba(0,0,0,0.6); line-height: 1; }
        .mtt-card-body { padding: 22px; display: flex; flex-direction: column; flex-grow: 1; }
        .mtt-tour-name { font-size: 1.15rem; font-weight: 700; line-height: 1.4; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .mtt-tour-name a { color: #0F172A; text-decoration: none; }
        .mtt-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 10px; margin-bottom: 20px; }
        .mtt-info-item { font-size: 0.85rem; color: #475569; display: flex; align-items: center; gap: 8px; font-weight: 500; }
        .mtt-info-item i { color: #0284C7; font-size: 1rem; }
        .mtt-slots-left { background: #FEF2F2; color: #DC2626; padding: 6px 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #FECACA; }
        .mtt-card-footer { margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #E2E8F0; padding-top: 18px; }
        .mtt-btn-detail { color: #00558F; font-weight: 700; font-size: 0.9rem; text-decoration: none; display: flex; align-items: center; gap: 5px; }
        .mtt-btn-book { background: linear-gradient(135deg, #FF862E, #EA580C); color: white !important; padding: 8px 24px; border-radius: 50px; font-weight: 700; font-size: 0.95rem; text-decoration: none; }

        /* --- CSS ĐIỂM ĐẾN NỔI BẬT DƯỚI ĐÁY --- */
        .dest-card { position: relative; border-radius: 16px; overflow: hidden; height: 260px; display: block; background: #e2e8f0; transition: transform 0.4s ease, box-shadow 0.4s ease; }
        .dest-card.large { height: 536px; }
        .dest-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .dest-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.2); }
        .dest-card:hover img { transform: scale(1.1); }
        .dest-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 50%); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; }
        .dest-btn { background: transparent; border: 1px solid white; color: white; border-radius: 50px; padding: 6px 16px; font-size: 0.85rem; display: inline-block; width: max-content; margin-top: 8px; transition: 0.3s; }
        .dest-card:hover .dest-btn { background: white; color: #00284d; }
    </style>
</head>
<body style="background-color: #F8FAFC;">
    
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- THUẬT TOÁN TÌM TOP 10 ĐIỂM ĐẾN (STORY CIRCLES) -->
    <?php
    $bubble_destinations = [];
    $seen_names = [];
    $sql_bubbles = "SELECT t.TourName, t.ImageURL, COALESCE(SUM(b.Quantity), 0) as TotalBooked 
                    FROM tours t LEFT JOIN bookings b ON t.TourID = b.TourID AND b.Status != N'Đã hủy'
                    GROUP BY t.TourID, t.TourName, t.ImageURL ORDER BY TotalBooked DESC LIMIT 10";
    $res_bubbles = mysqli_query($conn, $sql_bubbles);
    
    if ($res_bubbles && mysqli_num_rows($res_bubbles) > 0) {
        while($row = mysqli_fetch_assoc($res_bubbles)) {
            $name = str_ireplace(['Tour ', 'Khám Phá ', 'Di Sản ', 'Đảo Ngọc ', 'Ngàn Hoa '], '', $row['TourName']); 
            $parts = explode(':', $name); $parts = explode('-', $parts[0]); $short_name = trim($parts[0]);
            if (!empty($short_name) && !in_array($short_name, $seen_names)) {
                $seen_names[] = $short_name;
                $img = !empty($row['ImageURL']) ? $row['ImageURL'] : 'https://images.unsplash.com/photo-1528127269322?q=80&w=150';
                $bubble_destinations[] = ['name' => $short_name, 'img' => $img];
            }
        }
    }
    
    $default_bubbles = [
        ['name' => 'Phú Quốc', 'img' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSYVVGsw8nr505nz_5p603_AbqX6j6VKiUcXVCP9Uxs_g&s=10'],
        ['name' => 'Đà Lạt', 'img' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS7xpQaIHe--cpd-xD8Y5VaUr3MzdUqTKtI7uAdFRaA9Q&s=10'],
        ['name' => 'Hạ Long', 'img' => 'https://cdn3.ivivu.com/2026/03/du-lich-Ha-Long-tiet-kiem-ivivu.jpg'],
        ['name' => 'Đà Nẵng', 'img' => 'https://vietluxtour.com/Upload/images/2024/khamphatrongnuoc/C%E1%BA%A7u%20R%E1%BB%93ng%20%C4%90%C3%A0%20N%E1%BA%B5ng/cau-rong-da-nang%20(6)-min.jpg'],
        ['name' => 'Sapa', 'img' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQakyM4_0BD3MXfQaM_qrzNZye3srzaKxMtdx7JKaMHsg&s=10'],
        ['name' => 'Nha Trang', 'img' => 'https://cdn3.ivivu.com/2014/01/nha-trang-ivivu.jpg'],
        ['name' => 'Quy Nhơn', 'img' => 'https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?q=80&w=400'],
        ['name' => 'Cần Thơ', 'img' => 'https://images.unsplash.com/photo-1528127269322?q=80&w=400'],
        ['name' => 'Hà Nội', 'img' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=400'],
        ['name' => 'Vũng Tàu', 'img' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=400']
    ];
    foreach ($default_bubbles as $def) {
        if (count($bubble_destinations) >= 10) break;
        if (!in_array($def['name'], $seen_names)) { $bubble_destinations[] = $def; $seen_names[] = $def['name']; }
    }
    $bubble_destinations = array_slice($bubble_destinations, 0, 10); 
    ?>

    <!-- BANNER CHÍNH KÈM TOP 10 STORY CIRCLES -->
    <header class="ab-hero">
        <div class="ab-hero-bg"></div>
        <div class="ab-hero-grad"></div>

        <div class="container ab-hero-content">
            <div class="row align-items-center">
                <!-- Cột Text (Trái) -->
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <span class="hero-eyebrow anim-up">Miền Tây Travel</span>
                    <h1 class="hero-h1 anim-up d1">ĐI ĐỂ THẤY<br><span class="accent">VIỆT NAM</span><br>ĐẸP ĐẾN NHƯỜNG NÀO</h1>
                    <p class="hero-sub anim-up d2">Mỗi hành trình là một câu chuyện. Mỗi điểm đến là một ký ức. Tận hưởng những chuyến đi đẳng cấp cùng Miền Tây Travel.</p>
                    <div class="anim-up d3">
                        <a href="#search-widget" class="ab-btn ab-btn-solid"><i class="bi bi-compass"></i> Khám phá ngay</a>
                    </div>
                </div>

                <!-- Cột Top 10 Địa điểm (Phải) -->
                <div class="col-lg-6">
                    <div class="story-container anim-up d3">
                        <div class="story-title"><i class="bi bi-fire text-warning fs-5"></i> TOP 10 ĐIỂM ĐẾN YÊU THÍCH NHẤT</div>
                        <div class="story-track">
                            <?php foreach($bubble_destinations as $index => $dest): ?>
                            <a href="index.php?search=<?php echo urlencode($dest['name']); ?>#tour-section" class="story-item">
                                <div class="story-img-wrap">
                                    <img src="<?php echo $dest['img']; ?>" class="story-img" alt="<?php echo $dest['name']; ?>">
                                    <div class="story-badge"><?php echo $index + 1; ?></div>
                                </div>
                                <span class="story-name"><?php echo $dest['name']; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- WIDGET TÌM KIẾM -->
    <div class="container search-widget-glass anim-up d4" id="search-widget" style="margin-top: -60px; position: relative; z-index: 20;">
        <div class="search-box glassmorphism shadow-lg p-4" style="border-radius: 20px; background: white;">
            <ul class="nav nav-tabs mb-3" id="searchTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link <?php echo $active_tab === 'tour' ? 'active' : ''; ?>" id="tab-tour-btn" data-bs-toggle="tab" data-bs-target="#tab-tour-pane" type="button"><i class="bi bi-briefcase"></i> Tour trọn gói</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?php echo $active_tab === 'flight' ? 'active' : ''; ?>" id="tab-flight-btn" data-bs-toggle="tab" data-bs-target="#tab-flight-pane" type="button"><i class="bi bi-airplane"></i> Vé máy bay</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?php echo $active_tab === 'hotel' ? 'active' : ''; ?>" id="tab-hotel-btn" data-bs-toggle="tab" data-bs-target="#tab-hotel-pane" type="button"><i class="bi bi-building"></i> Khách sạn</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade <?php echo $active_tab === 'tour' ? 'show active' : ''; ?>" id="tab-tour-pane">
                    <form id="tourSearchForm" action="index.php#tour-section" method="GET" class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label text-muted small fw-medium">Điểm đến</label>
                            <input type="text" name="search" class="form-control" placeholder="Bạn muốn đi đâu?" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label text-muted small fw-medium">Danh mục tour</label>
                            <select name="category" class="form-select">
                                <option value="0">Tất cả danh mục</option>
                                <?php if($res_cats): mysqli_data_seek($res_cats, 0); while($c = mysqli_fetch_assoc($res_cats)): ?>
                                    <option value="<?php echo $c['CategoryID']; ?>" <?php echo ($category_id == $c['CategoryID']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['CategoryName']); ?></option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-9">
                            <label class="form-label text-muted small fw-medium">Mức giá tối đa</label>
                            <input type="hidden" id="price_range_input" name="price_range" value="<?php echo htmlspecialchars($price_range); ?>">
                            <input type="number" name="custom_price" class="form-control" placeholder="Nhập ngân sách..." min="0" value="<?php echo $custom_price > 0 ? $custom_price : ''; ?>" oninput="clearPreset()">
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <button class="btn btn-primary w-100" style="background: var(--orange, #FF862E); border:none; font-weight: bold; padding: 10px;" type="submit"><i class="bi bi-search"></i> Tìm kiếm</button>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade <?php echo $active_tab === 'flight' ? 'show active' : ''; ?>" id="tab-flight-pane">
                    <?php include 'tab_flight.php'; ?>
                </div>
                <div class="tab-pane fade <?php echo $active_tab === 'hotel' ? 'show active' : ''; ?>" id="tab-hotel-pane">
                    <?php include 'tab_hotel.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- KHU VỰC IN DANH SÁCH TỪNG TAB -->
    <?php
    if ($active_tab === 'tour') {
        echo "<div id='tour-wrapper-container' class='pb-5'>"; 
        
        // Thêm trường IsFullPackage vào câu truy vấn Flash Sale để hiển thị nhãn
        $sql_flash = "SELECT fs.*, t.TourName, t.Price as OriginalPrice, t.DepartureLocation, t.Duration, t.DepartureSchedule, t.ImageURL, t.Transport, t.Accommodation, t.IsFullPackage 
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
        <!-- KHU VỰC SIÊU ƯU ĐÃI (ĐÃ LÀM LẠI ĐẸP HƠN) -->
        <section class="container mt-5 anim-up">
            <!-- BANNER -->
            <div class="position-relative overflow-hidden mb-5 p-4 p-md-5 rounded-4 border-0" style="background: linear-gradient(135deg, #ff416c, #ff4b2b); box-shadow: 0 10px 30px rgba(200, 255, 3, 0.3);">
                <i class="bi bi-geo-alt-fill position-absolute" style="font-size: 15rem; color: rgba(255, 255, 255, 0.1); top: -30px; right: 5%; transform: rotate(15deg);"></i>
                
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center position-relative z-index-1">
                    <div class="mb-4 mb-lg-0 text-center text-lg-start">
                        <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-pill mb-3 bg-white shadow-sm">
                            <span class="spinner-grow spinner-grow-sm text-danger" role="status"></span>
                            <span class="text-danger fw-bold small">SỰ KIỆN ĐANG DIỄN RA</span>
                        </div>
                        <h2 class="text-white fw-bold mb-2" style="font-size: 2.8rem; text-transform: uppercase; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">
                            <i class="bi bi-lightning-charge-fill me-2" style="color: #FFD700 !important;"></i>SIÊU ƯU ĐÃI
                        </h2>
                        <p class="text-white fs-5 mb-0 fw-medium">Săn ngay các deal du lịch cực Hot với mức giá chạm đáy. Đừng bỏ lỡ!</p>
                    </div>

                    <?php if (!empty($earliest_end)): ?>
                    <div class="d-flex gap-2 gap-md-3 justify-content-center mt-3 mt-lg-0" id="flash-countdown" data-end="<?php echo htmlspecialchars($earliest_end); ?>">
                        <div class="d-flex flex-column align-items-center rounded-4 p-3 bg-white shadow-sm" style="min-width: 80px;">
                            <h3 class="fw-bold m-0 text-danger" id="cd-days" style="font-size: 2.2rem; line-height: 1;">00</h3>
                            <small class="fw-bold text-muted text-uppercase mt-1" style="font-size: 0.75rem;">Ngày</small>
                        </div>
                        <div class="d-flex flex-column align-items-center rounded-4 p-3 bg-white shadow-sm" style="min-width: 80px;">
                            <h3 class="fw-bold m-0 text-danger" id="cd-hours" style="font-size: 2.2rem; line-height: 1;">00</h3>
                            <small class="fw-bold text-muted text-uppercase mt-1" style="font-size: 0.75rem;">Giờ</small>
                        </div>
                        <div class="d-flex flex-column align-items-center rounded-4 p-3 bg-white shadow-sm" style="min-width: 80px;">
                            <h3 class="fw-bold m-0 text-danger" id="cd-mins" style="font-size: 2.2rem; line-height: 1;">00</h3>
                            <small class="fw-bold text-muted text-uppercase mt-1" style="font-size: 0.75rem;">Phút</small>
                        </div>
                        <div class="d-flex flex-column align-items-center rounded-4 p-3 shadow-sm" style="min-width: 80px; background: #0f172a;">
                            <h3 class="fw-bold m-0 text-warning" id="cd-secs" style="font-size: 2.2rem; line-height: 1;">00</h3>
                            <small class="fw-bold text-white-50 text-uppercase mt-1" style="font-size: 0.75rem;">Giây</small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row g-4 mb-5">
                <?php foreach($flash_items as $fs):
                    $percent = round((($fs['OriginalPrice'] - $fs['DiscountPrice']) / $fs['OriginalPrice']) * 100);
                    $slots_left = max(0, $fs['Quota'] - $fs['Sold']);
                    $progress = ($fs['Quota'] > 0) ? min(100, ($fs['Sold'] / $fs['Quota']) * 100) : 0;
                    $img_url = !empty($fs['ImageURL']) ? $fs['ImageURL'] : "https://images.unsplash.com/photo-1528127269322?q=80&w=600";
                ?>
                <div class="col-lg-4 col-md-6 anim-up">
                    <div class="mtt-tour-card border border-danger border-opacity-50">
                        <div class="mtt-card-img">
                            <div class="mtt-badge-top">
                                <span class="mtt-badge-hot" style="background:#00558F;box-shadow:none;"><i class="bi bi-tag-fill"></i> ƯU ĐÃI</span>
                                <span class="mtt-badge-hot"><i class="bi bi-lightning-charge-fill"></i> -<?php echo $percent; ?>%</span>
                            </div>
                            <img src="<?php echo htmlspecialchars($img_url); ?>" alt="" loading="lazy">
                            <div class="mtt-card-overlay"></div>
                            <div class="mtt-price-tag">
                                <div class="mtt-price-label mb-1">
                                    <?php 
                                    if (isset($fs['IsFullPackage']) && $fs['IsFullPackage'] == 0) {
                                        echo "<span style='color: #ff4444; font-weight: 800; font-size: 0.85rem;'><i class='bi bi-person-walking'></i> TOUR TỰ TÚC TỪ</span>";
                                    } else {
                                        echo "<span style='color: #ff4444; font-weight: 800; font-size: 0.85rem;'><i class='bi bi-box-seam'></i> GIÁ TRỌN GÓI TỪ</span>";
                                    }
                                    ?>
                                </div>
                                <div>
                                    <span class="mtt-price-label" style="text-decoration: line-through; color: #cbd5e1; font-size: 0.9rem; margin-right: 8px;">
                                        <?php echo number_format($fs['OriginalPrice']); ?>đ
                                    </span>
                                    <span class="mtt-price-val" style="color: #FDE047; font-size: 1.5rem; text-shadow: 0 2px 4px rgba(0,0,0,0.8);">
                                        <?php echo number_format($fs['DiscountPrice']); ?>đ
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="mtt-card-body">
                            <h3 class="mtt-tour-name">
                                <a href="detail.php?id=<?php echo $fs['TourID']; ?>"><?php echo htmlspecialchars($fs['TourName']); ?></a>
                            </h3>
                            <div class="mtt-info-grid mb-2">
                                <div class="mtt-info-item" title="Nơi khởi hành"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($fs['DepartureLocation'] ?? 'TP.HCM'); ?></div>
                                <div class="mtt-info-item" title="Thời gian"><i class="bi bi-clock-fill"></i> <?php echo htmlspecialchars($fs['Duration']); ?></div>
                                <div class="mtt-info-item" title="Phương tiện"><i class="bi bi-bus-front"></i> <?php echo !empty($fs['Transport']) ? htmlspecialchars($fs['Transport']) : "Ô tô/Máy bay"; ?></div>
                                <div class="mtt-info-item" title="Lưu trú"><i class="bi bi-building"></i> <?php echo !empty($fs['Accommodation']) ? htmlspecialchars($fs['Accommodation']) : "Khách sạn tiêu chuẩn"; ?></div>
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
        </section>
        <?php endif; ?>

        <!-- KHU VỰC DANH SÁCH TOUR THƯỜNG -->
        <div class="container mt-5" id="tour-section">
            <div class="section-title-wrap anim-left">
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
                    $delay = 0;
                    while($row = mysqli_fetch_assoc($result)) {
                        $t_id = $row['TourID'];
                        $sql_c = "SELECT SUM(Quantity) as booked FROM Bookings WHERE TourID = $t_id AND Status != N'Đã hủy'";
                        $res_c = mysqli_query($conn, $sql_c);
                        $row_c = mysqli_fetch_assoc($res_c);
                        $max = isset($row['MaxPeople']) ? $row['MaxPeople'] : 10;
                        $booked = ($row_c['booked']) ? $row_c['booked'] : 0;
                        $remain = max(0, $max - $booked); // Đã sửa lại dòng bị đứt gãy
                        $img_url = !empty($row['ImageURL']) ? $row['ImageURL'] : "https://images.unsplash.com/photo-1528127269322?q=80&w=600";
                        $departure_schedule = !empty($row['DepartureSchedule']) ? htmlspecialchars($row['DepartureSchedule']) : "Theo yêu cầu";
                        $delayClass = "d" . ($delay % 4); 
                        ?>
                        <div class="col mb-4 anim-up <?php echo $delayClass; ?>">
                            <div class="mtt-tour-card"> 
                                <div class="mtt-card-img">
                                    <div class="mtt-badge-top"><span class="mtt-badge-hot"><i class="bi bi-fire"></i> Bán chạy</span></div>
                                    <img src="<?php echo htmlspecialchars($img_url); ?>" alt="<?php echo htmlspecialchars($row['TourName']); ?>" loading="lazy">
                                    <div class="mtt-card-overlay"></div>
                                    
                                    <!-- ĐÂY LÀ PHẦN HIỂN THỊ GIÁ CẢ KÈM NHÃN TỰ TÚC / TRỌN GÓI -->
                                    <div class="mtt-price-tag">
                                        <div class="mtt-price-label">
                                        <?php 
                                        if (isset($row['IsFullPackage']) && $row['IsFullPackage'] == 0) {
                                            echo "<span style='color: #ff4444; font-weight: 800; font-size: 0.85rem;'><i class='bi bi-person-walking'></i> TOUR TỰ TÚC TỪ</span>";
                                        } else {
                                            echo "<span style='color: #ff4444; font-weight: 800; font-size: 0.85rem;'><i class='bi bi-box-seam'></i> GIÁ TRỌN GÓI TỪ</span>";
                                        }
                                        ?>
                                    </div>
                                        <div class="mtt-price-val"><?php echo number_format($row['Price']); ?>đ</div>
                                    </div>

                                </div>
                                <div class="mtt-card-body">
                                    <h3 class="mtt-tour-name"><a href="detail.php?id=<?php echo $row['TourID']; ?>"><?php echo htmlspecialchars($row['TourName']); ?></a></h3>
                                    <div class="mtt-info-grid">
                                        <div class="mtt-info-item" title="Nơi khởi hành"><i class="bi bi-geo-alt-fill"></i> <?php echo (!empty($row['DepartureLocation'])) ? htmlspecialchars($row['DepartureLocation']) : "TP.HCM"; ?></div>
                                        <div class="mtt-info-item" title="Thời gian"><i class="bi bi-clock-fill"></i> <?php echo htmlspecialchars($row['Duration']); ?></div>
                                        <div class="mtt-info-item" title="Phương tiện"><i class="bi bi-bus-front"></i> <?php echo !empty($row['Transport']) ? htmlspecialchars($row['Transport']) : "Ô tô/Máy bay"; ?></div>
                                        <div class="mtt-info-item" title="Lưu trú"><i class="bi bi-building"></i> <?php echo !empty($row['Accommodation']) ? htmlspecialchars($row['Accommodation']) : "Khách sạn tiêu chuẩn"; ?></div>
                                        <div class="mtt-info-item" style="grid-column: span 2;" title="Lịch khởi hành"><i class="bi bi-calendar-check-fill"></i> Khởi hành: <span class="text-dark fw-bold"><?php echo $departure_schedule; ?></span></div>
                                    </div>
                                    <div class="mb-4">
                                        <span class="mtt-slots-left"><i class="bi bi-person-check-fill"></i> Còn <?php echo $remain; ?> chỗ nhận khách</span>
                                    </div>
                                    <div class="mtt-card-footer"> 
                                        <a href="detail.php?id=<?php echo $row['TourID']; ?>" class="mtt-btn-detail">Xem chi tiết <i class="bi bi-arrow-right-short fs-5"></i></a>
                                        <a href="detail.php?id=<?php echo $row['TourID']; ?>" class="mtt-btn-book">Đặt Ngay</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $delay++;
                    }
                } else {
                    echo "<div class='col-12 text-center py-5 anim-up'><p class='fs-5 text-muted'>Không tìm thấy chuyến đi nào phù hợp.</p></div>";
                }
                ?>
            </div>

            <!-- PHÂN TRANG -->
            <?php if (isset($total_pages) && $total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-5 pb-5 anim-up">
                    <ul class="pagination justify-content-center">
                        <?php 
                        $qs_array = $_GET; unset($qs_array['page']);
                        $qs = http_build_query($qs_array);
                        $prefix = (!empty($qs) ? "&" . $qs : "") . "#tour-section";
                        if ($page > 1): 
                        ?>
                        <!-- Đã sửa dòng bị đứt gãy -->
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1 . $prefix; ?>"><i class="bi bi-chevron-left"></i> Trước</a></li>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i . $prefix; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1 . $prefix; ?>">Sau <i class="bi bi-chevron-right"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
        <?php
        echo "</div>"; 
    } elseif ($active_tab === 'flight') {
        include 'home_flight.php';
    } elseif ($active_tab === 'hotel') {
        include 'home_hotel.php';
    }
    ?>

    <!-- ĐIỂM ĐẾN NỔI BẬT DƯỚI ĐÁY -->
    <section class="section-padding py-5" style="background: #F8FAFC;">
        <div class="container">
            <div class="section-title-wrap mb-5 anim-up">
                <h2 class="m-0" style="font-size: 30px; font-weight: 800; color: #0F172A; text-transform: uppercase;">ĐIỂM ĐẾN NỔI BẬT</h2>
                <p class="mt-1 mb-0" style="font-size: 15px; color: #64748B;">Khám phá các tọa độ du lịch được yêu thích nhất</p>
            </div>
            
            <div class="row g-3">
                <div class="col-lg-6 col-md-12 anim-left">
                    <a href="index.php?search=Đà+Nẵng#tour-section" class="dest-card large">
                        <img src="https://vietluxtour.com/Upload/images/2024/khamphatrongnuoc/C%E1%BA%A7u%20R%E1%BB%93ng%20%C4%90%C3%A0%20N%E1%BA%B5ng/cau-rong-da-nang%20(6)-min.jpg" alt="Đà Nẵng">
                        <div class="dest-overlay">
                            <h3 class="fw-bold text-white mb-2">Đà Nẵng - Hội An</h3>
                            <span class="dest-btn">Khám phá ngay</span>
                        </div>
                    </a>
                </div>
                <div class="col-lg-6 col-md-12">
                    <div class="row g-3">
                        <div class="col-sm-6 anim-up d1">
                            <a href="index.php?search=Phú+Quốc#tour-section" class="dest-card">
                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSYVVGsw8nr505nz_5p603_AbqX6j6VKiUcXVCP9Uxs_g&s=10" alt="Phú Quốc">
                                <div class="dest-overlay"><h4 class="fw-bold text-white mb-0">Phú Quốc</h4></div>
                            </a>
                        </div>
                        <div class="col-sm-6 anim-up d2">
                            <a href="index.php?search=Đà+Lạt#tour-section" class="dest-card">
                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS7xpQaIHe--cpd-xD8Y5VaUr3MzdUqTKtI7uAdFRaA9Q&s=10" alt="Đà Lạt">
                                <div class="dest-overlay"><h4 class="fw-bold text-white mb-0">Đà Lạt</h4></div>
                            </a>
                        </div>
                        <div class="col-sm-6 anim-up d3">
                            <a href="index.php?search=Hạ+Long#tour-section" class="dest-card">
                                <img src="https://cdn3.ivivu.com/2026/03/du-lich-Ha-Long-tiet-kiem-ivivu.jpg" alt="Hạ Long">
                                <div class="dest-overlay"><h4 class="fw-bold text-white mb-0">Hạ Long</h4></div>
                            </a>
                        </div>
                        <div class="col-sm-6 anim-up d4">
                            <a href="index.php?search=Sapa#tour-section" class="dest-card">
                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQakyM4_0BD3MXfQaM_qrzNZye3srzaKxMtdx7JKaMHsg&s=10" alt="Sapa">
                                <div class="dest-overlay"><h4 class="fw-bold text-white mb-0">Sapa</h4></div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- VÌ SAO CHỌN CHÚNG TÔI -->
    <section class="py-5" style="background: #ffffff;">
        <div class="container">
            <div class="text-center mb-5 anim-up">
                <h2 class="fw-bold" style="color: #0F172A;">Vì sao chọn chúng tôi?</h2>
                <div style="width: 60px; height: 4px; background: #FF862E; margin: 15px auto; border-radius: 2px;"></div>
            </div>
            <div class="row g-4 text-center">
                <div class="col-md-3 anim-up d1">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100" style="border-top: 4px solid #0284C7; border-bottom: 1px solid #f1f5f9; border-left: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9;">
                        <i class="bi bi-shield-check text-primary" style="font-size: 2.5rem;"></i>
                        <h5 class="fw-bold mt-3">An toàn tuyệt đối</h5>
                        <p class="text-muted small">Cam kết bảo hiểm du lịch và chất lượng dịch vụ tốt nhất.</p>
                    </div>
                </div>
                <div class="col-md-3 anim-up d2">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100" style="border-top: 4px solid #FF862E; border-bottom: 1px solid #f1f5f9; border-left: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9;">
                        <i class="bi bi-wallet2 text-warning" style="font-size: 2.5rem;"></i>
                        <h5 class="fw-bold mt-3">Giá cả hợp lý</h5>
                        <p class="text-muted small">Luôn có các chương trình khuyến mãi và giá tốt nhất thị trường.</p>
                    </div>
                </div>
                <div class="col-md-3 anim-up d3">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100" style="border-top: 4px solid #10B981; border-bottom: 1px solid #f1f5f9; border-left: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9;">
                        <i class="bi bi-headset text-success" style="font-size: 2.5rem;"></i>
                        <h5 class="fw-bold mt-3">Hỗ trợ 24/7</h5>
                        <p class="text-muted small">Đội ngũ chăm sóc khách hàng luôn sẵn sàng hỗ trợ bạn.</p>
                    </div>
                </div>
                <div class="col-md-3 anim-up d4">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100" style="border-top: 4px solid #8B5CF6; border-bottom: 1px solid #f1f5f9; border-left: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9;">
                        <i class="bi bi-stars text-danger" style="font-size: 2.5rem;"></i>
                        <h5 class="fw-bold mt-3">Trải nghiệm đỉnh cao</h5>
                        <p class="text-muted small">Lịch trình đa dạng, mới lạ mang lại những kỷ niệm khó quên.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- VỀ CHÚNG TÔI -->
    <section class="py-5" id="about-section" style="background: #F8FAFC; overflow: hidden;">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0 anim-left">
                    <span style="color: #0284C7; font-weight: 600; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1.2px;"><i class="bi bi-award me-1"></i> Về chúng tôi</span>
                    <h2 class="fw-bold mt-2 mb-3" style="color: #0F172A;">Miền Tây Travel</h2>
                    <h4 style="color: #64748B; font-weight: 400;" class="mb-4">Nâng tầm giá trị cuộc sống</h4>
                    <p style="color: #475569; line-height: 1.8;">Miền Tây Travel tự hào là đơn vị kiến tạo những hành trình trọn vẹn, an toàn và giàu trải nghiệm. Chúng tôi không chỉ bán những chuyến đi, chúng tôi trao gửi sự chân thành, văn hóa và lòng hiếu khách đặc trưng của người dân miền Tây đến với mọi du khách.</p>
                </div>
                <div class="col-lg-6 text-center anim-right">
                    <img src="https://dynamic-media-cdn.tripadvisor.com/media/photo-o/17/9c/85/60/caption.jpg?w=1200&h=-1&s=1" class="img-fluid rounded-4 shadow-lg" alt="Về chúng tôi" style="max-height: 350px; object-fit: cover;">
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setPricePreset(range) {
            document.getElementById('price_range_input').value = range;
            const tourForm = document.getElementById('tourSearchForm');
            if (tourForm) { tourForm.action = "index.php#tour-section"; tourForm.submit(); }
        }

        // Xử lý Tabs
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('click', function(e) {
                const tabId = this.getAttribute('id').split('-')[1]; 
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('tab', tabId);
                window.location.href = currentUrl.href;
            });
        });

        // --- HỆ THỐNG SCROLL REVEAL TỰ ĐỘNG ---
        document.addEventListener('DOMContentLoaded', function() {
            const els = document.querySelectorAll('.anim-up, .anim-left, .anim-right');
            const obs = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) { 
                        e.target.classList.add('anim-visible'); 
                        obs.unobserve(e.target); // Đảm bảo hiệu ứng chỉ chạy 1 lần, KHÔNG BAO GIỜ TRẮNG TRANG
                    } 
                });
            }, { threshold: 0.1, rootMargin: '0px 0px 50px 0px' });

            els.forEach(el => obs.observe(el));
            
            // Kích hoạt ngay các phần tử ở trên cùng màn hình
            setTimeout(() => {
                els.forEach(el => {
                    const rect = el.getBoundingClientRect();
                    if(rect.top < window.innerHeight) el.classList.add('anim-visible');
                });
            }, 100);

            // Bật đếm ngược Flash Sale
            const flashCountdownEl = document.getElementById('flash-countdown');
            if (flashCountdownEl && flashCountdownEl.dataset.end) {
                const rawEnd = flashCountdownEl.dataset.end.replace(' ', 'T');
                const flashEnd = new Date(rawEnd).getTime();
                setInterval(function() {
                    const now = new Date().getTime(); const distance = flashEnd - now;
                    if (distance <= 0) return;
                    document.getElementById("cd-days").textContent = Math.floor(distance / (1000 * 60 * 60 * 24));
                    document.getElementById("cd-hours").textContent = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    document.getElementById("cd-mins").textContent = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    document.getElementById("cd-secs").textContent = Math.floor((distance % (1000 * 60)) / 1000);
                }, 1000);
            }
        });
    </script>
</body>
</html>