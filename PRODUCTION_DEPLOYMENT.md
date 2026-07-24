# Production Deployment Guide — Safo Backend

## Server Requirements

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 2 GB | 4 GB |
| Storage | 20 GB SSD | 50 GB SSD |
| OS | Ubuntu 22.04+ | Ubuntu 24.04 LTS |

## Software Stack

| Component | Version |
|-----------|---------|
| PHP | 8.2+ (8.3 recommended) |
| Laravel | 12.x |
| MySQL | 8.0 |
| Redis | 7.x |
| Nginx | 1.24+ |
| Node.js | 20 LTS (for asset building if needed) |

## Step-by-Step Deployment

### 1. Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
    php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath \
    php8.2-gd php8.2-intl php8.2-redis php8.2-opcache

# Install MySQL 8
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install Redis
sudo apt install -y redis-server
sudo systemctl enable redis-server

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

### 2. MySQL Setup

```sql
CREATE DATABASE safo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'safo'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON safo.* TO 'safo'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Application Setup

```bash
# Clone repository
cd /var/www
git clone https://github.com/htsoftpro-ui/safo-app.git
cd safo-app/safo-backend

# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.example .env
# Edit .env with production values:
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://api.safo.app
#   DB_PASSWORD=STRONG_PASSWORD_HERE
#   REDIS_HOST=127.0.0.1

# Generate key
php artisan key:generate --force

# Publish Sanctum migrations
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Run migrations
php artisan migrate --force

# Seed (optional — remove in production)
php artisan db:seed --force

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Link storage
php artisan storage:link
```

### 4. Nginx Configuration

```nginx
server {
    listen 80;
    server_name api.safo.app;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.safo.app;
    root /var/www/safo-app/safo-backend/public;
    index index.php;

    # SSL (use Certbot for Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/api.safo.app/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.safo.app/privkey.pem;

    # Security
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Gzip
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
```

### 5. SSL with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d api.safo.app
sudo certbot renew --dry-run
```

### 6. Queue Worker (systemd)

Create `/etc/systemd/system/safo-queue.service`:

```ini
[Unit]
Description=Safo Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/safo-app/safo-backend
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable safo-queue
sudo systemctl start safo-queue
```

### 7. Scheduler (Cron)

```bash
sudo crontab -u www-data -e
# Add:
* * * * * cd /var/www/safo-app/safo-backend && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Backups

```bash
# Daily MySQL backup cron
0 2 * * * mysqldump -u safo -p'STRONG_PASSWORD' safo | gzip > /backups/safo-$(date +\%Y\%m\%d).sql.gz
# Keep 30 days
find /backups -name "safo-*.sql.gz" -mtime +30 -delete
```

### 9. Monitoring

```bash
# Health check endpoint
curl https://api.safo.app/api/v1/health
# Expected: {"status":"ok","version":"1.0.0"}

# Log monitoring
tail -f /var/www/safo-app/safo-backend/storage/logs/laravel.log
```

## Environment Variables (Production)

| Variable | Value | Notes |
|----------|-------|-------|
| APP_ENV | production | |
| APP_DEBUG | false | **Never true in production** |
| APP_URL | https://api.safo.app | |
| DB_PASSWORD | (strong password) | Never commit to git |
| REDIS_HOST | 127.0.0.1 | |
| MAIL_MAILER | smtp | Configure real SMTP |
| FIREBASE_PROJECT_ID | (your project) | For push notifications |

## Security Checklist

- [ ] APP_DEBUG=false
- [ ] Strong DB password
- [ ] Redis password set
- [ ] SSL enabled
- [ ] Rate limiting configured
- [ ] CORS configured for frontend domain
- [ ] No .env in git
- [ ] No secrets in code
- [ ] File permissions correct (775 for storage)
- [ ] MySQL only accessible locally
