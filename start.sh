#!/bin/bash

echo "🐳 PHP 版本比较工具 - Docker 环境"
echo "===================================="

# 检查 Docker 是否运行
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker 未运行，请先启动 Docker Desktop"
    exit 1
fi

echo "📦 构建并启动 Docker 容器..."
cd docker
docker compose up -d

echo "⏳ 等待容器启动..."
sleep 5

# 检查容器状态
if ! docker ps | grep -q "php72-env"; then
    echo "❌ PHP 7.2 容器启动失败"
    exit 1
fi

if ! docker ps | grep -q "php84-env"; then
    echo "❌ PHP 8.4 容器启动失败"
    exit 1
fi

echo "✅ Docker 容器启动成功"

# 回到主目录
cd ..

# 安装 Composer 依赖（如果需要）
if [ ! -d "vendor" ]; then
    echo "📦 安装 Composer 依赖..."
    docker exec php84-env composer install
fi

echo "🚀 开始执行比较测试..."
docker exec php84-env php src/run_comparison.php

echo ""
echo "🎉 测试完成！请查看 output 目录中的 Excel 报告文件。"

