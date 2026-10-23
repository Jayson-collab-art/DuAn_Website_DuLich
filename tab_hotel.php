<?php
// Lấy dữ liệu từ form tìm kiếm khách sạn
$hotel_destination = $_GET['hotel_destination'] ?? 'Tiền Giang';
$hotel_checkin = $_GET['hotel_checkin'] ?? date('Y-m-d', strtotime('+1 day'));
$hotel_checkout = $_GET['hotel_checkout'] ?? date('Y-m-d', strtotime('+2 days'));
$hotel_rooms = max(1, min(10, intval($_GET['hotel_rooms'] ?? 1)));
$hotel_guests = max(1, min(20, intval($_GET['hotel_guests'] ?? 2)));

$search_hotel = isset($_GET['search_hotel']);

// Tính số đêm lưu trú
$dStart = new DateTime($hotel_checkin);
$dEnd  = new DateTime($hotel_checkout);
$dDiff = $dStart->diff($dEnd);
$nights = max(1, $dDiff->days);

// Biến cờ kiểm tra đăng nhập truyền xuống JS
$is_user_logged_in = isset($_SESSION['user_id']) ? 'true' : 'false';
?>

<!-- FORM TÌM KIẾM KHÁCH SẠN -->
<form id="hotelSearchForm" action="index.php" method="GET" class="mb-4" onsubmit="showHotelLoading(event)">
    <input type="hidden" name="tab" value="hotel">
    <div class="row g-3 align-items-end">
        <div class="col-lg-4 col-md-12">
            <label class="form-label text-muted small fw-medium">Thành phố, địa điểm hoặc tên khách sạn</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                <input type="text" name="hotel_destination" class="form-control fw-bold" placeholder="VD: Cần Thơ, Phú Quốc..." value="<?= htmlspecialchars($hotel_destination) ?>" required>
            </div>
        </div>

        <div class="col-lg-2 col-md-6">
            <label class="form-label text-muted small fw-medium">Nhận phòng</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-calendar-check text-success"></i></span>
                <input type="date" id="hotel_checkin" name="hotel_checkin" class="form-control fw-bold" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($hotel_checkin) ?>" required>
            </div>
        </div>

        <div class="col-lg-2 col-md-6">
            <label class="form-label text-muted small fw-medium">Trả phòng</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-calendar-x text-danger"></i></span>
                <input type="date" id="hotel_checkout" name="hotel_checkout" class="form-control fw-bold" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= htmlspecialchars($hotel_checkout) ?>" required>
            </div>
        </div>

        <div class="col-lg-2 col-md-6">
            <label class="form-label text-muted small fw-medium">Phòng & Khách</label>
            <div class="dropdown">
                <button class="btn btn-outline-secondary w-100 text-start bg-white d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="fw-bold text-dark"><i class="bi bi-people-fill text-primary me-2"></i><span id="hotel_guest_summary"><?= $hotel_rooms ?> Phòng, <?= $hotel_guests ?> Khách</span></span>
                </button>
                <div class="dropdown-menu p-3 shadow" style="min-width: 250px;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-medium">Số phòng</span>
                        <input type="number" name="hotel_rooms" id="hotel_rooms" class="form-control text-center" style="width: 80px;" min="1" max="10" value="<?= $hotel_rooms ?>" onchange="updateHotelSummary()">
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-medium">Số khách</span>
                        <input type="number" name="hotel_guests" id="hotel_guests" class="form-control text-center" style="width: 80px;" min="1" max="20" value="<?= $hotel_guests ?>" onchange="updateHotelSummary()">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-6">
            <button type="submit" name="search_hotel" value="1" class="btn w-100 fw-bold py-2 shadow-sm" style="background: var(--orange, #FF862E); color: white; transition: 0.3s;">
                <i class="bi bi-search me-1"></i> Tìm phòng
            </button>
        </div>
    </div>
</form>

