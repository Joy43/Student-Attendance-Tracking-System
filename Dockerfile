FROM php:8.2-apache

# Install and enable mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy the application code into the container's web root
COPY . /var/www/html/attapp
COPY index.php /var/www/html/index.php

# Expose Apache default port
EXPOSE 80
