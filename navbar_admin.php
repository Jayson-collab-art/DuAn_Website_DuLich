<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<nav class="navbar navbar-expand-lg sticky-top py-3" style="background: #00284D !important; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="admin_dashboard.php"
           style="color: #ffffff !important; font-size: 1.1rem; letter-spacing: 0.5px;">
            <i class="bi bi-shield-lock-fill" style="font-size: 1.4rem; color: #E0F2FE;"></i>
            ADMIN PANEL
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar"
                style="border-color: rgba(255,255,255,0.2);">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-1">

                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center" href="index.php"
                       style="color: #CBD5E1 !important; font-weight: 500; border-radius: 6px; transition: all 0.15s ease;">
                        <i class="bi bi-house-door me-2"></i> Xem Website
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center" href="admin_dashboard.php"
                       style="color: #CBD5E1 !important; font-weight: 500; border-radius: 6px; transition: all 0.15s ease;">
                        <i class="bi bi-speedometer2 me-2"></i> Thống kê
                    </a>
                </li>

                <!-- MENU THẢ XUỐNG: ĐƠN HÀNG & ĐIỀU HÀNH -->
                <li class="nav-item dropdown">
                    <a class="nav-link px-3 dropdown-toggle d-flex align-items-center" href="#"
                       id="navbarBookings" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                       style="color: #CBD5E1 !important; font-weight: 500; border-radius: 6px;">
                        <i class="bi bi-cart-check me-2"></i> Quản lý Đơn hàng
                    </a>
                    <ul class="dropdown-menu shadow border-0 mt-2" aria-labelledby="navbarBookings"
                        style="background: #012d54; border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 10px;">
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center" href="admin_bookings.php"
                               style="color: #CBD5E1; border-radius: 6px;">
                                <i class="bi bi-list-ul me-2" style="color: #E0F2FE;"></i> Danh sách Đơn đặt
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center" href="admin_payments.php"
                               style="color: #CBD5E1; border-radius: 6px;">
                                <i class="bi bi-wallet2 me-2" style="color: #10B981;"></i> Lịch sử Thanh toán
                            </a>
                        </li>
                        <li><hr class="dropdown-divider" style="border-color: rgba(255,255,255,0.08);"></li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center" href="admin_tour_operations.php"
                               style="color: #CBD5E1; border-radius: 6px;">
                                <i class="bi bi-bus-front me-2" style="color: #FF862E;"></i> Điều hành theo Tour
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center" href="admin_list_tours.php"
                       style="color: #CBD5E1 !important; font-weight: 500; border-radius: 6px; transition: all 0.15s ease;">
                        <i class="bi bi-geo-alt me-2"></i> Danh Sách Tour
                    </a>
                </li>

                <!-- MENU THẢ XUỐNG: KHUYẾN MÃI -->
                <li class="nav-item dropdown">
                    <a class="nav-link px-3 dropdown-toggle d-flex align-items-center" href="#"
                       id="navbarPromotions" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                       style="color: #ff9800 !important; font-weight: bold; border-radius: 6px; transition: all 0.15s ease;">
                        <i class="bi bi-tags me-2"></i> Quản lý Khuyến mãi
                    </a>
                    <ul class="dropdown-menu shadow border-0 mt-2" aria-labelledby="navbarPromotions"
                        style="background: #012d54; border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 10px;">
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center" href="admin_flashsale.php"
                               style="color: #CBD5E1; border-radius: 6px;">
                                <i class="bi bi-alarm me-2" style="color: #ff9800;"></i> Tour Ưu Đãi
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center" href="admin_coupons.php"
                               style="color: #CBD5E1; border-radius: 6px;">
                                <i class="bi bi-ticket-perforated me-2" style="color: #E0F2FE;"></i> Mã Giảm Giá
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- MENU THẢ XUỐNG: TƯƠNG TÁC -->
                <li class="nav-item dropdown">
                    <a class="nav-link px-3 dropdown-toggle d-flex align-items-center" href="#"
                       id="navbarInteract" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                       style="color: #CBD5E1 !important; font-weight: 500; border-radius: 6px;">
                        <i class="bi bi-chat-right-dots me-2"></i> Tương tác
                    </a>
                    <ul class="dropdown-menu shadow border-0 mt-2" aria-labelledby="navbarInteract"
                        style="background: #012d54; border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 10px;">
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center" href="admin_contacts.php"
                               style="color: #CBD5E1; border-radius: 6px;">
                                <i class="bi bi-inbox me-2" style="color: #E0F2FE;"></i> Hộp thư Liên hệ
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center" href="admin_news.php"
                               style="color: #CBD5E1; border-radius: 6px;">
                                <i class="bi bi-newspaper me-2" style="color: #E0F2FE;"></i> Quản lý Tin tức
                            </a>
                        </li>
                        <li><hr class="dropdown-divider" style="border-color: rgba(255,255,255,0.08);"></li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center" href="admin_reviews.php"
                               style="color: #CBD5E1; border-radius: 6px;">
                                <i class="bi bi-star-half me-2" style="color: #E0F2FE;"></i> Quản lý Bình luận
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    <a class="nav-link px-4 d-flex align-items-center fw-600" href="admin_add_tour.php"
                       style="background: #FF862E; color: #ffffff !important; font-weight: 600; border-radius: 50px; transition: background 0.15s ease;"
                       onmouseover="this.style.background='#E06B16'"
                       onmouseout="this.style.background='#FF862E'">
                        <i class="bi bi-plus-circle me-2"></i> Thêm Tour
                    </a>
                </li>

            </ul>
        </div>
    </div>
</nav>

<style>
    html { overflow-y: scroll; }
    nav[style*="00284D"] .navbar-nav .nav-link:hover {
        background: rgba(255,255,255,0.10) !important;
        color: #ffffff !important;
        transform: none !important;
    }
    nav[style*="00284D"] .dropdown-item:hover {
        background: rgba(2,132,199,0.20) !important;
        color: #ffffff !important;
        padding-left: 1.25rem;
    }
</style>