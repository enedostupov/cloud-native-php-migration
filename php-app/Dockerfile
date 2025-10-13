FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git unzip zip curl \
 && a2enmod rewrite \
 && docker-php-ext-install pdo pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

ARG APP_ENV=development
RUN if [ "$APP_ENV" = "production" ]; then \
        composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader; \
    else \
        composer install --no-interaction --prefer-dist; \
    fi

COPY public/ public/
COPY src/ src/
COPY tests/ tests/
COPY phpunit.xml .

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
 && echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/public.conf \
 && a2enconf public

RUN chown -R www-data:www-data /var/www/html

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/health || exit 1

EXPOSE 80
USER www-data
CMD ["apache2-foreground"]
