# Dockerfile
FROM php:7.1-zts

# Install Composer, NPM
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    ;apt update -y \
    ;apt install -y nodejs npm git zip unzip \
    ;npm i -g npm

CMD ["/usr/local/bin/composer", "docker"]
