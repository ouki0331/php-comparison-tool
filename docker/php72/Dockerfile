FROM php:7.2-cli

# 更新软件源到归档源
RUN sed -i 's/deb.debian.org/archive.debian.org/g' /etc/apt/sources.list && \
    sed -i 's/security.debian.org/archive.debian.org/g' /etc/apt/sources.list && \
    sed -i '/stretch-updates/d' /etc/apt/sources.list

# 安装必要的扩展和工具
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# 安装 Composer
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# 设置工作目录
WORKDIR /app

# 保持容器运行
CMD ["tail", "-f", "/dev/null"]

