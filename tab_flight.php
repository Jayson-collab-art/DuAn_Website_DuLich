<?php
// ===================================================================
// XỬ LÝ AJAX LƯU DỮ LIỆU ĐẶT VÉ VÀO BẢNG FLIGHT_BOOKINGS VÀ CHUYỂN VNPAY
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_flight') {
    header('Content-Type: application/json');
    
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Vui lòng đăng nhập để đặt vé!']);
        exit();
    }

    $user_id = (int)$_SESSION['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $airline = mysqli_real_escape_string($conn, $_POST['airline']);
    $pnr = mysqli_real_escape_string($conn, $_POST['pnr']);
    
    // Xử lý giá tiền: Lọc bỏ các ký tự không phải số
    $price_str = $_POST['price'] ?? '0';
    $price = (float)preg_replace('/[^0-9]/', '', $price_str);

    $sql_insert = "INSERT INTO flight_bookings (UserID, CustomerName, Phone, Airline, FlightRoute, DepartDate, FlightTime, TotalPrice, PNR_Code, PaymentStatus) 
                   VALUES (?, ?, ?, ?, 'Theo chi tiet', NOW(), 'Theo chi tiet', ?, ?, 'Pending')";
                   
    $stmt = mysqli_prepare($conn, $sql_insert);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssds", $user_id, $name, $phone, $airline, $price, $pnr);
        
        if (mysqli_stmt_execute($stmt)) {
            $flight_id = mysqli_insert_id($conn);
            echo json_encode([
                'status' => 'success', 
                'redirect' => "vnpay_create.php?flight_id=" . $flight_id
            ]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Lỗi DB: ' . mysqli_error($conn)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi chuẩn bị truy vấn SQL']);
    }
    exit();
}
// ===================================================================

$airports = [
    'SGN' => 'TP. Hồ Chí Minh (SGN)',
    'HAN' => 'Hà Nội (HAN)',
    'DAD' => 'Đà Nẵng (DAD)',
    'VCA' => 'Cần Thơ (VCA)',
    'PQC' => 'Phú Quốc (PQC)',
    'CXR' => 'Nha Trang (CXR)',
    'DLI' => 'Đà Lạt (DLI)',
    'HPH' => 'Hải Phòng (HPH)'
];

$seat_classes = [
    'ECONOMY' => 'Phổ thông (Economy)',
    'BUSINESS' => 'Thương gia (Business)'
];

$flight_trip_type = $_GET['trip_type'] ?? 'roundtrip';
$flight_from = $_GET['flight_from'] ?? 'SGN';
$flight_to = $_GET['flight_to'] ?? 'VCA';
$flight_depart = $_GET['flight_depart'] ?? '';
$flight_return = $_GET['flight_return'] ?? '';
$flight_passengers = max(1, min(20, intval($_GET['flight_passengers'] ?? 1)));
$flight_seat_class = $_GET['flight_seat_class'] ?? 'ECONOMY';

$flight_searched = isset($_GET['search_flight']);
$flight_errors = [];

if ($flight_searched) {
    if (empty($flight_from) || empty($flight_to))$flight_errors[] = "Vui lòng chọn sân bay.";
    if ($flight_from === $flight_to)$flight_errors[] = "Sân bay đi và đến không được trùng nhau.";
    if (empty($flight_depart))$flight_errors[] = "Vui lòng chọn ngày khởi hành.";
}

$is_user_logged_in = isset($_SESSION['user_id']) ? 'true' : 'false';
?>

