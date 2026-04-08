# Asset Management Backend API 🛠️

[![Laravel](https://img.shields.io/badge/Laravel-11+-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A comprehensive **Asset Management System Backend API** built with Laravel. Track IT assets, consumables, licenses, maintenance, support tickets, SSL certificates, transfers, and more across your organization.

## 🌟 Features

- **Asset Lifecycle Management**: Track assets (IT, Furniture, Vehicles) with dynamic specifications, warranty tracking, assignments, transfers, returns.
- **Consumables & Accessories**: Manage stock levels, colors, usage history.
- **Maintenance & Support**: Ticketing system, issues, scheduled/unscheduled maintenance with workflows.
- **SSL Certificate Monitoring**: Automated expiry alerts, change logs, escalations.
- **Inventory & Reports**: Categories, locations, departments, suppliers, purchase requests, detailed reports.
- **User Management**: Role-based permissions, OTP auth, activity logging.
- **Workflows**: Approval chains for transfers, returns, maintenance.
- **Notifications**: Email alerts for assignments, expiries, escalations.
- **API-First**: RESTful endpoints with Sanctum authentication.

## 🛠️ Tech Stack

- **Backend**: Laravel 11+, Eloquent ORM, Services/Observers pattern
- **Frontend Assets**: Vite, Tailwind CSS, PostCSS
- **Database**: MySQL/PostgreSQL (configurable)
- **Auth**: Laravel Sanctum (API tokens)
- **Queue/Jobs**: For emails, processing
- **Testing**: PHPUnit

## 📋 Prerequisites

- PHP >= 8.2
- Composer
- Node.js >= 18 & NPM
- MySQL 8.0+ or PostgreSQL
- Git

## 🚀 Quick Start (Development)

1. **Clone the repo**:
   ```
   git clone <your-repo-url>
   cd asset-management-backend
   ```

2. **Copy environment file**:
   ```
   cp .env.example .env
   ```

3. **Configure `.env`**:
   ```
   APP_NAME="Asset Management"
   APP_URL=http://localhost:8000
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=asset_management
   DB_USERNAME=root
   DB_PASSWORD=

   # Mail (for notifications)
   MAIL_MAILER=smtp
   MAIL_HOST=mailpit
   MAIL_PORT=1025
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   MAIL_ENCRYPTION=null
   MAIL_FROM_ADDRESS="hello@example.com"
   MAIL_FROM_NAME="${APP_NAME}"
   ```

4. **Install PHP dependencies**:
   ```
   composer install --optimize-autoloader --no-dev
   ```

5. **Install Node dependencies & build assets**:
   ```
   npm install
   npm run build
   ```

6. **Generate app key**:
   ```
   php artisan key:generate
   ```

7. **Run migrations & seed** (create DB first):
   ```
   php artisan migrate --seed
   ```

8. **Start the development server**:
   ```
   php artisan serve
   ```

   API available at `http://localhost:8000/api`

## 🔑 API Usage

- **Authentication**: POST `/api/login` with email/password → returns token.
- **Protected routes**: Include `Authorization: Bearer {token}` header.
- **Key Endpoints** (browse `routes/api.php` for full list):
  - `GET /api/assets` - List assets
  - `POST /api/assets` - Create asset
  - `GET /api/tickets` - Support tickets
  - `GET /api/ssl-certificates` - SSL monitoring
- **Swagger/Postman**: Add later or generate via artisan package.

Example login (test user after seeding):
```
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

## 🧪 Testing

```
composer install # ensure dev deps
php artisan test
# or
./vendor/bin/phpunit
```

## 🚀 Deployment

- **Laravel Forge/Vapor**: Standard Laravel deploy.
- **Docker**: Create Dockerfile/docker-compose.yml.
- **Heroku**: `heroku create`, set env vars, run migrations.

1. Optimize for production:
   ```
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   composer install --optimize-autoloader --no-dev
   npm ci && npm run build
   ```

2. Set production `.env` (APP_ENV=production, DB creds, queue worker).

3. Run migrations: `php artisan migrate --force`

4. Supervisor for queues: `php artisan queue:work`

## 📚 Additional Notes

- **Seeding**: Run `php artisan db:seed` for sample data.
- **Queues**: Use `php artisan queue:work` or Redis/Horizon.
- **Dynamic Fields**: Assets support spec tables (ITAssetSpecification, etc.).
- **Observers**: Auto-log activities, notifications.

## 🤝 Contributing

1. Fork & clone.
2. Create feature branch: `git checkout -b feature/AmazingFeature`
3. Commit: `git commit -m 'Add some AmazingFeature'`
4. Push & PR.

See [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📄 License

MIT License. See [LICENSE](LICENSE) file.

## 🙏 Support

[Report issues](https://github.com/yourusername/asset-management-backend/issues) | [Feature requests](https://github.com/yourusername/asset-management-backend/discussions)

---

⭐ **Star this repo if it helps!**

