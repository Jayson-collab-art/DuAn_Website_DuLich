<?php
session_start();
include 'db.php';

// Kiểm tra xem khách có đi từ luồng đặt Tour sang không
if (!isset($_SESSION['upsell_booking_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id =$_SESSION['upsell_booking_id'];
$destination =$_SESSION['upsell_destination'];
$dep_date =$_SESSION['upsell_dep_date'];
$payment_method =$_SESSION['upsell_payment_method'];

// ==========================================
// TỪ ĐIỂN MAP ĐỊA ĐIỂM SANG MÃ SÂN BAY (CHUẨN TIẾNG VIỆT)
// ==========================================
$airport_code = ''; 

if (mb_stripos($destination, 'phú quốc', 0, 'UTF-8') !== false) $airport_code = 'PQC';
elseif (mb_stripos($destination, 'đà lạt', 0, 'UTF-8') !== false) $airport_code = 'DLI';
elseif (mb_stripos($destination, 'đà nẵng', 0, 'UTF-8') !== false) $airport_code = 'DAD';
elseif (mb_stripos($destination, 'nha trang', 0, 'UTF-8') !== false)$airport_code = 'CXR';
elseif (mb_stripos($destination, 'hà nội', 0, 'UTF-8') !== false) $airport_code = 'HAN';
elseif (mb_stripos($destination, 'cần thơ', 0, 'UTF-8') !== false) $airport_code = 'VCA';
elseif (mb_stripos($destination, 'cà mau', 0, 'UTF-8') !== false) $airport_code = 'CAH';
elseif (mb_stripos($destination, 'hải phòng', 0, 'UTF-8') !== false) $airport_code = 'HPH';
// THÊM ĐẢO PHÚ QUÝ ĐỂ HIỂN THỊ MÁY BAY:
elseif (mb_stripos($destination, 'phú quý', 0, 'UTF-8') !== false) $airport_code = 'SGN'; 

// Giả lập giá dịch vụ phụ trợ
$flight_price = 890000;
$hotel_price = 650000;

// ==========================================
// XỬ LÝ KHI KHÁCH BẤM "THANH TOÁN COMBO" HOẶC "BỎ QUA"
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Nếu khách chốt mua thêm Combo
    if (isset($_POST['btn_checkout_combo'])) {$extra_cost = 0;
        $combo_note = " (Gói LandTour";
        $has_extra = false;
        
        if (isset($_POST['flight_added']) &&$_POST['flight_added'] == '1') {
            $extra_cost +=$flight_price;
            $combo_note .= " + VMB";
            $has_extra = true;
        }
        if (isset($_POST['hotel_added']) &&$_POST['hotel_added'] == '1') {
            $extra_cost +=$hotel_price;
            $combo_note .= " + Khách sạn";
            $has_extra = true;
        }
        $combo_note .= ")";

        // LOGIC ÉP BUỘC THANH TOÁN ONLINE
        if ($has_extra) {
            // Nếu ban đầu chọn Tiền mặt, giờ mua thêm Máy bay/Khách sạn -> Bắt buộc phải là VNPay
            if ($payment_method === 'Tiền mặt') {
                $payment_method = 'VNPay';
            }
            
            // Cập nhật lại Tổng tiền, Ghi chú và Phương thức thanh toán vào Database
            $sql_update = "UPDATE bookings SET TotalPrice = TotalPrice + ?, CustomerName = CONCAT(CustomerName, ?), PaymentMethod = ? WHERE BookingID = ?";
            $stmt = mysqli_prepare($conn,$sql_update);
            mysqli_stmt_bind_param($stmt, "dssi", $extra_cost,$combo_note, $payment_method,$booking_id);
            mysqli_stmt_execute($stmt);
        }
    }

    // Xóa session upsell để tránh lỗi lặp
    unset($_SESSION['upsell_booking_id'],$_SESSION['upsell_destination'], $_SESSION['upsell_dep_date'],$_SESSION['upsell_payment_method']);
    
    // TIẾP TỤC LUỒNG THANH TOÁN (Đã được cập nhật nếu có mua Combo)
    if ($payment_method === 'VNPay') {
        header("Location: vnpay_create.php?booking_id=" . $booking_id);
    } elseif ($payment_method === 'Chuyển khoản') {
        header("Location: payment_create.php?booking_id=" . $booking_id);
    } else {
        echo "<script>alert('Đặt tour thành công! Vui lòng thanh toán trực tiếp tại văn phòng.'); window.location.href = 'my_bookings.php';</script>";
    }
    exit();
}

$display_date = date('d/m/Y', strtotime($dep_date));
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoàn thiện chuyến đi - MIỀNTÂY TRAVEL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #F8FAFC; font-family: 'Inter', sans-serif; padding-bottom: 100px; }
        .hero-upsell { background: linear-gradient(135deg, #00284D 0%, #00558F 100%); color: white; padding: 40px 0; border-bottom-left-radius: 40px; border-bottom-right-radius: 40px; margin-bottom: -50px; }
        .card-upsell { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: transform 0.3s; overflow: hidden; background: #fff; cursor: pointer; border: 2px solid transparent;}
        .card-upsell:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.12); }
        .card-upsell.selected-card { border-color: #0ea5e9; background-color: #f0f9ff; }
        .badge-hot { position: absolute; top: 15px; left: -10px; background: #E11D48; color: white; padding: 5px 15px; font-weight: 700; font-size: 0.8rem; border-radius: 0 20px 20px 0; z-index: 2; box-shadow: 0 4px 10px rgba(225,29,72,0.3); }
        .btn-add-combo { border-radius: 50px; font-weight: 700; border: 2px solid #0ea5e9; color: #0ea5e9; padding: 8px 20px; transition: 0.3s; background: white;}
        .btn-add-combo.added { background: #0ea5e9; color: white; border-color: #0ea5e9; }
        
        /* Thanh công cụ nổi ở đáy màn hình */
        .bottom-action-bar { position: fixed; bottom: 0; left: 0; right: 0; background: white; padding: 20px; box-shadow: 0 -5px 20px rgba(0,0,0,0.1); z-index: 1000; transform: translateY(100%); transition: transform 0.3s ease-out; border-top: 1px solid #e2e8f0; }
        .bottom-action-bar.show { transform: translateY(0); }
    </style>
</head>
<body>

    <div class="hero-upsell text-center pb-5">
        <div class="container pb-4">
            <div class="mb-3"><i class="bi bi-check-circle-fill text-success" style="font-size: 3rem; filter: drop-shadow(0 0 10px rgba(25,135,84,0.5));"></i></div>
            <h2 class="fw-bold mb-2">Tuyệt vời! Bạn đã chốt Land Tour đi <?= htmlspecialchars($destination) ?></h2>
            <p class="text-white-50 fs-5">Tour tự túc chưa bao gồm vé máy bay và lưu trú. Hãy xem ngay các ưu đãi đặc quyền dành riêng cho bạn dưới đây!</p>
        </div>
    </div>

    <div class="container position-relative" style="z-index: 10;">
        <div class="row g-4 justify-content-center">
            
            <!-- CỘT VÉ MÁY BAY -->
            <?php if($airport_code !== ''): // Chỉ hiện máy bay nếu điểm đến có sân bay ?>
            <div class="col-lg-6">
                <div class="d-flex align-items-center mb-3 mt-4">
                    <i class="bi bi-airplane-engines-fill text-primary fs-3 me-2"></i>
                    <h4 class="fw-bold text-dark m-0">Chuyến bay đến <?= htmlspecialchars($destination) ?></h4>
                </div>
                
                <div class="card-upsell p-3 mb-3 position-relative" id="card-flight" onclick="toggleItem('flight', <?= $flight_price ?>)">
                    <div class="badge-hot">Bay sớm giá tốt</div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/67/Bamboo_Airways_logo.svg/1024px-Bamboo_Airways_logo.svg.png" width="80" alt="Bamboo">
                            <div>
                                <h5 class="fw-bold mb-1">SGN <i class="bi bi-arrow-right mx-1 text-muted"></i> <?= $airport_code ?></h5>
                                <p class="text-muted small mb-0"><i class="bi bi-calendar-event me-1"></i> Ngày đi: <?= $display_date ?></p>
                            </div>
                        </div>
                        <div class="text-end">
                            <h4 class="fw-bold text-danger mb-2"><?= number_format($flight_price, 0, ',', '.') ?>đ</h4>
                            <button type="button" class="btn btn-add-combo btn-sm" id="btn-flight">+ Thêm vào</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- CỘT KHÁCH SẠN -->
            <div class="col-lg-6">
                <div class="d-flex align-items-center mb-3 mt-4">
                    <i class="bi bi-buildings-fill text-warning fs-3 me-2"></i>
                    <h4 class="fw-bold text-dark m-0">Lưu trú tại <?= htmlspecialchars($destination) ?></h4>
                </div>

                <div class="card-upsell p-0 mb-3 position-relative d-flex" id="card-hotel" onclick="toggleItem('hotel', <?= $hotel_price ?>)">
                    <div class="badge-hot" style="background: #10B981;">Deal Độc Quyền</div>
                    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=300" style="width: 140px; object-fit: cover;" alt="Hotel">
                    <div class="p-3 w-100 d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-1">Khách sạn <?= htmlspecialchars($destination) ?> 4⭐</h5>
                            <span class="text-warning small">⭐⭐⭐⭐</span>
                            <span class="text-muted small ms-2"><i class="bi bi-geo-alt-fill"></i> Trung tâm</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-end mt-2">
                            <div>
                                <h5 class="fw-bold text-danger mb-0"><?= number_format($hotel_price, 0, ',', '.') ?>đ<span class="text-muted fs-6 fw-normal">/đêm</span></h5>
                            </div>
                            <button type="button" class="btn btn-add-combo btn-sm" id="btn-hotel">+ Thêm vào</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Khung lựa chọn Bỏ qua nằm trong body (Hiện khi chưa chọn gì) -->
        <div class="text-center mt-5" id="skip-container">
            <p class="text-muted mb-3">Bạn đã có phương tiện di chuyển và nơi ở riêng?</p>
            <form method="POST">
                <button type="submit" name="btn_skip" class="btn btn-light fw-bold rounded-pill px-5 shadow-sm" style="color: #64748b; border: 1px solid #cbd5e1;">Bỏ qua, chỉ đặt Tour</button>
            </form>
        </div>
    </div>

    <!-- THANH CÔNG CỤ THANH TOÁN (Trượt lên khi khách chọn ít nhất 1 món) -->
    <div class="bottom-action-bar" id="action-bar">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark m-0" id="combo-status">Bạn đã chọn 1 dịch vụ</h5>
                <p class="text-primary fw-bold fs-5 m-0">Tạm tính thêm: <span id="total-extra-price">0</span>đ</p>
            </div>
            
            <!-- FORM CHÍNH NỘP DỮ LIỆU VỀ MÁY CHỦ -->
            <form method="POST" class="m-0">
                <input type="hidden" name="flight_added" id="input-flight" value="0">
                <input type="hidden" name="hotel_added" id="input-hotel" value="0">
                
                <button type="submit" name="btn_checkout_combo" class="btn btn-primary fw-bold rounded-pill px-5 py-2 shadow" style="background: linear-gradient(135deg, #0284c7, #0369a1); border: none; font-size: 1.1rem;">
                    Tiếp tục thanh toán Combo <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        let state = { flight: false, hotel: false };
        let prices = { flight: 0, hotel: 0 };

        function toggleItem(type, price) {
            state[type] = !state[type];
            prices[type] = state[type] ? price : 0;
            
            const card = document.getElementById('card-' + type);
            const btn = document.getElementById('btn-' + type);
            const input = document.getElementById('input-' + type);
            
            if(state[type]) {
                card.classList.add('selected-card');
                btn.classList.add('added');
                btn.innerHTML = "<i class='bi bi-check2'></i> Đã thêm";
                input.value = "1";
            } else {
                card.classList.remove('selected-card');
                btn.classList.remove('added');
                btn.innerHTML = "+ Thêm vào";
                input.value = "0";
            }
            
            updateCart();
        }

        function updateCart() {
            let count = (state.flight ? 1 : 0) + (state.hotel ? 1 : 0);
            let totalPrice = prices.flight + prices.hotel;
            
            const actionBar = document.getElementById('action-bar');
            const skipContainer = document.getElementById('skip-container');
            const totalText = document.getElementById('total-extra-price');
            const statusText = document.getElementById('combo-status');
            
            if(count > 0) {
                actionBar.classList.add('show');
                skipContainer.style.display = 'none';
                statusText.innerHTML = `<span class='text-success'><i class='bi bi-cart-check-fill'></i> Đã thêm ${count} dịch vụ phụ trợ</span>`;
                totalText.innerText = totalPrice.toLocaleString('vi-VN');
            } else {
                actionBar.classList.remove('show');
                skipContainer.style.display = 'block';
            }
        }
    </script>
</body>
</html>