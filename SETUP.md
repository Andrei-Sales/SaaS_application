# Quick Setup Guide

This document provides step-by-step instructions to get the application running.

## Prerequisites

Ensure you have installed:
- PHP 8.2+
- Composer
- MySQL 8.0+ or PostgreSQL 12+
- Node.js & NPM

## Quick Start (5 minutes)

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Database

Edit `.env` and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoice_saas
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Run Migrations & Seed

```bash
php artisan migrate --seed
```

This creates the database structure and demo data.

### 5. Build Assets

```bash
npm run build
```

### 6. Start Application

Terminal 1 - Web Server:
```bash
php artisan serve
```

Terminal 2 - Queue Worker:
```bash
php artisan queue:work
```

### 7. Access Application

Open browser to: **http://localhost:8000**

## Demo Login Credentials

### Company 1: Demo Company (Pro Plan)
- Owner: `owner@democompany.com` / `password`
- Member: `member@democompany.com` / `password`

### Company 2: Test Company (Free Plan)
- Owner: `test@testcompany.com` / `password`

## Next Steps

1. **Install Laravel Breeze** (if you want to customize auth):
   ```bash
   composer require laravel/breeze --dev
   php artisan breeze:install blade
   npm install && npm run build
   php artisan migrate
   ```

2. **Configure Stripe** (for payments):
   - Get API keys from Stripe Dashboard
   - Add to `.env`:
     ```env
     STRIPE_KEY=your_publishable_key
     STRIPE_SECRET=your_secret_key
     ```

3. **Configure Email** (for invoice emails):
   
   For development (logs emails):
   ```env
   MAIL_MAILER=log
   ```
   
   For production (e.g., Mailgun):
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailgun.org
   MAIL_PORT=587
   MAIL_USERNAME=your_username
   MAIL_PASSWORD=your_password
   MAIL_ENCRYPTION=tls
   ```

## Running Tests

```bash
php artisan test
```

## Troubleshooting

### Issue: "Access denied for user"
**Solution**: Check database credentials in `.env`

### Issue: "Class not found"
**Solution**: Run `composer dump-autoload`

### Issue: "Storage not writable"
**Solution**: 
```bash
chmod -R 775 storage bootstrap/cache
```

### Issue: "Queue not processing"
**Solution**: Make sure queue worker is running:
```bash
php artisan queue:work
```

### Issue: "Assets not loading"
**Solution**: Run asset compilation:
```bash
npm run build
```

## Development Tools (Optional)

### Laravel Telescope (Debugging)
```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

### Laravel Debugbar
```bash
composer require barryvdh/laravel-debugbar --dev
```

### IDE Helper
```bash
composer require --dev barryvdh/laravel-ide-helper
php artisan ide-helper:generate
```

## Production Optimization

Before deploying to production:

```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

## Need Help?

Check the main [README.md](README.md) for:
- Complete architecture documentation
- Multi-tenancy implementation details
- Testing guide
- Project structure overview
