<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php'; 

// Tạo mã CSRF Token bảo mật form nếu chưa có
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success_msg = '';

// Dữ liệu form lưu lại khi submit lỗi (tránh bắt người dùng gõ lại từ đầu)
$form_data = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => ''
];

// Nhận dữ liệu pre-fill từ các liên kết yêu cầu báo giá Vé máy bay / Khách sạn
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_GET['subject'])) {
        $form_data['subject'] = trim($_GET['subject']);
    }
    if (!empty($_GET['message'])) {
        $form_data['message'] = trim($_GET['message']);
    }
}

// Lưu trữ lỗi từng trường cụ thể để hiển thị label đỏ dưới ô nhập
$field_errors = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'message' => ''
];

/**
 * Hàm kiểm tra số điện thoại di động Việt Nam hợp lệ (10 chữ số)
 * Quy định các đầu số di động hiện hành:
 * - Viettel: 086, 096, 097, 098, 032, 033, 034, 035, 036, 037, 038, 039
 * - Mobifone: 089, 090, 093, 070, 079, 077, 076, 078
 * - Vinaphone: 088, 091, 094, 083, 084, 085, 081, 082
 * - Vietnamobile: 092, 056, 058
 * - Gmobile: 099, 059
 * - Itelecom / Wintel: 087, 055
 */