<!-- FORM TÌM KIẾM VÉ MÁY BAY -->
<form id="flightSearchForm" action="index.php" method="GET" class="mb-4" onsubmit="showFlightLoading(event)">
    <input type="hidden" name="tab" value="flight">

    <!-- Chọn loại hình chuyến bay -->
    <div class="d-flex align-items-center gap-4 mb-3 pb-2 border-bottom">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="trip_type" id="trip_roundtrip" value="roundtrip" <?= $flight_trip_type === 'roundtrip' ? 'checked' : '' ?> onchange="toggleFlightReturnDate(true)">
            <label class="form-check-label fw-semibold text-dark" for="trip_roundtrip"><i class="bi bi-arrow-left-right text-primary me-1"></i> Khứ hồi</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="trip_type" id="trip_oneway" value="oneway" <?= $flight_trip_type === 'oneway' ? 'checked' : '' ?> onchange="toggleFlightReturnDate(false)">
            <label class="form-check-label fw-semibold text-dark" for="trip_oneway"><i class="bi bi-arrow-right text-primary me-1"></i> Một chiều</label>
        </div>
    </div>

    <!-- Chặng bay -->
    <div class="row g-3 align-items-end mb-3">
        <div class="col-lg-5 col-md-5">
            <label class="form-label text-muted small fw-medium">Điểm đi</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-geo-alt-fill text-danger"></i></span>
                <select name="flight_from" id="flight_from" class="form-select fw-bold" required>
                    <?php foreach ($airports as $code =>$name): ?>
                        <option value="<?= $code ?>" <?= $flight_from === $code ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="col-lg-2 col-md-2 text-center d-flex justify-content-center align-items-end pb-1">
            <button type="button" class="btn btn-outline-secondary rounded-circle shadow-sm" onclick="swapAirports()" style="width: 42px; height: 42px; transition: 0.3s;"><i class="bi bi-arrow-left-right"></i></button>
        </div>

        <div class="col-lg-5 col-md-5">
            <label class="form-label text-muted small fw-medium">Điểm đến</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-geo-alt-fill text-success"></i></span>
                <select name="flight_to" id="flight_to" class="form-select fw-bold" required>
                    <?php foreach ($airports as $code =>$name): ?>
                        <option value="<?= $code ?>" <?= $flight_to === $code ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Ngày đi, Ngày về, Hành khách -->
    <div class="row g-3 align-items-end">
        <div class="col-lg-3 col-md-6">
            <label class="form-label text-muted small fw-medium">Ngày đi</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-calendar-event text-primary"></i></span>
                <input type="date" id="flight_depart" name="flight_depart" class="form-control fw-bold text-primary" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($flight_depart) ?>" required>
            </div>
        </div>

        <div class="col-lg-3 col-md-6" id="return_date_wrapper" style="<?= $flight_trip_type === 'oneway' ? 'opacity:0.5;' : '' ?>">
            <label class="form-label text-muted small fw-medium">Ngày về</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-calendar-check text-primary"></i></span>
                <input type="date" id="flight_return" name="flight_return" class="form-control fw-bold text-primary" min="<?= $flight_depart ?: date('Y-m-d') ?>" value="<?= htmlspecialchars($flight_return) ?>" <?= $flight_trip_type === 'oneway' ? 'disabled' : '' ?>>
            </div>
        </div>

        <div class="col-lg-2 col-md-4">
            <label class="form-label text-muted small fw-medium">Hành khách</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-people-fill text-muted"></i></span>
                <input type="number" name="flight_passengers" class="form-control fw-bold text-center" min="1" max="20" value="<?= $flight_passengers ?>" required>
            </div>
        </div>

        <div class="col-lg-2 col-md-4">
            <label class="form-label text-muted small fw-medium">Hạng vé</label>
            <select name="flight_seat_class" class="form-select fw-semibold text-secondary">
                <?php foreach ($seat_classes as $k =>$v): ?>
                    <option value="<?= $k ?>" <?= $flight_seat_class === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-lg-2 col-md-4">
            <button type="submit" name="search_flight" value="1" class="btn w-100 fw-bold py-2 btn-flight-search" style="background: var(--orange, #FF862E); color: white; transition: 0.3s;">
                <i class="bi bi-search me-1"></i> Tìm chuyến bay
            </button>
        </div>
    </div>
</form>

<!-- HIỆU ỨNG LOADING -->
<div id="flight-loading-overlay" class="text-center py-5" style="display: none;">
    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
    <h5 class="text-primary fw-bold">Đang kết nối hệ thống hàng không...</h5>
    <p class="text-muted">Vui lòng chờ trong giây lát, chúng tôi đang tìm mức giá tốt nhất cho bạn!</p>
</div>

