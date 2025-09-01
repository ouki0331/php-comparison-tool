#!/bin/bash

echo "🛑 停止 Docker 容器..."
cd docker
docker-compose down

echo "✅ Docker 容器已停止"

