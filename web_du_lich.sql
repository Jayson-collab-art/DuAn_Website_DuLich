-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 23, 2026 lúc 05:22 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `web_du_lich`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admins`
--

CREATE TABLE `admins` (
  `AdminID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `FullName` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `FailedAttempts` tinyint(4) DEFAULT 0,
  `LockUntil` datetime DEFAULT NULL,
  `LastLogin` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `admins`
--

INSERT INTO `admins` (`AdminID`, `Username`, `Password`, `FullName`, `Email`, `FailedAttempts`, `LockUntil`, `LastLogin`) VALUES
(1, 'admin', '$2y$10$gSi1IEgIACGSxi0fwetSyOkPZR4Vk4NMqPpyNLYA/PKeyZ3zD.Pdy', 'Nguyễn Ngọc Sơn', NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `BookingID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `TourID` int(11) DEFAULT NULL,
  `BookingDate` datetime DEFAULT current_timestamp(),
  `Quantity` int(11) DEFAULT NULL,
  `TotalPrice` decimal(18,2) DEFAULT NULL,
  `Status` enum('Chờ xác nhận','Đã duyệt','Đã hủy') DEFAULT 'Chờ xác nhận',
  `PaymentMethod` varchar(50) DEFAULT 'Tiền mặt',
  `PaymentStatus` enum('Chưa thanh toán','Đã thanh toán','Hoàn tiền') DEFAULT 'Chưa thanh toán',
  `DepartureDate` date DEFAULT NULL,
  `CustomerName` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bookings`
--

INSERT INTO `bookings` (`BookingID`, `UserID`, `TourID`, `BookingDate`, `Quantity`, `TotalPrice`, `Status`, `PaymentMethod`, `PaymentStatus`, `DepartureDate`, `CustomerName`, `Phone`) VALUES
(32, 1, 22, '2026-10-17 20:58:19', 1, 10000000.00, 'Chờ xác nhận', 'VNPay', 'Đã thanh toán', '2026-10-17', 'nguyễn Ngọc Sơn', '0336363276'),
(33, 1, 22, '2026-10-17 22:05:36', 1, 10000000.00, 'Đã duyệt', 'Tiền mặt', 'Đã thanh toán', '2026-10-17', 'nguyễn Ngọc Sơn', '0336363276'),
(34, 1, 16, '2026-10-18 19:09:07', 1, 1600000.00, 'Chờ xác nhận', 'Tiền mặt', 'Chưa thanh toán', '2027-08-18', 'nguyễn Ngọc Sơn', '0336363276'),
(35, 1, 20, '2026-10-19 18:33:40', 8, 29592000.00, 'Chờ xác nhận', 'Tiền mặt', 'Chưa thanh toán', '2027-05-04', 'nguyễn Ngọc Sơn', '0336363276');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`CategoryID`, `CategoryName`, `Description`) VALUES
(1, 'Du Lịch Biển', 'Các tour nghỉ dưỡng tại các bãi biển đẹp'),
(2, 'Du Lịch Núi Rừng', 'Khám phá vẻ đẹp hùng vĩ của núi rừng'),
(3, 'Du lịch Miền Tây', NULL),
(4, 'Du lịch Hành Hương', NULL),
(5, 'Du lịch Hải Đảo', NULL),
(6, 'Du Lịch Miền Trung', NULL),
(8, 'Du Lịch Miền Bắc', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `contacts`
--

CREATE TABLE `contacts` (
  `ContactID` int(11) NOT NULL,
  `FullName` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Message` text NOT NULL,
  `Status` tinyint(1) DEFAULT 0 COMMENT '0: Chưa xử lý, 1: Đã xử lý',
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `contacts`
--

INSERT INTO `contacts` (`ContactID`, `FullName`, `Email`, `Phone`, `Message`, `Status`, `CreatedAt`) VALUES
(1, 'sơn', 'joji19982@gmail.com', '0336363276', 'tôi cần đi du lịch', 1, '2026-09-18 20:54:04');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupons`
--

CREATE TABLE `coupons` (
  `CouponID` int(11) NOT NULL,
  `CouponCode` varchar(50) NOT NULL,
  `DiscountType` enum('Percent','Fixed') NOT NULL DEFAULT 'Fixed',
  `DiscountValue` int(11) NOT NULL,
  `MinOrderValue` int(11) DEFAULT 0,
  `UsageLimit` int(11) DEFAULT 0,
  `UsedCount` int(11) DEFAULT 0,
  `ExpiryDate` datetime NOT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `flash_sales`
--

CREATE TABLE `flash_sales` (
  `FlashSaleID` int(11) NOT NULL,
  `TourID` int(11) NOT NULL,
  `DiscountPrice` decimal(18,2) NOT NULL,
  `SaleStart` datetime DEFAULT NULL,
  `SaleEnd` datetime NOT NULL,
  `Quota` int(11) NOT NULL,
  `Sold` int(11) DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `flash_sales`
--

INSERT INTO `flash_sales` (`FlashSaleID`, `TourID`, `DiscountPrice`, `SaleStart`, `SaleEnd`, `Quota`, `Sold`, `status`) VALUES
(1, 17, 1200000.00, NULL, '2026-10-19 11:55:06', 10, 5, 'Deleted'),
(2, 4, 2500000.00, NULL, '2026-10-18 11:55:06', 15, 7, 'Deleted'),
(3, 15, 2600000.00, NULL, '2026-10-21 11:55:06', 12, 0, 'Deleted'),
(4, 16, 1600000.00, '2026-10-16 10:32:00', '2026-10-30 15:32:00', 10, 1, 'Active');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `flight_bookings`
--

CREATE TABLE `flight_bookings` (
  `FlightBookingID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `CustomerName` varchar(100) NOT NULL,
  `Phone` varchar(20) NOT NULL,
  `Airline` varchar(50) NOT NULL,
  `FlightRoute` varchar(100) NOT NULL,
  `DepartDate` date NOT NULL,
  `FlightTime` varchar(50) NOT NULL,
  `Passengers` int(11) NOT NULL DEFAULT 1,
  `SeatClass` varchar(50) NOT NULL,
  `TotalPrice` decimal(18,2) NOT NULL,
  `PNR_Code` varchar(20) DEFAULT NULL,
  `PaymentStatus` enum('Pending','Paid','Failed','Cancelled') DEFAULT 'Pending',
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hotel_bookings`
--

CREATE TABLE `hotel_bookings` (
  `HotelBookingID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `CustomerName` varchar(100) NOT NULL,
  `Phone` varchar(20) NOT NULL,
  `HotelName` varchar(150) NOT NULL,
  `Destination` varchar(100) NOT NULL,
  `CheckInDate` date NOT NULL,
  `CheckOutDate` date NOT NULL,
  `Rooms` int(11) NOT NULL DEFAULT 1,
  `Guests` int(11) NOT NULL DEFAULT 2,
  `TotalPrice` decimal(18,2) NOT NULL,
  `BookingCode` varchar(20) DEFAULT NULL,
  `PaymentStatus` enum('Pending','Paid','Failed','Cancelled') DEFAULT 'Pending',
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `news`
--

CREATE TABLE `news` (
  `NewsID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` varchar(500) DEFAULT NULL,
  `Content` longtext DEFAULT NULL,
  `ImageURL` varchar(500) DEFAULT NULL,
  `SourceURL` varchar(500) DEFAULT NULL,
  `Category` varchar(50) DEFAULT NULL,
  `PublishDate` date DEFAULT NULL,
  `Status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `news`
--

INSERT INTO `news` (`NewsID`, `Title`, `Description`, `Content`, `ImageURL`, `SourceURL`, `Category`, `PublishDate`, `Status`) VALUES
(1, 'Du Lịch Đảo Cô Tô', 'Cô Tô là huyện đảo ở phía đông tỉnh Quảng Ninh, cách đất liền khoảng 80 km. Cô Tô có gần 50 đảo nhỏ, trong đó khách du lịch chủ yếu khám phá cụm đảo Cô Tô', NULL, 'https://i1-dulich.vnecdn.net/2022/06/21/co-to-con-5816-1655807496.jpg?w=0&h=0&q=100&dpr=1&fit=crop&s=J1uge9ZEb_tYb7JAcPxFeQ', 'https://vnexpress.net/cam-nang-du-lich-co-to-4478268.html', 'Cẩm nang', '2026-03-27', 1),
(2, 'Du Lịch Phú Yên', 'Là một tỉnh ven biển thuộc vùng duyên hải Nam Trung Bộ, Phú Yên có ba mặt giáp núi và hệ thống sông, đầm, vịnh, hải đảo...', NULL, 'https://i1-dulich.vnecdn.net/2022/05/23/Phu-Yen-Zannier-jpeg-2691-1653279226.jpg?w=0&h=0&q=100&dpr=1&fit=crop&s=kuOXMQ14SPEIjkzr5rwd4g', 'https://vnexpress.net/cam-nang-du-lich-phu-yen-4465949.html', 'Cẩm nang', '2026-03-27', 1),
(3, 'Những điểm thưởng thức hải sản tại Cần Giờ', 'Du khách đến Cần Giờ có thể ghé cảng Đông Hòa, các quán ăn nổi tiếng bản địa để thưởng thức, tìm mua các loại tôm, ghẹ, mực, ốc tươi sống...', NULL, 'https://i1-vnexpress.vnecdn.net/2026/03/20/z7639300413153-52797d3cd9138a6-4400-3728-1774005056.jpg?w=1020&h=0&q=100&dpr=1&fit=crop&s=HnZEqQqrpUIXtqxzEkEs_A', 'https://vnexpress.net/nhung-diem-thuong-thuc-hai-san-tai-can-gio-5052677.html', 'Ẩm thực', '2026-03-27', 1),
(4, 'Hàng nghìn người xem hội chọi trâu Đồ Sơn', 'Hàng nghìn người đổ về sân vận động trung tâm quận Đồ Sơn xem lễ hội chọi trâu, được tổ chức sau 2 năm hoãn vì Covid-19...', NULL, 'https://i1-vnexpress.vnecdn.net/2022/09/04/LeTan-trauchoi-15-1662268349.jpg?w=1200&h=0&q=100&dpr=2&fit=crop&s=PTzIQYwV3ZsBSJWanv_IEg', 'https://vnexpress.net/hang-nghin-nguoi-xem-hoi-choi-trau-do-son-4507082.html', 'Lễ hội', '2026-03-27', 1),
(5, 'Hành trình trở về của khách Mỹ mắc kẹt ở Trung Đông', 'Antoinette Radford, khách Mỹ, ghi lại hành trình vui mừng xen lẫn lo lắng, khi bước chân lên máy bay rời Trung Đông về nhà, sau hai tuần xảy ra xung đột ở khu vực này...', NULL, 'https://i1-dulich.vnecdn.net/2026/03/09/5-1773030791-5111-1773030927.png?w=1020&h=0&q=100&dpr=1&fit=crop&s=wB9neRqM4an9D7xeVK5zPg', 'https://vnexpress.net/hanh-trinh-tro-ve-cua-khach-my-mac-ket-o-trung-dong-5052739.html', 'Cẩm nang', '2026-03-27', 1),
(6, 'Lặn cùng cá voi sát thủ ở Bắc Cực', 'Vận động viên lặn tự do người Pháp Arthur Guérin-Boëri lặn xuống vùng nước lạnh giá ở Na Uy để chạm mặt cá voi sát thủ, loài săn mồi đứng đầu chuỗi thức ăn đại dương...', NULL, 'https://i1-dulich.vnecdn.net/2026/03/12/s-01CD55AE6E4D023CA2236D2F157C28074166610BFA98955D69AF66E1A436337B-1687799935943-13-GettyImages-1246686745-1773299975.jpg?w=1200&h=0&q=100&dpr=2&fit=crop&s=mK1jfz6DC3DNlc_LIrgr7g', 'https://vnexpress.net/lan-cung-ca-voi-sat-thu-o-bac-cuc-5049688.html', 'Kinh nghiệm', '2026-03-27', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
--

CREATE TABLE `payments` (
  `PaymentID` bigint(20) NOT NULL,
  `BookingID` int(11) DEFAULT NULL,
  `FlightBookingID` int(11) DEFAULT NULL,
  `HotelBookingID` int(11) DEFAULT NULL,
  `PaymentMethodID` int(11) NOT NULL,
  `PaymentAccountID` int(11) DEFAULT NULL,
  `OrderCode` varchar(100) NOT NULL,
  `Amount` decimal(18,2) NOT NULL,
  `TransactionCode` varchar(150) DEFAULT NULL,
  `ProviderTransactionID` varchar(150) DEFAULT NULL,
  `PaymentStatus` enum('Pending','Paid','Failed','Cancelled','Refunded') DEFAULT 'Pending',
  `PaymentUrl` text DEFAULT NULL,
  `QRContent` text DEFAULT NULL,
  `PaidAt` datetime DEFAULT NULL,
  `ExpiresAt` datetime DEFAULT NULL,
  `FailureReason` text DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `BankCode` varchar(50) DEFAULT NULL,
  `TransactionInfo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `payments`
--

INSERT INTO `payments` (`PaymentID`, `BookingID`, `FlightBookingID`, `HotelBookingID`, `PaymentMethodID`, `PaymentAccountID`, `OrderCode`, `Amount`, `TransactionCode`, `ProviderTransactionID`, `PaymentStatus`, `PaymentUrl`, `QRContent`, `PaidAt`, `ExpiresAt`, `FailureReason`, `CreatedAt`, `UpdatedAt`, `BankCode`, `TransactionInfo`) VALUES
(1, NULL, NULL, NULL, 5, NULL, 'MT202610161517399016', 960000.00, NULL, NULL, 'Pending', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=96000000&vnp_Command=pay&vnp_CreateDate=20261016151739&vnp_CurrCode=VND&vnp_ExpireDate=20261016153239&vnp_IpAddr=%3A%3A1&vnp_Locale=vn&vnp_OrderInfo=Thanh+toan+Ve+may+bay+ID%3A+4&vnp_OrderType=other&vnp_ReturnUrl=http%3A%2F%2Flocalhost%2Fweb_du_lich%2Fvnpay_return.php&vnp_TmnCode=CJ0S7YG7&vnp_TxnRef=MT202610161517399016&vnp_Version=2.1.0&vnp_SecureHash=3cbb9bccec14d41fdd69e187e1d393ee931fd2359e1b897118a1857678406be7b063a6bd542b6a4ae09e30d4ab9b19beac3997c9918f04946c07745c3026a613', NULL, NULL, '2026-10-16 15:32:39', NULL, '2026-10-16 20:17:39', '2026-10-16 20:17:39', NULL, NULL),
(2, NULL, NULL, NULL, 5, NULL, 'MT202610161517553980', 960000.00, NULL, NULL, 'Pending', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=96000000&vnp_Command=pay&vnp_CreateDate=20261016151755&vnp_CurrCode=VND&vnp_ExpireDate=20261016153255&vnp_IpAddr=%3A%3A1&vnp_Locale=vn&vnp_OrderInfo=Thanh+toan+Ve+may+bay+ID%3A+5&vnp_OrderType=other&vnp_ReturnUrl=http%3A%2F%2Flocalhost%2Fweb_du_lich%2Fvnpay_return.php&vnp_TmnCode=CJ0S7YG7&vnp_TxnRef=MT202610161517553980&vnp_Version=2.1.0&vnp_SecureHash=b7c097d0debc4467a847d55f9e6e073b5001f7c2c09fcb8427d552debc07e9f88d40b2bce4d14b5bf166c8c8aceb78999dbdabeadccdfa5284f8f5c1571d78c0', NULL, NULL, '2026-10-16 15:32:55', NULL, '2026-10-16 20:17:55', '2026-10-16 20:17:55', NULL, NULL),
(3, NULL, NULL, NULL, 5, NULL, 'MT202610162020163341', 960000.00, '15706950', NULL, 'Paid', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=96000000&vnp_Command=pay&vnp_CreateDate=20261016202016&vnp_CurrCode=VND&vnp_ExpireDate=20261016203516&vnp_IpAddr=%3A%3A1&vnp_Locale=vn&vnp_OrderInfo=Thanh+toan+Ve+may+bay+ID%3A+6&vnp_OrderType=other&vnp_ReturnUrl=http%3A%2F%2Flocalhost%2Fweb_du_lich%2Fvnpay_return.php&vnp_TmnCode=CJ0S7YG7&vnp_TxnRef=MT202610162020163341&vnp_Version=2.1.0&vnp_SecureHash=70a7b24083a377f0f95741397e059455bfea9e0aa39888209cb9e7bf82d5addc69f5697da04d69738b9c52739e2774ee1f8cddb40cc1d36dbe13c2eeaa576c2e', NULL, '2026-10-16 20:24:46', '2026-10-16 20:35:16', NULL, '2026-10-16 20:20:16', '2026-10-16 20:24:46', 'NCB', NULL),
(4, NULL, NULL, NULL, 5, NULL, 'MT202610162043527976', 5000000.00, NULL, NULL, 'Pending', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=500000000&vnp_Command=pay&vnp_CreateDate=20261016204352&vnp_CurrCode=VND&vnp_ExpireDate=20261016205852&vnp_IpAddr=%3A%3A1&vnp_Locale=vn&vnp_OrderInfo=Thanh+toan+Khach+san+ID%3A+1&vnp_OrderType=other&vnp_ReturnUrl=http%3A%2F%2Flocalhost%2Fweb_du_lich%2Fvnpay_return.php&vnp_TmnCode=CJ0S7YG7&vnp_TxnRef=MT202610162043527976&vnp_Version=2.1.0&vnp_SecureHash=31140c72650b7221da37c00da532addfb43f5452706b82072e4f75b431c11f421f271ac39fdfb1db98f2ce0f539b50b1987b26f098fafe2b2eb04a8cc42f2104', NULL, NULL, '2026-10-16 20:58:52', NULL, '2026-10-16 20:43:52', '2026-10-16 20:43:52', NULL, NULL),
(5, 32, NULL, NULL, 5, NULL, 'MT202610172058197818', 10000000.00, '15707441', NULL, 'Paid', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=1000000000&vnp_Command=pay&vnp_CreateDate=20261017205819&vnp_CurrCode=VND&vnp_ExpireDate=20261017211319&vnp_IpAddr=%3A%3A1&vnp_Locale=vn&vnp_OrderInfo=Thanh+toan+don+Tour+ID%3A+32&vnp_OrderType=other&vnp_ReturnUrl=http%3A%2F%2Flocalhost%2Fweb_du_lich%2Fvnpay_return.php&vnp_TmnCode=CJ0S7YG7&vnp_TxnRef=MT202610172058197818&vnp_Version=2.1.0&vnp_SecureHash=e81f5f7adc1f493252dd2a579ae5be04c7053cd239674a1656eb8c2060053cb88d6b1fae6844e2e8de0474c7cc45eb2de4d741a71d20e5bd86e5911528f22631', NULL, '2026-10-17 20:59:24', '2026-10-17 21:13:19', NULL, '2026-10-17 20:58:19', '2026-10-17 20:59:24', 'NCB', NULL),
(6, 33, NULL, NULL, 5, NULL, 'MT202610172205447691', 10000000.00, NULL, NULL, 'Paid', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=1000000000&vnp_Command=pay&vnp_CreateDate=20261017220544&vnp_CurrCode=VND&vnp_ExpireDate=20261017222044&vnp_IpAddr=%3A%3A1&vnp_Locale=vn&vnp_OrderInfo=Thanh+toan+don+Tour+ID%3A+33&vnp_OrderType=other&vnp_ReturnUrl=http%3A%2F%2Flocalhost%2Fweb_du_lich%2Fvnpay_return.php&vnp_TmnCode=CJ0S7YG7&vnp_TxnRef=MT202610172205447691&vnp_Version=2.1.0&vnp_SecureHash=a81e539c8c9b2f8cc165b94fbd25a63ac94049e095929862de9e0e1f82fd490acaf9eea6426d68ff6a9132716e6aac63fccbcac1fbfa587c0f2f685b011b6771', NULL, '2026-10-17 22:28:57', '2026-10-17 22:20:44', NULL, '2026-10-17 22:05:44', '2026-10-17 22:28:57', NULL, NULL),
(7, 35, NULL, NULL, 5, NULL, 'MT202610191800328348', 23734000.00, NULL, NULL, 'Cancelled', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=2373400000&vnp_Command=pay&vnp_CreateDate=20261019180032&vnp_CurrCode=VND&vnp_ExpireDate=20261019181532&vnp_IpAddr=%3A%3A1&vnp_Locale=vn&vnp_OrderInfo=Thanh+toan+don+Tour+ID%3A+35&vnp_OrderType=other&vnp_ReturnUrl=http%3A%2F%2Flocalhost%2Fweb_du_lich%2Fvnpay_return.php&vnp_TmnCode=CJ0S7YG7&vnp_TxnRef=MT202610191800328348&vnp_Version=2.1.0&vnp_SecureHash=2271bc9d1e2f3f973c2564315e9f04f0a905ef78b40e093c1ad700d11b2fb18a266b27cc51be74562b68170561dffd43cb1bcb454e66d0b22c8c79dd4fe42381', NULL, NULL, '2026-10-19 18:15:32', NULL, '2026-10-19 18:00:32', '2026-10-19 18:32:32', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_accounts`
--

CREATE TABLE `payment_accounts` (
  `PaymentAccountID` int(11) NOT NULL,
  `PaymentMethodID` int(11) NOT NULL,
  `AccountName` varchar(150) NOT NULL,
  `AccountNumber` varchar(100) DEFAULT NULL,
  `BankName` varchar(100) DEFAULT NULL,
  `BankCode` varchar(30) DEFAULT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `QRContent` text DEFAULT NULL,
  `IsDefault` tinyint(1) DEFAULT 0,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `payment_accounts`
--

INSERT INTO `payment_accounts` (`PaymentAccountID`, `PaymentMethodID`, `AccountName`, `AccountNumber`, `BankName`, `BankCode`, `PhoneNumber`, `QRContent`, `IsDefault`, `IsActive`, `CreatedAt`) VALUES
(1, 2, 'PHAM TAN PHAT', '1040744527', 'Vietcombank', '970436', NULL, NULL, 1, 1, '2026-10-14 04:44:36');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_methods`
--

CREATE TABLE `payment_methods` (
  `PaymentMethodID` int(11) NOT NULL,
  `Code` varchar(30) NOT NULL,
  `MethodName` varchar(100) NOT NULL,
  `ProviderName` varchar(100) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `payment_methods`
--

INSERT INTO `payment_methods` (`PaymentMethodID`, `Code`, `MethodName`, `ProviderName`, `IsActive`) VALUES
(1, 'CASH', 'Tien mat', 'Mien Tay Travel', 1),
(2, 'BANK_TRANSFER', 'Chuyen khoan ngan hang', 'Ngan hang', 1),
(3, 'MOMO', 'MoMo', 'MoMo', 1),
(4, 'ZALOPAY', 'ZaloPay', 'ZaloPay', 1),
(5, 'VNPAY', 'VNPay', 'VNPay', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `ReviewID` int(11) NOT NULL,
  `TourID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Rating` int(11) DEFAULT NULL CHECK (`Rating` >= 1 and `Rating` <= 5),
  `Comment` text DEFAULT NULL,
  `Status` tinyint(1) DEFAULT 0 COMMENT '0: Chờ duyệt, 1: Đã hiển thị',
  `ReviewDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT INTO `reviews` (`ReviewID`, `TourID`, `UserID`, `Rating`, `Comment`, `Status`, `ReviewDate`) VALUES
(1, 2, 1, 5, 'quá tuyệt vời\r\n', 1, '2026-03-21 20:31:13'),
(2, 19, 1, 5, 'I Love Mộc Châu', 1, '2026-03-22 13:52:29'),
(3, 21, 1, 5, 'An Giang Xứ Sở Thần Tiên I Love An Giang', 1, '2026-03-23 12:08:43'),
(4, 22, 1, 5, 'I Love Cà Mau', 1, '2026-03-23 14:00:52'),
(6, 15, 1, 5, 'I Love Hà Giang', 1, '2026-04-02 08:03:46'),
(8, 22, 1, 1, 'aaaaa', 1, '2026-09-23 17:44:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tours`
--

CREATE TABLE `tours` (
  `TourID` int(11) NOT NULL,
  `TourName` varchar(250) NOT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `Price` decimal(18,2) NOT NULL,
  `Duration` varchar(50) DEFAULT NULL,
  `ImageURL` varchar(500) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `DepartureSchedule` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `DepartureLocation` varchar(255) DEFAULT NULL,
  `MaxPeople` int(11) DEFAULT 10,
  `Vehicle` varchar(255) DEFAULT '',
  `Accommodation` varchar(255) DEFAULT '',
  `Destination` varchar(255) DEFAULT '',
  `Includes` text DEFAULT NULL,
  `Excludes` text DEFAULT NULL,
  `Highlights` text DEFAULT NULL,
  `GalleryURLs` text DEFAULT NULL,
  `Transport` varchar(255) DEFAULT NULL,
  `IsFullPackage` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tours`
--

INSERT INTO `tours` (`TourID`, `TourName`, `CategoryID`, `Price`, `Duration`, `ImageURL`, `Description`, `DepartureSchedule`, `DepartureLocation`, `MaxPeople`, `Vehicle`, `Accommodation`, `Destination`, `Includes`, `Excludes`, `Highlights`, `GalleryURLs`, `Transport`, `IsFullPackage`) VALUES
(1, 'Tour Nha Trang: Vịnh Biển Thiên Đường', 1, 3500000.00, '3 Ngày 2 Đêm', 'https://statics.vinpearl.com/kinh-nghiem-du-lich-nha-trang-2_1689411065.jpg', 'Ngày 1: Khám phá Thành phố Biển Nha Trang\r\n\r\nSáng: Đến Nha Trang, tham quan Tháp Bà Ponagar và Chùa Long Sơn.\r\n\r\nChiều: Vui chơi tại VinWonders hoặc tắm bùn khoáng nóng tại Hòn Tằm.\r\n\r\nTối: Thưởng thức nem nướng Ninh Hòa và dạo chợ đêm.\r\n\r\nNgày 2: Nha Trang – Vịnh Vĩnh Hy – Hang Rái\r\n\r\nSáng: Di chuyển dọc cung đường ven biển Bình Tiên đến Vịnh Vĩnh Hy. Đi tàu đáy kính ngắm san hô.\r\n\r\nChiều: Tham quan Hang Rái – nơi có bãi san hô cổ hóa thạch và vườn nho Thái An.\r\n\r\nTối: Nghỉ đêm tại Vĩnh Hy hoặc trở về Nha Trang.\r\n\r\nNgày 3: Mua sắm & Tạm biệt\r\n\r\nSáng: Tham quan Viện Hải dương học, mua sắm đặc sản tại Chợ Đầm.\r\n\r\nTrưa: Kết thúc hành trình.', '', 'TP.HCM', 10, '', 'Khách sạn 3 sao', '', '+ Phương tiện di chuyển đời mới\n+ Khách sạn tiêu chuẩn\n+ Các bữa ăn theo chương trình\n+ Vé tham quan các điểm\n+ Hướng dẫn viên chuyên nghiệp\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\n+ Thuế VAT\n+ Tiền tip cho HDV và tài xế\n+ Phụ thu phòng đơn', NULL, NULL, 'Ô tô du lịch đời mới', 1),
(2, 'Tour Đà Lạt Ngàn Hoa', 2, 2900000.00, '2 Ngày 1 Đêm', 'https://cdn.nhandan.vn/images/6e407d305cea747ceadbf81d9ed5d5614c5275b6497477e68beb293bf8c03a5fa0c142aa2a9708a43dc1b6d808bd3be2f30df6e68b0f816001601be460586338c2381094306107c33e4c998a37d06158/1-da-lat-mong-manh-man-suong-4611-737.jpg', 'Ngày 1: Kiến trúc Cổ điển – Chinh phục Cao nguyên\r\n\r\nSáng: Check-in Quảng trường Lâm Viên, tham quan Nhà ga Đà Lạt và Nhà thờ Domaine de Marie (Nhà thờ Mai Anh).\r\n\r\nChiều: Chinh phục đỉnh Langbiang bằng xe Jeep, ngắm toàn cảnh Suối Vàng từ trên cao. Sau đó ghé Phân viện Sinh học Tây Nguyên.\r\n\r\nTối: Thưởng thức lẩu gà lá é, dạo Chợ Đêm Đà Lạt và tận hưởng không khí se lạnh bên Hồ Xuân Hương.\r\n\r\nNgày 2: Bình minh Cầu Đất – Thác Datanla\r\n\r\nSáng: Săn mây tại Đồi chè Cầu Đất, check-in tuabin điện gió. Tiếp tục tham quan Chùa Linh Phước (Chùa Ve Chai) với kiến trúc khảm sành độc đáo.\r\n\r\nChiều: Trải nghiệm máng trượt xuyên rừng tại Thác Datanla. Tham quan Thiền Viện Trúc Lâm và ngắm cảnh Hồ Tuyền Lâm.\r\n\r\nTối: Mua sắm đặc sản tại L\'angfarm và kết thúc hành trình.', '', 'Tiền Giang', 10, '', 'Villa sườn đồi', '', '+ Phương tiện di chuyển đời mới\n+ Khách sạn tiêu chuẩn\n+ Các bữa ăn theo chương trình\n+ Vé tham quan các điểm\n+ Hướng dẫn viên chuyên nghiệp\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\n+ Thuế VAT\n+ Tiền tip cho HDV và tài xế\n+ Phụ thu phòng đơn', NULL, NULL, 'Xe phòng nằm đôi', 0),
(4, 'Tour Khám Phá Đảo Ngọc Phú Quốc', 1, 3000000.00, '3 ngày 2 đêm', 'https://bcp.cdnchinhphu.vn/334894974524682240/2025/6/23/phu-quoc-17506756503251936667562.jpg', 'Ngày 1: Đông Đảo & Chùa Hộ Quốc\r\n\r\nSáng: Đến Phú Quốc, tham quan Dinh Cậu và Chùa Hộ Quốc (ngôi chùa lớn nhất đảo).\r\n\r\nChiều: Check-in Địa Trung Hải (Sunset Town), xem show diễn Kiss The Stars.\r\n\r\nTối: Dạo chợ đêm Phú Quốc, thưởng thức bún quậy Thanh Hùng.\r\n\r\nNgày 2: Cano 4 Đảo & Cáp Treo Hòn Thơm\r\n\r\nSáng: Đi cano tham quan 4 đảo (Hòn Móng Tay, Hòn Gầm Ghì, Hòn Mây Rút). Lặn ngắm san hô.\r\n\r\nChiều: Trải nghiệm Cáp treo vượt biển dài nhất thế giới sang Hòn Thơm. Vui chơi tại công viên nước Aquatopia.\r\n\r\nTối: Tự do dạo biển.\r\n\r\nNgày 3: Grand World - Thành Phố Không Ngủ\r\n\r\nSáng: Tham quan Grand World, đi thuyền trên sông Venice thu nhỏ, thăm bảo tàng Gấu Teddy.\r\n\r\nTrưa: Mua sắm đặc sản ngọc trai, nước mắm và tiễn sân bay.', '01/04/2026', 'Cần Thơ', 10, '', 'Resort 4 sao có hồ bơi', '', '+ Phương tiện di chuyển đời mới\n+ Khách sạn tiêu chuẩn\n+ Các bữa ăn theo chương trình\n+ Vé tham quan các điểm\n+ Hướng dẫn viên chuyên nghiệp\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\n+ Thuế VAT\n+ Tiền tip cho HDV và tài xế\n+ Phụ thu phòng đơn', NULL, NULL, 'Máy bay khứ hồi', 0),
(5, 'Tour Núi Cao: Sapa – Chinh phục Fansipan', 2, 2499000.00, '3 ngày 2 đêm', 'https://image.vietnamnews.vn/uploadvnnews/Article/2025/9/5/448159_fansipan.jpg', 'Ngày 1: Hà Nội – Sapa – Bản Cát Cát\r\n\r\nSáng: Di chuyển từ Hà Nội lên Sapa qua cao tốc.\r\n\r\nChiều: Thăm Bản Cát Cát, tìm hiểu văn hóa người H\'Mông và check-in tại thác Tiên Sa.\r\n\r\nTối: Ăn lẩu cá tầm/cá hồi, dạo nhà thờ Đá.\r\n\r\nNgày 2: Đỉnh Fansipan – Moana Sapa\r\n\r\nSáng: Đi cáp treo chinh phục \"Nóc nhà Đông Dương\" Fansipan. Thăm quần thể tâm linh trên đỉnh núi.\r\n\r\nChiều: Check-in tại khu Moana Sapa với các tiểu cảnh hướng ra thung lũng Mường Hoa.\r\n\r\nTối: Tham gia phiên Chợ Tình (nếu vào thứ 7).\r\n\r\nNgày 3: Hàm Rồng – Tạm biệt Sapa\r\n\r\nSáng: Leo núi Hàm Rồng, ngắm toàn cảnh Sapa từ trên cao.\r\n\r\nTrưa: Thưởng thức thắng cố và lên xe về lại Hà Nội.', '18/08/2026', 'TP.HCM', 10, '', 'Khách sạn 3 sao', '', '+ Phương tiện di chuyển đời mới\n+ Khách sạn tiêu chuẩn\n+ Các bữa ăn theo chương trình\n+ Vé tham quan các điểm\n+ Hướng dẫn viên chuyên nghiệp\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\n+ Thuế VAT\n+ Tiền tip cho HDV và tài xế\n+ Phụ thu phòng đơn', NULL, NULL, 'Ô tô du lịch đời mới', 1),
(12, 'Tour Miền Tây: Cần Thơ – Sóc Trăng – Bạc Liêu – Cà Mau', 3, 3399000.00, '4 Ngày 3 đêm', 'https://1phutsaigon.vn/wp-content/uploads/2022/07/1-1.jpeg', 'Ngày 1: TP.HCM – Mỹ Tho – Cần Thơ\r\n\r\nSáng: Thăm Chùa Vĩnh Tràng, đi thuyền tham quan 4 cù lao (Long, Lân, Quy, Phụng), lò kẹo dừa Bến Tre.\r\n\r\nChiều: Đến Cần Thơ, check-in bến Ninh Kiều.\r\n\r\nTối: Ăn tối trên du thuyền Cần Thơ.\r\n\r\nNgày 2: Chợ nổi Cái Răng – Sóc Trăng – Cà Mau\r\n\r\nSáng: Đi chợ nổi Cái Răng từ sớm. Sau đó khởi hành đi Sóc Trăng thăm Chùa Som Rong (chùa Khmer tiêu biểu).\r\n\r\nChiều: Đến Bạc Liêu thăm nhà Công tử Bạc Liêu, sau đó di chuyển thẳng xuống TP. Cà Mau.\r\n\r\nNgày 3: Đất Mũi Cà Mau – TP.HCM\r\n\r\nSáng: Chinh phục Đất Mũi, check-in cột mốc tọa độ quốc gia GPS 0001 và biểu tượng Con Tàu.\r\n\r\nTrưa: Thưởng thức đặc sản cua Cà Mau.\r\n\r\nChiều: Khởi hành về lại điểm xuất phát.', '13/05/2026', 'Tiền Giang', 10, '', 'Homestay sinh thái Đất Mũi', '', '+ Phương tiện di chuyển đời mới\n+ Khách sạn tiêu chuẩn\n+ Các bữa ăn theo chương trình\n+ Vé tham quan các điểm\n+ Hướng dẫn viên chuyên nghiệp\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\n+ Thuế VAT\n+ Tiền tip cho HDV và tài xế\n+ Phụ thu phòng đơn', NULL, NULL, 'Xe Limousine 9 chỗ VIP', 1),
(13, 'Tour Cố Đô Huế - Vẻ Đẹp Trầm Mặc', 6, 1499000.00, '2 Ngày 1 Đêm', 'https://cdn-media.sforum.vn/storage/app/media/ctvseo_MH/hu%E1%BA%BF%20mi%E1%BB%81n%20n%C3%A0o/hue-thuoc-mien-nao-thumbnail.jpg', 'Ngày 1: Đại Nội & Ca Huế Sông Hương\r\n\r\nSáng: Tham quan Kinh Thành Huế (Đại Nội): Ngọ Môn, Điện Thái Hòa, Tử Cấm Thành.\r\n\r\nChiều: Viếng Chùa Thiên Mụ và tham quan Lăng Khải Định với kiến trúc khảm sành sứ đỉnh cao.\r\n\r\nTối: Đi thuyền rồng, nghe Ca Huế trên sông Hương và thả hoa đăng.\r\n\r\nNgày 2: Chợ Đông Ba & Làng Hương Thủy Xuân\r\n\r\nSáng: Check-in Làng hương Thủy Xuân rực rỡ sắc màu. Thăm Lăng Tự Đức thơ mộng.\r\n\r\nTrưa: Thưởng thức cơm hến, bánh bèo, nậm, lọc tại chợ Đông Ba.\r\n\r\nChiều: Kết thúc hành trình.', '11/11/2026', 'Cần Thơ', 10, '', 'Khách sạn 3 sao', 'Huế', '+ Phương tiện di chuyển đời mới\r\n+ Khách sạn tiêu chuẩn\r\n+ Các bữa ăn theo chương trình\r\n+ Vé tham quan các điểm\r\n+ Hướng dẫn viên chuyên nghiệp\r\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\r\n+ Thuế VAT\r\n+ Tiền tip cho HDV và tài xế\r\n+ Phụ thu phòng đơn', '', '', 'Ô tô du lịch đời mới', 1),
(14, 'Tour Quy Nhơn - Kỳ Co - Eo Gió', 1, 2499000.00, '3 ngày 2 đêm', 'https://ik.imagekit.io/tvlk/blog/2024/08/thoi-tiet-quy-nhon-1.jpg?tr=q-70,c-at_max,w-1000,h-600', 'Ngày 1: Tây Sơn Tam Kiệt - Hầm Hô\r\n\r\nSáng: Thăm Bảo tàng Quang Trung, xem show võ cổ truyền Bình Định.\r\n\r\nChiều: Khu du lịch sinh thái Hầm Hô, đi thuyền trên sông Kut.\r\n\r\nTối: Ăn bánh xèo tôm nhảy, dạo phố biển Quy Nhơn.\r\n\r\nNgày 2: Kỳ Co - Eo Gió - Tượng Phật Hai Mặt\r\n\r\nSáng: Đi cano sang Bãi Kỳ Co (Maldives Việt Nam). Lặn ngắm san hô tại Bãi San Hô.\r\n\r\nChiều: Đi bộ trên con đường ven biển tại Eo Gió, viếng Tịnh xá Ngọc Hòa có tượng Phật đôi cao nhất Việt Nam.\r\n\r\nTối: Ăn hải sản tại làng chài Nhơn Lý.\r\n\r\nNgày 3: Tháp Đôi - Ghềnh Ráng Tiên Sa\r\n\r\nSáng: Thăm Tháp Đôi (kiến trúc Chăm Pa), viếng mộ Hàn Mặc Tử và bãi tắm Hoàng Hậu.\r\n\r\nTrưa: Kết thúc tour.', '04/05/2026', 'TP.HCM', 10, '', 'Khách sạn 3 sao', '', '+ Phương tiện di chuyển đời mới\n+ Khách sạn tiêu chuẩn\n+ Các bữa ăn theo chương trình\n+ Vé tham quan các điểm\n+ Hướng dẫn viên chuyên nghiệp\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\n+ Thuế VAT\n+ Tiền tip cho HDV và tài xế\n+ Phụ thu phòng đơn', NULL, NULL, 'Ô tô du lịch đời mới', 1),
(15, 'Tour Hà Giang: Mùa Hoa Tam Giác Mạch', 2, 3000000.00, '3 ngày 2 đêm', 'https://media.vietravel.com/images/Content/du-lich-ha-giang-1.jpg', 'Ngày 1: Hà Giang - Quản Bạ - Yên Minh\r\n\r\nSáng: Check-in Km0 Hà Giang. Chinh phục dốc Bắc Sum.\r\n\r\nChiều: Ngắm Núi Đôi Cô Tiên, dạo rừng thông Yên Minh.\r\n\r\nTối: Nghỉ đêm tại thị trấn Yên Minh.\r\n\r\nNgày 2: Đồng Văn - Cột Cờ Lũng Cú - Mã Pì Lèng\r\n\r\nSáng: Thăm Dinh Thự Họ Vương, check-in Cột cờ Lũng Cú (điểm cực Bắc).\r\n\r\nChiều: Chinh phục Đèo Mã Pì Lèng, đi thuyền trên Sông Nho Quế qua hẻm Tu Sản.\r\n\r\nTối: Dạo phố cổ Đồng Văn, uống cafe phố cổ.\r\n\r\nNgày 3: Chợ Phiên - Hà Giang\r\n\r\nSáng: Tham gia chợ phiên Đồng Văn (Chủ nhật). Về Lại Hà Nội kết thúc tour', '18/06/2026', 'Hà Nội', 10, '', 'Khách sạn 3 sao', '', '+ Phương tiện di chuyển đời mới\n+ Khách sạn tiêu chuẩn\n+ Các bữa ăn theo chương trình\n+ Vé tham quan các điểm\n+ Hướng dẫn viên chuyên nghiệp\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\n+ Thuế VAT\n+ Tiền tip cho HDV và tài xế\n+ Phụ thu phòng đơn', NULL, NULL, 'Ô tô du lịch đời mới', 1),
(16, 'Tour Đảo Lý Sơn - Vương Quốc Tỏi', 5, 2000000.00, '2 Ngày 1 Đêm', 'https://statics.vinpearl.com/dong-toi-ly-son_1743166043.jpg', 'Ngày 1: Đảo Lớn - Hang Câu\r\n\r\nSáng: Đi tàu cao tốc ra đảo. Viếng Chùa Hang nằm trong lòng hang đá.\r\n\r\nChiều: Chinh phục đỉnh núi thới lới, check-in cột cờ và ngắm hoàng hôn tại Cổng Tò Vò.\r\n\r\nTối: Thưởng thức gỏi tỏi, hải sản tại cầu cảng.\r\n\r\nNgày 2: Đảo Bé - Thiên Đường Giữa Biển\r\n\r\nSáng: Đi cano sang Đảo Bé (An Bình). Tắm biển, chèo thuyền thúng ngắm san hô.\r\n\r\nTrưa: Về lại Đảo Lớn, mua sắm tỏi và hành Lý Sơn.\r\n\r\nChiều: Lên tàu về lại đất liền.', '18/08/2027', 'Cần Thơ', 10, '', 'Khách sạn 3 sao', '', '+ Phương tiện di chuyển đời mới\r\n+ Khách sạn tiêu chuẩn\r\n+ Các bữa ăn theo chương trình\r\n+ Vé tham quan các điểm\r\n+ Hướng dẫn viên chuyên nghiệp\r\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\r\n+ Thuế VAT\r\n+ Tiền tip cho HDV và tài xế\r\n+ Phụ thu phòng đơn', '', '', 'Ô tô du lịch đời mới', 1),
(17, 'Tour Di Sản Hạ Long: Kỳ Quan Thiên Nhiên', 1, 1499000.00, '2 Ngày 1 Đêm', 'https://nhandan.vn/special/30-nam-mot-chang-duong-di-san-Vinh-Ha-Long/assets/HLCklusX0n/things-to-do-in-ha-long-bay-banner-1-1920x1080.jpg', 'Ngày 1: Vịnh Hạ Long – Động Thiên Cung – Hang Đầu Gỗ\r\n\r\nSáng: Lên tàu du lịch khởi hành thăm Vịnh. Ngắm nhìn Hòn Gà Chọi, Hòn Đỉnh Hương (biểu tượng trên tờ tiền 200.000đ).\r\n\r\nChiều: Khám phá Động Thiên Cung rực rỡ sắc màu và Hang Sửng Sốt – hang động lớn nhất Vịnh Hạ Long. Trải nghiệm chèo thuyền Kayak tại Hang Luồn.\r\n\r\nTối: Thưởng thức hải sản trên tàu hoặc tại Bãi Cháy. Ngắm cầu Bãi Cháy về đêm.\r\n\r\nNgày 2: Sun World Hạ Long – Bảo Tàng Quảng Ninh\r\n\r\nSáng: Check-in Bảo tàng Quảng Ninh (kiến trúc khối đen độc đáo). Vui chơi tại Công viên Rồng hoặc đi cáp treo Nữ Hoàng ngắm toàn cảnh vịnh từ trên cao.\r\n\r\nTrưa: Thưởng thức chả mực giã tay Hạ Long và mua sắm tại chợ Cái Dăm.', '18/08/2027', 'TP.HCM', 10, '', '', '', '+ Phương tiện di chuyển đời mới\r\n+ Khách sạn tiêu chuẩn\r\n+ Các bữa ăn theo chương trình\r\n+ Vé tham quan các điểm\r\n+ Hướng dẫn viên chuyên nghiệp\r\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\r\n+ Thuế VAT\r\n+ Tiền tip cho HDV và tài xế\r\n+ Phụ thu phòng đơn', '', '', 'Xe Limousine & Tàu', 1),
(18, 'Tour Ninh Bình: Tràng An – Bái Đính – Hang Múa', 2, 999999.00, '1 Ngày', 'https://media.vietravel.com/images/news/du-lich-ninh-binh-thang-7-0.png', 'Sáng: Chùa Bái Đính – Kỷ lục Đông Nam Á\r\n\r\nTham quan quần thể Chùa Bái Đính với những kỷ lục: Tượng Phật bằng đồng dát vàng lớn nhất, Hành lang La Hán dài nhất Việt Nam.\r\n\r\nTrưa: Thưởng thức đặc sản Cơm cháy, Thịt dê núi Ninh Bình.\r\n\r\nChiều: Tràng An – Hang Múa\r\n\r\nĐi thuyền dọc dòng sông Ngô Đồng tại Khu di tích Tràng An (Di sản thế giới). Tham quan các hang động và phim trường King Kong.\r\n\r\nChinh phục 500 bậc đá tại Hang Múa, ngắm nhìn \"Tam Cốc\" từ trên đỉnh núi Ngọa Long – điểm check-in đẹp nhất Ninh Bình.', '13/05/2027', 'Hà Nội', 10, '', 'Khách sạn 3 sao', 'Ninh Bình', '+ Phương tiện di chuyển đời mới\r\n+ Khách sạn tiêu chuẩn\r\n+ Các bữa ăn theo chương trình\r\n+ Vé tham quan các điểm\r\n+ Hướng dẫn viên chuyên nghiệp\r\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\r\n+ Thuế VAT\r\n+ Tiền tip cho HDV và tài xế\r\n+ Phụ thu phòng đơn', '', '', 'Ô tô du lịch đời mới', 1),
(19, 'Tour Mộc Châu: Sắc Màu Cao Nguyên', 2, 2799000.00, '2 Ngày 1 Đêm', 'https://vitracotour.com/wp-content/uploads/2023/12/moc-chau.png', 'Ngày 1: Đồi Chè Trái Tim – Thung Lũng Mận Nà Ka\r\n\r\nSáng: Di chuyển lên Mộc Châu. Check-in Đồi chè trái tim xanh mướt, trải nghiệm mặc trang phục dân tộc H\'Mông.\r\n\r\nChiều: Tham quan Thung lũng mận Nà Ka (ngắm hoa mận trắng vào mùa xuân hoặc hái quả vào mùa hè). Ghé thăm Thác Dải Yếm.\r\n\r\nTối: Lửa trại, thưởng thức bê chao, cá suối và rượu cần vùng cao.\r\n\r\nNgày 2: Cầu Kính Bạch Long – Rừng Thông Bản Áng\r\n\r\nSáng: Trải nghiệm Cầu kính Bạch Long (cầu kính đi bộ dài nhất thế giới). Sau đó dạo chơi tại Rừng thông Bản Áng – được ví như Đà Lạt thu nhỏ của miền Bắc.\r\n\r\nTrưa: Mua sắm sữa bò tươi, bánh sữa Mộc Châu và kết thúc hành trình.', '11/11/2026', 'Hà Nội', 10, '', 'Khách sạn 3 sao', '', '+ Phương tiện di chuyển đời mới\n+ Khách sạn tiêu chuẩn\n+ Các bữa ăn theo chương trình\n+ Vé tham quan các điểm\n+ Hướng dẫn viên chuyên nghiệp\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\n+ Thuế VAT\n+ Tiền tip cho HDV và tài xế\n+ Phụ thu phòng đơn', NULL, NULL, 'Ô tô du lịch đời mới', 1),
(20, 'Tour Đảo Phú Quý: Thiên Đường Thu Nhỏ', 1, 3699000.00, '3 ngày 2 đêm', 'https://vj-prod-website-cms.s3.ap-southeast-1.amazonaws.com/shutterstock597812177huge1-1679385132078.jpg', 'Ngày 1: Phan Thiết – Vượt Sóng Ra Khơi\r\n\r\nSáng: Di chuyển từ Cảng Phan Thiết đi tàu cao tốc ra Đảo Phú Quý. Check-in nhận phòng.\r\n\r\nChiều: Tham quan Cột cờ chủ quyền biển đảo, ngắm nhìn Bãi Nhỏ - Gành Hang (nơi có hồ bơi vô cực tự nhiên giữa các vách đá).\r\n\r\nTối: Thưởng thức đặc sản Cua Huỳnh Đế hoặc bò nóng Phú Quý.\r\n\r\nNgày 2: Check-in \"Cây cô đơn\" – Đỉnh Cao Cát\r\n\r\nSáng: Đón bình minh tại Dốc Phượt (cung đường ven biển đẹp nhất đảo). Check-in \"Cây cô đơn\" và viếng Chùa Linh Sơn trên đỉnh núi Cao Cát hùng vĩ.\r\n\r\nChiều: Đi cano ra Hòn Tranh – hòn đảo phụ đẹp nhất Phú Quý để tắm biển và lặn ngắm san hô. Thăm mộ Thầy Sài Nại.\r\n\r\nTối: Dạo quanh bờ kè phía Bắc, hóng gió biển và ăn vặt hải sản.\r\n\r\nNgày 3: Điện Gió Phú Quý – Tạm Biệt\r\n\r\nSáng: Tham quan cánh đồng Điện Gió, chụp ảnh với những chiếc quạt gió khổng lồ trắng muốt trên nền cỏ xanh.\r\n\r\nTrưa: Mua hải sản khô và đồ lưu niệm tại cảng. Lên tàu cao tốc trở về đất liền.', '04/05/2027', 'TP.HCM', 12, '', '', 'Đảo Phú Quý', '+ Phương tiện di chuyển đời mới\r\n+ Khách sạn tiêu chuẩn\r\n+ Các bữa ăn theo chương trình\r\n+ Vé tham quan các điểm\r\n+ Hướng dẫn viên chuyên nghiệp\r\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\r\n+ Thuế VAT\r\n+ Tiền tip cho HDV và tài xế\r\n+ Phụ thu phòng đơn', '', '', 'Tàu cao tốc Superdong', 0),
(21, 'Tour An Giang: Thất Sơn Hùng Vĩ – Rừng Tràm Trà Sư', 4, 999999.00, '2 Ngày 1 Đêm', 'https://cdn3.ivivu.com/2025/12/du-lich-an-giang-ivivu-1.png', 'Ngày 1: Châu Đốc – Miếu Bà Chúa Xứ – Núi Sam\r\n\r\nSáng: Viếng Miếu Bà Chúa Xứ Núi Sam, chùa Tây An và lăng Thoại Ngọc Hầu. Đây là cụm di tích tâm linh lớn nhất miền Tây.\r\n\r\nChiều: Chinh phục Núi Cấm, tham quan hồ Thủy Liêm và chiêm bái tượng Phật Di Lặc khổng lồ trên đỉnh núi.\r\n\r\nTối: Thưởng thức lẩu mắm Châu Đốc, dạo chợ đêm mua sắm các loại mắm đặc sản.\r\n\r\nNgày 2: Rừng Tràm Trà Sư – Cánh Đồng Thốt Nốt\r\n\r\nSáng: Khám phá Rừng Tràm Trà Sư, đi tắc ráng xuyên qua thảm bèo xanh mướt, ngắm nhìn hệ sinh thái chim cò đa dạng.\r\n\r\nChiều: Check-in những hàng thốt nốt hình trái tim tại Tri Tôn. Ghé mua đường thốt nốt làm quà.\r\n\r\nTối: Kết thúc hành trình tại TP. Long Xuyên.', '18/08/2027', 'Mỹ Tho', 15, '', '', 'An Giang', '+ Phương tiện di chuyển đời mới\r\n+ Khách sạn tiêu chuẩn\r\n+ Các bữa ăn theo chương trình\r\n+ Vé tham quan các điểm\r\n+ Hướng dẫn viên chuyên nghiệp\r\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\r\n+ Thuế VAT\r\n+ Tiền tip cho HDV và tài xế\r\n+ Phụ thu phòng đơn', '', '', 'Xe du lịch 16 chỗ', 1),
(22, 'Tour Cà Mau: Hành Trình Đất Mũi – Điểm Cực Nam', 3, 10000000.00, '2 Ngày 1 Đêm', 'https://phuotvivu.com/blog/wp-content/uploads/2021/06/c%C3%A0-mau2.jpg', 'Ngày 1: Sóc Trăng – Bạc Liêu – Khám Phá Đất Mũi\r\n\r\nSáng: Khởi hành đi Sóc Trăng, viếng Chùa Som Rong với tượng Phật Thích Ca nằm lớn nhất Việt Nam. Sau đó ghé Bạc Liêu tham quan Nhà Công tử Bạc Liêu huyền thoại.\r\n\r\nChiều: Check-in Cánh đồng điện gió Bạc Liêu (được mệnh danh là \"Hà Lan thu nhỏ\"). Tiếp tục di chuyển xuyên qua những cung đường rừng ngập mặn để đến với Xóm Mũi.\r\n\r\nTối: Trải nghiệm ngủ đêm tại homestay giữa rừng đước. Thưởng thức đặc sản: Cua Cà Mau, cá thòi lòi nướng muối ớt, vọp hấp gừng.\r\n\r\nNgày 2: Cột Mốc Tọa Độ GPS 0001 – Hệ Sinh Thái Rừng Đước\r\n\r\nSáng: Check-in Cột mốc tọa độ quốc gia GPS 0001 và Biểu tượng Con Tàu tại Công viên Văn hóa Du lịch Mũi Cà Mau. Chụp ảnh tại điểm cuối cùng của đường Hồ Chí Minh (Km 2436).\r\n\r\nTrưa: Trải nghiệm đi vỏ lãi (xuồng máy) len lỏi qua những con rạch nhỏ trong rừng đước, tìm hiểu về hệ sinh thái rừng ngập mặn lớn thứ 2 thế giới.\r\n\r\nChiều: Tham quan Đầm Thị Tường – \"biển hồ\" giữa đất đất liền. Sau đó khởi hành trở về điểm xuất phát.', '10/10/2027', 'Đồng Tháp', 17, '', '', 'Cà Mau', '+ Phương tiện di chuyển đời mới\r\n+ Khách sạn tiêu chuẩn\r\n+ Các bữa ăn theo chương trình\r\n+ Vé tham quan các điểm\r\n+ Hướng dẫn viên chuyên nghiệp\r\n+ Bảo hiểm du lịch', '+ Chi phí vui chơi cá nhân\r\n+ Thuế VAT\r\n+ Tiền tip cho HDV và tài xế\r\n+ Phụ thu phòng đơn', '', '', 'Xe Limousine 9 chỗ VIP', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tour_images`
--

CREATE TABLE `tour_images` (
  `ImageID` int(11) NOT NULL,
  `TourID` int(11) NOT NULL,
  `ImageURL` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tour_images`
--

INSERT INTO `tour_images` (`ImageID`, `TourID`, `ImageURL`) VALUES
(9, 21, 'https://ik.imagekit.io/tvlk/blog/2022/02/dia-diem-du-lich-an-giang-cover.jpeg?tr=q-70,c-at_max,w-500,h-250,dpr-2'),
(10, 21, 'https://ik.imagekit.io/tvlk/blog/2022/02/dia-diem-du-lich-an-giang-1-819x1024.jpg?tr=q-70,c-at_max,w-1000,h-600'),
(11, 21, 'https://ik.imagekit.io/tvlk/blog/2022/02/dia-diem-du-lich-an-giang-2-983x1024.jpg?tr=q-70,c-at_max,w-1000,h-600'),
(12, 20, 'https://statics.vinpearl.com/nui-cao-cat-phu-quy_1758717381.jpg'),
(13, 20, 'https://statics.vinpearl.com/bai-nho-phu-quy_1758717412.jpg'),
(14, 20, 'https://statics.vinpearl.com/vinh-trieu-duong-phu-quy_1758717535.jpeg'),
(15, 19, 'https://ik.imagekit.io/tvlk/blog/2024/01/du-lich-moc-chau-mua-nao-dep-2.jpg?tr=q-70,c-at_max,w-500,h-250,dpr-2'),
(16, 19, 'https://ik.imagekit.io/tvlk/blog/2024/01/du-lich-moc-chau-mua-nao-dep-3-1024x614.jpg?tr=q-70,c-at_max,w-1000,h-600'),
(17, 19, 'https://ik.imagekit.io/tvlk/blog/2024/01/du-lich-moc-chau-mua-nao-dep-1.jpg?tr=q-70,c-at_max,w-1000,h-600'),
(18, 18, 'https://ik.imagekit.io/tvlk/blog/2025/05/canh-dep-ninh-binh-cover.png?tr=q-70,c-at_max,w-500,h-250,dpr-2'),
(19, 18, 'https://ik.imagekit.io/tvlk/blog/2025/05/canh-dep-ninh-binh-1-1024x631.jpg?tr=q-70,c-at_max,w-1000,h-600'),
(20, 18, 'https://ik.imagekit.io/tvlk/blog/2025/05/canh-dep-ninh-binh-2-1024x768.jpg?tr=q-70,c-at_max,w-1000,h-600'),
(21, 17, 'https://media.vietravel.com/images/Content/dia-diem-du-lich-ha-long1.jpg'),
(22, 17, 'https://media.vietravel.com/images/Content/sun-world-ha-long107.jpg'),
(23, 17, 'https://media.vietravel.com/images/Content/bao-tang-quang-ninh-1.jpg'),
(24, 16, 'https://statics.vinpearl.com/vi-tri-dao-ly-son_1743165853.jpg'),
(25, 16, 'https://statics.vinpearl.com/dia-hinh-dao-ly-son_1743165909.jpg'),
(26, 16, 'https://statics.vinpearl.com/khi-hau-dao-ly-son_1743165930.jpg'),
(27, 22, 'https://cdn3.ivivu.com/2025/11/du-lich-ca-mau-ivivu-1.jpg'),
(28, 22, 'https://cdn3.ivivu.com/2025/11/du-lich-ca-mau-ivivu-3.jpg'),
(29, 22, 'https://cdn3.ivivu.com/2025/11/du-lich-ca-mau-ivivu.jpg'),
(30, 22, 'https://tgu.edu.vn/upload/images/4_DHTG-2024.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `FullName` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `CreateDate` datetime DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`UserID`, `Username`, `Password`, `FullName`, `Email`, `Phone`, `CreateDate`, `reset_token`, `reset_expires`) VALUES
(1, 'Sơn Vi Vu', '$2y$10$4uft9Z1qmKbze7eWObgczOW3dlyoWOITURolDn.iudch0F5mNmZP.', 'nguyễn Ngọc Sơn', 'joji19982@gmail.com', NULL, '2026-03-21 19:55:51', NULL, NULL),
(2, 'Nguyễn Văn An', '$2y$10$4uft9Z1qmKbze7eWObgczOW3dlyoWOITURolDn.iudch0F5mNmZP.', 'An', 'joji19982@gmail.com', NULL, '2026-04-07 17:14:34', NULL, NULL),
(3, 'Nguyễn Văn Anh', '$2y$10$4uft9Z1qmKbze7eWObgczOW3dlyoWOITURolDn.iudch0F5mNmZP.', 'Anh', 'joji19982@gmail.com', NULL, '2026-04-07 17:45:53', NULL, NULL),
(4, 'An', '$2y$10$4uft9Z1qmKbze7eWObgczOW3dlyoWOITURolDn.iudch0F5mNmZP.', 'Trần An Alex', 'joji19982@gmail.com', NULL, '2026-04-07 17:56:50', NULL, NULL),
(5, 'Sơn', '$2y$10$4uft9Z1qmKbze7eWObgczOW3dlyoWOITURolDn.iudch0F5mNmZP.', 'Trần An Alex', 'joji19982@gmail.com', NULL, '2026-04-07 18:01:42', NULL, NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`BookingID`),
  ADD KEY `TourID` (`TourID`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`CategoryID`);

--
-- Chỉ mục cho bảng `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`ContactID`);

--
-- Chỉ mục cho bảng `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`CouponID`),
  ADD UNIQUE KEY `CouponCode` (`CouponCode`);

--
-- Chỉ mục cho bảng `flash_sales`
--
ALTER TABLE `flash_sales`
  ADD PRIMARY KEY (`FlashSaleID`),
  ADD KEY `TourID` (`TourID`);

--
-- Chỉ mục cho bảng `flight_bookings`
--
ALTER TABLE `flight_bookings`
  ADD PRIMARY KEY (`FlightBookingID`);

--
-- Chỉ mục cho bảng `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  ADD PRIMARY KEY (`HotelBookingID`);

--
-- Chỉ mục cho bảng `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`NewsID`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`PaymentID`),
  ADD UNIQUE KEY `UQ_payments_order_code` (`OrderCode`),
  ADD KEY `IDX_payments_booking` (`BookingID`),
  ADD KEY `IDX_payments_method` (`PaymentMethodID`),
  ADD KEY `IDX_payments_account` (`PaymentAccountID`),
  ADD KEY `IDX_payments_status` (`PaymentStatus`),
  ADD KEY `fk_payment_flight` (`FlightBookingID`),
  ADD KEY `fk_payment_hotel` (`HotelBookingID`);

--
-- Chỉ mục cho bảng `payment_accounts`
--
ALTER TABLE `payment_accounts`
  ADD PRIMARY KEY (`PaymentAccountID`),
  ADD KEY `IDX_payment_accounts_method` (`PaymentMethodID`);

--
-- Chỉ mục cho bảng `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`PaymentMethodID`),
  ADD UNIQUE KEY `UQ_payment_methods_code` (`Code`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`ReviewID`),
  ADD KEY `TourID` (`TourID`),
  ADD KEY `UserID` (`UserID`);

--
-- Chỉ mục cho bảng `tours`
--
ALTER TABLE `tours`
  ADD PRIMARY KEY (`TourID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Chỉ mục cho bảng `tour_images`
--
ALTER TABLE `tour_images`
  ADD PRIMARY KEY (`ImageID`),
  ADD KEY `TourID` (`TourID`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admins`
--
ALTER TABLE `admins`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `BookingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `contacts`
--
ALTER TABLE `contacts`
  MODIFY `ContactID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `coupons`
--
ALTER TABLE `coupons`
  MODIFY `CouponID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `flash_sales`
--
ALTER TABLE `flash_sales`
  MODIFY `FlashSaleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `flight_bookings`
--
ALTER TABLE `flight_bookings`
  MODIFY `FlightBookingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  MODIFY `HotelBookingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `news`
--
ALTER TABLE `news`
  MODIFY `NewsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `PaymentID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `payment_accounts`
--
ALTER TABLE `payment_accounts`
  MODIFY `PaymentAccountID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `PaymentMethodID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `ReviewID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `tours`
--
ALTER TABLE `tours`
  MODIFY `TourID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `tour_images`
--
ALTER TABLE `tour_images`
  MODIFY `ImageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`TourID`) REFERENCES `tours` (`TourID`);

--
-- Các ràng buộc cho bảng `flash_sales`
--
ALTER TABLE `flash_sales`
  ADD CONSTRAINT `flash_sales_ibfk_1` FOREIGN KEY (`TourID`) REFERENCES `tours` (`TourID`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_flight` FOREIGN KEY (`FlightBookingID`) REFERENCES `flight_bookings` (`FlightBookingID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_hotel` FOREIGN KEY (`HotelBookingID`) REFERENCES `hotel_bookings` (`HotelBookingID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payments_account` FOREIGN KEY (`PaymentAccountID`) REFERENCES `payment_accounts` (`PaymentAccountID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payments_booking` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`BookingID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payments_method` FOREIGN KEY (`PaymentMethodID`) REFERENCES `payment_methods` (`PaymentMethodID`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `payment_accounts`
--
ALTER TABLE `payment_accounts`
  ADD CONSTRAINT `fk_payment_accounts_method` FOREIGN KEY (`PaymentMethodID`) REFERENCES `payment_methods` (`PaymentMethodID`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`TourID`) REFERENCES `tours` (`TourID`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Các ràng buộc cho bảng `tours`
--
ALTER TABLE `tours`
  ADD CONSTRAINT `tours_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`);

--
-- Các ràng buộc cho bảng `tour_images`
--
ALTER TABLE `tour_images`
  ADD CONSTRAINT `tour_images_ibfk_1` FOREIGN KEY (`TourID`) REFERENCES `tours` (`TourID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
