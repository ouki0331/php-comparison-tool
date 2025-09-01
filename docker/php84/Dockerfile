FROM php:8.4-cli

# 安装必要的扩展和工具
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install zip

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 设置工作目录
WORKDIR /app

# 保持容器运行
CMD ["tail", "-f", "/dev/null"]

