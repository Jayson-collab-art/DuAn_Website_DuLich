<?php
// footer.php - Footer chuẩn dùng chung cho website Miền Tây Travel
?>
<style>
/* ── FOOTER STYLING BẢO ĐẢM HIỂN THỊ ĐẦY ĐỦ, CHỮ TO RÕ RÀNG VÀ SẮC NÉT ── */
footer.footer-premium {
    background-color: #00284D !important;
    color: #CBD5E1 !important;
    padding: 55px 0 25px 0 !important;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    position: relative !important;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    width: 100% !important;
    margin-top: auto !important;
}

/* Triệt tiêu hoàn toàn opacity: 0 hoặc transform ẩn từ bất kỳ nguồn CSS nào */
footer.footer-premium,
footer.footer-premium *,
footer.footer-premium .container,
footer.footer-premium .row,
footer.footer-premium [class*="col-"] {
    visibility: visible !important;
}

footer.footer-premium .col-md-6,
footer.footer-premium .col-lg-5,
footer.footer-premium .col-lg-4,
footer.footer-premium .col-lg-3,
footer.footer-premium .col-lg-2,
footer.footer-premium .col-sm-6,
footer.footer-premium .col-12 {
    opacity: 1 !important;
    transform: none !important;
    display: block !important;
}

/* Tiêu đề các cột trong footer */
footer.footer-premium h5.footer-heading {
    color: #FFFFFF !important;
    font-size: 1.2rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.5px;
    margin-bottom: 1.4rem !important;
    text-transform: uppercase;
}

/* Tên thương hiệu */
footer.footer-premium .brand-name {
    color: #FFFFFF !important;
    font-size: 1.5rem !important;
    font-weight: 800 !important;
    letter-spacing: 0.5px;
}

/* Thông tin liên hệ (Địa chỉ, Số điện thoại) */
footer.footer-premium .footer-contact-item {
    font-size: 1.05rem !important;
    color: #CBD5E1 !important;
    line-height: 1.6 !important;
    margin-bottom: 0.6rem !important;
}

/* Các liên kết trang */
footer.footer-premium .footer-link {
    display: block;
    color: #CBD5E1 !important;
    text-decoration: none;
    margin-bottom: 11px !important;
    font-size: 1.05rem !important;
    font-weight: 400;
    transition: color 0.2s ease, transform 0.2s ease;
}

footer.footer-premium .footer-link:hover {
    color: #FF862E !important;
    transform: translateX(4px);
    text-decoration: none;
}

/* Hotline */
footer.footer-premium .phone-link {
    color: #FFFFFF !important;
    font-weight: 700 !important;
    text-decoration: none;
    font-size: 1.25rem !important;
    transition: color 0.2s ease;
}

footer.footer-premium .phone-link:hover {
    color: #FF862E !important;
    text-decoration: underline;
}

/* Mạng xã hội */
footer.footer-premium .social-icon {
    width: 42px !important;
    height: 42px !important;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    color: #FFFFFF !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 1.25rem !important;
    transition: all 0.25s ease;
}

footer.footer-premium .social-icon:hover {
    background: #FF862E !important;
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(255, 134, 46, 0.4);
}

/* Bản quyền chân trang */
footer.footer-premium .copyright-text {
    font-size: 0.98rem !important;
    color: #94A3B8 !important;
}
</style>