<!-- KHU VỰC KẾT QUẢ TÌM KIẾM VÉ MÁY BAY -->
<div id="flight-results-container">
    <?php if ($flight_searched && empty($flight_errors)): ?>
        <div class="mt-4 border-top pt-4" id="flight-results">
            <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded-3 border">
                <h5 class="fw-bold text-dark m-0">
                    <i class="bi bi-airplane-engines-fill text-primary me-2"></i>Chuyến bay: 
                    <span class="text-primary mx-1"><?= $airports[$flight_from] ?></span> 
                    <i class="bi bi-arrow-right text-muted mx-1"></i> 
                    <span class="text-success mx-1"><?= $airports[$flight_to] ?></span>
                </h5>
                <span class="badge bg-secondary px-3 py-2 fs-6">Khởi hành: <?= date('d/m/Y', strtotime($flight_depart)) ?></span>
            </div>

            <?php 
            $base_price = ($flight_seat_class === 'BUSINESS') ? 3500000 : 1200000;
            $mock_flights = [
                [
                    'airline' => 'Vietjet Air', 
                    'logo' => 'https://seeklogo.com/images/V/vietjet-air-logo-0FC6471E20-seeklogo.com.png',
                    'time_out' => '07:30', 'time_in' => '09:45',
                    'price' => $base_price * 0.8 * $flight_passengers,
                    'code' => 'VJ-' . rand(100, 999)
                ],
                [
                    'airline' => 'Vietnam Airlines', 
                    'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Vietnam_Airlines_logo.svg/1200px-Vietnam_Airlines_logo.svg.png',
                    'time_out' => '10:00', 'time_in' => '12:15',
                    'price' => $base_price * 1.3 * $flight_passengers,
                    'code' => 'VN-' . rand(100, 999)
                ],
                [
                    'airline' => 'Bamboo Airways', 
                    'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b3/Bamboo_Airways_logo.svg/2560px-Bamboo_Airways_logo.svg.png',
                    'time_out' => '15:20', 'time_in' => '17:35',
                    'price' => $base_price * 1.1 * $flight_passengers,
                    'code' => 'QH-' . rand(100, 999)
                ]
            ];
            ?>

            <div class="d-flex flex-column gap-3">
                <?php foreach ($mock_flights as$flight): 
                    $price_format = number_format($flight['price'], 0, ',', '.');
                ?>
                <div class="card border border-light-subtle rounded-4 shadow-sm flight-card transition-hover">
                    <div class="card-body p-3 p-md-4">
                        <div class="row align-items-center text-center text-md-start">
                            <div class="col-md-3 col-12 mb-3 mb-md-0 d-flex flex-column align-items-center justify-content-center justify-content-md-start">
                                <img src="<?= $flight['logo'] ?>" alt="<?= $flight['airline'] ?>" style="height: 35px; max-width: 130px; object-fit: contain; margin-bottom: 8px;">
                                <span class="badge bg-light text-secondary border"><?= $flight['code'] ?></span>
                            </div>
                            
                            <div class="col-md-5 col-12 mb-3 mb-md-0 d-flex align-items-center justify-content-center">
                                <div class="text-end me-3">
                                    <h3 class="fw-bold text-dark m-0"><?= $flight['time_out'] ?></h3>
                                    <span class="badge bg-primary bg-opacity-10 text-primary mt-1"><?= $flight_from ?></span>
                                </div>
                                <div class="d-flex flex-column align-items-center px-2" style="flex:1;">
                                    <span class="small text-muted mb-1"><i class="bi bi-clock"></i> 2h 15m</span>
                                    <div class="w-100 border-top border-2 border-primary position-relative">
                                        <i class="bi bi-airplane-fill text-primary position-absolute top-50 start-50 translate-middle" style="transform: translate(-50%, -50%) rotate(90deg); font-size: 1.2rem;"></i>
                                    </div>
                                    <span class="small text-success mt-1 fw-medium">Bay thẳng</span>
                                </div>
                                <div class="text-start ms-3">
                                    <h3 class="fw-bold text-dark m-0"><?= $flight['time_in'] ?></h3>
                                    <span class="badge bg-success bg-opacity-10 text-success mt-1"><?= $flight_to ?></span>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 text-md-end text-center border-start-md">
                                <div class="text-muted small mb-1">Tổng cộng (<?= $flight_passengers ?> khách)</div>
                                <h3 class="text-danger fw-bold mb-3"><?= $price_format ?>đ</h3>
                                
                                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm btn-book-flight" 
                                        style="background: #00558F; transition: 0.3s;"
                                        onclick="checkLoginAndBook(<?= $is_user_logged_in ?>, '<?= htmlspecialchars($flight['airline']) ?>', '<?= htmlspecialchars($flight['logo']) ?>', '<?= htmlspecialchars($flight['code']) ?>', '<?= htmlspecialchars($flight['time_out']) ?> - <?= htmlspecialchars($flight['time_in']) ?>', '<?= $price_format ?>', '<?= htmlspecialchars($flight_from) ?>', '<?= htmlspecialchars($flight_to) ?>', '<?= date('d/m/Y', strtotime($flight_depart)) ?>')">
                                    Chọn vé này <i class="bi bi-check2-circle ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================== -->
