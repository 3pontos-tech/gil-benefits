# Developer Documentation

## Overview

This document provides comprehensive technical documentation for developers working on the Laravel application. It covers architecture decisions, coding standards, development workflows, and implementation details.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Development Environment](#development-environment)
3. [Coding Standards](#coding-standards)
4. [Database Design](#database-design)
5. [Testing Strategy](#testing-strategy)
6. [Performance Guidelines](#performance-guidelines)
7. [Security Implementation](#security-implementation)
8. [Deployment Guide](#deployment-guide)
9. [Troubleshooting](#troubleshooting)

---

## Architecture Overview

### Technology Stack

**Backend:**
- **Laravel 12**: PHP framework with modern features
- **PHP 8.4**: Latest PHP version with performance improvements
- **SQLite**: Default database (production can use MySQL/PostgreSQL)
- **Redis**: Caching and session storage (optional)

**Frontend:**
- **Filament 4.1**: Admin panel framework with multi-panel architecture
- **Livewire 3**: Full-stack framework for dynamic interfaces
- **Alpine.js**: Lightweight JavaScript framework (via Livewire)
- **Tailwind CSS 4**: Utility-first CSS framework
- **Vite 6**: Build tool and development server

**Testing & Quality:**
- **Pest 4**: Modern PHP testing framework with browser testing
- **PHPStan**: Static analysis tool (level 8)
- **Laravel Pint**: Code style fixer (PSR-12)
- **Rector**: Automated code refactoring

### Architectural Patterns

#### Modular Monolith
```
app-modules/
├── appointments/          # Appointment booking domain
├── billing/              # Subscription and payment domain
├── company/              # Multi-tenant company domain
├── consultants/          # Consultant management domain
├── integration-highlevel/ # CRM integration domain
├── tenant/               # Tenancy features domain
└── user/                 # User management domain
```

#### Repository Pattern
```php
// Interface definition
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function getActiveUsers(): Collection;
}

// Implementation
class UserRepository implements UserRepositoryInterface
{
    public function __construct(private User $model) {}
    
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }
}
```

#### Action Pattern
```php
class RegisterPartnerCollaboratorAction
{
    public function execute(PartnerRegistrationDTO $dto): RegistrationResult
    {
        return DB::transaction(function () use ($dto) {
            $user = $this->createUser($dto);
            $this->createUserDetails($user, $dto);
            $this->associateWithCompany($user, $dto->partnerCode);
            
            return RegistrationResult::success($user);
        });
    }
}
```

---

## Development Environment

### Prerequisites
```bash
# Required software
PHP 8.4+
Composer 2.x
Node.js 18+
Git
SQLite (or MySQL/PostgreSQL for production)

# Optional but recommended
Redis (for caching and queues)
Mailpit (for local email testing)
```

### Setup Instructions

1. **Clone and Install**
   ```bash
   git clone <repository-url>
   cd <project-directory>
   composer install
   npm install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   touch database/identifier.sqlite
   ```

3. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed --class=EssentialsSeeder
   ```

4. **Development Server**
   ```bash
   # Start all services
   composer run dev
   
   # Or individually
   php artisan serve          # Laravel server (localhost:8000)
   php artisan queue:listen   # Background jobs
   php artisan pail          # Log viewer
   npm run dev               # Asset compilation with HMR
   ```

### Development Tools

#### Make Commands
```bash
# Code Quality
make check               # Run all quality checks
make pint               # Fix code style with Laravel Pint
make phpstan            # Run PHPStan static analysis
make rector             # Run Rector refactoring
make test               # Run Pest tests

# Database
make migrate-fresh      # Fresh migration with seeding
make essentials-seeder  # Run essential data seeder

# Performance
make optimize           # Optimize for production
make cache-clear        # Clear all caches
```

---

## Coding Standards

### PHP Standards

#### PSR-12 Compliance
```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Service class for handling user operations.
 */
class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private NotificationService $notificationService
    ) {}

    /**
     * Create a new user with validation.
     */
    public function createUser(array $data): User
    {
        $validatedData = $this->validateUserData($data);
        
        return DB::transaction(function () use ($validatedData) {
            $user = $this->userRepository->create($validatedData);
            $this->notificationService->sendWelcomeEmail($user);
            
            return $user;
        });
    }
}
```

#### Type Declarations
```php
// Always use explicit return types
public function getUserById(int $id): ?User
{
    return $this->userRepository->find($id);
}

// Use union types when appropriate
public function processPayment(string|int $amount): PaymentResult
{
    // Implementation
}
```

### Laravel Conventions

#### Model Definitions
```php
class User extends Authenticatable
{
    use HasFactory, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user's company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
```

---

## Database Design

### Schema Conventions

#### Table Naming
```sql
-- Use plural, snake_case table names
users
companies
appointments
company_employees (pivot table)

-- Foreign key naming
user_id
company_id
consultant_id
```

#### Migration Structure
```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultant_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled']);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['consultant_id', 'scheduled_at']);
        });
    }
};
```

---

## Testing Strategy

### Test Structure

```
tests/
├── Unit/                          # Unit tests (isolated components)
│   ├── Actions/                   # Action class tests
│   ├── DTO/                       # Data transfer object tests
│   └── Services/                  # Service class tests
├── Feature/                       # Integration tests
│   ├── API/                       # API endpoint tests
│   ├── Auth/                      # Authentication tests
│   └── Filament/                  # Admin panel tests
└── Browser/                       # End-to-end tests
    ├── Registration/              # User registration flows
    └── Dashboard/                 # Dashboard interactions
```

### Unit Testing Example

```php
class RegisterPartnerCollaboratorActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_registration(): void
    {
        // Arrange
        $company = Company::factory()->create(['partner_code' => 'TEST123']);
        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '123.456.789-00',
            email: 'joao@example.com',
            password: 'SecurePass123!',
            partnerCode: 'TEST123'
        );

        // Act
        $result = $this->action->execute($dto);

        // Assert
        expect($result->isSuccess())->toBeTrue();
        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);
    }
}
```

---

## Performance Guidelines

### Database Optimization

#### Query Optimization
```php
// Bad: N+1 query problem
$appointments = Appointment::all();
foreach ($appointments as $appointment) {
    echo $appointment->user->name; // Triggers query for each appointment
}

