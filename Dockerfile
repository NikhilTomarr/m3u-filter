FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy all files
COPY . /var/www/html/

# Apache config
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 8080
