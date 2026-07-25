#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# Safo — Oracle Cloud Free Tier Deployment Script
# ═══════════════════════════════════════════════════════════════
# This script sets up a complete Safo environment on an
# Oracle Cloud Always Free ARM VM (2 OCPUs, 12 GB RAM).
#
# Prerequisites:
# - Oracle Cloud account (free, credit card for verification only)
# - Ubuntu 22.04 ARM instance created
# - SSH access to the instance
# - Domain name (optional, can use IP)
# ═══════════════════════════════════════════════════════════════

set -e

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  Safo — Oracle Cloud Free Tier Setup                    ║"
echo "╚═══════════════════════════════════════════════════════════╝"

# ── 1. System Update ──
echo "[1/8] Updating system..."
sudo apt update && sudo apt upgrade -y

# ── 2. Install PHP 8.2 ──
echo "[2/8] Installing PHP 8.2..."
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
    php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath \
    php8.2-gd php8.2-intl php8.2-redis php8.2-opcache php8.2-sqlite3

# ── 3. Install MySQL 8 ──
echo "[3/8] Installing MySQL 8..."
sudo apt install -y mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql

# Create database and user
sudo mysql -e "CREATE DATABASE IF NOT EXISTS safo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'safo'@'localhost' IDENTIFIED BY '$(openssl rand -base64 24)';"
sudo mysql -e "GRANT ALL PRIVILEGES ON safo.* TO 'safo'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# ── 4. Install Redis ──
echo "[4/8] Installing Redis..."
sudo apt install -y redis-server
sudo systemctl start redis-server
sudo systemctl enable redis-server

# ── 5. Install Nginx ──
echo "[5/8] Installing Nginx..."
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# ── 6. Install Composer ──
echo "[6/8] Installing Composer..."
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# ── 7. Clone and Setup Laravel ──
echo "[7/8] Setting up Laravel..."
cd /var/www
sudo git clone https://github.com/htsoftpro-ui/safo-app.git
cd safo-app/safo-backend
sudo composer install --no-dev --optimize-autoloader

# Create .env
sudo cp .env.example .env
DB_PASS=$(sudo mysql -e "SELECT authentication_string FROM mysql.user WHERE user='safo';" -sN)
sudo sed -i "s/DB_HOST=mysql/DB_HOST=127.0.0.1/" .env
sudo sed -i "s/DB_PASSWORD=secret/DB_PASSWORD=$DB_PASS/" .env
sudo sed -i "s/DB_DATABASE=safo/DB_DATABASE=safo/" .env
sudo sed -i "s/DB_USERNAME=safo/DB_USERNAME=safo/" .env
sudo sed -i "s/REDIS_HOST=redis/REDIS_HOST=127.0.0.1/" .env
sudo sed -i "s/APP_ENV=local/APP_ENV=production/" .env
sudo sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" .env

# Generate key and setup
sudo php artisan key:generate --force
sudo php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
sudo php artisan migrate --force
sudo php artisan db:seed --force
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan storage:link

# Permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# ── 8. Nginx Configuration ──
echo "[8/8] Configuring Nginx..."
sudo tee /etc/nginx/sites-available/safo > /dev/null << 'NGINX'
server {
    listen 80;
    server_name _;
    root /var/www/safo-app/safo-backend/public;
    index index.php;

    charset utf-8;
    server_tokens off;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
NGINX

sudo ln -sf /etc/nginx/sites-available/safo /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# ── Queue Worker (systemd) ──
sudo tee /etc/systemd/system/safo-queue.service > /dev/null << 'SERVICE'
[Unit]
Description=Safo Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/safo-app/safo-backend
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
SERVICE

sudo systemctl daemon-reload
sudo systemctl enable safo-queue
sudo systemctl start safo-queue

# ── Scheduler (cron) ──
(sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd /var/www/safo-app/safo-backend && php artisan schedule:run >> /dev/null 2>&1") | sudo crontab -u www-data -

# ── Install Certbot for SSL ──
sudo apt install -y certbot python3-certbot-nginx

# ── Open Firewall ──
sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 80 -j ACCEPT
sudo iptables -I INPUT 7 -m state --state NEW -p tcp --dport 443 -j ACCEPT
sudo netfilter-persistent save 2>/dev/null || true

# ── Get Public IP ──
PUBLIC_IP=$(curl -s ifconfig.me)

echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  ✅ DEPLOYMENT COMPLETE                                 ║"
echo "╠═══════════════════════════════════════════════════════════╣"
echo "║                                                          ║"
echo "║  API URL:        http://$PUBLIC_IP/api/v1/               ║"
echo "║  Health Check:   http://$PUBLIC_IP/api/v1/health         ║"
echo "║                                                          ║"
echo "║  Next Steps:                                             ║"
echo "║  1. Point your domain to $PUBLIC_IP                      ║"
echo "║  2. Run: sudo certbot --nginx -d your-domain.com        ║"
echo "║  3. Update APP_URL in .env                               ║"
echo "║                                                          ║"
echo "║  Test Accounts:                                          ║"
echo "║  Supplier: 771000001 / password123                       ║"
echo "║  Customer: 772000001 / password123                       ║"
echo "║                                                          ║"
echo "╚═══════════════════════════════════════════════════════════╝"
