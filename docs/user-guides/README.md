# User Guides and Developer Documentation

## Overview

This document provides comprehensive user guides and developer documentation for all features in the Laravel application. It covers both end-user functionality and developer implementation details.

## Table of Contents

1. [User Guides](#user-guides)
   - [Partner Registration](#partner-registration)
   - [User Dashboard](#user-dashboard)
   - [Company Management](#company-management)
   - [Appointment Booking](#appointment-booking)
   - [Billing and Subscriptions](#billing-and-subscriptions)
2. [Developer Documentation](#developer-documentation)
   - [Getting Started](#getting-started)
   - [Development Workflow](#development-workflow)
   - [Testing Guidelines](#testing-guidelines)
   - [Deployment Process](#deployment-process)
3. [Feature Documentation](#feature-documentation)
   - [Authentication System](#authentication-system)
   - [Multi-Panel Architecture](#multi-panel-architecture)
   - [Modular System](#modular-system)
   - [Performance Optimization](#performance-optimization)

---

## User Guides

### Partner Registration

#### Overview
The partner registration system allows new users to register as collaborators for partner companies using a unique partner code.

#### How to Register as a Partner Collaborator

1. **Access Registration Page**
   - Navigate to `/partners` or click "Partner Registration" from the main page
   - The page displays a registration form with required fields

2. **Fill Registration Form**
   - **Full Name**: Enter your complete name (2-255 characters)
   - **Email**: Provide a valid email address (will be used for login)
   - **Password**: Create a secure password (minimum 8 characters with letters, numbers, symbols)
   - **Confirm Password**: Re-enter your password for verification
   - **CPF**: Enter your Brazilian tax ID (with or without formatting)
   - **RG**: Enter your Brazilian identity document number
   - **Partner Code**: Enter the unique code provided by your partner company

3. **Submit Registration**
   - Click "Register" to submit your information
   - The system validates all data and checks for duplicates
   - If successful, you'll be redirected to the appropriate dashboard
   - If there are errors, they'll be displayed with specific guidance

#### Common Registration Issues

**Invalid Partner Code**
- Error: "Código de parceiro inválido ou não encontrado"
- Solution: Verify the partner code with your company administrator

**Duplicate Email**
- Error: "Este email já está cadastrado no sistema"
- Solution: Use a different email or contact support if you forgot your account

**Invalid CPF**
- Error: "CPF inválido. Verifique o formato"
- Solution: Ensure CPF follows Brazilian format (11 digits)

**Duplicate CPF**
- Error: "Este CPF já está cadastrado no sistema"
- Solution: Contact support if you believe this is an error

#### After Registration
- You'll be automatically associated with the partner company
- Your role will be set to "Employee" by default
- You can access company-specific features and data
- Check your email for welcome messages and next steps

### User Dashboard

#### Overview
The user dashboard provides a personalized interface for managing appointments, viewing company information, and accessing available services.

#### Dashboard Features

**Main Dashboard (`/app/{company}`):**
- **Appointment Overview**: View upcoming and past appointments
- **Quick Actions**: Book new appointments, view company info
- **Notifications**: Important updates and reminders
- **Subscription Status**: Current plan and usage information

**Navigation:**
- **Appointments**: Manage your appointment bookings
- **Billing**: View subscription and payment information
- **Profile**: Update personal information and preferences
- **Company Info**: View company details and contacts

#### Using the Dashboard

1. **Viewing Appointments**
   - Click "Appointments" to see all your bookings
   - Filter by status: Pending, Confirmed, Completed, Cancelled
   - Sort by date, consultant, or appointment type

2. **Booking New Appointments**
   - Click "Book Appointment" or "New Appointment"
   - Select consultant and preferred date/time
   - Choose appointment type and add notes if needed
   - Confirm booking details and submit

3. **Managing Existing Appointments**
   - Click on any appointment to view details
   - Options available: Reschedule, Cancel, Add Notes
   - Cancellation policy: Must cancel at least 2 hours before appointment

4. **Checking Subscription Status**
   - Navigate to "Billing" section
   - View current plan, usage limits, and renewal date
   - Access payment history and download invoices

### Company Management

#### Overview
Company administrators can manage employees, view company-wide statistics, and configure company settings through the company panel.

#### Accessing Company Panel
- Navigate to `/company/{company-slug}`
- Login with company administrator credentials
- Access requires "Company Admin" or "Manager" role

#### Company Dashboard Features

**Employee Management:**
- View all company employees and their roles
- Add new employees or update existing ones
- Manage employee permissions and access levels
- View employee appointment history and statistics

**Company Statistics:**
- Total appointments booked and completed
- Employee performance metrics
- Monthly/quarterly usage reports
- Subscription utilization tracking

**Company Settings:**
- Update company profile information
- Manage partner code and registration settings
- Configure appointment policies and restrictions
- Set up company-specific notifications

#### Managing Employees

1. **Adding New Employees**
   - Go to "Employees" section
   - Click "Add Employee"
   - Fill employee information (name, email, role)
   - Set permissions and access levels
   - Send invitation email

2. **Updating Employee Information**
   - Find employee in the list
   - Click "Edit" next to their name
   - Update information as needed
   - Save changes

3. **Managing Employee Roles**
   - Available roles: Admin, Manager, Employee
   - Admins: Full company access
   - Managers: Employee management and reporting
   - Employees: Basic appointment and profile access

#### Company Reporting

**Appointment Reports:**
- Total appointments by period
- Completion rates and cancellation statistics
- Popular appointment types and times
- Consultant performance metrics

**Usage Reports:**
- Subscription plan utilization
- Feature usage statistics
- Cost analysis and optimization suggestions
- Trend analysis and forecasting

### Appointment Booking

#### Overview
The appointment booking system allows users to schedule consultations with available consultants based on their availability and company policies.

#### Booking Process

1. **Select Consultant**
   - Browse available consultants
   - View consultant profiles, specialties, and ratings
   - Check consultant availability for your preferred dates

2. **Choose Date and Time**
   - Select preferred date from calendar
   - View available time slots (typically 30-minute intervals)
   - Consider consultant's working hours (usually 9 AM - 5 PM)

3. **Appointment Details**
   - Choose appointment type (consultation, follow-up, etc.)
   - Add notes or special requirements
   - Specify duration if different from default

4. **Confirmation**
   - Review all appointment details
   - Confirm booking
   - Receive confirmation email with appointment details

#### Managing Appointments

**Rescheduling:**
- Open appointment details
- Click "Reschedule"
- Select new date/time from available slots
- Confirm changes (both parties receive notifications)

**Cancellation:**
- Open appointment details
- Click "Cancel Appointment"
- Provide cancellation reason (optional)
- Confirm cancellation
- Note: Must cancel at least 2 hours before appointment

**Adding Notes:**
- Open appointment details
- Click "Add Notes" or "Edit Notes"
- Enter relevant information for the consultant
- Save changes

#### Appointment Status Types

- **Pending**: Appointment booked, awaiting confirmation
- **Confirmed**: Appointment confirmed by consultant
- **In Progress**: Appointment currently taking place
- **Completed**: Appointment finished successfully
- **Cancelled**: Appointment cancelled by user or consultant
- **No Show**: User didn't attend confirmed appointment

#### Notifications and Reminders

**Automatic Reminders:**
- 24 hours before appointment (email)
- 2 hours before appointment (email/SMS if configured)
- 30 minutes before appointment (push notification if available)

**Status Updates:**
- Booking confirmation
- Rescheduling notifications
- Cancellation confirmations
- Consultant messages

### Billing and Subscriptions

#### Overview
The billing system manages subscription plans, payments, and usage tracking through Stripe integration.

#### Subscription Plans

**Available Plans:**
- **Basic**: Limited appointments per month
- **Professional**: Increased appointment limits and features
- **Enterprise**: Unlimited appointments and premium features

**Plan Features:**
- Monthly appointment limits
- Access to premium consultants
- Priority booking
- Advanced reporting
- Custom integrations

#### Managing Subscriptions

1. **Viewing Current Subscription**
   - Navigate to "Billing" section
   - View current plan details and usage
   - Check renewal date and payment method

2. **Upgrading/Downgrading Plans**
   - Click "Change Plan" or "Upgrade"
   - Compare available plans and features
   - Select new plan and confirm changes
   - Prorated billing applies automatically

3. **Payment Methods**
   - Add/update credit cards
   - Set default payment method
   - View payment history and receipts
   - Download invoices for accounting

#### Payment Processing

**Automatic Billing:**
- Monthly/annual billing cycles
- Automatic payment processing
- Email receipts and invoices
- Failed payment retry logic

**Manual Payments:**
- One-time payments for overages
- Plan upgrades with immediate billing
- Custom invoicing for enterprise clients

#### Usage Tracking

**Appointment Usage:**
- Track appointments against plan limits
- View usage history and trends
- Receive notifications when approaching limits
- Automatic overage billing if configured

**Feature Usage:**
- Premium feature utilization
- Integration API calls
- Storage and bandwidth usage
- Custom reporting metrics

---

## Developer Documentation

### Getting Started

#### Prerequisites
- PHP 8.4 or higher
- Composer 2.x
- Node.js 18+ and npm
- SQLite (default) or MySQL/PostgreSQL
- Git

#### Installation

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd <project-directory>
   ```

2. **Install Dependencies**
   ```bash
   # PHP dependencies
   composer install
   
   # Node.js dependencies
   npm install
   ```

3. **Environment Setup**
   ```bash
   # Copy environment file
   cp .env.example .env
   
   # Generate application key
   php artisan key:generate
   
   # Create database
   touch database/identifier.sqlite
   ```

4. **Database Setup**
   ```bash
   # Run migrations
   php artisan migrate
   
   # Seed essential data
   php artisan db:seed --class=EssentialsSeeder
   ```

5. **Build Assets**
   ```bash
   # Development build
   npm run dev
   
   # Production build
   npm run build
   ```

#### Development Environment

**Start Development Server:**
```bash
# All services (recommended)
composer run dev

# Individual services
php artisan serve          # Laravel server
php artisan queue:listen   # Background jobs
php artisan pail          # Log viewer
npm run dev               # Asset compilation
```

**Available Commands:**
```bash
# Code quality
make check               # Run all quality checks
make pint               # Fix code style
make phpstan            # Static analysis
make test               # Run tests

# Database
make migrate-fresh      # Fresh migration with seeding
make essentials-seeder  # Run essential seeder

# Stripe (if using billing)
make stripe-fresh       # Setup Stripe integration
make stripe-listen      # Listen to webhooks
```

### Development Workflow

#### Code Standards

**PHP Standards:**
- PSR-12 coding standard (enforced by Laravel Pint)
- PHPStan level 8 static analysis
- Constructor property promotion for PHP 8.4
- Explicit return type declarations
- Comprehensive PHPDoc blocks

**Laravel Conventions:**
- Eloquent ORM over raw queries
- Form Request classes for validation
- Resource classes for API responses
- Policy classes for authorization
- Event-driven architecture for module communication

#### Git Workflow

1. **Feature Development**
   ```bash
   # Create feature branch
   git checkout -b feature/new-feature
   
   # Make changes and commit
   git add .
   git commit -m "feat: add new feature"
   
   # Push and create PR
   git push origin feature/new-feature
   ```

2. **Code Review Process**
   - All changes require pull request review
   - Automated tests must pass
   - Code quality checks must pass
   - At least one approval required

3. **Branch Protection**
   - Main branch is protected
   - Direct pushes not allowed
   - Status checks required

#### Module Development

**Creating New Module:**
```bash
# Generate module structure
php artisan make:module ModuleName

# This creates:
# app-modules/module-name/
# ├── composer.json
# ├── src/Providers/ModuleNameServiceProvider.php
# └── Standard module structure
```

**Module Structure:**
```
app-modules/module-name/
├── src/
│   ├── Providers/           # Service providers
│   ├── Models/             # Eloquent models
│   ├── Filament/           # Admin resources
│   ├── Repositories/       # Data access layer
│   ├── Services/           # Business logic
│   ├── Actions/            # Single-purpose actions
│   ├── DTO/                # Data transfer objects
│   └── Events/             # Domain events
├── database/
│   ├── migrations/         # Database migrations
│   └── factories/          # Model factories
├── tests/                  # Module tests
└── lang/                   # Translations
```

### Testing Guidelines

#### Test Structure

**Test Organization:**
```
tests/
├── Unit/                   # Unit tests
│   ├── Actions/           # Action class tests
│   ├── DTO/               # DTO tests
│   └── Services/          # Service tests
├── Feature/               # Integration tests
│   ├── API/               # API endpoint tests
│   ├── Auth/              # Authentication tests
│   └── Filament/          # Admin panel tests
└── Browser/               # End-to-end tests
    ├── Registration/      # User flows
    └── Dashboard/         # UI interactions
```

#### Writing Tests

**Pest Syntax:**
```php
// Feature test example
it('allows partner registration with valid data', function () {
    $data = [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'cpf' => '123.456.789-00',
        'rg' => '12.345.678-9',
        'partner_code' => 'VALID123'
    ];

    $response = $this->post('/partners/register', $data);

    $response->assertSuccessful();
    $this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
});

// Unit test example
it('validates CPF format correctly', function () {
    expect(CpfValidator::validate('123.456.789-00'))->toBeTrue();
    expect(CpfValidator::validate('invalid-cpf'))->toBeFalse();
});

// Browser test example
it('completes partner registration flow', function () {
    $page = visit('/partners');
    
    $page->assertSee('Partner Registration')
        ->fill('name', 'João Silva')
        ->fill('email', 'joao@example.com')
        ->fill('password', 'SecurePass123!')
        ->fill('password_confirmation', 'SecurePass123!')
        ->fill('cpf', '123.456.789-00')
        ->fill('rg', '12.345.678-9')
        ->fill('partner_code', 'VALID123')
        ->click('Register')
        ->assertSee('Registration successful');
});
```

**Test Best Practices:**
- Use descriptive test names
- Test both success and failure scenarios
- Mock external dependencies
- Use factories for test data
- Clean up after tests

#### Running Tests

```bash
# All tests
php artisan test

# Specific test file
php artisan test tests/Feature/PartnerRegistrationTest.php

# Filter by test name
php artisan test --filter="partner registration"

# Coverage report
php artisan test --coverage

# Browser tests (requires additional setup)
php artisan test tests/Browser/
```

### Deployment Process

#### Environment Setup

**Production Environment Variables:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=production_db
DB_USERNAME=db_user
DB_PASSWORD=secure_password

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

#### Deployment Steps

1. **Pre-deployment Checks**
   ```bash
   # Run tests
   php artisan test
   
   # Check code quality
   make check
   
   # Verify configuration
   php artisan config:show
   ```

2. **Deploy Application**
   ```bash
   # Pull latest code
   git pull origin main
   
   # Install dependencies
   composer install --no-dev --optimize-autoloader
   npm ci --production
   
   # Build assets
   npm run build
   ```

3. **Database Migration**
   ```bash
   # Backup database
   php artisan backup:run
   
   # Run migrations
   php artisan migrate --force
   
   # Seed essential data (if needed)
   php artisan db:seed --class=EssentialsSeeder --force
   ```

4. **Optimization**
   ```bash
   # Cache configuration
   php artisan config:cache
   
   # Cache routes
   php artisan route:cache
   
   # Cache views
   php artisan view:cache
   
   # Cache events
   php artisan event:cache
   
   # Optimize autoloader
   composer dump-autoload --optimize
   ```

5. **Queue and Scheduler**
   ```bash
   # Restart queue workers
   php artisan queue:restart
   
   # Setup cron job for scheduler
   # * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

#### Monitoring and Maintenance

**Health Checks:**
```bash
# Check application status
php artisan health:check

# Monitor queue status
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed
```

**Log Monitoring:**
```bash
# View logs in real-time
php artisan pail

# Check error logs
tail -f storage/logs/laravel.log
```

**Performance Monitoring:**
- Database query performance
- Cache hit rates
- Response times
- Memory usage
- Queue processing times

---

## Feature Documentation

### Authentication System

#### Multi-Panel Authentication
The application uses Filament's multi-panel authentication system with separate login flows for different user types.

**Available Panels:**
- **Admin Panel** (`/admin`): System administrators
- **User Panel** (`/app`): End users and employees
- **Company Panel** (`/company`): Company administrators
- **Consultant Panel** (`/consultant`): Consultant users
- **Guest Panel** (`/`): Public access and registration

#### Authentication Flow

1. **User Registration**
   - Partner registration through guest panel
   - Admin-created accounts through admin panel
   - Company-invited employees through company panel

2. **Login Process**
   - Panel-specific login pages
   - Session-based authentication
   - Remember me functionality
   - Password reset capabilities

3. **Authorization**
   - Role-based access control using Spatie Permission
   - Policy-based resource authorization
   - Panel-specific middleware protection

#### Security Features

**Password Security:**
- Minimum 8 characters
- Must include letters, numbers, and symbols
- Mixed case requirements
- Compromised password detection
- Secure hashing with bcrypt

**Session Security:**
- CSRF protection on all forms
- Secure session configuration
- Session timeout handling
- Multiple device login support

**Rate Limiting:**
- Login attempt limiting (5 per minute)
- Registration limiting (3 per minute)
- Password reset limiting (2 per minute)
- General API limiting (60 per minute)

### Multi-Panel Architecture

#### Panel Configuration
Each panel is configured independently with its own:
- Authentication middleware
- Resource discovery paths
- Color schemes and branding
- Navigation structure
- Dashboard widgets

#### Resource Organization
Filament resources are organized by panel:
```
app/Filament/
├── Admin/Resources/        # System administration
├── Shared/                 # Shared components
└── Guest/Pages/           # Public pages

app-modules/*/src/Filament/
├── Admin/Resources/        # Module admin resources
├── App/Resources/         # Module user resources
├── Company/Resources/     # Module company resources
└── Consultant/Resources/  # Module consultant resources
```

#### Panel-Specific Features

**Admin Panel:**
- User management
- Company management
- System configuration
- Reporting and analytics
- Module administration

**User Panel:**
- Personal dashboard
- Appointment booking
- Profile management
- Billing information

**Company Panel:**
- Employee management
- Company statistics
- Subscription management
- Company settings

### Modular System

#### Module Architecture
The application follows a modular monolith pattern where each business domain is encapsulated in its own module.

#### Module Communication
Modules communicate through:
- **Events**: Domain events for loose coupling
- **Interfaces**: Shared contracts and interfaces
- **Services**: Injected services from other modules
- **Repositories**: Data access layer abstraction

#### Module Development
New modules can be created using:
```bash
php artisan make:module ModuleName
```

This generates a complete module structure with:
- Service provider registration
- Filament resource discovery
- Database migration support
- Translation loading
- Test structure

### Performance Optimization

#### Caching Strategy
Multi-level caching system:
- **Application Cache**: Frequently accessed data
- **Query Cache**: Expensive database queries
- **View Cache**: Compiled Blade templates
- **Route Cache**: Optimized route resolution

#### Database Optimization
- **Eager Loading**: Prevents N+1 query problems
- **Indexing**: Strategic database indexes
- **Query Optimization**: Optimized Eloquent queries
- **Connection Pooling**: Efficient database connections

#### Asset Optimization
- **Vite Build System**: Modern asset compilation
- **Image Optimization**: Automatic image compression
- **WebP Generation**: Modern image format support
- **Asset Versioning**: Cache busting for updates

#### Background Processing
- **Queue System**: Asynchronous job processing
- **Scheduled Tasks**: Automated maintenance tasks
- **Event Processing**: Decoupled event handling
- **Cache Warming**: Proactive cache population

This comprehensive documentation provides both users and developers with the information needed to effectively use and maintain the application.