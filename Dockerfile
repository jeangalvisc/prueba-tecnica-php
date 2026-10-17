FROM php:8.3-apache

# Habilitar mod_rewrite de Apache para el Front Controller
RUN a2enmod rewrite headers

# Instalar dependencias del sistema y extensiones de PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    git \
    libzip-dev \
    libonig-dev \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar Apache para que el DocumentRoot sea /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Copiar ficheros del proyecto
COPY . /var/www/html

# Instalar dependencias de Composer optimizadas para producción
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permisos para el usuario www-data
RUN chown -R www-data:www-data /var/www/html/var 2>/dev/null || true

EXPOSE 80

CMD ["apache2-foreground"]
