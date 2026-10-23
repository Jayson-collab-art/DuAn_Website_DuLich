<?php
/**
 * export_revenue.php – Xuất báo cáo doanh thu theo khoảng ngày
 * Bảo vệ: Chỉ Admin đăng nhập mới được truy cập
 * Định dạng xuất: File Excel (.xlsx) chuẩn Office Open XML sử dụng PhpSpreadsheet (fallback OpenXML ZIP)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── BẢO VỆ PHÂN QUYỀN: CHỈ ADMIN MỚI ĐƯỢC PHÉP TRUY CẬP ──
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Bạn chưa đăng nhập trang Quản trị.');
}

include 'db.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ── 1. LẤY VÀ VALIDATE THAM SỐ KHOẢNG NGÀY ──
$start_raw = trim($_GET['start_date'] ?? '');
$end_raw   = trim($_GET['end_date']   ?? '');

function is_valid_date(string $d): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
    [$y, $m, $day] = explode('-', $d);
    return checkdate((int)$m, (int)$day, (int)$y);
}

if (!is_valid_date($start_raw) || !is_valid_date($end_raw)) {
    http_response_code(400);
    exit('Ngày bắt đầu hoặc ngày kết thúc không đúng định dạng (YYYY-MM-DD).');
}

if ($start_raw > $end_raw) {
    http_response_code(400);
    exit('Ngày bắt đầu không được lớn hơn ngày kết thúc.');
}

$start_date = $start_raw;
$end_date   = $end_raw;

// ── 2. TRUY VẤN DỮ LIỆU THANH TOÁN XÁC MINH THÀNH CÔNG ──
// Chỉ tính PaymentStatus = 'Paid'
// Lọc theo giờ Việt Nam qua DATE(PaidAt, CreatedAt)
$sql = "
    SELECT
        p.PaymentID,
        p.OrderCode,
        p.BookingID,
        COALESCE(u.FullName, b.CustomerName, 'Khách lẻ') AS CustomerName,
        COALESCE(u.Email, '') AS Email,
        COALESCE(b.Phone, '') AS Phone,
        pm.MethodName AS PaymentMethod,
        COALESCE(p.TransactionCode, p.ProviderTransactionID, '') AS TransactionCode,
        COALESCE(p.PaidAt, p.CreatedAt) AS PaidAt,
        p.Amount,
        p.PaymentStatus
    FROM payments p
    LEFT JOIN bookings b ON p.BookingID = b.BookingID
    LEFT JOIN users    u ON b.UserID    = u.UserID
    LEFT JOIN payment_methods pm ON p.PaymentMethodID = pm.PaymentMethodID
    WHERE p.PaymentStatus = 'Paid'
      AND DATE(COALESCE(p.PaidAt, p.CreatedAt)) >= ?
      AND DATE(COALESCE(p.PaidAt, p.CreatedAt)) <= ?
    ORDER BY COALESCE(p.PaidAt, p.CreatedAt) ASC
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    exit('Lỗi chuẩn bị truy vấn cơ sở dữ liệu: ' . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
$total_amount = 0.0;
$distinct_bookings = [];

while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
    $total_amount += (float)$row['Amount'];
    if (!empty($row['BookingID'])) {
        $distinct_bookings[$row['BookingID']] = true;
    }
}
mysqli_stmt_close($stmt);

$total_transactions = count($rows);
$total_successful_bookings = count($distinct_bookings);
$export_time = date('d/m/Y H:i:s');
$filename_start = date('dmY', strtotime($start_date));
$filename_end   = date('dmY', strtotime($end_date));
$filename       = "BaoCaoDoanhThu_{$filename_start}_{$filename_end}.xlsx";

// Chống công thức Excel nguy hiểm (=, +, -, @)
function clean_excel_text(?string $str): string {
    $s = trim((string)$str);
    if ($s !== '' && in_array(substr($s, 0, 1), ['=', '+', '-', '@', '|', '%'])) {
        $s = "'" . $s;
    }
    return $s;
}

// ── 3. XUẤT EXCEL BẰNG PHPSPREADSHEET NẾU CÓ SẴN ──
$autoload_file = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload_file)) {
    require_once $autoload_file;
}

if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Báo Cáo Doanh Thu');

    // Cài đặt font chung
    $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

    // Tiêu đề lớn
    $sheet->setCellValue('A1', 'BÁO CÁO DOANH THU – MIỀN TÂY TRAVEL');
    $sheet->mergeCells('A1:J1');
    $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true)->getColor()->setRGB('00284D');
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Thông tin báo cáo
    $sheet->setCellValue('A2', 'Khoảng thời gian: ' . date('d/m/Y', strtotime($start_date)) . ' – ' . date('d/m/Y', strtotime($end_date)));
    $sheet->mergeCells('A2:J2');
    $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('475569');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A3', 'Thời điểm xuất báo cáo: ' . $export_time);
    $sheet->mergeCells('A3:J3');
    $sheet->getStyle('A3')->getFont()->setSize(10)->getColor()->setRGB('64748B');
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Thẻ tổng kết
    $sheet->setCellValue('A5', 'Tổng doanh thu đã thanh toán (Paid):');
    $sheet->setCellValue('C5', $total_amount);
    $sheet->getStyle('A5')->getFont()->setBold(true);
    $sheet->getStyle('C5')->getFont()->setBold(true)->getColor()->setRGB('059669');
    $sheet->getStyle('C5')->getNumberFormat()->setFormatCode('#,##0 "₫"');

    $sheet->setCellValue('A6', 'Tổng số giao dịch thành công:');
    $sheet->setCellValue('C6', $total_transactions);
    $sheet->getStyle('A6')->getFont()->setBold(true);
    $sheet->getStyle('C6')->getFont()->setBold(true);

    $sheet->setCellValue('E6', 'Số đơn tour đã thanh toán:');
    $sheet->setCellValue('G6', $total_successful_bookings);
    $sheet->getStyle('E6')->getFont()->setBold(true);
    $sheet->getStyle('G6')->getFont()->setBold(true);

    // Header bảng chi tiết (Hàng 8)
    $headers = [
        'A8' => 'STT',
        'B8' => 'Mã Đơn Hàng',
        'C8' => 'Mã Đặt Tour (ID)',
        'D8' => 'Khách Hàng',
        'E8' => 'Số Điện Thoại',
        'F8' => 'Phương Thức TT',
        'G8' => 'Mã Giao Dịch Cổng',
        'H8' => 'Ngày Giờ Thanh Toán',
        'I8' => 'Số Tiền (VNĐ)',
        'J8' => 'Trạng Thái'
    ];

    foreach ($headers as $cell => $text) {
        $sheet->setCellValue($cell, $text);
    }

    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '00284D']
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText'   => true
        ]
    ];
    $sheet->getStyle('A8:J8')->applyFromArray($headerStyle);
    $sheet->getRowDimension(8)->setRowHeight(28);

    // Đổ dữ liệu hàng chi tiết
    $rowIdx = 9;
    $stt = 1;

    foreach ($rows as $item) {
        $sheet->setCellValue("A{$rowIdx}", $stt++);
        $sheet->setCellValueExplicit("B{$rowIdx}", clean_excel_text($item['OrderCode']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue("C{$rowIdx}", (int)$item['BookingID']);
        $sheet->setCellValue("D{$rowIdx}", clean_excel_text($item['CustomerName']));
        $sheet->setCellValueExplicit("E{$rowIdx}", clean_excel_text($item['Phone']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue("F{$rowIdx}", clean_excel_text($item['PaymentMethod'] ?? 'VNPay'));
        $sheet->setCellValueExplicit("G{$rowIdx}", clean_excel_text($item['TransactionCode']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        $paidDt = !empty($item['PaidAt']) ? date('d/m/Y H:i', strtotime($item['PaidAt'])) : '';
        $sheet->setCellValue("H{$rowIdx}", $paidDt);
        
        $sheet->setCellValue("I{$rowIdx}", (float)$item['Amount']);
        $sheet->setCellValue("J{$rowIdx}", 'Đã thanh toán (Paid)');

        // Format ô
        $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$rowIdx}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$rowIdx}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$rowIdx}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H{$rowIdx}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$rowIdx}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("J{$rowIdx}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("J{$rowIdx}")->getFont()->getColor()->setRGB('059669');

        $rowIdx++;
    }

    // Hàng tổng kết cuối bảng
    $sheet->setCellValue("A{$rowIdx}", 'TỔNG CỘNG DOANH THU:');
    $sheet->mergeCells("A{$rowIdx}:H{$rowIdx}");
    $sheet->setCellValue("I{$rowIdx}", "=SUM(I9:I" . ($rowIdx - 1) . ")");
    $sheet->setCellValue("J{$rowIdx}", '');

    $summaryStyle = [
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '00284D']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E0F2FE']
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT
        ]
    ];
    $sheet->getStyle("A{$rowIdx}:J{$rowIdx}")->applyFromArray($summaryStyle);
    $sheet->getStyle("I{$rowIdx}")->getNumberFormat()->setFormatCode('#,##0 "₫"');

    // Kẻ viền bảng (Borders)
    $borderRange = "A8:J{$rowIdx}";
    $sheet->getStyle($borderRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

    // Tự động căn chỉnh độ rộng cột
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Gửi file XLSX về browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ── 4. FALLBACK: TẠO FILE XLSX NGUYÊN BẢN BẰNG ZIPARCHIVE + SPREADSHEETML ──
function xe(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$rows_xml = '';
$rows_xml .= "<row r=\"1\"><c r=\"A1\" t=\"inlineStr\"><is><t>BÁO CÁO DOANH THU – MIỀN TÂY TRAVEL</t></is></c></row>";
$rows_xml .= "<row r=\"2\"><c r=\"A2\" t=\"inlineStr\"><is><t>Khoảng thời gian: " . xe(date('d/m/Y', strtotime($start_date))) . " – " . xe(date('d/m/Y', strtotime($end_date))) . "</t></is></c></row>";
$rows_xml .= "<row r=\"3\"><c r=\"A3\" t=\"inlineStr\"><is><t>Thời điểm xuất: " . xe($export_time) . "</t></is></c></row>";
$rows_xml .= "<row r=\"4\"></row>";
$rows_xml .= "<row r=\"5\"><c r=\"A5\" t=\"inlineStr\"><is><t>Tổng doanh thu (Paid):</t></is></c><c r=\"B5\" t=\"inlineStr\"><is><t>" . xe(number_format($total_amount, 0, ',', '.')) . " VNĐ</t></is></c></row>";
$rows_xml .= "<row r=\"6\"><c r=\"A6\" t=\"inlineStr\"><is><t>Số giao dịch thành công:</t></is></c><c r=\"B6\"><v>" . (int)$total_transactions . "</v></c></row>";
$rows_xml .= "<row r=\"7\"></row>";

$headers = ['STT','Mã đơn hàng','BookingID','Khách hàng','SĐT','Phương thức TT','Mã giao dịch','Ngày giờ TT','Số tiền (VNĐ)','Trạng thái'];
$cols    = ['A','B','C','D','E','F','G','H','I','J'];
$rows_xml .= "<row r=\"8\">";
foreach ($headers as $i => $h) {
    $rows_xml .= "<c r=\"{$cols[$i]}8\" t=\"inlineStr\"><is><t>" . xe($h) . "</t></is></c>";
}
$rows_xml .= "</row>";

$r = 9;
$stt = 1;
foreach ($rows as $row) {
    $rows_xml .= "<row r=\"{$r}\">";
    $rows_xml .= "<c r=\"A{$r}\"><v>{$stt}</v></c>";
    $rows_xml .= "<c r=\"B{$r}\" t=\"inlineStr\"><is><t>" . xe(clean_excel_text($row['OrderCode'] ?? '')) . "</t></is></c>";
    $rows_xml .= "<c r=\"C{$r}\"><v>" . (int)($row['BookingID'] ?? 0) . "</v></c>";
    $rows_xml .= "<c r=\"D{$r}\" t=\"inlineStr\"><is><t>" . xe(clean_excel_text($row['CustomerName'] ?? '')) . "</t></is></c>";
    $rows_xml .= "<c r=\"E{$r}\" t=\"inlineStr\"><is><t>" . xe(clean_excel_text($row['Phone'] ?? '')) . "</t></is></c>";
    $rows_xml .= "<c r=\"F{$r}\" t=\"inlineStr\"><is><t>" . xe(clean_excel_text($row['PaymentMethod'] ?? '')) . "</t></is></c>";
    $rows_xml .= "<c r=\"G{$r}\" t=\"inlineStr\"><is><t>" . xe(clean_excel_text($row['TransactionCode'] ?? '')) . "</t></is></c>";
    $rows_xml .= "<c r=\"H{$r}\" t=\"inlineStr\"><is><t>" . xe(!empty($row['PaidAt']) ? date('d/m/Y H:i', strtotime($row['PaidAt'])) : '') . "</t></is></c>";
    $rows_xml .= "<c r=\"I{$r}\"><v>" . (float)($row['Amount'] ?? 0) . "</v></c>";
    $rows_xml .= "<c r=\"J{$r}\" t=\"inlineStr\"><is><t>Paid</t></is></c>";
    $rows_xml .= "</row>";
    $r++;
    $stt++;
}

$rows_xml .= "<row r=\"{$r}\">";
$rows_xml .= "<c r=\"H{$r}\" t=\"inlineStr\"><is><t>TỔNG CỘNG</t></is></c>";
$rows_xml .= "<c r=\"I{$r}\"><v>{$total_amount}</v></c>";
$rows_xml .= "</row>";

$content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';

$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

$workbook_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';

$workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="Doanh Thu" sheetId="1" r:id="rId1"/></sheets>
</workbook>';

$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
  <fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
  <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
</styleSheet>';

$sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>' . $rows_xml . '</sheetData>
</worksheet>';

$zip_file = tempnam(sys_get_temp_dir(), 'xlsx_');
$zip = new ZipArchive();
if ($zip->open($zip_file, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('Không thể tạo file Excel.');
}

$zip->addFromString('[Content_Types].xml',        $content_types);
$zip->addFromString('_rels/.rels',                $rels);
$zip->addFromString('xl/workbook.xml',            $workbook);
$zip->addFromString('xl/_rels/workbook.xml.rels', $workbook_rels);
$zip->addFromString('xl/styles.xml',              $styles);
$zip->addFromString('xl/worksheets/sheet1.xml',   $sheet);
$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
header('Content-Length: ' . filesize($zip_file));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($zip_file);
unlink($zip_file);
exit;
