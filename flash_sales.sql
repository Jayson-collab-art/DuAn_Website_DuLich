CREATE TABLE IF NOT EXISTS `flash_sales` (
  `FlashSaleID` int(11) NOT NULL AUTO_INCREMENT,
  `TourID` int(11) NOT NULL,
  `DiscountPrice` decimal(18,2) NOT NULL,
  `SaleEnd` datetime NOT NULL,
  `Quota` int(11) NOT NULL,
  `Sold` int(11) DEFAULT 0,
  PRIMARY KEY (`FlashSaleID`),
  FOREIGN KEY (`TourID`) REFERENCES `tours`(`TourID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `flash_sales` (`TourID`, `DiscountPrice`, `SaleEnd`, `Quota`, `Sold`) VALUES
(17, 1200000.00, DATE_ADD(NOW(), INTERVAL 3 DAY), 10, 5),
(4, 2500000.00, DATE_ADD(NOW(), INTERVAL 2 DAY), 15, 7),
(15, 2600000.00, DATE_ADD(NOW(), INTERVAL 5 DAY), 12, 0);