<footer class="footer-premium mt-5">
    <div class="container text-md-start text-center">
        <div class="row gy-4 mb-4">
            
            <!-- CỘT 1: THƯƠNG HIỆU VÀ LIÊN HỆ -->
            <div class="col-lg-5 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-center gap-2 justify-content-md-start justify-content-center mb-3">
                    <img src="https://cdn-icons-png.flaticon.com/128/5968/5968879.png" width="38" height="38" style="filter: brightness(0) invert(1);" alt="Miền Tây Travel Logo">
                    <span class="brand-name">MIỀNTÂY TRAVEL</span>
                </div>
                <p class="footer-contact-item mb-2">
                    <i class="bi bi-geo-alt-fill me-2 text-warning fs-5 align-middle"></i>
                    Địa chỉ: Xã Châu Thành, tỉnh Đồng Tháp
                </p>
                <p class="footer-contact-item mb-3">
                    <i class="bi bi-telephone-fill me-2 text-warning fs-5 align-middle"></i>
                    Số điện thoại: <a href="tel:0382123805" class="phone-link">0382123805</a>
                </p>
                <div class="d-flex mt-3 justify-content-center justify-content-md-start gap-2">
                    <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://tiktok.com" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                    <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>

            <!-- CỘT 2: THÔNG TIN -->
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3 mb-lg-0">
                <h5 class="footer-heading">THÔNG TIN</h5>
                <a href="about.php" class="footer-link">Về chúng tôi</a>
                <a href="news.php" class="footer-link">Tin tức du lịch</a>
                <a href="news.php?category=C%E1%BA%A9m+nang" class="footer-link">Cẩm nang du lịch</a>
                <a href="contact.php" class="footer-link">Hỗ trợ khách hàng</a>
            </div>

            <!-- CỘT 3: CHÍNH SÁCH -->
            <div class="col-lg-4 col-md-6 col-sm-6 mb-3 mb-lg-0">
                <h5 class="footer-heading">CHÍNH SÁCH</h5>
                <a href="policy.php?tab=terms" class="footer-link">Điều khoản sử dụng</a>
                <a href="policy.php?tab=privacy" class="footer-link">Chính sách bảo mật</a>
                <a href="policy.php?tab=cancel" class="footer-link">Điều kiện hủy tour</a>
            </div>

        </div>

        <!-- PHÂN CÁCH VÀ BẢN QUYỀN -->
        <div class="border-top border-secondary pt-3 mt-4 text-center copyright-text" style="border-color: rgba(255, 255, 255, 0.12) !important;">
            Bản quyền của Miền Tây Travel ® 2026. Bảo lưu mọi quyền.
        </div>
    </div>
</footer>

<!-- ══════════════════════ AI TRAVEL ASSISTANT WIDGET ══════════════════════ -->
<?php if (!isset($hide_chatbot) || !$hide_chatbot): ?>
<div id="chat-circle" style="position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; z-index: 1050; cursor: pointer; transition: 0.3s;" title="Trợ lý du lịch AI">
    <div style="width:100%;height:100%;border-radius:50%;background:linear-gradient(135deg, var(--blue, #00558F) 0%, var(--orange, #FF862E) 100%);display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:#fff;box-shadow:0 10px 25px rgba(0,85,143,0.4); border: 2px solid white; animation: pulseChat 2s infinite;">
        <i class="bi bi-robot"></i>
    </div>
    <span style="position:absolute;top:0;right:0;width:14px;height:14px;background:#10B981;border-radius:50%;border:2px solid #fff;"></span>
</div>

<style>
@keyframes pulseChat {
    0% { box-shadow: 0 0 0 0 rgba(255,134,46,0.6); }
    70% { box-shadow: 0 0 0 15px rgba(255,134,46,0); }
    100% { box-shadow: 0 0 0 0 rgba(255,134,46,0); }
}
@media (max-width: 575.98px) {
    #chat-box {
        width: calc(100vw - 30px) !important;
        right: 15px !important;
        bottom: 85px !important;
        height: 70vh !important;
    }
}
</style>

