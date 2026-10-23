<?php
$c = file_get_contents('admin_bookings.php');
$lines = explode("\n", $c);
foreach ($lines as $num => $line) {
    if (mb_stripos($line, 'status', 0, 'UTF-8') !== false || mb_stripos($line, 'xác nhận', 0, 'UTF-8') !== false || mb_stripos($line, 'chờ', 0, 'UTF-8') !== false || mb_stripos($line, 'trâu', 0, 'UTF-8') !== false) {
        echo ($num + 1) . ": " . trim($line) . "\n";
    }
}
