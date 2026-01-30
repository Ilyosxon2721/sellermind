#!/bin/bash

###############################################################################
# SellerMind AI - Quick Migration & Cache Clear Script
# Run this after git pull to apply database changes
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Auto-detect app directory
if [ -d "/var/www/sellermind" ]; then
    APP_DIR="/var/www/sellermind"
elif [ -d "/home/*/public_html" ]; then
    APP_DIR=$(ls -d /home/*/public_html 2>/dev/null | head -1)
else
    APP_DIR=$(pwd)
fi

echo -e "${BLUE}"
echo "========================================"
echo "  SellerMind - Quick Deploy"
echo "========================================"
echo -e "${NC}"
echo -e "App directory: ${YELLOW}$APP_DIR${NC}"
echo ""

cd "$APP_DIR"

# Step 1: Pull latest changes
echo -e "${BLUE}[1/5] Pulling latest changes...${NC}"
git pull origin main 2>/dev/null || echo -e "${YELLOW}Git pull skipped (not a git repo or no access)${NC}"
echo ""

# Step 2: Run migrations
echo -e "${BLUE}[2/5] Running database migrations...${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Migrations complete${NC}"
echo ""

# Step 3: Clear and rebuild cache
echo -e "${BLUE}[3/5] Clearing cache...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo -e "${GREEN}✓ Cache cleared${NC}"
echo ""

# Step 4: Rebuild cache for production
echo -e "${BLUE}[4/5] Rebuilding cache...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Cache rebuilt${NC}"
echo ""

# Step 5: Gracefully restart queue workers
echo -e "${BLUE}[5/5] Restarting queue workers...${NC}"
php artisan queue:restart
echo -e "${GREEN}✓ Queue restart signal sent (workers will restart after current job)${NC}"
echo ""

echo -e "${GREEN}"
echo "========================================"
echo "  ✓ DEPLOY COMPLETE!"
echo "========================================"
echo -e "${NC}"