<!-- YÊU CẦU LƯU TRÚ SUMMARY -->
<div class="alert bg-warning bg-opacity-10 border-warning border-opacity-50 d-flex justify-content-between align-items-center rounded-3 mb-4">
    <div>
        <span class="badge bg-warning text-dark mb-1">Thông tin lưu trú (<?= $nights ?> đêm)</span>
        <div class="fw-bold text-dark fs-5"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($hotel_destination) ?></div>
        <div class="small text-muted mt-1">
            <i class="bi bi-calendar-check me-1"></i> Nhận: <strong class="text-dark"><?= date('d/m/Y', strtotime($hotel_checkin)) ?></strong> 
            <span class="mx-2">|</span> 
            <i class="bi bi-calendar-x me-1"></i> Trả: <strong class="text-dark"><?= date('d/m/Y', strtotime($hotel_checkout)) ?></strong>
        </div>
    </div>
    <div class="text-end d-none d-md-block">
        <button class="btn btn-outline-danger rounded-pill fw-bold bg-white"><i class="bi bi-telephone-fill me-1"></i> Hotline hỗ trợ: 0382 123 805</button>
    </div>
</div>

<!-- HIỆU ỨNG LOADING -->
<div id="hotel-loading-overlay" class="text-center py-5" style="display: none;">
    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
    <h5 class="text-primary fw-bold">Đang tìm phòng tốt nhất...</h5>
    <p class="text-muted">Hệ thống đang quét các khách sạn tại <?= htmlspecialchars($hotel_destination) ?></p>
</div>

