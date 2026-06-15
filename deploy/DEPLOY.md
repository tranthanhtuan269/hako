# Production deploy (Ubuntu + Nginx + MySQL)

## Requirements

- PHP 8.1+ with FPM, Nginx, MySQL 8, Composer

## App path

```bash
sudo mkdir -p /var/www/thuoc360
sudo rsync -a --exclude='.git' --exclude='node_modules' --exclude='public/storage' --exclude='.env' ./ /var/www/thuoc360/
cd /var/www/thuoc360
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
```

Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://thuoc360.com`, MySQL credentials, and `SITE_*` vars (see `.env.example`).

## Database

```bash
php artisan migrate --seed --force
```

## Nginx & SSL

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/thuoc360
sudo ln -sf /etc/nginx/sites-available/thuoc360 /etc/nginx/sites-enabled/thuoc360
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d thuoc360.com -d www.thuoc360.com
```

## Permissions & cache

```bash
sudo chown -R www-data:www-data /var/www/thuoc360
sudo chmod -R 775 storage bootstrap/cache
sudo bash deploy/post-deploy.sh /var/www/thuoc360
```

Or manually:

```bash
rm -f /var/www/thuoc360/public/storage
ln -sfn /var/www/thuoc360/storage/app/public /var/www/thuoc360/public/storage
sudo -u www-data php artisan config:cache view:cache
```

Admin: `https://thuoc360.com/login` — `admin@thuoc360.com` / `password` (change after deploy).