<!-- MODAL NHẬP THÔNG TIN KHÁCH HÀNG ĐẶT VÉ     -->
<!-- ========================================== -->
<div class="modal fade" id="flightCheckoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header text-white" style="background: #00558F; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i>Xác nhận thông tin đặt vé</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="bg-light p-3 rounded-3 border mb-4">
                    <h6 class="fw-bold text-primary mb-2" id="modal-airline">Hãng bay</h6>
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Chặng bay:</span> <strong class="text-dark"><span id="modal-from">SGN</span> <i class="bi bi-arrow-right"></i> <span id="modal-to">HAN</span></strong>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Ngày đi:</span> <strong class="text-dark" id="modal-date">dd/mm/yyyy</strong>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Giờ bay:</span> <strong class="text-dark" id="modal-time">00:00</strong>
                    </div>
                    <div class="d-flex justify-content-between text-muted small pt-2 mt-2 border-top">
                        <span>Tổng tiền (<?= $flight_passengers ?> vé):</span> <strong class="text-danger fs-5" id="modal-price">0đ</strong>
                    </div>
                </div>

                <form id="fakeCheckoutForm" onsubmit="processFakeBooking(event)">
                    <h6 class="fw-bold mb-3">Thông tin liên hệ nhận vé</h6>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" id="custName" name="custName" class="form-control" required placeholder="Ví dụ: NGUYEN VAN A" value="<?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : (isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']) : '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small text-muted">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                        <input type="tel" id="custPhone" name="custPhone" class="form-control" required placeholder="Ví dụ: 0987654321">
                    </div>
                    <button type="submit" id="btnSubmitBooking" class="btn btn-warning w-100 fw-bold py-2 fs-5 text-white" style="background: var(--orange, #FF862E); border: none;">
                        Hoàn Tất Giữ Chỗ
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .flight-card { transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); }
    .flight-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,85,143,0.12) !important; border-color: #BAE6FD !important; }
    .btn-flight-search:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 134, 46, 0.4); }
    .card-route { cursor: pointer; transition: all 0.3s; }
    .card-route:hover { background: #F0F9FF; border-color: #0284C7 !important; transform: translateY(-3px); }
    .letter-spacing-2 { letter-spacing: 2px; }
    @media (min-width: 768px) { .border-start-md { border-left: 1px dashed #dee2e6; } }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const checkoutModal = document.getElementById('flightCheckoutModal');
        if(checkoutModal) {
            // Ép Modal ra khỏi các thẻ chứa (div) lồng nhau, đưa ra ngoài cùng thẻ body
            // Việc này giải quyết triệt để lỗi lớp đen mờ đè lên bảng Modal
            document.body.appendChild(checkoutModal);
        }
    });

    function toggleFlightReturnDate(isRoundtrip) {
        const returnInput = document.getElementById('flight_return');
        const wrapper = document.getElementById('return_date_wrapper');
        if (returnInput) {
            returnInput.disabled = !isRoundtrip;
            wrapper.style.opacity = isRoundtrip ? '1' : '0.5';
            if (!isRoundtrip) returnInput.value = '';
        }
    }

    function swapAirports() {
        const from = document.getElementById('flight_from');
        const to = document.getElementById('flight_to');
        if (from && to) { const temp = from.value; from.value = to.value; to.value = temp; }
    }

    const fDepart = document.getElementById('flight_depart');
    const fReturn = document.getElementById('flight_return');
    if (fDepart && fReturn) {
        fDepart.addEventListener('change', function() {
            fReturn.min = this.value;
            if (fReturn.value && fReturn.value < this.value) fReturn.value = this.value;
        });
    }

    function showFlightLoading(e) {
        if(document.getElementById('flightSearchForm').checkValidity()) {
            const resultsContainer = document.getElementById('flight-results-container');
            if (resultsContainer) resultsContainer.style.display = 'none';
            
            const overlay = document.getElementById('flight-loading-overlay');
            if (overlay) {
                overlay.style.display = 'block';
                overlay.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
        }
    }

    function quickSearchFlight(from, to) {
        document.getElementById('flight_from').value = from;
        document.getElementById('flight_to').value = to;
        document.querySelector('button[name="search_flight"]').click();
    }

    let selectedFlight = {};

    function checkLoginAndBook(isLoggedIn, airline, logo, code, time, price, from, to, date) {
        if (!isLoggedIn) {
            alert("Vui lòng Đăng Nhập để thực hiện chức năng đặt vé máy bay!");
            window.location.href = 'login_user.php';
            return;
        }
        
        selectedFlight = { airline, logo, code, time, price, from, to, date };
        
        document.getElementById('modal-airline').innerText = airline + ' (' + code + ')';
        document.getElementById('modal-time').innerText = time;
        document.getElementById('modal-price').innerText = price;
        document.getElementById('modal-from').innerText = from;
        document.getElementById('modal-to').innerText = to;
        document.getElementById('modal-date').innerText = date;
        
        const modalElement = document.getElementById('flightCheckoutModal');
        if (modalElement) {
            const modalInstance = new bootstrap.Modal(modalElement);
            modalInstance.show();
            window.currentModalInstance = modalInstance;
            
            // Mở khóa và tự động focus vào ô nhập liệu khi modal bật lên
            modalElement.addEventListener('shown.bs.modal', function () {
                const nameInput = document.getElementById('custName');
                const phoneInput = document.getElementById('custPhone');
                
                if (nameInput) {
                    nameInput.removeAttribute('readonly');
                    nameInput.removeAttribute('disabled');
                    nameInput.style.backgroundColor = '#ffffff';
                }
                
                if (phoneInput) {
                    phoneInput.removeAttribute('readonly');
                    phoneInput.removeAttribute('disabled');
                    phoneInput.style.backgroundColor = '#ffffff';
                    phoneInput.focus();
                }
            }, { once: true });
        } else {
            console.error("Modal element not found!");
        }
    }

    async function processFakeBooking(e) {
        e.preventDefault(); 
        
        const btn = document.getElementById('btnSubmitBooking');
        const name = document.getElementById('custName').value;
        const phone = document.getElementById('custPhone').value;
        
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang xử lý...';
        btn.disabled = true;

        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let pnr = '';
        for (let i = 0; i < 6; i++) pnr += chars.charAt(Math.floor(Math.random() * chars.length));

        const details = `Chặng: ${selectedFlight.from} -> ${selectedFlight.to} | Ngày: ${selectedFlight.date} | Giờ: ${selectedFlight.time}`;

        try {
            let formData = new FormData();
            formData.append('action', 'book_flight');
            formData.append('name', name);
            formData.append('phone', phone);
            formData.append('airline', selectedFlight.airline);
            formData.append('pnr', pnr);
            formData.append('price', selectedFlight.price);
            formData.append('details', details);

            let response = await fetch('ajax_flight_booking.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            let result = await response.json();

            if(result.status === 'success') {
                if (window.currentModalInstance) {
                    window.currentModalInstance.hide();
                }
                window.location.href = result.redirect; 
            } else {
                alert("Lỗi hệ thống: " + result.msg);
                btn.innerHTML = 'Hoàn Tất Giữ Chỗ';
                btn.disabled = false;
            }
        } catch (err) {
            console.error("Lỗi fetch:", err); 
            alert("Lỗi kết nối máy chủ! Vui lòng thử lại.");
            btn.innerHTML = 'Hoàn Tất Giữ Chỗ';
            btn.disabled = false;
        }
    }

    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('search_flight')) {
            const resultsDiv = document.getElementById('flight-results');
            if(resultsDiv) resultsDiv.scrollIntoView({behavior: 'smooth', block: 'start'});
        }
    };
</script>