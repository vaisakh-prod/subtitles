# Use an official PHP CLI image
FROM php:8.2-cli

# Set the base working directory for the application
WORKDIR /app

# Copy all files into the container's working directory
COPY . /app

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install dependencies without dev requirements
RUN composer install --no-dev

# Change the working directory to the build folder
WORKDIR /app/build

# Disable phar.readonly by appending to the PHP configuration
RUN echo "phar.readonly = Off" >> /usr/local/etc/php/php.ini

# Run the build script
RUN php docker_build.php

# Return to the main application directory
WORKDIR /app

# Default command (can be overridden)
ENTRYPOINT ["php", "subtitles.phar"]
