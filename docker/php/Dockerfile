FROM php:8.3-fpm

# Instalación de paquetes del sistema necesarios
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP necesarias para CodeIgniter 4 y MySQL
RUN docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd

# Configuración e instalación de la librería GD para imágenes (JPEG, PNG, WebP)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd

# Instalación de extensiones de PHP requeridas por CI4 y la aplicación
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath intl

# Copiar Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html