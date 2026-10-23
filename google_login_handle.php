<?php

$secure_session = (
    isset($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] !== 'off'
);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure_session,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

include 'db.php';

$login_error = "
<script>
    alert('Đăng nhập Google thất bại. Vui lòng thử lại!');
    window.location.href = 'login_user.php';
</script>
";

$google_client_id = '309156140127-3q48ipe4b089jtt06nockp76jhj8859n.apps.googleusercontent.com';

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    empty($_POST['credential'])
) {
    header('Location: login_user.php');
    exit();
}

$credential = trim($_POST['credential']);

$tokeninfo_url =
    'https://oauth2.googleapis.com/tokeninfo?' .
    http_build_query([
        'id_token' => $credential
    ]);

$curl = curl_init($tokeninfo_url);

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);

$tokeninfo_response = curl_exec($curl);

$http_status = curl_getinfo(
    $curl,
    CURLINFO_HTTP_CODE
);

$curl_error = curl_error($curl);

curl_close($curl);

if (
    $tokeninfo_response === false ||
    $http_status !== 200
) {
    error_log(
        'Google token verification request failed. HTTP status: ' .
        $http_status .
        (
            $curl_error !== ''
                ? '; cURL error: ' . $curl_error
                : ''
        )
    );

    echo $login_error;
    exit();
}

$token_payload = json_decode(
    $tokeninfo_response,
    true
);

if (!is_array($token_payload)) {
    error_log(
        'Google login rejected: invalid token response.'
    );

    echo $login_error;
    exit();
}

$google_email = isset($token_payload['email'])
    ? strtolower(trim($token_payload['email']))
    : '';

$issuer_is_valid =
    isset($token_payload['iss']) &&
    in_array(
        $token_payload['iss'],
        [
            'accounts.google.com',
            'https://accounts.google.com'
        ],
        true
    );

$email_verified =
    ($token_payload['email_verified'] ?? false) === true ||
    ($token_payload['email_verified'] ?? false) === 'true';

$token_is_valid =
    isset(
        $token_payload['aud'],
        $token_payload['exp'],
        $token_payload['email']
    ) &&
    hash_equals(
        $google_client_id,
        $token_payload['aud']
    ) &&
    $issuer_is_valid &&
    is_numeric($token_payload['exp']) &&
    (int)$token_payload['exp'] > time() &&
    filter_var(
        $google_email,
        FILTER_VALIDATE_EMAIL
    ) &&
    $email_verified;

if (!$token_is_valid) {
    error_log(
        'Google login rejected: invalid, expired, unverified, or wrong-audience token.'
    );

    echo $login_error;
    exit();
}

$google_name = isset($token_payload['name'])
    ? trim($token_payload['name'])
    : '';

if ($google_name === '') {
    $email_prefix = strstr(
        $google_email,
        '@',
        true
    );

    $google_name = $email_prefix ?: 'Google User';
}

$username_base = strtolower(
    preg_replace(
        '/[^a-zA-Z0-9]/',
        '',
        $google_name
    )
);

if ($username_base === '') {
    $username_base = 'googleuser';
}

$username_temp = $username_base . rand(1000, 9999);

$check_username_stmt = $conn->prepare(
    'SELECT UserID FROM Users WHERE Username = ? LIMIT 1'
);

if (!$check_username_stmt) {
    error_log(
        'Google username check prepare failed: ' .
        $conn->error
    );

    echo $login_error;
    exit();
}

$check_username_stmt->bind_param(
    's',
    $username_temp
);

$check_username_stmt->execute();

$check_username_result =
    $check_username_stmt->get_result();

while ($check_username_result->num_rows > 0) {

    $username_temp =
        $username_base .
        rand(1000, 9999);

    $check_username_stmt->bind_param(
        's',
        $username_temp
    );

    $check_username_stmt->execute();

    $check_username_result =
        $check_username_stmt->get_result();
}

$check_username_stmt->close();

$stmt = $conn->prepare(
    'SELECT UserID, Username, Email
     FROM Users
     WHERE Email = ?
     LIMIT 1'
);

if (!$stmt) {
    error_log(
        'Google login user lookup prepare failed: ' .
        $conn->error
    );

    echo $login_error;
    exit();
}

$stmt->bind_param(
    's',
    $google_email
);

if (!$stmt->execute()) {
    error_log(
        'Google login user lookup failed: ' .
        $stmt->error
    );

    $stmt->close();

    echo $login_error;
    exit();
}

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();

    $stmt->close();

    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['UserID'];
    $_SESSION['user'] = $user['Username'];
    $_SESSION['user_name'] = $google_name;
    $_SESSION['google_email'] = $google_email;
    $_SESSION['login_method'] = 'Google';

    header('Location: index.php');
    exit();
}

$stmt->close();

$random_password = password_hash(
    bin2hex(random_bytes(32)),
    PASSWORD_DEFAULT
);

$insert_stmt = $conn->prepare(
    'INSERT INTO Users
    (Username, Email, Password)
    VALUES (?, ?, ?)'
);

if (!$insert_stmt) {
    error_log(
        'Google login user creation prepare failed: ' .
        $conn->error
    );

    echo $login_error;
    exit();
}

$insert_stmt->bind_param(
    'sss',
    $username_temp,
    $google_email,
    $random_password
);

if (!$insert_stmt->execute()) {

    error_log(
        'Google login user creation failed: ' .
        $insert_stmt->error
    );

    $insert_stmt->close();

    echo $login_error;
    exit();
}

$new_user_id = (int)$conn->insert_id;

$insert_stmt->close();

session_regenerate_id(true);

$_SESSION['user_id'] = $new_user_id;
$_SESSION['user'] = $username_temp;
$_SESSION['user_name'] = $google_name;
$_SESSION['google_email'] = $google_email;
$_SESSION['login_method'] = 'Google';

echo "
<script>
    alert('Chào mừng bạn mới! Tài khoản đã được tự động tạo qua Google.');
    window.location.href = 'index.php';
</script>
";

exit();
?>