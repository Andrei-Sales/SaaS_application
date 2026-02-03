# Multi-Tenant Invoice & Subscription Management SaaS

A production-ready Laravel 10 SaaS application for multi-tenant invoice and subscription management. Built with clean architecture, SOLID principles, and Laravel best practices.

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture Overview](#architecture-overview)
- [Multi-Tenancy Implementation](#multi-tenancy-implementation)
- [Installation](#installation)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [Testing](#testing)
- [Project Structure](#project-structure)
- [Key Design Decisions](#key-design-decisions)
- [Demo Accounts](#demo-accounts)

## Features

### Core Functionality
- ✅ **Multi-tenant architecture** with complete data isolation
- ✅ **Invoice Management** (CRUD operations with status tracking)
- ✅ **Subscription Management** (Free & Pro plans)
- ✅ **Role-based Access Control** (Owner & Member roles)
- ✅ **PDF Generation** for invoices
- ✅ **Email Notifications** via queued jobs
- ✅ **Event-driven architecture** (InvoiceCreated, InvoicePaid events)
- ✅ **Feature restrictions** based on subscription plans
- ✅ **Comprehensive test coverage**

### Technical Features
- Thin controllers with business logic in service classes
- Form Request validation
- Policy-based authorization
- Global query scopes for tenant isolation
- Database queue driver
- Factory and seeder support
- Soft deletes for invoices
- Caching for performance optimization

## Tech Stack

- **Framework**: Laravel 10.x
- **PHP**: 8.2+
- **Database**: MySQL/PostgreSQL
- **Frontend**: Blade templates with Tailwind CSS
- **Authentication**: Laravel Breeze
- **PDF Generation**: barryvdh/laravel-dompdf
- **Queue**: Database driver
- **Testing**: PHPUnit
- **Payment Processing**: Stripe (mock/test mode)

## Architecture Overview

### Clean Architecture Principles

This application follows a clean, maintainable architecture:

```
┌─────────────────────────────────────────────┐
│              Controllers                     │
│        (Thin, delegates to services)        │
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────▼──────────────────────────┐
│              Services                        │
│    (Business logic, orchestration)          │
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────▼──────────────────────────┐
│              Models                          │
│  (Eloquent ORM, relationships, scopes)      │
└─────────────────────────────────────────────┘
```

### Layer Responsibilities

1. **Controllers** (`app/Http/Controllers/`)
   - Handle HTTP requests/responses
   - Validate via Form Requests
   - Authorize via Policies
   - Delegate business logic to Services
   - Return views or redirects

2. **Services** (`app/Services/`)
   - **InvoiceService**: Invoice CRUD, status management, statistics
   - **SubscriptionService**: Plan management, feature access control
   - **PdfService**: PDF generation and streaming

3. **Models** (`app/Models/`)
   - Eloquent relationships
   - Query scopes
   - Model events
   - Accessors/Mutators

4. **Policies** (`app/Policies/`)
   - InvoicePolicy: Invoice authorization rules
   - CompanyPolicy: Company management authorization

5. **Jobs** (`app/Jobs/`)
   - SendInvoiceEmail: Queued email sending

6. **Events & Listeners** (`app/Events/`, `app/Listeners/`)
   - InvoiceCreated → Logging and notifications
   - InvoicePaid → Payment confirmations and tracking

## Multi-Tenancy Implementation

### Overview

The application implements **row-level multi-tenancy** where:
- Each tenant is represented by a `Company`
- All users belong to one company
- All data (invoices) is scoped to a company
- Data isolation is enforced at multiple layers

### Implementation Layers

#### 1. Database Layer

```sql
-- Every tenant-specific table has company_id
companies (id, name, email, ...)
users (id, company_id, role, ...)
invoices (id, company_id, ...)
subscriptions (id, company_id, plan, status, ...)
```

#### 2. Model Layer (Global Scopes)

**BelongsToTenant Trait** (`app/Models/Concerns/BelongsToTenant.php`)
```php
// Automatically applied to tenant-specific models
trait BelongsToTenant {
    protected static function bootBelongsToTenant() {
        // Add global scope to filter by company_id
        static::addGlobalScope(new TenantScope);
        
        // Auto-set company_id on create
        static::creating(function ($model) {
            if (auth()->check() && !$model->company_id) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }
}
```

**TenantScope** (`app/Models/Scopes/TenantScope.php`)
```php
// Global scope that filters all queries by authenticated user's company
public function apply(Builder $builder, Model $model) {
    if (auth()->check() && auth()->user()->company_id) {
        $builder->where('company_id', auth()->user()->company_id);
    }
}
```

#### 3. Middleware Layer

**EnsureTenantAccess** (`app/Http/Middleware/EnsureTenantAccess.php`)
- Verifies authenticated user has a valid company
- Applied to all protected routes via `tenant` middleware alias

#### 4. Policy Layer

Policies include company_id checks:
```php
public function view(User $user, Invoice $invoice): bool {
    return $user->company_id === $invoice->company_id;
}
```

### Data Isolation Guarantees

1. **Query Scope**: Global scope filters all Invoice queries automatically
2. **Manual Checks**: Policies verify company ownership
3. **Route Protection**: Middleware ensures valid tenant context
4. **Factory/Seeder**: Proper company_id assignment in tests

### Why This Approach?

✅ **Simple**: Single database, standard Laravel patterns  
✅ **Secure**: Multiple layers of isolation  
✅ **Scalable**: Works for small to medium tenant counts  
✅ **Cost-effective**: No database-per-tenant overhead  
✅ **Developer-friendly**: Standard Eloquent queries work as expected

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0+ or PostgreSQL 12+
- Node.js & NPM (for asset compilation)

### Step-by-Step Setup

1. **Clone the repository**
   ```bash
   cd invoice-saas
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Configure database**
   
   Edit `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=invoice_saas
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

7. **Configure mail settings** (for email features)
   ```env
   MAIL_MAILER=log  # Use 'log' for development
   MAIL_FROM_ADDRESS=noreply@invoicesaas.com
   MAIL_FROM_NAME="${APP_NAME}"
   ```

8. **Run migrations**
   ```bash
   php artisan migrate
   ```

9. **Seed database** (optional, creates demo data)
   ```bash
   php artisan db:seed
   ```

10. **Create jobs table** (for queue)
    ```bash
    php artisan queue:table
    php artisan migrate
    ```

11. **Compile assets**
    ```bash
    npm run build
    ```

## Configuration

### Queue Configuration

The application uses database queues. Configure in `.env`:

```env
QUEUE_CONNECTION=database
```

### Subscription Plans

Plans are defined in `SubscriptionService`:
- **Free Plan**: 10 invoices max, PDF export only
- **Pro Plan**: Unlimited invoices, all features

Modify `app/Services/SubscriptionService.php` to adjust limits.

### PDF Configuration

PDF generation uses DomPDF. Configuration in `config/dompdf.php` (auto-generated).

## Running the Application

### Development Server

1. **Start Laravel server**
   ```bash
   php artisan serve
   ```
   Application available at: `http://localhost:8000`

2. **Start queue worker** (separate terminal)
   ```bash
   php artisan queue:work
   ```

3. **Watch assets** (optional, for development)
   ```bash
   npm run dev
   ```

### Access Points

- **Homepage**: http://localhost:8000
- **Dashboard**: http://localhost:8000/dashboard
- **Invoices**: http://localhost:8000/invoices
- **Subscriptions**: http://localhost:8000/subscriptions

## Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Invoice tests
php artisan test --filter InvoiceTest

# Tenant isolation tests
php artisan test --filter TenantIsolationTest

# Subscription tests
php artisan test --filter SubscriptionTest
```

### Test Coverage

- ✅ Invoice CRUD operations
- ✅ Tenant data isolation
- ✅ Subscription plan limits
- ✅ Role-based authorization
- ✅ Invoice status changes
- ✅ Policy enforcement

## Project Structure

```
app/
├── Events/                 # Domain events
│   ├── InvoiceCreated.php
│   └── InvoicePaid.php
├── Http/
│   ├── Controllers/        # Thin controllers
│   │   ├── InvoiceController.php
│   │   └── SubscriptionController.php
│   ├── Middleware/         # Custom middleware
│   │   └── EnsureTenantAccess.php
│   └── Requests/           # Form validation
│       ├── StoreInvoiceRequest.php
│       └── UpdateInvoiceRequest.php
├── Jobs/                   # Queued jobs
│   └── SendInvoiceEmail.php
├── Listeners/              # Event listeners
│   ├── SendInvoiceCreatedNotification.php
│   └── SendInvoicePaidNotification.php
├── Mail/                   # Mailable classes
│   └── InvoiceMail.php
├── Models/                 # Eloquent models
│   ├── Company.php
│   ├── Invoice.php
│   ├── Subscription.php
│   ├── User.php
│   ├── Concerns/           # Model traits
│   │   └── BelongsToTenant.php
│   └── Scopes/             # Query scopes
│       └── TenantScope.php
├── Policies/               # Authorization policies
│   ├── CompanyPolicy.php
│   └── InvoicePolicy.php
└── Services/               # Business logic
    ├── InvoiceService.php
    ├── PdfService.php
    └── SubscriptionService.php

database/
├── factories/              # Model factories
│   ├── CompanyFactory.php
│   ├── InvoiceFactory.php
│   ├── SubscriptionFactory.php
│   └── UserFactory.php
├── migrations/             # Database migrations
└── seeders/                # Database seeders

resources/
├── views/
│   ├── layouts/            # Layout templates
│   ├── invoices/           # Invoice views
│   ├── subscriptions/      # Subscription views
│   ├── emails/             # Email templates
│   └── pdf/                # PDF templates

tests/
└── Feature/                # Feature tests
    ├── InvoiceTest.php
    ├── SubscriptionTest.php
    └── TenantIsolationTest.php
```

## Key Design Decisions

### 1. Service Layer Pattern

**Why**: Keeps controllers thin and business logic reusable

**Implementation**:
- Controllers handle HTTP concerns only
- Services contain business logic
- Services are injected via dependency injection

### 2. Policy-Based Authorization

**Why**: Centralized, reusable authorization logic

**Implementation**:
- InvoicePolicy: Invoice-specific rules
- CompanyPolicy: Company management rules
- Gates: Subscription feature checks

### 3. Event-Driven Architecture

**Why**: Decoupled, extensible system for side effects

**Implementation**:
- Events dispatched at key moments
- Listeners handle side effects (logging, notifications)
- Queued listeners for async processing

### 4. Global Query Scopes

**Why**: Automatic tenant isolation without manual filtering

**Implementation**:
- TenantScope applies to all invoice queries
- Transparent to developers
- Can be disabled when needed (withoutGlobalScopes())

### 5. Form Request Validation

**Why**: Clean separation of validation logic

**Implementation**:
- StoreInvoiceRequest: Creation validation
- UpdateInvoiceRequest: Update validation
- Authorization checks included

### 6. Factory Pattern for Testing

**Why**: Easy test data generation

**Implementation**:
- Factories for all models
- State methods (e.g., `paid()`, `draft()`)
- Relationships handled automatically

## Demo Accounts

After running `php artisan db:seed`, you can log in with:

### Demo Company (Pro Plan)
- **Owner**: owner@democompany.com / password
- **Member**: member@democompany.com / password

### Test Company (Free Plan - Trial)
- **Owner**: test@testcompany.com / password

## Common Tasks

### Create a New Migration

```bash
php artisan make:migration create_table_name --create=table_name
```

### Create a New Controller

```bash
php artisan make:controller ControllerName
```

### Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Run Specific Migration

```bash
php artisan migrate --path=/database/migrations/filename.php
```

### Generate IDE Helper (optional)

```bash
composer require --dev barryvdh/laravel-ide-helper
php artisan ide-helper:generate
php artisan ide-helper:models
```

## Production Deployment Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `APP_KEY`
- [ ] Configure production database
- [ ] Set up proper queue worker (Supervisor)
- [ ] Configure mail driver (SMTP, Mailgun, etc.)
- [ ] Set up Stripe live API keys
- [ ] Enable HTTPS
- [ ] Set up regular backups
- [ ] Configure proper logging
- [ ] Set up monitoring (Laravel Telescope, Sentry, etc.)
- [ ] Optimize autoloader: `composer install --optimize-autoloader --no-dev`
- [ ] Cache configuration: `php artisan config:cache`
- [ ] Cache routes: `php artisan route:cache`
- [ ] Cache views: `php artisan view:cache`

## Troubleshooting

### Queue Jobs Not Processing

```bash
# Check queue worker is running
php artisan queue:work

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### PDF Generation Issues

```bash
# Clear view cache
php artisan view:clear

# Check storage permissions
chmod -R 775 storage
```

### Migration Issues

```bash
# Rollback and re-migrate
php artisan migrate:fresh

# With seeding
php artisan migrate:fresh --seed
```

## License

This project is for demonstration and interview purposes.

## Author

Built as a senior Laravel engineer interview project demonstrating:
- Clean architecture
- SOLID principles
- Laravel best practices
- Production-ready code quality
- Comprehensive testing
- Clear documentation
