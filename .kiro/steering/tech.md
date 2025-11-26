# Technology Stack

## Framework & Core
- **Laravel 12**: PHP framework with modern features
- **PHP 8.4**: Latest PHP version requirement
- **Filament 4.1**: Admin panel framework with multi-panel architecture
- **Livewire**: Full-stack framework for dynamic interfaces

## Frontend
- **Vite 6**: Build tool and dev server
- **Tailwind CSS 4**: Utility-first CSS framework
- **Alpine.js**: Lightweight JavaScript framework (via Livewire)
- **Blade**: Laravel's templating engine

## Database & Storage
- **SQLite**: Default database (identifier.sqlite)
- **Spatie Media Library**: File and media management
- **Laravel Cashier**: Stripe subscription management

## Testing & Quality
- **Pest 4**: Modern PHP testing framework
- **PHPStan**: Static analysis tool
- **Laravel Pint**: Code style fixer (PSR-12)
- **Rector**: Automated code refactoring
- **Playwright**: Browser testing

## Key Packages
- **Spatie Laravel Permission**: Role and permission management
- **Spatie Laravel Tags**: Tagging system
- **Laravel Trend**: Data trending and analytics
- **Filament Webhooks**: Webhook management
- **Blade FontAwesome**: Icon integration

## Common Commands

### Development
```bash
# Start development environment (concurrent processes)
composer run dev

# Individual services
php artisan serve          # Start Laravel server
php artisan queue:listen   # Process background jobs
php artisan pail          # View logs in real-time
npm run dev               # Start Vite dev server
```

### Database
```bash
make migrate-fresh        # Fresh migration with seeding
make essentials-seeder    # Run essential data seeder
php artisan migrate:fresh --seed --seeder=EssentialsSeeder
```

### Code Quality
```bash
make check               # Run all quality checks
make pint               # Fix code style
make phpstan            # Run static analysis
make rector             # Run automated refactoring
make test               # Run Pest tests
make test-pest          # Run tests excluding browser tests
```

### Billing (Stripe)
```bash
make stripe-fresh       # Fresh setup with Stripe sync
php artisan billing:sync-stripe  # Sync Stripe resources
make stripe-listen      # Listen to Stripe webhooks
```

### Module Management
```bash
php artisan make:module {name}  # Create new module
php artisan ide-helper:models   # Generate IDE helper files
```