<?php
include 'db.php';
$r = mysqli_query($conn, 'SELECT DISTINCT Status FROM bookings');
while($row = mysqli_fetch_assoc($r)) {
    echo "STATUS: [" . $row['Status'] . "]\n";
    echo "HEX: " . bin2hex($row['Status']) . "\n";
}
