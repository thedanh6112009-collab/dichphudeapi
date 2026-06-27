FROM php:8.2-apache

# Cài đặt extension mở rộng PDO MySQL để kết nối với cơ sở dữ liệu của bạn
RUN docker-php-ext-install pdo pdo_mysql

# Kích hoạt mod_rewrite của Apache để xử lý điều hướng nếu cần
RUN a2enmod rewrite

# Sao chép toàn bộ mã nguồn PHP hiện tại vào thư mục chạy web
COPY . /var/www/html/

# Mở cổng 80 mặc định cho web
EXPOSE 80