<div id="chat-box" class="card shadow-lg border-0" style="position: fixed; bottom: 100px; right: 30px; width: 360px; height: 500px; display: none; z-index: 1050; border-radius: 20px; overflow: hidden; background: #fff; box-shadow: 0 20px 40px rgba(0,0,0,0.2) !important;">
    <div class="card-header text-white d-flex justify-content-between align-items-center py-3" style="background: linear-gradient(90deg, #00284D, #00558F); border-bottom: none;">
        <span class="fw-bold d-flex align-items-center gap-2"><i class="bi bi-robot fs-5"></i> Trợ lý du lịch AI</span>
        <i class="bi bi-x-lg" id="close-chat" style="cursor: pointer; opacity: 0.8; transition: 0.2s;" title="Đóng"></i>
    </div>
    <div class="card-body" id="chat-content" style="overflow-y: auto; background: rgba(248, 250, 252, 0.95); height: calc(100% - 130px);">
        <div class="bot-msg mb-3"><span class="p-2 px-3 rounded-4 bg-white shadow-sm d-inline-block small border border-light" style="border-bottom-left-radius: 4px !important;">Chào bạn! Mình là Trợ lý AI của Miền Tây Travel. Bạn cần mình tư vấn tour nào không?</span></div>
        <div class="d-flex flex-wrap gap-2 mt-2 mb-3">
            <span class="badge bg-white text-primary border shadow-sm p-2" style="cursor:pointer;" onclick="fillSuggest('Tour giờ chót')">Tour giờ chót</span>
            <span class="badge bg-white text-primary border shadow-sm p-2" style="cursor:pointer;" onclick="fillSuggest('Tìm tour giá rẻ')">Tìm tour giá rẻ</span>
            <span class="badge bg-white text-primary border shadow-sm p-2" style="cursor:pointer;" onclick="fillSuggest('Hotline liên hệ')">Hotline liên hệ</span>
        </div>
    </div>
    <div class="card-footer bg-white border-top p-3">
        <div class="input-group">
            <input type="text" id="user-input" class="form-control bg-light rounded-pill border-0 ps-3" placeholder="Nhập câu hỏi..." style="box-shadow: none;">
            <button class="btn btn-primary rounded-circle ms-2" id="send-chat-btn" onclick="sendMsg()" style="background: #00558F; border: none; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-send-fill"></i></button>
        </div>
    </div>
</div>

<script>
(function() {
    function initChatWidget() {
        const circle = document.getElementById('chat-circle');
        const box = document.getElementById('chat-box');
        const closeBtn = document.getElementById('close-chat');
        const input = document.getElementById('user-input');
        const sendBtn = document.getElementById('send-chat-btn');
        let isSending = false;

        if (!circle || !box) return;

        function toggle() {
            if (box.style.display === 'none' || box.style.display === '') {
                box.style.display = 'flex';
                box.style.flexDirection = 'column';
                if (input) input.focus();
            } else {
                box.style.display = 'none';
            }
        }

        circle.onclick = toggle;
        if (closeBtn) closeBtn.onclick = toggle;

        window.fillSuggest = function(txt) {
            if (input) {
                input.value = txt;
                input.focus();
            }
        };

        window.sendMsg = async function() {
            if (!input || isSending) return;
            const msg = input.value.trim();
            const content = document.getElementById('chat-content');
            if (!msg || !content) return;

            isSending = true;
            if (sendBtn) sendBtn.disabled = true;
            input.disabled = true;

            content.innerHTML += `<div class="text-end mb-3"><span class="p-2 px-3 rounded-4 bg-primary text-white d-inline-block small shadow-sm" style="border-bottom-right-radius: 4px !important; background: #00558F !important;">${msg.replace(/</g, '&lt;')}</span></div>`;
            input.value = '';
            content.scrollTop = content.scrollHeight;

            const loadingId = 'loading-' + Date.now();
            content.innerHTML += `<div class="bot-msg mb-3" id="${loadingId}"><span class="p-2 px-3 rounded-4 bg-white shadow-sm d-inline-block small border border-light" style="border-bottom-left-radius: 4px !important;"><div class="spinner-grow spinner-grow-sm text-primary" role="status"></div> Đang tra cứu...</span></div>`;
            content.scrollTop = content.scrollHeight;

            try {
                const response = await fetch('chat_ai.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: msg })
                });
                const result = await response.json();
                const loader = document.getElementById(loadingId);
                if (loader) loader.remove();

                const replyText = (result && result.reply) ? result.reply.replace(/\n/g, '<br>') : 'Không có phản hồi từ máy chủ.';
                content.innerHTML += `<div class="bot-msg mb-3"><span class="p-2 px-3 rounded-4 bg-white shadow-sm d-inline-block small border border-light" style="border-bottom-left-radius: 4px !important;">${replyText}</span></div>`;
                content.scrollTop = content.scrollHeight;
            } catch (err) {
                const loader = document.getElementById(loadingId);
                if (loader) loader.innerHTML = `<span class="p-2 px-3 rounded-4 bg-white shadow-sm d-inline-block small text-danger">Lỗi kết nối máy chủ! Vui lòng thử lại.</span>`;
            } finally {
                isSending = false;
                if (sendBtn) sendBtn.disabled = false;
                input.disabled = false;
                input.focus();
            }
        };

        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    window.sendMsg();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChatWidget);
    } else {
        initChatWidget();
    }
})();
</script>
<?php endif; ?>
