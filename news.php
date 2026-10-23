<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php'; 

// ── 1. THAM SỐ LỌC & TÌM KIẾM ──
$search = isset($_GET['search']) ? trim(mysqli_real_escape_string($conn, $_GET['search'])) : "";
$category = isset($_GET['category']) ? trim(mysqli_real_escape_string($conn, $_GET['category'])) : "all";

// ── 2. TRUY VẤN DANH MỤC CÓ SẴN TRONG DATABASE ĐỂ HIỂN THỊ CHIP/TAB ──
$sql_cats = "SELECT Category, COUNT(*) as total FROM News WHERE Status = 1 GROUP BY Category ORDER BY total DESC";
$res_cats = mysqli_query($conn, $sql_cats);
$categories_list = [];
$total_all_news = 0;
if ($res_cats) {
    while ($c_row = mysqli_fetch_assoc($res_cats)) {
        $categories_list[] = $c_row;
        $total_all_news += (int)$c_row['total'];
    }
}

// ── 3. PHÂN TRANG (PAGINATION) ──
$limit = 6; // 6 bài viết / trang để lưới 3 cột hiển thị đều đặn 2 hàng
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Điều kiện lọc
$where_clauses = ["Status = 1"];
if (!empty($search)) {
    $where_clauses[] = "(Title LIKE '%$search%' OR Description LIKE '%$search%' OR Category LIKE '%$search%')";
}
if (!empty($category) && $category !== 'all') {
    $where_clauses[] = "Category = '$category'";
}
$where_sql = implode(" AND ", $where_clauses);

