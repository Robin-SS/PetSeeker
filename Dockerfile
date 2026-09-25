FROM php:8.2-apache

# Install PostgreSQL client headers and required libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module for clean URLs and routing
RUN a2enmod rewrite

# Configure Apache to allow .htaccess overrides and disable directory listings
RUN echo '<Directory /var/www/html/>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/petseeker.conf \
    && a2enconf petseeker

# Copy project files into web root
COPY . /var/www/html/

# Set appropriate directory permissions for Apache
RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html/

# Standard HTTP port
EXPOSE 80

CMD ["apache2-foreground"]