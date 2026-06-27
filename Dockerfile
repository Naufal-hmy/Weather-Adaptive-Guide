# Gunakan image PHP 8.2 dengan Apache
FROM php:8.2-apache

# Install dependencies sistem, Node.js, dan ekstensi PHP (termasuk PostgreSQL untuk Supabase)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    nodejs \
    npm

# Install ekstensi PHP yang dibutuhkan
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd pdo pdo_pgsql

# Aktifkan mod_rewrite Apache (wajib untuk route Laravel)
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory ke /var/www/html
WORKDIR /var/www/html

# Salin semua file project ke dalam container
COPY . .

# Install dependencies PHP dan Node.js, lalu build frontend Vite
RUN composer install --optimize-autoloader --no-dev
RUN npm install
RUN npm run build

# Berikan hak akses penuh ke folder storage dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Ubah DocumentRoot Apache agar mengarah ke folder /public milik Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Render menggunakan Environment Variable $PORT (bukan port 80 statis)
# Kita harus menginstruksikan Apache untuk mendengarkan port Dinamis tersebut
RUN sed -i 's/Listen 80/Listen ${PORT:-80}/g' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT:-80}>/g' /etc/apache2/sites-available/000-default.conf

# Start Apache
CMD ["apache2-foreground"]