<!-- KẾT QUẢ TÌM KIẾM KHÁCH SẠN -->
<div id="hotel-results-container">
    <h4 class="fw-bold mb-4">🏠 Đề xuất Khách sạn tại "<?= htmlspecialchars($hotel_destination) ?>"</h4>
    
    <?php 
    // Dữ liệu khách sạn giả lập
    $mock_hotels = [
        [
            'name' => 'Mường Thanh Luxury ' . htmlspecialchars($hotel_destination),
            'stars' => 5,
            'image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=800',
            'rating' => 9.2,
            'reviews' => 1245,
            'features' => ['Bể bơi ngoài trời', 'Ăn sáng miễn phí', 'Spa'],
            'price_per_night' => 1250000
        ],
        [
            'name' => 'Vinpearl Resort & Spa',
            'stars' => 5,
            'image' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?q=80&w=800',
            'rating' => 8.8,
            'reviews' => 856,
            'features' => ['View biển/sông', 'Phòng Gym', 'Buffet'],
            'price_per_night' => 1800000
        ],
        [
            'name' => 'Riverside Boutique Hotel',
            'stars' => 4,
            'image' => 'https://images.unsplash.com/photo-1551882547-ff40eb0d8d73?q=80&w=800',
            'rating' => 8.5,
            'reviews' => 432,
            'features' => ['Trung tâm thành phố', 'Quầy bar', 'Hỗ trợ đưa đón'],
            'price_per_night' => 750000
        ]
    ];
    ?>

    <div class="d-flex flex-column gap-4">
        <?php foreach ($mock_hotels as $hotel): 
            // Tính tổng tiền = Giá 1 đêm * số đêm * số phòng
            $total_price = $hotel['price_per_night'] * $nights * $hotel_rooms;
        ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden hotel-card transition-hover" style="border: 1px solid #e2e8f0 !important;">
            <div class="row g-0">
                <div class="col-md-4 position-relative">
                    <img src="<?= $hotel['image'] ?>" class="img-fluid h-100 w-100 object-fit-cover" alt="Hotel">
                    <div class="position-absolute top-0 start-0 m-3">
                        <span class="badge bg-danger bg-opacity-75 fs-6 shadow-sm"><i class="bi bi-star-fill text-warning"></i> <?= $hotel['stars'] ?> Sao</span>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card-body p-4 d-flex flex-column h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h4 class="fw-bold text-dark mb-1"><?= $hotel['name'] ?></h4>
                                <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill text-primary"></i> Khu vực trung tâm <?= htmlspecialchars($hotel_destination) ?></p>
                            </div>
                            <div class="text-end">
                                <div class="d-inline-block bg-primary text-white rounded-3 px-2 py-1 fw-bold fs-5 shadow-sm"><?= $hotel['rating'] ?></div>
                                <div class="small text-muted mt-1"><?= $hotel['reviews'] ?> đánh giá</div>
                            </div>
                        </div>
                        
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach($hotel['features'] as $feat): ?>
                                <span class="badge bg-light text-secondary border"><i class="bi bi-check2 text-success"></i> <?= $feat ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-auto border-top pt-3 row align-items-end">
                            <div class="col-md-7 mb-3 mb-md-0">
                                <div class="text-muted small">Chi tiết đặt phòng:</div>
                                <div class="fw-medium text-dark"><i class="bi bi-moon-stars text-primary"></i> <?= $nights ?> đêm | <i class="bi bi-door-open text-primary"></i> <?= $hotel_rooms ?> phòng | <i class="bi bi-people text-primary"></i> <?= $hotel_guests ?> khách</div>
                                <div class="small text-success mt-1"><i class="bi bi-check-circle-fill"></i> Hủy phòng miễn phí trước ngày <?= date('d/m/Y', strtotime($hotel_checkin . ' - 2 days')) ?></div>
                            </div>
                            <div class="col-md-5 text-md-end text-center">
                                <div class="text-muted small text-decoration-line-through"><?= number_format($total_price * 1.2, 0, ',', '.') ?>đ</div>
                                <h3 class="fw-bold text-danger mb-2"><?= number_format($total_price, 0, ',', '.') ?>đ</h3>
                                
                                <button class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm py-2" style="background: #00558F;"
                                    onclick="openHotelModal(<?= $is_user_logged_in ?>, '<?= addslashes($hotel['name']) ?>', '<?= htmlspecialchars($hotel_destination) ?>', '<?= date('d/m/Y', strtotime($hotel_checkin)) ?>', '<?= date('d/m/Y', strtotime($hotel_checkout)) ?>', <?= $nights ?>, <?= $hotel_rooms ?>, <?= $hotel_guests ?>, <?= $total_price ?>)">
                                    Đặt Phòng Ngay
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL ĐIỀN THÔNG TIN ĐẶT PHÒNG KHÁCH SẠN   -->
<!-- ========================================== -->
<div class="modal fade" id="hotelCheckoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header text-white" style="background: #00558F; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-journal-check me-2"></i>Xác nhận Đặt Phòng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="bg-light p-3 rounded-3 border mb-4">
                    <h5 class="fw-bold text-primary mb-3" id="modal-hotel-name">Tên khách sạn</h5>
                    <div class="row text-muted small mb-2">
                        <div class="col-6">Nhận phòng: <br><strong class="text-dark fs-6" id="modal-h-checkin"></strong></div>
                        <div class="col-6">Trả phòng: <br><strong class="text-dark fs-6" id="modal-h-checkout"></strong></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-2 border-top pt-2">
                        <span>Chi tiết:</span> <strong class="text-dark"><span id="modal-h-nights"></span> đêm | <span id="modal-h-rooms"></span> phòng | <span id="modal-h-guests"></span> khách</strong>
                    </div>
                    <div class="d-flex justify-content-between text-muted small pt-2 mt-2 border-top">
                        <span>Tổng thanh toán:</span> <strong class="text-danger fs-4" id="modal-h-price-display">0đ</strong>
                    </div>
                </div>

                <form id="hotelBookingForm" onsubmit="processHotelBooking(event)">
                    <h6 class="fw-bold mb-3">Thông tin người đặt</h6>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" id="hotelCustName" class="form-control" required placeholder="Nhập tên người nhận phòng" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']) : '' ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small text-muted">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                        <input type="tel" id="hotelCustPhone" class="form-control" required placeholder="Nhập số điện thoại">
                    </div>
                    <button type="submit" id="btnSubmitHotelBooking" class="btn btn-warning w-100 fw-bold py-3 fs-5 text-white shadow-sm" style="background: var(--orange, #FF862E); border: none;">
                        Thanh Toán Qua VNPAY
                    </button>
                    <p class="text-center text-muted small mt-2"><i class="bi bi-shield-lock-fill text-success"></i> Thanh toán an toàn và bảo mật</p>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .hotel-card { transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); }
    .hotel-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,85,143,0.15) !important; border-color: #BAE6FD !important; }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const hModal = document.getElementById('hotelCheckoutModal');
        if(hModal) document.body.appendChild(hModal);
    });

    // Cập nhật text hiển thị số phòng/khách
    function updateHotelSummary() {
        const rooms = document.getElementById('hotel_rooms').value;
        const guests = document.getElementById('hotel_guests').value;
        document.getElementById('hotel_guest_summary').innerText = rooms + ' Phòng, ' + guests + ' Khách';
    }

    // Validate logic ngày nhận trả
    const hCheckin = document.getElementById('hotel_checkin');
    const hCheckout = document.getElementById('hotel_checkout');
    if (hCheckin && hCheckout) {
        hCheckin.addEventListener('change', function() {
            let nextDay = new Date(this.value);
            nextDay.setDate(nextDay.getDate() + 1);
            hCheckout.min = nextDay.toISOString().split('T')[0];
            if (hCheckout.value <= this.value) hCheckout.value = hCheckout.min;
        });
    }

    function showHotelLoading(e) {
        if(document.getElementById('hotelSearchForm').checkValidity()) {
            document.getElementById('hotel-results-container').style.display = 'none';
            document.getElementById('hotel-loading-overlay').style.display = 'block';
        }
    }

    let selectedHotel = {};

    function openHotelModal(isLoggedIn, hotelName, destination, checkIn, checkOut, nights, rooms, guests, totalPrice) {
        if (!isLoggedIn) {
            alert("Vui lòng Đăng Nhập để thực hiện chức năng đặt phòng!");
            window.location.href = 'login_user.php';
            return;
        }

        selectedHotel = { hotelName, destination, checkIn, checkOut, nights, rooms, guests, totalPrice };

        document.getElementById('modal-hotel-name').innerText = hotelName;
        document.getElementById('modal-h-checkin').innerText = checkIn;
        document.getElementById('modal-h-checkout').innerText = checkOut;
        document.getElementById('modal-h-nights').innerText = nights;
        document.getElementById('modal-h-rooms').innerText = rooms;
        document.getElementById('modal-h-guests').innerText = guests;
        document.getElementById('modal-h-price-display').innerText = new Intl.NumberFormat('vi-VN').format(totalPrice) + 'đ';

        const modal = new bootstrap.Modal(document.getElementById('hotelCheckoutModal'));
        modal.show();
    }

    async function processHotelBooking(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btnSubmitHotelBooking');
        const name = document.getElementById('hotelCustName').value;
        const phone = document.getElementById('hotelCustPhone').value;
        
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang kết nối VNPAY...';
        btn.disabled = true;

        try {
            let formData = new FormData();
            formData.append('action', 'book_hotel');
            formData.append('name', name);
            formData.append('phone', phone);
            formData.append('hotel_name', selectedHotel.hotelName);
            formData.append('destination', selectedHotel.destination);
            // Convert dd/mm/yyyy to yyyy-mm-dd format for Database if necessary, but ajax_hotel_booking assumes String parsing.
            formData.append('check_in', selectedHotel.checkIn.split('/').reverse().join('-')); 
            formData.append('check_out', selectedHotel.checkOut.split('/').reverse().join('-'));
            formData.append('rooms', selectedHotel.rooms);
            formData.append('guests', selectedHotel.guests);
            formData.append('price', selectedHotel.totalPrice);

            // GỌI SANG FILE AJAX KHÁCH SẠN
            let response = await fetch('ajax_hotel_booking.php', {
                method: 'POST',
                body: formData
            });
            
            let result = await response.json();

            if(result.status === 'success') {
                // Tắt Modal và chuyển hướng VNPAY
                const modalEl = document.getElementById('hotelCheckoutModal');
                const modalObj = bootstrap.Modal.getInstance(modalEl);
                modalObj.hide();
                window.location.href = result.redirect; 
            } else {
                alert("Lỗi hệ thống: " + result.msg);
                btn.innerHTML = 'Thanh Toán Qua VNPAY';
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert("Lỗi kết nối máy chủ! Vui lòng kiểm tra lại ajax_hotel_booking.php");
            btn.innerHTML = 'Thanh Toán Qua VNPAY';
            btn.disabled = false;
        }
    }
</script>