FROM php:7-apache

RUN apt-get update && \
    apt-get install -y git zlib1g-dev imagemagick libjpeg-dev libpng-dev \
        mariadb-client libzip-dev && \
docker-php-ext-install zip mysqli gd

COPY ./ /var/www/html/

# Amazon CodePipeline / CodeBuild is actually pretty terrible and drops
# execute bits somewhere during the build process. So let's fix this here.
RUN chmod +x /var/www/html/worker