// Good: Eager loading
$appointments = Appointment::with('user')->get();
foreach ($appointments as $appointment) {
    echo $appointment->user->name; // No additional queries
}
```

#### Caching Strategies
```php
class AppointmentRepository
{
    public function getStatsForCompany(int $companyId): array
    {
        $cacheKey = "appointment_stats:company:{$companyId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($companyId) {
            return [
                'total' => $this->model->where('company_id', $companyId)->count(),
                'completed' => $this->model->where('company_id', $companyId)
                    ->where('status', 'completed')->count(),
            ];
        });
    }
}
```

---

## Security Implementation

### Input Validation

```php
class PartnerRegistrationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZÀ-ÿ\s\-\'\.]+$/u',
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->mixedCase()
                    ->symbols()
                    ->uncompromised(),
                'confirmed',
            ],
        ];
    }
}
```

---

## Deployment Guide

### Production Environment Setup

#### Environment Configuration
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=production_database

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

STRIPE_KEY=pk_live_your_live_key
STRIPE_SECRET=sk_live_your_live_secret
```

#### Deployment Script
```bash
#!/bin/bash
set -e

echo "Starting deployment..."

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci --production && npm run build

# Run migrations
php artisan migrate --force

# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
php artisan queue:restart

echo "Deployment completed!"
```

---

## Troubleshooting

### Common Issues

#### Database Connection Issues
```bash
# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check configuration
php artisan config:show database
```

#### Cache Issues
```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
```

#### Queue Issues
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Debugging Tools

#### Log Analysis
```bash
# View logs in real-time
php artisan pail

# View specific log file
tail -f storage/logs/laravel.log
```

This developer documentation provides the essential information for maintaining and extending the Laravel application while following best practices.