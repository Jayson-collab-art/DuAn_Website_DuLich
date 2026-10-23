<?php
$file = 'admin_bookings.php';
$content = file_get_contents($file);

// 1. Cập nhật CSS cho các class status
$css_target = '        .status-wait {
            color: #fd7e14;
            font-weight: bold;
        }

        .status-done {
            color: #198754;
            font-weight: bold;
        }

        .status-cancel {
            color: #dc3545;
            font-weight: bold;
        }';

$css_replacement = '        .badge-booking-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
            letter-spacing: 0.3px;
        }

        .status-wait {
            background-color: #FFF7ED;
            color: #EA580C;
            border: 1px solid #FFEDD5;
        }

        .status-done {
            background-color: #ECFDF5;
            color: #047857;
            border: 1px solid #A7F3D0;
        }

        .status-cancel {
            background-color: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }';

// 2. Thêm cột th style nowrap
$th_target = '                        <th>Trạng Thái</th>';
$th_replacement = '                        <th style="white-space: nowrap; min-width: 140px;">Trạng Thái Vé</th>';

// 3. Cập nhật nhãn và class cho row
$row_status_target = '                        $statusClass =
                            \'status-wait\';

                        if (
                            $row[\'Status\'] === \'Đã duyệt\'
                        ) {
                            $statusClass =
                                \'status-done\';
                        }

                        if (
                            $row[\'Status\'] === \'Đã hủy\'
                        ) {
                            $statusClass =
                                \'status-cancel\';
                        }';

$row_status_replacement = '                        // Chuẩn hóa nhãn hiển thị trạng thái tour chính xác
                        $raw_status = trim($row[\'Status\'] ?? \'\');
                        if ($raw_status === \'Đã duyệt\') {
                            $statusClass = \'status-done\';
                            $statusText = \'Đã xác nhận\';
                            $statusIcon = \'bi-check-circle-fill\';
                        } elseif ($raw_status === \'Đã hủy\') {
                            $statusClass = \'status-cancel\';
                            $statusText = \'Đã hủy\';
                            $statusIcon = \'bi-x-circle-fill\';
                        } else {
                            // Mặc định hoặc \'Chờ xác nhận\'
                            $statusClass = \'status-wait\';
                            $statusText = \'Chờ xác nhận\';
                            $statusIcon = \'bi-hourglass-split\';
                        }';

// 4. Cập nhật TD hiển thị
$td_target = '                        <td>

                            <span
                                class="<?php
                                    echo $statusClass;
                                ?>"
                            >
                                <?php
                                echo htmlspecialchars(
                                    $row[\'Status\'],
                                    ENT_QUOTES,
                                    \'UTF-8\'
                                );
                                ?>
                            </span>

                        </td>';

$td_replacement = '                        <td style="white-space: nowrap;">

                            <span class="badge-booking-status <?php echo $statusClass; ?>">
                                <i class="bi <?php echo $statusIcon; ?>"></i>
                                <?php echo $statusText; ?>
                            </span>

                        </td>';

$count_replaced = 0;
if (strpos($content, $css_target) !== false) {
    $content = str_replace($css_target, $css_replacement, $content);
    $count_replaced++;
} else {
    echo "CSS_TARGET_NOT_FOUND\n";
}

if (strpos($content, $th_target) !== false) {
    $content = str_replace($th_target, $th_replacement, $content);
    $count_replaced++;
} else {
    echo "TH_TARGET_NOT_FOUND\n";
}

if (strpos($content, $row_status_target) !== false) {
    $content = str_replace($row_status_target, $row_status_replacement, $content);
    $count_replaced++;
} else {
    echo "ROW_STATUS_TARGET_NOT_FOUND\n";
}

if (strpos($content, $td_target) !== false) {
    $content = str_replace($td_target, $td_replacement, $content);
    $count_replaced++;
} else {
    echo "TD_TARGET_NOT_FOUND\n";
}

if ($count_replaced === 4) {
    file_put_contents($file, $content);
    echo "ALL_PATCHED_SUCCESSFULLY\n";
} else {
    echo "PARTIAL_OR_FAILED: $count_replaced\n";
}
