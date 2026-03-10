############################################
# Base Image
############################################

# Learn more about the Server Side Up PHP Docker Images at:
# https://serversideup.net/open-source/docker-php/
FROM serversideup/php:8.4-fpm-nginx-alpine AS base

## Uncomment if you need to install additional PHP extensions
# USER root
# RUN install-php-extensions bcmath gd

############################################
# Development Image
############################################
FROM base AS development

# We can pass USER_ID and GROUP_ID as build arguments
# to ensure the www-data user has the same UID and GID
# as the user running Docker.
ARG USER_ID
ARG GROUP_ID

# Switch to root so we can set the user ID and group ID
USER root

# Set the user ID and group ID for www-data
RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID  && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

# Drop privileges back to www-data    
USER www-data

############################################
# CI image
############################################
FROM base AS ci

# Sometimes CI images need to run as root
# so we set the ROOT user and configure
# the PHP-FPM pool to run as www-data
USER root
RUN echo "user = www-data" >> /usr/local/etc/php-fpm.d/docker-php-serversideup-pool.conf && \
    echo "group = www-data" >> /usr/local/etc/php-fpm.d/docker-php-serversideup-pool.conf

############################################
# Composer Stage (provides vendor for Vite build)
############################################
FROM composer:2 AS composer
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize

############################################
# Vite Build Stage
############################################
FROM node:20-alpine AS vite-build
WORKDIR /app

# Copy vendor from Composer stage (needed for flux.css and @source paths)
COPY --from=composer /app/vendor ./vendor

# Copy package files and install dependencies
COPY package.json yarn.lock ./
RUN yarn install

# Copy source files needed for build
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

# Build Vite assets (outputs to public/build/)
RUN yarn build

############################################
# Production Image
############################################
FROM base AS deploy
COPY --chown=www-data:www-data . /var/www/html

# Copy built Vite assets from build stage
COPY --from=vite-build --chown=www-data:www-data /app/public/build /var/www/html/public/build

# Override .env with production config so app uses credentials from .env.production.
# Spin deploy loads .env.production before build; ensure it exists in project root.
COPY --chown=www-data:www-data .env.production /var/www/html/.env

# Never ship Vite's dev-server indicator file to production.
# If present, Laravel will try to load assets from the Vite dev server
# (e.g. http://localhost:5173) instead of /public/build.
RUN rm -f /var/www/html/public/hot

# Create the SQLite directory and set the owner to www-data (remove this if you're not using SQLite)
RUN mkdir -p /var/www/html/.infrastructure/volume_data/sqlite/ && \
    chown -R www-data:www-data /var/www/html/.infrastructure/volume_data/sqlite/

USER www-data