<?php
$displayName = $_SESSION['user_name'] ?? $_SESSION['user'] ?? 'Bạn';

// ── XÁC ĐỊNH TRANG HIỆN TẠI ĐỂ TỰ ĐỘNG GÁN TRẠNG THÁI ACTIVE ──
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

$isHome = ($currentPage === 'index.php' || $currentPage === '' || empty($currentPage));
if ($currentPage === 'detail.php') {
    $isHome = true;
}

$isAbout = ($currentPage === 'about.php');
$isNews = ($currentPage === 'news.php');
$isContact = ($currentPage === 'contact.php');
$isBookings = in_array($currentPage, ['my_bookings.php', 'edit_booking.php', 'payment_create.php']);
$isLogin = ($currentPage === 'login_user.php');
$isRegister = ($currentPage === 'register.php');
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Lexend:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">

<nav class="navbar navbar-expand-lg sticky-top mtt-navbar" aria-label="Thanh điều hướng chính">
    <div class="container">
        <a class="navbar-brand" href="index.php" title="Miền Tây Travel - Trang chủ">
            <img src="https://cdn-icons-png.flaticon.com/128/5968/5968879.png" width="44" height="44" alt="Miền Tây Travel Logo" class="brand-logo">
            <span class="brand-text">MIỀNTÂY <span class="brand-accent">TRAVEL</span></span>
        </a>

        <button class="navbar-toggler mtt-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Mở menu điều hướng">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <div class="navbar-nav ms-auto align-items-center mtt-nav-list">
                <a class="nav-link mtt-nav-link <?= $isHome ? 'active' : '' ?>" href="index.php" <?= $isHome ? 'aria-current="page"' : '' ?>>Trang Chủ</a>
                <a class="nav-link mtt-nav-link <?= $isAbout ? 'active' : '' ?>" href="about.php" <?= $isAbout ? 'aria-current="page"' : '' ?>>Giới Thiệu</a>
                <a class="nav-link mtt-nav-link <?= $isNews ? 'active' : '' ?>" href="news.php" <?= $isNews ? 'aria-current="page"' : '' ?>>Tin Tức</a>
                <a class="nav-link mtt-nav-link <?= $isContact ? 'active' : '' ?>" href="contact.php" <?= $isContact ? 'aria-current="page"' : '' ?>>Liên Hệ</a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link mtt-nav-link mtt-bookings-link <?= $isBookings ? 'active' : '' ?>" href="my_bookings.php" <?= $isBookings ? 'aria-current="page"' : '' ?>>
                        <i class="bi <?= $isBookings ? 'bi-bag-check-fill' : 'bi-bag-check' ?> mtt-icon"></i> <span>Đơn hàng</span>
                    </a>

                    <!-- Đã thêm $_SESSION['user'] vào $displayName ở dòng đầu file -->
                    <div class="mtt-user-badge" title="Tài khoản: <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-person-circle text-primary"></i>
                        <span class="mtt-user-name">Chào, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <a class="nav-link mtt-logout-link" href="logout.php" title="Đăng xuất khỏi tài khoản">
                        <i class="bi bi-box-arrow-right"></i> <span>Thoát</span>
                    </a>
                <?php else: ?>
                    <a class="nav-link mtt-nav-link mtt-login-link <?= $isLogin ? 'active' : '' ?>" href="login_user.php" <?= $isLogin ? 'aria-current="page"' : '' ?>>
                        <i class="bi bi-box-arrow-in-right me-1"></i> <span>Đăng nhập</span>
                    </a>
                    <a class="mtt-btn-register ms-lg-2" href="register.php">
                        <i class="bi bi-person-plus-fill me-1"></i> <span>Đăng ký</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<style>