function validate_vietnam_phone($phone_raw) {
    if (empty($phone_raw)) {
        return "Vui lòng nhập số điện thoại.";
    }
    
    // Loại bỏ khoảng trắng, dấu gạch nối, dấu chấm
    $cleaned = preg_replace('/[\s\-\.]/', '', $phone_raw);
    
    // Chuẩn hóa +84 sang 0
    if (strpos($cleaned, '+84') === 0) {
        $cleaned = '0' . substr($cleaned, 3);
    }
    
    // Chỉ chấp nhận ký tự số
    if (!ctype_digit($cleaned)) {
        return "Số điện thoại chỉ được chứa ký tự số.";
    }
    
    // Phải đúng 10 chữ số
    if (strlen($cleaned) !== 10) {
        return "Số điện thoại phải gồm đúng 10 chữ số.";
    }
    
    // Kiểm tra đầu số di động Việt Nam hợp lệ
    // Bắt đầu bằng 0, tiếp theo là 3, 5, 7, 8, 9 và 8 chữ số sau
    $vn_mobile_pattern = '/^0(3[2-9]|5[5689]|7[06-9]|8[1-9]|9[0-9])[0-9]{7}$/';
    if (!preg_match($vn_mobile_pattern, $cleaned)) {
        return "Đầu số di động không hợp lệ theo quy định nhà mạng Việt Nam.";
    }
    
    return true; // Hợp lệ
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    // Thu thập và chuẩn hóa dữ liệu
    $form_data['name'] = trim($_POST['name'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['phone'] = trim($_POST['phone'] ?? '');
    $form_data['subject'] = trim($_POST['subject'] ?? '');
    $form_data['message'] = trim($_POST['message'] ?? '');

    // 1. Kiểm tra CSRF Token bảo vệ form chống giả mạo
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $field_errors['name'] = "Phiên làm việc đã hết hạn. Vui lòng thử lại.";
    } else {
        $name = $form_data['name'];
        $email = $form_data['email'];
        $phone = $form_data['phone'];
        $subject = $form_data['subject'];
        $message = $form_data['message'];

        // 2. Validate Họ và tên
        if (empty($name)) {
            $field_errors['name'] = "Vui lòng nhập họ tên.";
        } elseif (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100) {
            $field_errors['name'] = "Họ và tên cần có độ dài từ 2 đến 100 ký tự.";
        }

        // 3. Validate Email
        if (empty($email)) {
            $field_errors['email'] = "Vui lòng nhập địa chỉ email.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $field_errors['email'] = "Vui lòng nhập địa chỉ email hợp lệ.";
        }

        // 4. Validate Số điện thoại Việt Nam (10 số, đầu số hợp lệ)
        $phone_check = validate_vietnam_phone($phone);
        if ($phone_check !== true) {
            $field_errors['phone'] = $phone_check;
        }

        // 5. Validate Nội dung liên hệ (không rỗng, ít nhất 10 ký tự)
        if (empty($message)) {
            $field_errors['message'] = "Vui lòng nhập nội dung liên hệ.";
        } elseif (mb_strlen($message, 'UTF-8') < 10) {
            $field_errors['message'] = "Nội dung liên hệ phải có ít nhất 10 ký tự.";
        }

        // Nếu tất cả các trường đều hợp lệ -> lưu vào cơ sở dữ liệu
        $has_error = false;
        foreach ($field_errors as $err) {
            if (!empty($err)) {
                $has_error = true;
                break;
            }
        }

        if (!$has_error) {
            // Chuẩn hóa số điện thoại lưu vào DB
            $clean_phone = preg_replace('/[\s\-\.]/', '', $phone);
            if (strpos($clean_phone, '+84') === 0) {
                $clean_phone = '0' . substr($clean_phone, 3);
            }

            // Chuẩn bị tin nhắn: gắn kèm chủ đề nếu khách có lựa chọn
            $db_message = $message;
            if (!empty($subject)) {
                $db_message = "[Chủ đề: " . $subject . "]\n\n" . $message;
            }

            // Prepared Statement chống SQL Injection
            $sql = "INSERT INTO contacts (FullName, Email, Phone, Message, Status, CreatedAt) VALUES (?, ?, ?, ?, 0, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $clean_phone, $db_message);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "Cảm ơn " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "! Yêu cầu của bạn đã được gửi thành công. Đội ngũ Miền Tây Travel sẽ liên hệ phản hồi sớm nhất.";
                    // Xóa trắng dữ liệu form khi gửi thành công
                    $form_data = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
                    // Đổi CSRF Token mới nhằm chống submit lặp
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $field_errors['message'] = "Hệ thống đang bận. Vui lòng thử lại sau hoặc gọi hotline.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $field_errors['message'] = "Có lỗi kết nối hệ thống. Vui lòng thử lại sau.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên Hệ - MIỀNTÂY TRAVEL | Dịch Vụ Du Lịch Chuyên Nghiệp</title>
    <meta name="description" content="Liên hệ Miền Tây Travel - Hotline: 0382 123 805. Địa chỉ: Xã Châu Thành, tỉnh Đồng Tháp. Tư vấn tour du lịch trọn gói, giải đáp thắc mắc và hỗ trợ khách hàng nhanh chóng.">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts: Inter đồng bộ với toàn bộ website -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- CSS Hệ Thống Của Project -->
    <link rel="stylesheet" href="style.css">

    <style>
        /* ═════════════════════════════════════════════════════════════
           GIAO DIỆN LIÊN HỆ TINH GỌN, HIỆN ĐẠI & ĐỒNG BỘ
        ═════════════════════════════════════════════════════════════ */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            background-color: var(--bg-main) !important;
            color: #334155;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1 0 auto;
        }

        /* ── BANNER GỌN GÀNG ── */
        .contact-hero-compact {
            background: linear-gradient(135deg, rgba(0, 40, 77, 0.9) 0%, rgba(0, 85, 143, 0.8) 100%),
                        url('https://images.unsplash.com/photo-1528127269322-539801943592?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;
            color: #FFFFFF;
            padding: 60px 0 50px 0;
            text-align: center;
        }

        .contact-hero-compact h1 {
            color: #FFFFFF !important;
            font-size: clamp(1.8rem, 3.5vw, 2.4rem);
            font-weight: 800;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .contact-hero-compact p {
            color: #E2E8F0;
            font-size: 1.05rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* ── BỐ CỤC 2 PHẦN: THÔNG TIN & BIỂU MẪU ── */
        .contact-card-box {
            background: #FFFFFF;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0, 40, 77, 0.06);
            overflow: hidden;
            margin-top: -30px;
            margin-bottom: 70px;
        }

        /* Khối thông tin liên hệ ngắn gọn bên trái */
        .contact-info-panel {
            background: linear-gradient(160deg, #00284D 0%, #004373 60%, #00558F 100%);
            color: #FFFFFF;
            padding: 45px 35px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .contact-info-panel h3 {
            color: #FFFFFF;
            font-size: 1.45rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .contact-info-panel p.sub {
            color: #CBD5E1;
            font-size: 0.92rem;
            line-height: 1.6;
            margin-bottom: 35px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 25px;
            color: #E2E8F0;
        }

        .info-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.12);
            color: #38BDF8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .info-content strong {
            display: block;
            color: #FFFFFF;
            font-size: 0.95rem;
            margin-bottom: 2px;
        }

        .info-content span, .info-content a {
            color: #CBD5E1;
            font-size: 0.9rem;
            line-height: 1.5;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .info-content a:hover {
            color: var(--orange);
        }

        .social-links {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .social-link-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .social-link-btn:hover {
            background: var(--orange);
            color: #FFFFFF;
            transform: translateY(-2px);
        }

        /* Biểu mẫu gửi liên hệ rõ ràng bên phải */
        .contact-form-panel {
            padding: 45px 40px;
            background: #FFFFFF;
        }

        .contact-form-panel h3 {
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .contact-form-panel p.sub {
            color: #64748B;
            font-size: 0.92rem;
            margin-bottom: 28px;
        }

        .form-label {
            font-size: 0.88rem;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 6px;
        }

        .form-control, .form-select {
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            padding: 11px 14px;
            font-size: 0.95rem;
            color: #0F172A;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--blue-bright);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
            outline: none;
        }

        /* ── FIELD ERROR LABELS (CHỮ ĐỎ NGAY DƯỚI Ô NHẬP) ── */
        .field-error {
            display: block;
            color: #DC2626;
            font-size: 0.825rem;
            font-weight: 500;
            margin-top: 5px;
            line-height: 1.4;
        }

        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #DC2626 !important;
            background-color: #FEF2F2;
        }

        .form-control.is-invalid:focus, .form-select.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15) !important;
        }

        /* Nút gửi form */
        .btn-submit-contact {
            background-color: var(--orange);
            color: #FFFFFF !important;
            border: none;
            font-weight: 700;
            padding: 13px 32px;
            border-radius: var(--radius-pill);
            font-size: 1rem;
            letter-spacing: 0.3px;
            transition: all 0.25s ease;
            box-shadow: 0 6px 16px rgba(255, 134, 46, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
        }

        .btn-submit-contact:hover {
            background-color: var(--orange-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(255, 134, 46, 0.4);
        }

        /* ── FOOTER ĐẢM BẢO HIỂN THỊ ĐẦY ĐỦ VÀ TRONG LUỒNG BÌNH THƯỜNG ── */
        footer.footer-premium {
            background-color: var(--navy) !important;
            color: #CBD5E1 !important;
            padding: 4.5rem 0 2rem 0 !important;
            position: static !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin-top: auto !important;
            width: 100% !important;
        }

        footer.footer-premium .col-md-6,
        footer.footer-premium .col-lg-4,
        footer.footer-premium .col-lg-2,
        footer.footer-premium .col-lg-3 {
            opacity: 1 !important;
            transform: none !important;
            visibility: visible !important;
        }

        @media (max-width: 991.98px) {
            .contact-info-panel, .contact-form-panel {
                padding: 35px 25px;
            }
            .contact-card-box {
                margin-top: -15px;
            }
        }
    </style>
</head>
<body>

    <!-- ══════════════════════ NAVBAR DÙNG CHUNG ══════════════════════ -->
    <?php include 'navbar.php'; ?>

    <main>
        <!-- ══════════════════════ BANNER LIÊN HỆ GỌN GÀNG ══════════════════════ -->
        <header class="contact-hero-compact">
            <div class="container">
                <span class="badge bg-white bg-opacity-25 px-3 py-2 rounded-pill text-white fw-bold mb-3 d-inline-block">
                    <i class="bi bi-headset me-1 text-warning"></i> HỖ TRỢ KHÁCH HÀNG 24/7
                </span>
                <h1>LIÊN HỆ VỚI CHÚNG TÔI</h1>
                <p>Bạn cần tư vấn hành trình, hỗ trợ đặt tour hoặc giải đáp thắc mắc? Hãy để lại thông tin để chúng tôi phục vụ bạn tốt nhất.</p>
            </div>
        </header>

        <!-- ══════════════════════ BỐ CỤC CHÍNH (THÔNG TIN & BIỂU MẪU) ══════════════════════ -->
        <section class="container">
            <div class="contact-card-box">
                <div class="row g-0">
                    <!-- Khu vực thông tin liên hệ ngắn gọn (Dữ liệu thật của dự án) -->
                    <div class="col-lg-5">
                        <div class="contact-info-panel">
                            <div>
                                <h3>Thông Tin Liên Hệ</h3>
                                <p class="sub">Kết nối trực tiếp với đội ngũ tư vấn viên của Miền Tây Travel qua các kênh thông tin chính thức.</p>

                                <div class="info-item">
                                    <div class="info-icon"><i class="bi bi-geo-alt"></i></div>
                                    <div class="info-content">
                                        <strong>Địa chỉ</strong>
                                        <span>Xã Châu Thành, tỉnh Đồng Tháp</span>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon"><i class="bi bi-telephone"></i></div>
                                    <div class="info-content">
                                        <strong>Số điện thoại</strong>
                                        <div><a href="tel:0382123805">0382 123 805</a></div>
                                    </div>
                                </div>


                            </div>

                            <div>
                                <div class="small text-white-50 mb-2">Mạng xã hội & Kênh kết nối:</div>
                                <div class="social-links">
                                    <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="social-link-btn" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                    <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="social-link-btn" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                                    <a href="https://tiktok.com" target="_blank" rel="noopener noreferrer" class="social-link-btn" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                                    <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="social-link-btn" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Khu vực biểu mẫu gửi liên hệ rõ ràng -->
                    <div class="col-lg-7">
                        <div class="contact-form-panel">
                            <h3>Gửi Tin Nhắn Cho Chúng Tôi</h3>
                            <p class="sub">Điền thông tin bên dưới, nhân viên hỗ trợ sẽ liên hệ lại với bạn trong thời gian sớm nhất.</p>

                            <!-- Thông báo thành công từ backend PHP -->
                            <?php if (!empty($success_msg)): ?>
                                <div class="alert alert-success alert-dismissible fade show border-0 rounded-4 p-3 mb-4 shadow-sm" role="alert" style="background-color: #ECFDF5; color: #065F46; border-left: 5px solid #10B981 !important;">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
                                        <div><?php echo $success_msg; ?></div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
                                </div>
                            <?php endif; ?>

                            <form action="contact.php" method="POST" id="contactForm" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                                <div class="row g-3">
                                    <!-- Họ và tên -->
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">
                                            Họ và tên <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               id="name" 
                                               name="name" 
                                               class="form-control <?php echo !empty($field_errors['name']) ? 'is-invalid' : ''; ?>" 
                                               placeholder="Ví dụ: Nguyễn Văn An"
                                               value="<?php echo htmlspecialchars($form_data['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                               aria-invalid="<?php echo !empty($field_errors['name']) ? 'true' : 'false'; ?>"
                                               aria-describedby="name-error"
                                               required>
                                        <label id="name-error" class="field-error" for="name" style="<?php echo empty($field_errors['name']) ? 'display: none;' : ''; ?>">
                                            <?php echo htmlspecialchars($field_errors['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </label>
                                    </div>

                                    <!-- Địa chỉ Email -->
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">
                                            Địa chỉ Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" 
                                               id="email" 
                                               name="email" 
                                               class="form-control <?php echo !empty($field_errors['email']) ? 'is-invalid' : ''; ?>" 
                                               placeholder="email@example.com"
                                               value="<?php echo htmlspecialchars($form_data['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                               aria-invalid="<?php echo !empty($field_errors['email']) ? 'true' : 'false'; ?>"
                                               aria-describedby="email-error"
                                               required>
                                        <label id="email-error" class="field-error" for="email" style="<?php echo empty($field_errors['email']) ? 'display: none;' : ''; ?>">
                                            <?php echo htmlspecialchars($field_errors['email'], ENT_QUOTES, 'UTF-8'); ?>
                                        </label>
                                    </div>

                                    <!-- Số điện thoại -->
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">
                                            Số điện thoại (10 chữ số) <span class="text-danger">*</span>
                                        </label>
                                        <input type="tel" 
                                               id="phone" 
                                               name="phone" 
                                               class="form-control <?php echo !empty($field_errors['phone']) ? 'is-invalid' : ''; ?>" 
                                               placeholder="Ví dụ: 0912345678"
                                               value="<?php echo htmlspecialchars($form_data['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                                               aria-invalid="<?php echo !empty($field_errors['phone']) ? 'true' : 'false'; ?>"
                                               aria-describedby="phone-error"
                                               required>
                                        <label id="phone-error" class="field-error" for="phone" style="<?php echo empty($field_errors['phone']) ? 'display: none;' : ''; ?>">
                                            <?php echo htmlspecialchars($field_errors['phone'], ENT_QUOTES, 'UTF-8'); ?>
                                        </label>
                                    </div>

                                    <!-- Chủ đề liên hệ -->
                                    <div class="col-md-6">
                                        <label for="subject" class="form-label">
                                            Chủ đề cần hỗ trợ
                                        </label>
                                        <select id="subject" name="subject" class="form-select">
                                            <option value="Tư vấn tour du lịch" <?php echo ($form_data['subject'] === 'Tư vấn tour du lịch' || empty($form_data['subject'])) ? 'selected' : ''; ?>>Tư vấn tour du lịch</option>
                                            <option value="Tư vấn & báo giá vé máy bay" <?php echo ($form_data['subject'] === 'Tư vấn & báo giá vé máy bay') ? 'selected' : ''; ?>>Tư vấn & báo giá vé máy bay</option>
                                            <option value="Tư vấn đặt phòng khách sạn" <?php echo ($form_data['subject'] === 'Tư vấn đặt phòng khách sạn') ? 'selected' : ''; ?>>Tư vấn đặt phòng khách sạn</option>
                                            <option value="Hỗ trợ đặt tour & giữ chỗ" <?php echo ($form_data['subject'] === 'Hỗ trợ đặt tour & giữ chỗ') ? 'selected' : ''; ?>>Hỗ trợ đặt tour & giữ chỗ</option>
                                            <option value="Thông tin thanh toán & hóa đơn" <?php echo ($form_data['subject'] === 'Thông tin thanh toán & hóa đơn') ? 'selected' : ''; ?>>Thông tin thanh toán & hóa đơn</option>
                                            <option value="Góp ý hoặc yêu cầu khác" <?php echo ($form_data['subject'] === 'Góp ý hoặc yêu cầu khác') ? 'selected' : ''; ?>>Góp ý hoặc yêu cầu khác</option>
                                        </select>
                                    </div>

                                    <!-- Nội dung liên hệ -->
                                    <div class="col-12">
                                        <label for="message" class="form-label">
                                            Nội dung liên hệ <span class="text-danger">*</span>
                                        </label>
                                        <textarea id="message" 
                                                  name="message" 
                                                  class="form-control <?php echo !empty($field_errors['message']) ? 'is-invalid' : ''; ?>" 
                                                  rows="4" 
                                                  placeholder="Vui lòng nhập nội dung câu hỏi hoặc yêu cầu cần hỗ trợ (tối thiểu 10 ký tự)..."
                                                  aria-invalid="<?php echo !empty($field_errors['message']) ? 'true' : 'false'; ?>"
                                                  aria-describedby="message-error"
                                                  required><?php echo htmlspecialchars($form_data['message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        <label id="message-error" class="field-error" for="message" style="<?php echo empty($field_errors['message']) ? 'display: none;' : ''; ?>">
                                            <?php echo htmlspecialchars($field_errors['message'], ENT_QUOTES, 'UTF-8'); ?>
                                        </label>
                                    </div>

                                    <!-- Nút Gửi liên hệ -->
                                    <div class="col-12 mt-4">
                                        <button type="submit" 
                                                name="send_message" 
                                                id="btnSubmit" 
                                                class="btn-submit-contact">
                                            <i class="bi bi-send-fill"></i> GỬI LIÊN HỆ
                                        </button>
                                        <div class="text-center text-muted small mt-2">
                                            <i class="bi bi-shield-check text-success"></i> Thông tin liên hệ được bảo mật theo quy định của Miền Tây Travel.
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>


    <!-- ══════════════════════ SCRIPTS & XÁC THỰC JAVASCRIPT PHÍA TRÌNH DUYỆT ══════════════════════ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contactForm');
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const messageInput = document.getElementById('message');

            const nameError = document.getElementById('name-error');
            const emailError = document.getElementById('email-error');
            const phoneError = document.getElementById('phone-error');
            const messageError = document.getElementById('message-error');

            // Regex kiểm tra email tiêu chuẩn RFC 5322 cơ bản
            const emailPattern = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;

            // Regex kiểm tra đầu số di động Việt Nam (10 số, bắt đầu bằng 0, tiếp theo là 3, 5, 7, 8, 9)
            const vnMobilePattern = /^0(3[2-9]|5[5689]|7[06-9]|8[1-9]|9[0-9])[0-9]{7}$/;

            function setFieldError(input, errorLabel, message) {
                if (message) {
                    input.classList.add('is-invalid');
                    input.setAttribute('aria-invalid', 'true');
                    errorLabel.textContent = message;
                    errorLabel.style.display = 'block';
                    return false;
                } else {
                    input.classList.remove('is-invalid');
                    input.setAttribute('aria-invalid', 'false');
                    errorLabel.textContent = '';
                    errorLabel.style.display = 'none';
                    return true;
                }
            }

            function validateName() {
                const val = nameInput.value.trim();
                if (val.length === 0) {
                    return setFieldError(nameInput, nameError, 'Vui lòng nhập họ tên.');
                }
                if (val.length < 2 || val.length > 100) {
                    return setFieldError(nameInput, nameError, 'Họ và tên cần có độ dài từ 2 đến 100 ký tự.');
                }
                return setFieldError(nameInput, nameError, '');
            }

            function validateEmail() {
                const val = emailInput.value.trim();
                if (val.length === 0) {
                    return setFieldError(emailInput, emailError, 'Vui lòng nhập địa chỉ email.');
                }
                if (!emailPattern.test(val)) {
                    return setFieldError(emailInput, emailError, 'Vui lòng nhập địa chỉ email hợp lệ.');
                }
                return setFieldError(emailInput, emailError, '');
            }

            function validatePhone() {
                let val = phoneInput.value.trim();
                if (val.length === 0) {
                    return setFieldError(phoneInput, phoneError, 'Vui lòng nhập số điện thoại.');
                }

                // Xóa khoảng trắng, dấu gạch nối, dấu chấm
                let cleaned = val.replace(/[\s\-\.]/g, '');

                // Chuẩn hóa +84 sang 0
                if (cleaned.startsWith('+84')) {
                    cleaned = '0' + cleaned.substring(3);
                }

                // Kiểm tra chỉ chứa ký tự số
                if (!/^\d+$/.test(cleaned)) {
                    return setFieldError(phoneInput, phoneError, 'Số điện thoại chỉ được chứa ký tự số.');
                }

                // Phải đúng 10 chữ số
                if (cleaned.length !== 10) {
                    return setFieldError(phoneInput, phoneError, 'Số điện thoại phải gồm đúng 10 chữ số.');
                }

                // Kiểm tra đầu số di động Việt Nam
                if (!vnMobilePattern.test(cleaned)) {
                    return setFieldError(phoneInput, phoneError, 'Đầu số di động không hợp lệ theo quy định nhà mạng Việt Nam.');
                }

                return setFieldError(phoneInput, phoneError, '');
            }

            function validateMessage() {
                const val = messageInput.value.trim();
                if (val.length === 0) {
                    return setFieldError(messageInput, messageError, 'Vui lòng nhập nội dung liên hệ.');
                }
                if (val.length < 10) {
                    return setFieldError(messageInput, messageError, 'Nội dung liên hệ phải có ít nhất 10 ký tự.');
                }
                return setFieldError(messageInput, messageError, '');
            }

            // Lắng nghe sự kiện input/blur để xóa hoặc hiển thị lỗi ngay tức thì
            nameInput.addEventListener('blur', validateName);
            nameInput.addEventListener('input', function() {
                if (nameInput.classList.contains('is-invalid')) validateName();
            });

            emailInput.addEventListener('blur', validateEmail);
            emailInput.addEventListener('input', function() {
                if (emailInput.classList.contains('is-invalid')) validateEmail();
            });

            phoneInput.addEventListener('blur', validatePhone);
            phoneInput.addEventListener('input', function() {
                if (phoneInput.classList.contains('is-invalid')) validatePhone();
            });

            messageInput.addEventListener('blur', validateMessage);
            messageInput.addEventListener('input', function() {
                if (messageInput.classList.contains('is-invalid')) validateMessage();
            });

            // Khi submit form: Kiểm tra toàn bộ, đưa con trỏ đến trường sai đầu tiên
            if (form) {
                form.addEventListener('submit', function(e) {
                    const isNameValid = validateName();
                    const isEmailValid = validateEmail();
                    const isPhoneValid = validatePhone();
                    const isMessageValid = validateMessage();

                    if (!isNameValid || !isEmailValid || !isPhoneValid || !isMessageValid) {
                        e.preventDefault();

                        // Focus vào ô sai đầu tiên
                        if (!isNameValid) {
                            nameInput.focus();
                        } else if (!isEmailValid) {
                            emailInput.focus();
                        } else if (!isPhoneValid) {
                            phoneInput.focus();
                        } else if (!isMessageValid) {
                            messageInput.focus();
                        }
                    }
                });
            }

        });
    </script>
</body>
</html>