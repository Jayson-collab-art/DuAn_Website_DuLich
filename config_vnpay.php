<?php
// ÉP MÚI GIỜ VIỆT NAM ĐỂ KHÔNG BỊ VNPAY BÁO HẾT HẠN
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình môi trường VNPAY Sandbox của bạn
define('VNPAY_TMN_CODE', 'CJ0S7YG7'); 
define('VNPAY_HASH_SECRET', 'LIUCMQIFSMEWRTABYWRJMTVZHJKOROAI'); 
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');

// Lưu ý: Đổi URL này cho đúng với đường dẫn localhost thực tế của bạn
define('VNPAY_RETURN_URL', 'http://localhost/web_du_lich/vnpay_return.php');


function vnpay_is_configured() {
    return VNPAY_TMN_CODE !== '' && VNPAY_HASH_SECRET !== '';
}

function vnpay_build_url($vnp_params) {
    ksort($vnp_params);
    $query = "";
    $i = 0;
    $hashdata = "";
    foreach ($vnp_params as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }
    $vnp_Url = VNPAY_URL . "?" . $query;
    if (VNPAY_HASH_SECRET) {
        $vnpSecureHash = hash_hmac('sha512', $hashdata, VNPAY_HASH_SECRET);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
    }
    return $vnp_Url;
}

function vnpay_verify_response($params) {
    $vnp_SecureHash = $params['vnp_SecureHash'] ?? '';
    unset($params['vnp_SecureHash']);
    unset($params['vnp_SecureHashType']);
    ksort($params);
    $i = 0;
    $hashData = "";
    foreach ($params as $key => $value) {
        if ($i == 1) {
            $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
    }
    $secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);
    return $secureHash === $vnp_SecureHash;
}

function vnpay_get_client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}
?>