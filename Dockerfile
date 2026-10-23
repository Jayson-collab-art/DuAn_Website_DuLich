FROM php:8.2-apache

# Cài đặt cả 2 thư viện PDO và mysqli để tương thích mọi kiểu kết nối database
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Bật mod_rewrite cho Apache
RUN a2enmod rewrite

# Phân quyền thư mục web
WORKDIR /var/www/html