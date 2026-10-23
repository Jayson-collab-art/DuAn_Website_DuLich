<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
include 'db.php';

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['reply' => 'Phương thức không hợp lệ. Chỉ chấp nhận POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy tin nhắn người dùng (hỗ trợ cả JSON body và POST form data)
$rawBody = file_get_contents('php://input');
$dataInput = !empty($rawBody) ? json_decode($rawBody, true) : null;
$userMessage = '';

if (is_array($dataInput) && isset($dataInput['message'])) {
    $userMessage = trim((string)$dataInput['message']);
} elseif (isset($_POST['message'])) {
    $userMessage = trim((string)$_POST['message']);
}

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Chào bạn! Mình là Trợ lý AI của Miền Tây Travel. Bạn cần mình tư vấn lịch trình hoặc kiểm tra tour du lịch nào không?'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (mb_strlen($userMessage, 'UTF-8') > 1000) {
    echo json_encode(['reply' => 'Câu hỏi hơi dài (tối đa 1000 ký tự). Bạn hãy rút gọn câu hỏi để mình hỗ trợ nhanh nhất nhé!'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Hàm sinh câu trả lời thông minh từ Dữ Liệu Thật trong Database ──
function getSmartFallbackReply($conn, $userMsg) {
    $msgLower = mb_strtolower($userMsg, 'UTF-8');

    // 1. Hỏi về thông tin liên hệ / địa chỉ / hotline
    if (preg_match('/(liên hệ|hotline|số điện thoại|sđt|địa chỉ|ở đâu|công ty|văn phòng|trụ sở)/ui', $msgLower)) {
        return "🏢 **Thông tin liên hệ chính thức Miền Tây Travel:**\n"
            . "• Địa chỉ: Xã Châu Thành, tỉnh Đồng Tháp\n"
            . "• Hotline hỗ trợ 24/7: 0382 123 805 (bấm để gọi trực tiếp)\n"
            . "• Bạn có thể gửi yêu cầu trực tuyến tại trang [Liên Hệ](contact.php) để được nhân viên tư vấn chi tiết!";
    }

    // 2. Hỏi về Khách sạn / Vé máy bay (Tuân thủ Nguyên tắc: Không bịa dữ liệu)
    if (preg_match('/(khách sạn|hotel|phòng nghỉ|resort|homestay)/ui', $msgLower)) {
        return "🏨 Miền Tây Travel cung cấp tiêu chuẩn phòng nghỉ/khách sạn đã bao gồm trọn gói theo từng hành trình tour.\n"
            . "Hệ thống hiện tại chưa kết nối cổng đặt phòng khách sạn lẻ trực tiếp từ các đơn vị lưu trú.\n"
            . "👉 Quý khách có nhu cầu đặt phòng riêng hoặc nâng hạng phòng, vui lòng liên hệ Hotline: **0382 123 805** để được nhân viên hỗ trợ nhanh nhất!";
    }
    if (preg_match('/(vé máy bay|chuyến bay|hàng không|flight|sân bay)/ui', $msgLower)) {
        return "✈️ Miền Tây Travel hiện chưa tích hợp cổng tra cứu vé máy bay trực tiếp từ các hãng hàng không.\n"
            . "Các tour đường bay xa (Đà Nẵng, Hà Nội, Phú Quốc...) đã bao gồm vé máy bay khứ hồi theo gói tour.\n"
            . "👉 Quý khách cần tra cứu giá vé máy bay lẻ hoặc đặt theo đoàn, vui lòng liên hệ Hotline: **0382 123 805** để kiểm tra theo lịch khởi hành thực tế từ hãng!";
    }

    // 3. Hỏi về Tour Giờ Chót / Giảm giá / Khuyến mãi
    if (preg_match('/(giờ chót|flash sale|giảm giá|khuyến mãi|ưu đãi|sale)/ui', $msgLower)) {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT t.TourName, fs.DiscountPrice, t.Price, fs.SaleEnd, fs.Quota, fs.Sold 
                FROM flash_sales fs 
                JOIN tours t ON fs.TourID = t.TourID 
                WHERE fs.status = 'Active' 
                  AND fs.SaleEnd > '$now' 
                  AND fs.SaleStart <= '$now' 
                  AND fs.Sold < fs.Quota 
                ORDER BY fs.DiscountPrice ASC LIMIT 3";
        $res = mysqli_query($conn, $sql);
        if ($res && mysqli_num_rows($res) > 0) {
            $reply = "🔥 **Các chương trình Tour Giờ Chót đang mở:**\n";
            while ($row = mysqli_fetch_assoc($res)) {
                $left = max(0, (int)$row['Quota'] - (int)$row['Sold']);
                $reply .= "• **" . htmlspecialchars($row['TourName']) . "**: Giá ưu đãi **" . number_format($row['DiscountPrice'], 0, ',', '.') . "đ** (Giá gốc: " . number_format($row['Price'], 0, ',', '.') . "đ) — Còn " . $left . " suất\n";
            }
            $reply .= "\n👉 Quý khách có thể xem và đặt ngay tại mục **Tour Giờ Chót** trên trang chủ!";
            return $reply;
        } else {
            return "Dạ hiện tại chưa có chương trình Tour Giờ Chót nào đang diễn ra. Bạn có thể tham khảo danh sách các tour du lịch giá tốt tại trang chủ hoặc liên hệ Hotline **0382 123 805**!";
        }
    }

    // 4. Hỏi tour giá rẻ / rẻ nhất
    if (preg_match('/(giá rẻ|rẻ nhất|tiết kiệm|chi phí thấp)/ui', $msgLower)) {
        $sql = "SELECT TourID, TourName, Price, Duration, DepartureLocation FROM tours ORDER BY Price ASC LIMIT 3";
        $res = mysqli_query($conn, $sql);
        if ($res && mysqli_num_rows($res) > 0) {
            $reply = "💰 **Top tour có mức giá tốt nhất hiện tại:**\n";
            while ($row = mysqli_fetch_assoc($res)) {
                $reply .= "• **" . htmlspecialchars($row['TourName']) . "** — Giá chỉ: **" . number_format($row['Price'], 0, ',', '.') . "đ** (" . ($row['Duration'] ?? 'Trong ngày') . ")\n";
            }
            $reply .= "\n👉 Bạn có thể xem chi tiết và đặt chỗ ngay tại danh sách tour trên website!";
            return $reply;
        }
    }

    // 5. Tìm kiếm theo địa danh / tên điểm đến trong database
    $keywords = ['đà nẵng', 'phú quốc', 'cần thơ', 'miền tây', 'hà nội', 'hạ long', 'đà lạt', 'sapa', 'nha trang', 'huế', 'châu đốc', 'đồng tháp', 'an giang', 'bến tre', 'tiền giang', 'cà mau', 'vĩnh long', 'bạc liêu', 'sóc trăng'];
    $matchedKw = '';
    foreach ($keywords as $kw) {
        if (mb_strpos($msgLower, $kw) !== false) {
            $matchedKw = $kw;
            break;
        }
    }

    if (!empty($matchedKw)) {
        $safeKw = mysqli_real_escape_string($conn, $matchedKw);
        $sql = "SELECT TourID, TourName, Price, Duration, DepartureLocation 
                FROM tours 
                WHERE TourName LIKE '%$safeKw%' OR Description LIKE '%$safeKw%' OR DepartureLocation LIKE '%$safeKw%'
                LIMIT 4";
        $res = mysqli_query($conn, $sql);
        if ($res && mysqli_num_rows($res) > 0) {
            $reply = "🏝️ **Các hành trình " . mb_convert_case($matchedKw, MB_CASE_TITLE, "UTF-8") . " hiện có:**\n";
            while ($row = mysqli_fetch_assoc($res)) {
                $reply .= "• **" . htmlspecialchars($row['TourName']) . "** | Giá: " . number_format($row['Price'], 0, ',', '.') . "đ | Thời lượng: " . ($row['Duration'] ?? 'Theo lịch') . "\n";
            }
            $reply .= "\n👉 Quý khách bấm vào tour trên website để xem lịch trình chi tiết và đặt tour nhé!";
            return $reply;
        } else {
            return "Dạ hiện tại hệ thống chưa có sẵn tour cố định đến **" . mb_convert_case($matchedKw, MB_CASE_TITLE, "UTF-8") . "**. Quý khách có thể liên hệ Hotline **0382 123 805** để được thiết kế tour riêng theo yêu cầu!";
        }
    }

    // 6. Hướng dẫn đặt tour và thanh toán
    if (preg_match('/(đặt tour|thanh toán|vnpay|cách đặt|hướng dẫn|chuyển khoản|tiền mặt)/ui', $msgLower)) {
        return "📝 **Quy trình đặt tour tại Miền Tây Travel:**\n"
            . "1. Chọn tour bạn yêu thích và bấm **Xem chi tiết**.\n"
            . "2. Chọn ngày khởi hành và số lượng khách.\n"
            . "3. Chọn phương thức: **Thanh toán tại văn phòng** hoặc **VNPay (ATM/QR/Visa)**.\n"
            . "4. Bấm **Xác nhận đặt tour**. Quý khách có thể theo dõi tình trạng đơn tại mục **Đơn hàng của tôi**!";
    }

    // 7. Câu hỏi chung / danh sách tour nổi bật
    $sql = "SELECT TourName, Price, Duration FROM tours ORDER BY TourID DESC LIMIT 3";
    $res = mysqli_query($conn, $sql);
    $reply = "Chào bạn! Miền Tây Travel rất hân hạnh được đồng hành cùng bạn trên mọi nẻo đường sông nước.\n"
        . "Một số tour nổi bật hiện có trong hệ thống:\n";
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $reply .= "• " . htmlspecialchars($row['TourName']) . " (" . number_format($row['Price'], 0, ',', '.') . "đ - " . ($row['Duration'] ?? 'N/A') . ")\n";
        }
    }
    $reply .= "\nBạn có thể hỏi mình về: Tour giờ chót, Tour giá rẻ, Điểm đến cụ thể hoặc liên hệ Hotline: **0382 123 805** (Xã Châu Thành, tỉnh Đồng Tháp)!";
    return $reply;
}

// ── Cố gắng gọi API Gemini nếu có API Key hợp lệ được cấu hình qua môi trường ──
$apiKey = trim((string)(getenv('GEMINI_API_KEY') ?: ''));
$useApi = (!empty($apiKey) && strlen($apiKey) > 20);

if ($useApi) {
    // Đọc danh sách tour thật từ DB để nạp bối cảnh
    $sql_tours = "SELECT TourName, Price, Duration, DepartureLocation FROM tours ORDER BY TourID DESC LIMIT 15";
    $res_tours = mysqli_query($conn, $sql_tours);
    $tour_data = "";
    if ($res_tours) {
        while ($t = mysqli_fetch_assoc($res_tours)) {
            $tour_data .= "- " . $t['TourName'] . " | Giá: " . number_format($t['Price']) . "đ | Khởi hành: " . ($t['DepartureLocation'] ?? 'N/A') . "\n";
        }
    }

    $prompt = "Bạn là trợ lý du lịch AI của Miền Tây Travel (Đ/C: Xã Châu Thành, tỉnh Đồng Tháp - Hotline: 0382 123 805).\n"
        . "DANH SÁCH TOUR THỰC TẾ TRONG HỆ THỐNG:\n" . ($tour_data ?: "(Không có tour)") . "\n"
        . "QUY TẮC BẮT BUỘC:\n"
        . "1. Chỉ tư vấn tour có trong danh sách trên, tuyệt đối không bịa tour không có thật.\n"
        . "2. KHÔNG bịa giá vé máy bay hoặc khách sạn riêng lẻ. Nếu khách hỏi, hướng dẫn liên hệ Hotline 0382 123 805.\n"
        . "3. Trả lời bằng tiếng Việt thân thiện, súc tích và chính xác.\n"
        . "Khách hỏi: " . $userMessage;

    $model = "gemini-1.5-flash";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

    $payload = [
        "contents" => [["parts" => [["text" => $prompt]]]]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if (!$err && $httpCode === 200 && !empty($response)) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $reply = trim($result['candidates'][0]['content']['parts'][0]['text']);
            echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

// Nếu không có API Key, API lỗi hoặc timeout: Dùng hệ thống trả lời thông minh dựa trên dữ liệu thật từ DB
$fallbackReply = getSmartFallbackReply($conn, $userMessage);
echo json_encode(['reply' => $fallbackReply], JSON_UNESCAPED_UNICODE);
exit;
?>