// Đếm tổng số bài viết theo điều kiện
$count_query = "SELECT COUNT(*) as total FROM News WHERE $where_sql";
$count_res = mysqli_query($conn, $count_query);
$total_items = ($count_res) ? (int)mysqli_fetch_assoc($count_res)['total'] : 0;
$total_pages = ceil($total_items / $limit);
if ($total_pages > 0 && $page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Lấy danh sách bài viết trang hiện tại
$sql_news = "SELECT * FROM News WHERE $where_sql ORDER BY PublishDate DESC, NewsID DESC LIMIT $limit OFFSET $offset";
$result_news = mysqli_query($conn, $sql_news);

// ── 4. BÀI VIẾT NỔI BẬT (FEATURED HERO CARD) ──
// Lấy 1 bài viết mới nhất có ảnh chất lượng để làm tiêu điểm banner (chỉ hiện khi đang ở trang 1 và không tìm kiếm đặc thù)
$featured_news = null;
if ($page === 1 && empty($search) && ($category === 'all' || empty($category))) {
    $sql_feat = "SELECT * FROM News WHERE Status = 1 AND ImageURL IS NOT NULL AND ImageURL != '' ORDER BY PublishDate DESC, NewsID DESC LIMIT 1";
    $res_feat = mysqli_query($conn, $sql_feat);
    if ($res_feat && mysqli_num_rows($res_feat) > 0) {
        $featured_news = mysqli_fetch_assoc($res_feat);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin Tức & Cẩm Nang Du Lịch - MIỀNTÂY TRAVEL</title>
    <meta name="description" content="Khám phá những tin tức du lịch miền Tây mới nhất, cẩm nang trải nghiệm sông nước, lễ hội và ẩm thực đặc sản hấp dẫn.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Lexend:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Main Style -->
    <link rel="stylesheet" href="style.css">

    <style>
        /* ═══════════════════════════════════════════════════════════════════
           NEWS PAGE ADVANCED STYLING (Scope: .news-page-wrapper)
           ═══════════════════════════════════════════════════════════════════ */
        .news-page-wrapper {
            background-color: var(--bg-main, #F8FAFC);
            min-height: 80vh;
            color: var(--text-body, #475569);
            overflow-x: clip;
        }

        /* ── HERO BANNER ── */
        .news-hero-section {
            position: relative;
            background: linear-gradient(135deg, #001B34 0%, #00284D 50%, #004071 100%);
            padding: 70px 0 65px;
            overflow: hidden;
            color: #FFFFFF;
        }

        /* Lớp nền hoa văn / ánh sáng nước tinh tế */
        .news-hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(2, 132, 199, 0.25) 0%, rgba(0, 40, 77, 0) 70%);
            pointer-events: none;
            filter: blur(40px);
        }

        .news-hero-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 134, 46, 0.15) 0%, rgba(0, 40, 77, 0) 70%);
            pointer-events: none;
            filter: blur(35px);
        }

        .news-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #FED7AA;
            backdrop-filter: blur(8px);
            margin-bottom: 18px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .news-hero-title {
            font-family: 'Lexend', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.25;
            color: #FFFFFF;
            margin-bottom: 16px;
        }

        .news-hero-title span {
            color: #FF862E;
        }

        .news-hero-desc {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.85);
            max-width: 640px;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        /* ── FEATURED NEWS CARD IN HERO ── */
        .hero-featured-card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(12px);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease, border-color 0.3s ease;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.25);
        }

        .hero-featured-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 134, 46, 0.6);
            box-shadow: 0 22px 50px rgba(0, 0, 0, 0.35);
            color: inherit;
        }

        .hero-featured-img-wrap {
            position: relative;
            height: 230px;
            overflow: hidden;
        }

        .hero-featured-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .hero-featured-card:hover .hero-featured-img {
            transform: scale(1.06);
        }

        .hero-featured-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: #FF862E;
            color: #FFFFFF;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(255, 134, 46, 0.4);
            z-index: 2;
        }

        .hero-featured-content {
            padding: 20px 24px;
            background: linear-gradient(180deg, rgba(0, 40, 77, 0.85) 0%, rgba(0, 27, 52, 0.95) 100%);
        }

        .hero-featured-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.65);
            margin-bottom: 10px;
        }

        .hero-featured-meta i {
            color: #FF862E;
        }

        .hero-featured-title {
            font-family: 'Lexend', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: #FFFFFF;
            line-height: 1.4;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.2s ease;
        }

        .hero-featured-card:hover .hero-featured-title {
            color: #FED7AA;
        }

        .hero-featured-desc {
            font-size: 0.88rem;
            color: rgba(255, 255, 255, 0.75);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 14px;
        }

        .hero-featured-link {
            font-size: 0.85rem;
            font-weight: 600;
            color: #38BDF8;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.2s ease, color 0.2s ease;
        }

        .hero-featured-card:hover .hero-featured-link {
            gap: 10px;
            color: #FF862E;
        }

        /* ── SEARCH & FILTER CONTROLS BAR ── */
        .news-controls-bar {
            background: #FFFFFF;
            border-radius: 18px;
            padding: 16px 22px;
            box-shadow: 0 10px 30px rgba(0, 40, 77, 0.06);
            border: 1px solid #E2E8F0;
            margin-top: -35px;
            position: relative;
            z-index: 10;
        }

        .news-search-form {
            position: relative;
            width: 100%;
        }

        .news-search-input {
            width: 100%;
            height: 48px;
            padding: 10px 48px 10px 42px;
            border-radius: 50px;
            border: 1.5px solid #E2E8F0;
            background: #F8FAFC;
            font-size: 0.92rem;
            color: var(--text-heading, #0F172A);
            transition: all 0.25s ease;
        }

        .news-search-input:focus {
            outline: none;
            border-color: #00558F;
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(0, 85, 143, 0.12);
        }

        .news-search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 1.05rem;
            pointer-events: none;
        }

        .news-search-submit {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: #00558F;
            color: #FFFFFF;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .news-search-submit:hover {
            background: #003F6B;
            transform: translateY(-50%) scale(1.05);
        }

        /* Filter Chips */
        .news-chips-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .news-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            background: #F1F5F9;
            color: #475569;
            border: 1px solid transparent;
            transition: all 0.22s ease;
            white-space: nowrap;
        }

        .news-chip .chip-count {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 50px;
            font-size: 0.72rem;
            background: rgba(0, 0, 0, 0.06);
            color: inherit;
        }

        .news-chip:hover {
            background: #E2E8F0;
            color: #00558F;
            transform: translateY(-1px);
        }

        .news-chip.active {
            background: #00558F;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(0, 85, 143, 0.25);
            border-color: #00558F;
        }

        .news-chip.active .chip-count {
            background: rgba(255, 255, 255, 0.25);
            color: #FFFFFF;
        }

        /* ── SECTION HEADER & STATS ── */
        .news-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 40px;
            margin-bottom: 25px;
        }

        .news-section-title {
            font-family: 'Lexend', sans-serif;
            font-size: 1.45rem;
            font-weight: 700;
            color: #00284D;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .news-section-title i {
            color: #FF862E;
        }

        .news-count-badge {
            font-size: 0.85rem;
            color: #64748B;
            background: #FFFFFF;
            padding: 6px 14px;
            border-radius: 50px;
            border: 1px solid #E2E8F0;
            font-weight: 500;
        }

        /* ── NEWS CARD (LƯỚI 3 CỘT CAO CẤP) ── */
        .news-grid-card {
            background: #FFFFFF;
            border-radius: 18px;
            border: 1px solid #E8EEF5;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease, border-color 0.3s ease;
            box-shadow: 0 4px 14px rgba(0, 40, 77, 0.04);
            position: relative;
        }

        .news-grid-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 36px rgba(0, 40, 77, 0.1);
            border-color: #CBD5E1;
        }

        .news-card-img-box {
            position: relative;
            width: 100%;
            height: 210px;
            overflow: hidden;
            background: #E2E8F0;
        }

        .news-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            display: block;
        }

        .news-grid-card:hover .news-card-img {
            transform: scale(1.08);
        }

        /* Overlay nhẹ trên ảnh */
        .news-card-img-box::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0) 60%, rgba(0,0,0,0.3) 100%);
            pointer-events: none;
        }

        /* Badge danh mục */
        .news-card-cat-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: #00558F;
            color: #FFFFFF;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(0, 40, 77, 0.3);
            backdrop-filter: blur(4px);
        }

        .news-card-cat-badge.cat-cam-nang { background: #00558F; }
        .news-card-cat-badge.cat-kinh-nghiem { background: #0284C7; }
        .news-card-cat-badge.cat-le-hoi { background: #EA580C; }
        .news-card-cat-badge.cat-am-thuc { background: #059669; }

        /* Nút icon chia sẻ hoặc đọc nhanh trên ảnh */
        .news-card-source-tag {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0, 0, 0, 0.65);
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.68rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            z-index: 2;
            backdrop-filter: blur(4px);
        }

        .news-card-body {
            padding: 22px 22px 18px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .news-card-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.8rem;
            color: #94A3B8;
            margin-bottom: 10px;
        }

        .news-card-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .news-card-title {
            font-family: 'Lexend', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #00284D;
            line-height: 1.45;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .news-card-title:hover {
            color: #FF862E;
        }

        .news-card-desc {
            font-size: 0.88rem;
            color: #64748B;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
        }

        .news-card-footer {
            border-top: 1px solid #F1F5F9;
            padding-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .news-btn-read {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #00558F;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .news-btn-read i {
            transition: transform 0.2s ease;
        }

        .news-grid-card:hover .news-btn-read {
            color: #FF862E;
        }

        .news-grid-card:hover .news-btn-read i {
            transform: translateX(4px);
        }

        .news-source-label {
            font-size: 0.76rem;
            color: #94A3B8;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* ── PAGINATION (PHÂN TRANG CHUYÊN NGHIỆP) ── */
        .news-pagination-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 50px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .news-page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            height: 44px;
            padding: 0 14px;
            border-radius: 12px;
            background: #FFFFFF;
            border: 1.5px solid #E2E8F0;
            color: #475569;
            font-size: 0.92rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        }

        .news-page-btn:hover:not(.disabled) {
            background: #00558F;
            color: #FFFFFF;
            border-color: #00558F;
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0, 85, 143, 0.2);
        }

        .news-page-btn.active {
            background: #FF862E;
            color: #FFFFFF;
            border-color: #FF862E;
            box-shadow: 0 6px 14px rgba(255, 134, 46, 0.3);
            pointer-events: none;
        }

        .news-page-btn.disabled {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
            background: #F1F5F9;
        }

        /* ── EMPTY STATE (TRẠNG THÁI RỖNG) ── */
        .news-empty-state {
            background: #FFFFFF;
            border-radius: 20px;
            border: 1.5px dashed #CBD5E1;
            padding: 60px 30px;
            text-align: center;
            margin: 30px 0;
        }

        .news-empty-icon {
            width: 80px;
            height: 80px;
            background: #F1F5F9;
            color: #94A3B8;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin-bottom: 20px;
        }

        .news-empty-title {
            font-family: 'Lexend', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: #00284D;
            margin-bottom: 8px;
        }

        .news-empty-desc {
            font-size: 0.95rem;
            color: #64748B;
            max-width: 450px;
            margin: 0 auto 20px;
        }

        .news-btn-reset {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 50px;
            background: #00558F;
            color: #FFFFFF;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .news-btn-reset:hover {
            background: #003F6B;
            color: #FFFFFF;
            transform: translateY(-2px);
        }

        /* ── NEWSLETTER SUBSCRIPTION BOX TRONG TRANG TIN ── */
        .news-subscribe-card {
            background: linear-gradient(135deg, #00284D 0%, #00558F 100%);
            border-radius: 22px;
            padding: 40px 35px;
            color: #FFFFFF;
            margin: 60px 0 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(0, 40, 77, 0.12);
        }

        .news-subscribe-card::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 134, 46, 0.18);
            pointer-events: none;
        }

        /* ── ANIMATIONS ── */
        .news-fade-in {
            opacity: 0;
            transform: translateY(22px);
            transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .news-fade-in.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Stagger card delays */
        .news-delay-1 { transition-delay: 0.08s; }
        .news-delay-2 { transition-delay: 0.16s; }
        .news-delay-3 { transition-delay: 0.24s; }
        .news-delay-4 { transition-delay: 0.32s; }
        .news-delay-5 { transition-delay: 0.40s; }
        .news-delay-6 { transition-delay: 0.48s; }

        @media (prefers-reduced-motion: reduce) {
            .news-fade-in {
                opacity: 1 !important;
                transform: none !important;
                transition: none !important;
            }
            .news-grid-card, .hero-featured-card, .news-card-img, .hero-featured-img {
                transition: none !important;
                transform: none !important;
            }
        }

        /* Focus accessibility */
        .news-search-input:focus-visible,
        .news-chip:focus-visible,
        .news-page-btn:focus-visible,
        .news-card-title:focus-visible,
        .news-btn-read:focus-visible {
            outline: 2px solid #FF862E;
            outline-offset: 2px;
        }

        @media (max-width: 991.98px) {
            .news-hero-section {
                padding: 50px 0 60px;
            }
            .news-hero-title {
                font-size: 2rem;
            }
            .news-controls-bar {
                margin-top: -25px;
            }
        }

        @media (max-width: 767.98px) {
            .news-hero-title {
                font-size: 1.7rem;
            }
            .news-card-img-box {
                height: 190px;
            }
            .news-controls-bar {
                padding: 14px 16px;
            }
            .news-chips-wrap {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 6px;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body>

    <!-- ══════════════════════ THANH ĐIỀU HƯỚNG NAVBAR ══════════════════════ -->
    <?php include 'navbar.php'; ?>

    <div class="news-page-wrapper">

        <!-- ══════════════════════ 1. HERO BANNER SECTION ══════════════════════ -->
        <section class="news-hero-section" aria-label="Banner tin tức du lịch">
            <div class="container">
                <div class="row align-items-center g-4">
                    <div class="col-lg-<?= $featured_news ? '7' : '12' ?> news-fade-in is-visible">
                        <div class="news-hero-badge">
                            <i class="bi bi-compass"></i> Khám phá & Trải nghiệm
                        </div>
                        <h1 class="news-hero-title">
                            Tin Tức & Cẩm Nang<br><span>Miệt Vườn Sông Nước</span>
                        </h1>
                        <p class="news-hero-desc">
                            Cập nhật liên tục những địa điểm check-in hấp dẫn, kinh nghiệm du lịch thực tế, lịch trình khám phá văn hóa miền Tây và các lễ hội truyền thống đặc sắc.
                        </p>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <a href="#newsListSection" class="btn btn-warning fw-bold px-4 py-2 rounded-pill shadow-sm text-white" style="background:#FF862E; border-color:#FF862E;">
                                <i class="bi bi-arrow-down-circle me-1"></i> Đọc tin mới nhất
                            </a>
                            <span class="text-white-50 small">
                                <i class="bi bi-shield-check text-warning me-1"></i> Thông tin chuẩn xác & chọn lọc
                            </span>
                        </div>
                    </div>

                    <?php if ($featured_news): ?>
                    <!-- CARD BÀI VIẾT NỔI BẬT ĐƯỢC CHỌN TỪ DATABASE -->
                    <div class="col-lg-5 news-fade-in is-visible news-delay-1">
                        <a href="<?= htmlspecialchars($featured_news['SourceURL']) ?>" target="_blank" rel="noopener noreferrer" class="hero-featured-card" title="Xem chi tiết bài viết nổi bật">
                            <div class="hero-featured-img-wrap">
                                <span class="hero-featured-badge">
                                    <i class="bi bi-star-fill me-1"></i> Tiêu điểm
                                </span>
                                <img 
                                    src="<?= htmlspecialchars($featured_news['ImageURL']) ?>" 
                                    alt="<?= htmlspecialchars($featured_news['Title']) ?>" 
                                    class="hero-featured-img"
                                    loading="lazy"
                                    onerror="this.src='https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=800';"
                                >
                            </div>
                            <div class="hero-featured-content">
                                <div class="hero-featured-meta">
                                    <span><i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($featured_news['PublishDate'])) ?></span>
                                    <span><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($featured_news['Category']) ?></span>
                                </div>
                                <h3 class="hero-featured-title">
                                    <?= htmlspecialchars($featured_news['Title']) ?>
                                </h3>
                                <p class="hero-featured-desc">
                                    <?= htmlspecialchars($featured_news['Description']) ?>
                                </p>
                                <div class="hero-featured-link">
                                    <span>Đọc toàn bộ bài viết</span>
                                    <i class="bi bi-arrow-up-right"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ══════════════════════ 2. CONTROLS BAR: TÌM KIẾM & DANH MỤC ══════════════════════ -->
        <div class="container" id="newsListSection">
            <div class="news-controls-bar">
                <div class="row g-3 align-items-center">
                    
                    <!-- Form Tìm kiếm -->
                    <div class="col-lg-5 col-md-6">
                        <form action="news.php#newsListSection" method="GET" class="news-search-form" role="search">
                            <i class="bi bi-search news-search-icon"></i>
                            <input 
                                type="text" 
                                name="search" 
                                class="news-search-input" 
                                placeholder="Tìm kiếm bài viết, cẩm nang, ẩm thực..." 
                                value="<?= htmlspecialchars($search) ?>"
                                aria-label="Tìm kiếm bài viết"
                            >
                            <?php if (!empty($category) && $category !== 'all'): ?>
                                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                            <?php endif; ?>
                            <button type="submit" class="news-search-submit" title="Tìm kiếm">
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Danh mục lọc dạng Chips -->
                    <div class="col-lg-7 col-md-6">
                        <div class="news-chips-wrap">
                            <!-- Chip Tất cả -->
                            <?php 
                                $all_url = "news.php?" . http_build_query(array_merge($_GET, ['category' => 'all', 'page' => 1])) . "#newsListSection";
                            ?>
                            <a href="<?= $all_url ?>" class="news-chip <?= ($category === 'all' || empty($category)) ? 'active' : '' ?>">
                                <i class="bi bi-grid-fill"></i> Tất cả
                                <span class="chip-count"><?= $total_all_news ?></span>
                            </a>

                            <!-- Các chuyên mục từ DB -->
                            <?php foreach ($categories_list as $cat_item): 
                                $cat_name = $cat_item['Category'];
                                $cat_count = $cat_item['total'];
                                $is_active = ($category === $cat_name);
                                $cat_url = "news.php?" . http_build_query(array_merge($_GET, ['category' => $cat_name, 'page' => 1])) . "#newsListSection";
                            ?>
                            <a href="<?= $cat_url ?>" class="news-chip <?= $is_active ? 'active' : '' ?>">
                                <?= htmlspecialchars($cat_name) ?>
                                <span class="chip-count"><?= $cat_count ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Header tiêu đề khu vực danh sách tin tức -->
            <div class="news-section-header">
                <div>
                    <h2 class="news-section-title">
                        <i class="bi bi-newspaper"></i> 
                        <?php 
                        if (!empty($search)) {
                            echo "Kết quả tìm kiếm cho: \"" . htmlspecialchars($search) . "\"";
                        } elseif (!empty($category) && $category !== 'all') {
                            echo "Chuyên mục: " . htmlspecialchars($category);
                        } else {
                            echo "Danh Sách Bài Viết Du Lịch Mới Nhất";
                        }
                        ?>
                    </h2>
                </div>
                <div class="news-count-badge">
                    Hiển thị <strong><?= mysqli_num_rows($result_news) ?></strong> / <strong><?= $total_items ?></strong> bài viết
                </div>
            </div>

            <!-- ══════════════════════ 3. LƯỚI CARD BÀI VIẾT (3 CỘT DESKTOP) ══════════════════════ -->
            <div class="row g-4" id="newsGrid">
                <?php 
                if ($result_news && mysqli_num_rows($result_news) > 0): 
                    $card_idx = 0;
                    while ($news_row = mysqli_fetch_assoc($result_news)):
                        $card_idx++;
                        $delay_class = 'news-delay-' . (($card_idx % 6) + 1);

                        // Phân loại CSS class cho từng category
                        $cat_slug = 'cat-cam-nang';
                        if ($news_row['Category'] === 'Kinh nghiệm') $cat_slug = 'cat-kinh-nghiem';
                        elseif ($news_row['Category'] === 'Lễ hội') $cat_slug = 'cat-le-hoi';
                        elseif ($news_row['Category'] === 'Ẩm thực') $cat_slug = 'cat-am-thuc';

                        // Xử lý link đích
                        $detail_url = !empty($news_row['SourceURL']) ? htmlspecialchars($news_row['SourceURL']) : '#';
                        $pub_date = date('d/m/Y', strtotime($news_row['PublishDate']));

                        // Fallback image phù hợp với từng danh mục nếu ảnh trống hoặc lỗi
                        $fallback_img = 'https://images.unsplash.com/photo-1528127269322-539801943592?q=80&w=800';
                        $img_src = !empty($news_row['ImageURL']) ? htmlspecialchars($news_row['ImageURL']) : $fallback_img;
                ?>
                <div class="col-lg-4 col-md-6 news-fade-in <?= $delay_class ?>" data-card-item>
                    <article class="news-grid-card">
                        
                        <!-- Box ảnh đại diện -->
                        <div class="news-card-img-box">
                            <span class="news-card-cat-badge <?= $cat_slug ?>">
                                <?= htmlspecialchars($news_row['Category']) ?>
                            </span>

                            <img 
                                src="<?= $img_src ?>" 
                                alt="<?= htmlspecialchars($news_row['Title']) ?>" 
                                class="news-card-img"
                                loading="lazy"
                                onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1528127269322-539801943592?q=80&w=800';"
                            >

                            <span class="news-card-source-tag">
                                <i class="bi bi-link-45deg"></i> Bài viết chi tiết
                            </span>
                        </div>

                        <!-- Thân card thông tin -->
                        <div class="news-card-body">
                            
                            <div class="news-card-meta">
                                <span><i class="bi bi-calendar3 text-primary"></i> <?= $pub_date ?></span>
                                <span><i class="bi bi-geo-alt text-warning"></i> Miền Tây</span>
                            </div>

                            <a href="<?= $detail_url ?>" target="_blank" rel="noopener noreferrer" class="news-card-title" title="<?= htmlspecialchars($news_row['Title']) ?>">
                                <?= htmlspecialchars($news_row['Title']) ?>
                            </a>

                            <p class="news-card-desc">
                                <?= !empty($news_row['Description']) ? htmlspecialchars($news_row['Description']) : 'Khám phá những trải nghiệm du lịch sông nước tuyệt vời, các địa danh nổi bật cùng ẩm thực bản địa độc đáo...' ?>
                            </p>

                            <div class="news-card-footer">
                                <a href="<?= $detail_url ?>" target="_blank" rel="noopener noreferrer" class="news-btn-read" aria-label="Đọc tiếp bài viết <?= htmlspecialchars($news_row['Title']) ?>">
                                    ĐỌC TIẾP <i class="bi bi-arrow-right"></i>
                                </a>
                                <span class="news-source-label">
                                    <i class="bi bi-eye"></i> Xem ngay
                                </span>
                            </div>

                        </div>

                    </article>
                </div>
                <?php 
                    endwhile; 
                else: 
                ?>
                <!-- Trạng thái trống khi không có bài viết phù hợp -->
                <div class="col-12 news-fade-in is-visible">
                    <div class="news-empty-state">
                        <div class="news-empty-icon">
                            <i class="bi bi-journal-x"></i>
                        </div>
                        <h3 class="news-empty-title">Không tìm thấy bài viết nào</h3>
                        <p class="news-empty-desc">
                            Không có kết quả nào phù hợp với bộ lọc hoặc từ khóa tìm kiếm của bạn. Hãy thử tìm kiếm bằng từ khóa khác hoặc quay lại danh sách đầy đủ.
                        </p>
                        <a href="news.php#newsListSection" class="news-btn-reset">
                            <i class="bi bi-arrow-counterclockwise"></i> Xem tất cả tin tức
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ══════════════════════ 4. PHÂN TRANG (PAGINATION) ══════════════════════ -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Phân trang tin tức" class="news-fade-in is-visible">
                <div class="news-pagination-wrap">
                    
                    <!-- Nút Trang trước -->
                    <?php 
                        $prev_page = max(1, $page - 1);
                        $prev_url = "news.php?" . http_build_query(array_merge($_GET, ['page' => $prev_page])) . "#newsListSection";
                    ?>
                    <a href="<?= $prev_url ?>" class="news-page-btn <?= ($page <= 1) ? 'disabled' : '' ?>" aria-label="Trang trước">
                        <i class="bi bi-chevron-left"></i>
                    </a>

                    <!-- Danh sách các trang -->
                    <?php for ($p = 1; $p <= $total_pages; $p++): 
                        $p_url = "news.php?" . http_build_query(array_merge($_GET, ['page' => $p])) . "#newsListSection";
                        $is_cur = ($p === $page);
                    ?>
                    <a href="<?= $p_url ?>" class="news-page-btn <?= $is_cur ? 'active' : '' ?>" aria-current="<?= $is_cur ? 'page' : 'false' ?>">
                        <?= $p ?>
                    </a>
                    <?php endfor; ?>

                    <!-- Nút Trang sau -->
                    <?php 
                        $next_page = min($total_pages, $page + 1);
                        $next_url = "news.php?" . http_build_query(array_merge($_GET, ['page' => $next_page])) . "#newsListSection";
                    ?>
                    <a href="<?= $next_url ?>" class="news-page-btn <?= ($page >= $total_pages) ? 'disabled' : '' ?>" aria-label="Trang kế tiếp">
                        <i class="bi bi-chevron-right"></i>
                    </a>

                </div>
            </nav>
            <?php endif; ?>

            <!-- ══════════════════════ 5. KHỐI NHẬN BẢN TIN KHUYẾN MÃI DU LỊCH ══════════════════════ -->
            <div class="news-subscribe-card news-fade-in">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2">BẢN TIN MIỀN TÂY</span>
                        <h3 class="fw-bold text-white mb-2" style="font-family:'Lexend',sans-serif;">Đăng Ký Nhận Cẩm Nang & Ưu Đãi Mới Nhất</h3>
                        <p class="text-white-50 mb-0 small">
                            Nhận ngay thông báo về các bài viết cẩm nang du lịch mới nhất, mã giảm giá tour độc quyền và cẩm nang khám phá miền sông nước gửi trực tiếp về email của bạn.
                        </p>
                    </div>
                    <div class="col-lg-5">
                        <div class="input-group">
                            <input type="email" class="form-control py-3 px-3 rounded-start-pill border-0" placeholder="Nhập địa chỉ email của bạn..." style="font-size:0.9rem;">
                            <button class="btn btn-warning fw-bold px-4 rounded-end-pill text-white" type="button" style="background:#FF862E;border-color:#FF862E;">
                                ĐĂNG KÝ
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.container -->

    </div><!-- /.news-page-wrapper -->

    <?php include 'footer.php'; ?>


    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Page Interaction Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ── INTERSECTION OBSERVER CHO HIỆU ỨNG CUỘN MƯỢT MÀ ──
            const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            
            if (!prefersReduced && 'IntersectionObserver' in window) {
                const fadeElements = document.querySelectorAll('.news-fade-in');
                const observerOptions = {
                    root: null,
                    rootMargin: '0px 0px -40px 0px',
                    threshold: 0.1
                };

                const newsObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, observerOptions);

                fadeElements.forEach(function(el) {
                    newsObserver.observe(el);
                });
            } else {
                // Nếu người dùng bật reduce-motion hoặc trình duyệt cũ, hiển thị luôn không chờ
                document.querySelectorAll('.news-fade-in').forEach(function(el) {
                    el.classList.add('is-visible');
                });
            }

        });
    </script>
</body>
</html>