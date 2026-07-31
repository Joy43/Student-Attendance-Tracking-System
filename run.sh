#!/bin/bash
set -e

# ANSI Color Codes
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}==================================================${NC}"
echo -e "${CYAN}🚀 Starting Attendance Tracking System Setup...${NC}"
echo -e "${BLUE}==================================================${NC}"

# 1. Clean macOS metadata files
echo -e "🧹 Cleaning up macOS temporary companion files..."
find . -name "._*" -delete || true

# 2. Build and start containers
echo -e "🐳 Building PHP web image and launching containers..."
docker build -t attapp-web:latest - < Dockerfile
docker-compose up -d

# 3. Wait for MySQL container to become ready
echo -e "⏳ Waiting for MySQL database to initialize..."
until docker-compose exec -T db mysqladmin ping -h localhost -u root --password=attapp_password --silent &>/dev/null; do
    echo -n "."
    sleep 1
done
echo -e "\n✅ Database container is fully online."

# 4. Initialize Database schema & mock data
echo -e "🗄️  Running database schema setup and seed scripts..."
docker-compose exec -T web php /var/www/html/attapp/database/createtable.php > /dev/null

echo -e "\n${GREEN}🎉 System is fully online and ready!${NC}"
echo -e "${BLUE}--------------------------------------------------${NC}"
echo -e "${YELLOW}👉 Faculty Portal (Web App):${NC} ${GREEN}http://localhost:8080/attapp/login/index.php${NC}"
echo -e "   Faculty Credentials: Username: ${CYAN}anika${NC} | Password: ${CYAN}123${NC}"
echo -e "${BLUE}--------------------------------------------------${NC}"
echo -e "${YELLOW}👉 Database Admin Panel (phpMyAdmin):${NC} ${GREEN}http://localhost:8081${NC}"
echo -e "${BLUE}--------------------------------------------------${NC}"
echo -e "${YELLOW}👉 API Docs (Swagger UI):${NC} ${GREEN}http://localhost:8082${NC}"
echo -e "${BLUE}--------------------------------------------------${NC}"
