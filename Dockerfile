FROM php:8.2-apache

# Cài đặt các thư viện hệ thống cần thiết cho PostgreSQL, sau đó cài extension pdo, pdo_mysql và pdo_pgsql
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Kích hoạt mod_rewrite của Apache để xử lý điều hướng nếu cần
RUN a2enmod rewrite

# Sao chép toàn bộ mã nguồn PHP hiện tại vào thư mục chạy web
COPY . /var/www/html/

# Mở cổng 80 mặc định cho web
EXPOSE 80
