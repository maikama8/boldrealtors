#!/bin/bash

# cPanel Quick Setup Script for Zarachtech Real Estate Platform
# Run this script via SSH after uploading files

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║     Zarachtech cPanel Setup Script                          ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Get current directory
CURRENT_DIR=$(pwd)
echo -e "${YELLOW}Current directory: ${CURRENT_DIR}${NC}"
echo ""

# Step 1: Check if .env exists
echo "Step 1: Checking .env file..."
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠ .env file not found. Creating from .env.example...${NC}"
    if [ -f .env.example ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ .env file created${NC}"
    else
        echo -e "${RED}✗ .env.example not found. Please create .env manually.${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✓ .env file exists${NC}"
fi
echo ""

# Step 2: Set permissions
echo "Step 2: Setting file permissions..."
find . -type d -exec chmod 755 {} \; 2>/dev/null
find . -type f -exec chmod 644 {} \; 2>/dev/null
chmod -R 775 storage bootstrap/cache 2>/dev/null
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

# Step 3: Install Composer dependencies
echo "Step 3: Installing Composer dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Composer dependencies installed${NC}"
    else
        echo -e "${RED}✗ Composer install failed${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠ Composer not found. Please install manually:${NC}"
    echo "   curl -sS https://getcomposer.org/installer | php"
    echo "   php composer.phar install --no-dev --optimize-autoloader"
fi
echo ""

# Step 4: Generate application key
echo "Step 4: Generating application key..."
if command -v php &> /dev/null; then
    php artisan key:generate --force 2>/dev/null
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Application key generated${NC}"
    else
        echo -e "${YELLOW}⚠ Key generation skipped (may already exist)${NC}"
    fi
else
    echo -e "${RED}✗ PHP not found${NC}"
    exit 1
fi
echo ""

# Step 5: Run migrations
echo "Step 5: Running database migrations..."
read -p "Do you want to run migrations now? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Migrations completed${NC}"
    else
        echo -e "${RED}✗ Migrations failed. Please check database configuration.${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Migrations skipped. Run manually: php artisan migrate --force${NC}"
fi
echo ""

# Step 6: Clear caches
echo "Step 6: Clearing caches..."
php artisan cache:clear 2>/dev/null
php artisan config:clear 2>/dev/null
php artisan route:clear 2>/dev/null
php artisan view:clear 2>/dev/null
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

# Step 7: Create storage link
echo "Step 7: Creating storage link..."
php artisan storage:link 2>/dev/null
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Storage link created${NC}"
else
    echo -e "${YELLOW}⚠ Storage link may already exist${NC}"
fi
echo ""

# Step 8: Cache for production
echo "Step 8: Caching for production..."
read -p "Do you want to cache configuration for production? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo -e "${GREEN}✓ Production caches created${NC}"
else
    echo -e "${YELLOW}⚠ Production caching skipped${NC}"
fi
echo ""

# Summary
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                    Setup Summary                             ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✓ Setup completed!${NC}"
echo ""
echo "Next steps:"
echo "1. Verify .env file configuration"
echo "2. Run migrations if not done: php artisan migrate --force"
echo "3. Visit your domain to complete installation"
echo "4. Access admin panel at: /admin"
echo ""
echo "Verification commands:"
echo "  php artisan migrate:status  # Check migration status"
echo "  php artisan route:list       # List all routes"
echo "  php artisan --version        # Check Laravel version"
echo ""