/* CSS Menu của bạn giữ nguyên */
.mtt-navbar { background: rgba(255, 255, 255, 0.98) !important; backdrop-filter: blur(14px); border-bottom: 1px solid rgba(226, 232, 240, 0.9) !important; box-shadow: 0 4px 20px rgba(0, 40, 77, 0.05) !important; padding: 0.75rem 0 !important; transition: all 0.25s ease; z-index: 1030; }
.mtt-navbar .navbar-brand { color: #00284D !important; font-family: 'Lexend', sans-serif !important; font-weight: 800 !important; font-size: 1.45rem !important; display: inline-flex; align-items: center; gap: 12px; padding: 0; text-decoration: none; transition: transform 0.25s ease, opacity 0.2s ease; }
.mtt-navbar .navbar-brand:hover { transform: translateY(-1px); opacity: 0.96; }
.mtt-navbar .brand-logo { width: 44px; height: 44px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0, 85, 143, 0.22)); transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1); }
.mtt-navbar .navbar-brand:hover .brand-logo { transform: rotate(-6deg) scale(1.08); }
.mtt-navbar .brand-text { color: #00284D; font-weight: 800; }
.mtt-navbar .brand-accent { color: #0284C7; font-weight: 800; }
.mtt-navbar .mtt-nav-list { gap: 8px; }
.mtt-navbar .mtt-nav-link { position: relative; color: #1E293B !important; font-family: 'Inter', sans-serif; font-weight: 600 !important; font-size: 1.0625rem !important; padding: 0.65rem 1.15rem !important; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; line-height: 1.45; text-decoration: none; transition: color 0.22s, background-color 0.22s, transform 0.15s, box-shadow 0.2s; }
.mtt-navbar .mtt-nav-link::after { content: ''; position: absolute; bottom: 3px; left: 50%; width: 0; height: 3px; background: linear-gradient(90deg, #0284C7, #00558F); border-radius: 4px; transition: width 0.22s, left 0.22s, background-color 0.22s; pointer-events: none; }
.mtt-navbar .mtt-nav-link:hover { color: #00558F !important; background-color: #F0F9FF !important; }
.mtt-navbar .mtt-nav-link:hover::after { width: calc(100% - 24px); left: 12px; }
.mtt-navbar .mtt-nav-link:hover .mtt-icon { color: #0284C7 !important; transform: scale(1.1); }
.mtt-navbar .mtt-nav-link.active { color: #004373 !important; font-weight: 700 !important; background-color: #E0F2FE !important; box-shadow: inset 0 0 0 1px rgba(2, 132, 199, 0.22); }
.mtt-navbar .mtt-nav-link.active::after { width: calc(100% - 20px); left: 10px; background: #004373; }
.mtt-navbar .mtt-nav-link.active .mtt-icon { color: #004373 !important; }
.mtt-navbar .mtt-nav-link:active { transform: scale(0.97); }
.mtt-navbar .mtt-icon { color: #0284C7; font-size: 1.1rem; line-height: 1; transition: color 0.25s ease, transform 0.25s ease; }
.mtt-navbar .mtt-login-link { color: #00558F !important; font-weight: 600 !important; font-size: 1.0625rem !important; border: 1.5px solid rgba(0, 85, 143, 0.24) !important; border-radius: 50px !important; padding: 0.55rem 1.3rem !important; background-color: transparent !important; }
.mtt-navbar .mtt-login-link::after { display: none !important; }
.mtt-navbar .mtt-login-link:hover { background-color: #E0F2FE !important; border-color: #00558F !important; color: #003F6B !important; transform: translateY(-1px); box-shadow: 0 3px 10px rgba(0, 85, 143, 0.12); }
.mtt-navbar .mtt-login-link.active { background-color: #E0F2FE !important; border-color: #00558F !important; color: #003F6B !important; font-weight: 700 !important; }
.mtt-navbar .mtt-btn-register { background: linear-gradient(135deg, #FF862E 0%, #EA580C 100%) !important; color: #ffffff !important; font-family: 'Inter', sans-serif; font-weight: 700 !important; font-size: 1.0625rem !important; padding: 0.58rem 1.55rem; border-radius: 50px; box-shadow: 0 4px 15px rgba(255, 134, 46, 0.35); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.mtt-navbar .mtt-btn-register:hover { background: linear-gradient(135deg, #F97316 0%, #C2410C 100%) !important; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 134, 46, 0.45); color: #ffffff !important; }
.mtt-navbar .mtt-btn-register:active { transform: scale(0.97); }
.mtt-navbar .mtt-user-badge { background: #F8FAFC; border: 1px solid #E2E8F0; color: #00558F; font-family: 'Inter', sans-serif; font-weight: 600; font-size: 0.95rem; padding: 0.45rem 1rem; border-radius: 50px; display: inline-flex; align-items: center; gap: 7px; cursor: default; white-space: nowrap; margin: 0 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03); }
.mtt-navbar .mtt-user-name { max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mtt-navbar .mtt-logout-link { color: #64748B !important; font-family: 'Inter', sans-serif; font-size: 0.95rem !important; font-weight: 500; padding: 0.5rem 0.85rem !important; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; transition: all 0.2s ease; }
.mtt-navbar .mtt-logout-link:hover { color: #E11D48 !important; background-color: #FFF1F2 !important; }
.mtt-navbar .mtt-toggler { border: 1px solid #E2E8F0; border-radius: 10px; padding: 0.45rem 0.75rem; }
@media (max-width: 991.98px) {
    .mtt-navbar .navbar-collapse { background: #ffffff; border: 1px solid #E2E8F0; border-radius: 16px; padding: 16px; margin-top: 12px; box-shadow: 0 10px 28px rgba(0, 40, 77, 0.08); }
    .mtt-navbar .mtt-nav-list { align-items: stretch !important; gap: 6px; }
    .mtt-navbar .mtt-nav-link { width: 100%; padding: 0.72rem 1rem !important; font-size: 1.05rem !important; justify-content: flex-start; border-radius: 10px; min-height: 44px; }
    .mtt-navbar .mtt-nav-link::after { display: none; }
    .mtt-navbar .mtt-nav-link.active { border-left: 4px solid #0284C7; border-radius: 4px 10px 10px 4px; }
    .mtt-navbar .mtt-user-badge { width: 100%; margin: 8px 0 4px; padding: 0.65rem 1rem; border-radius: 10px; }
    .mtt-navbar .mtt-logout-link { width: 100%; padding: 0.65rem 1rem !important; }
    .mtt-navbar .mtt-btn-register { width: 100%; margin-top: 8px; justify-content: center; }
}
</style>