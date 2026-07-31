FROM php:8.2-apache

# Install and enable mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Expose Apache default port
EXPOSE 80
