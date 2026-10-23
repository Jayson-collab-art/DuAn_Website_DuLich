<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

$tab = in_array($_GET['tab'] ?? '', ['terms','privacy','cancel']) ? ($_GET['tab'] ?? 'terms') : 'terms';
$tab_titles = [
    'terms'   => 'Điều Khoản Sử Dụng',
    'privacy' => 'Chính Sách Bảo Mật',
    'cancel'  => 'Điều Kiện Hủy Tour',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tab_titles[$tab]) ?> - Miền Tây Travel</title>
    <meta name="description" content="Chính sách của Miền Tây Travel về điều khoản sử dụng, bảo mật thông tin và điều kiện hủy tour.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .policy-hero {
            background: linear-gradient(135deg, #00284D 0%, #00558F 60%, #0284C7 100%);
            color: white;
            padding: 60px 0 40px;
        }
        .policy-nav .nav-link {
            color: #334155;
            font-weight: 500;
            border-radius: 8px;
            padding: 0.65rem 1.2rem;
            transition: all 0.2s;
        }
        .policy-nav .nav-link.active {
            background: #00558F;
            color: white !important;
        }
        .policy-nav .nav-link:hover:not(.active) {
            background: #E0F2FE;
            color: #0284C7 !important;
        }
        .policy-content h2 { font-size: 1.4rem; color: #00284D; margin-top: 2rem; margin-bottom: 0.75rem; }
        .policy-content h3 { font-size: 1.1rem; color: #00558F; margin-top: 1.25rem; }
        .policy-content p, .policy-content li { color: #475569; line-height: 1.8; }
        .policy-content ul { padding-left: 1.5rem; }
        .policy-content li { margin-bottom: 0.35rem; }
        .update-info { background: #E0F2FE; border-left: 4px solid #0284C7; padding: 12px 16px; border-radius: 0 8px 8px 0; font-size: 0.875rem; color: #0369A1; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<!-- HERO -->
<div class="policy-hero">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0" style="background:none; font-size:0.85rem;">
                <li class="breadcrumb-item"><a href="index.php" style="color:rgba(255,255,255,0.75);">Trang chủ</a></li>
                <li class="breadcrumb-item active text-white"><?= htmlspecialchars($tab_titles[$tab]) ?></li>
            </ol>
        </nav>
        <h1 class="fw-bold mb-1" style="font-size:2rem; color:#fff;"><?= htmlspecialchars($tab_titles[$tab]) ?></h1>
        <p style="color:rgba(255,255,255,0.8);">Miền Tây Travel – Xã Châu Thành, tỉnh Đồng Tháp &nbsp;|&nbsp; Hotline: <a href="tel:0382123805" style="color:#FFE082;">0382 123 805</a></p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- NAV TABS -->
        <div class="col-lg-3">
            <div class="policy-nav d-flex flex-column gap-1 sticky-top" style="top:90px;">
                <a href="policy.php?tab=terms"   class="nav-link <?= $tab==='terms'   ? 'active' : '' ?>"><i class="bi bi-file-text me-2"></i>Điều khoản sử dụng</a>
                <a href="policy.php?tab=privacy" class="nav-link <?= $tab==='privacy' ? 'active' : '' ?>"><i class="bi bi-shield-lock me-2"></i>Chính sách bảo mật</a>
                <a href="policy.php?tab=cancel"  class="nav-link <?= $tab==='cancel'  ? 'active' : '' ?>"><i class="bi bi-x-circle me-2"></i>Điều kiện hủy tour</a>
                <hr>
                <a href="contact.php" class="nav-link"><i class="bi bi-headset me-2"></i>Hỗ trợ khách hàng</a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="col-lg-9 policy-content">

            <?php if ($tab === 'terms'): ?>
            <div class="update-info mb-4"><i class="bi bi-calendar3 me-1"></i> Cập nhật lần cuối: tháng 10/2026</div>
            <p>Bằng cách sử dụng website <strong>mientaytravel.vn</strong>, bạn đồng ý tuân thủ các điều khoản sau đây. Vui lòng đọc kỹ trước khi sử dụng dịch vụ.</p>

            <h2>1. Phạm vi dịch vụ</h2>
            <p>Miền Tây Travel cung cấp dịch vụ đặt tour du lịch nội địa thông qua website. Chúng tôi không cung cấp dịch vụ vé máy bay, khách sạn độc lập nếu không ghi rõ trong từng gói tour cụ thể.</p>

            <h2>2. Tài khoản người dùng</h2>
            <ul>
                <li>Bạn chịu trách nhiệm bảo mật thông tin đăng nhập của mình.</li>
                <li>Không chia sẻ tài khoản cho người khác sử dụng.</li>
                <li>Chúng tôi có quyền khóa tài khoản nếu phát hiện hành vi vi phạm.</li>
            </ul>

            <h2>3. Đặt tour và thanh toán</h2>
            <ul>
                <li>Đơn đặt tour chỉ được xác nhận sau khi thanh toán thành công và admin phê duyệt.</li>
                <li>Giá tour được hiển thị tại thời điểm đặt là giá áp dụng cho đơn hàng đó.</li>
                <li>Thanh toán qua cổng VNPay Sandbox trong giai đoạn thử nghiệm.</li>
            </ul>

            <h2>4. Quyền và giới hạn trách nhiệm</h2>
            <p>Miền Tây Travel không chịu trách nhiệm về sự cố bất khả kháng như thiên tai, dịch bệnh hoặc thay đổi chính sách của điểm đến. Trong trường hợp đó, chúng tôi sẽ thông báo và phối hợp xử lý đơn hàng theo quy định hiện hành.</p>

            <h2>5. Thay đổi điều khoản</h2>
            <p>Chúng tôi có thể cập nhật điều khoản này bất cứ lúc nào. Phiên bản mới nhất luôn được công bố trên trang này.</p>

            <?php elseif ($tab === 'privacy'): ?>
            <div class="update-info mb-4"><i class="bi bi-calendar3 me-1"></i> Cập nhật lần cuối: tháng 10/2026</div>
            <p>Miền Tây Travel cam kết bảo vệ quyền riêng tư của bạn. Chính sách này mô tả cách chúng tôi thu thập, sử dụng và bảo vệ thông tin cá nhân.</p>

            <h2>1. Thông tin chúng tôi thu thập</h2>
            <ul>
                <li><strong>Thông tin bạn cung cấp:</strong> Họ tên, số điện thoại, email khi đăng ký tài khoản hoặc đặt tour.</li>
                <li><strong>Thông tin thanh toán:</strong> Chúng tôi không lưu trữ thông tin thẻ ngân hàng. Thanh toán được xử lý bởi VNPay.</li>
                <li><strong>Thông tin kỹ thuật:</strong> Địa chỉ IP, loại trình duyệt, trang được truy cập – phục vụ mục đích thống kê và bảo mật.</li>
            </ul>

            <h2>2. Mục đích sử dụng thông tin</h2>
            <ul>
                <li>Xử lý đơn đặt tour và liên hệ xác nhận.</li>
                <li>Gửi thông tin về tour mới hoặc chương trình ưu đãi (nếu bạn đồng ý nhận).</li>
                <li>Cải thiện chất lượng dịch vụ.</li>
            </ul>

            <h2>3. Chia sẻ thông tin</h2>
            <p>Chúng tôi không bán, trao đổi hoặc chuyển giao thông tin cá nhân của bạn cho bên thứ ba, trừ khi cần thiết để xử lý đơn hàng (ví dụ: cổng thanh toán VNPay) hoặc theo yêu cầu pháp luật.</p>

            <h2>4. Bảo mật dữ liệu</h2>
            <ul>
                <li>Mật khẩu được mã hóa bằng bcrypt trước khi lưu trữ.</li>
                <li>Session được cấu hình HttpOnly và SameSite để chống trộm session.</li>
                <li>Header bảo mật (X-Frame-Options, X-Content-Type-Options) được áp dụng.</li>
            </ul>

            <h2>5. Quyền của bạn</h2>
            <p>Bạn có quyền yêu cầu xem, sửa hoặc xóa thông tin cá nhân của mình bằng cách liên hệ qua số điện thoại <a href="tel:0382123805">0382 123 805</a>.</p>

            <?php elseif ($tab === 'cancel'): ?>
            <div class="update-info mb-4"><i class="bi bi-calendar3 me-1"></i> Cập nhật lần cuối: tháng 10/2026</div>
            <p>Dưới đây là chính sách hủy tour và hoàn tiền của Miền Tây Travel. Chúng tôi khuyến khích bạn đọc kỹ trước khi đặt tour.</p>

            <h2>1. Điều kiện hủy tour</h2>
            <ul>
                <li>Hủy trước ngày khởi hành <strong>≥ 7 ngày</strong>: Hoàn 80% tổng giá trị đơn.</li>
                <li>Hủy trước <strong>3–6 ngày</strong>: Hoàn 50% tổng giá trị đơn.</li>
                <li>Hủy trong vòng <strong>72 giờ</strong> trước khởi hành: Không hoàn tiền.</li>
            </ul>

            <h2>2. Quy trình hủy tour</h2>
            <ol>
                <li>Đăng nhập vào tài khoản và vào mục <strong>Đơn hàng của tôi</strong>.</li>
                <li>Chọn đơn hàng cần hủy và thực hiện yêu cầu hủy.</li>
                <li>Liên hệ xác nhận qua số điện thoại <a href="tel:0382123805">0382 123 805</a> để được xử lý nhanh.</li>
                <li>Admin xét duyệt và cập nhật trạng thái đơn trong vòng 1–2 ngày làm việc.</li>
            </ol>

            <h2>3. Hoàn tiền</h2>
            <ul>
                <li>Với đơn thanh toán qua VNPay: Hoàn tiền qua ngân hàng trong 5–7 ngày làm việc.</li>
                <li>Với đơn thanh toán tiền mặt: Liên hệ trực tiếp để sắp xếp hoàn tiền.</li>
            </ul>

            <h2>4. Trường hợp đặc biệt</h2>
            <p>Trong trường hợp bất khả kháng (thiên tai, dịch bệnh, lệnh phong tỏa từ cơ quan chức năng), Miền Tây Travel sẽ xem xét hoàn tiền toàn bộ hoặc chuyển ngày đi theo thỏa thuận với khách hàng.</p>

            <div class="alert alert-warning mt-4">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Lưu ý:</strong> Chính sách trên áp dụng cho tour được đặt trực tiếp qua website. Với tour đặt theo nhóm hoặc có điều kiện đặc biệt, vui lòng xác nhận điều khoản cụ thể với nhân viên trước khi thanh toán.
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- FOOTER -->
<?php include 'footer.php'; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
