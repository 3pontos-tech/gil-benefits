# Project Structure & Architecture

## Modular Architecture

This project uses a **modular monolith** architecture with Laravel's modular package system. Each business domain is organized as a separate module in `app-modules/`.

### Module Structure
```
app-modules/{module-name}/
├── composer.json                    # Module package definition
├── src/
│   ├── Providers/                   # Service providers
│   ├── Models/                      # Eloquent models
│   ├── Filament/                    # Admin panel resources
│   │   ├── Admin/Resources/         # Admin panel resources
│   │   ├── App/Resources/           # User panel resources
│   │   └── {Panel}/Resources/       # Panel-specific resources
│   ├── Enums/                       # Enum classes
│   ├── Actions/                     # Business logic actions
│   ├── DTO/                         # Data Transfer Objects
│   └── Policies/                    # Authorization policies
├── database/
│   ├── migrations/                  # Database migrations
│   ├── factories/                   # Model factories
│   └── seeders/                     # Database seeders
├── tests/                           # Module-specific tests
├── lang/                            # Translations (en, pt_BR)
└── resources/views/                 # Blade templates
```

## Current Modules

- **appointments**: Appointment booking and management
- **billing**: Stripe integration and subscription management
- **company**: Multi-tenant company management
- **consultants**: Consultant profiles and management
- **integration-highlevel**: CRM integration
- **tenant**: Tenancy and multi-tenant features
- **user**: User management and authentication

## Core Application Structure

### Main Application (`app/`)
```
app/
├── Console/Commands/               # Artisan commands
├── Filament/                      # Filament panel configurations
│   ├── Admin/                     # Admin panel resources
│   ├── Guest/                     # Public panel resources
│   └── Shared/                    # Shared components
├── Http/Controllers/              # HTTP controllers
├── Livewire/                      # Livewire components
├── Models/                        # Core application models
├── Policies/                      # Authorization policies
└── Providers/                     # Service providers
```

### Configuration & Setup
```
config/                            # Laravel configuration files
├── app-modules.php               # Module configuration
├── filament-*.php                # Filament panel configs
└── ...

bootstrap/
├── app.php                       # Application bootstrap
├── helpers.php                   # Global helper functions
└── providers.php                 # Provider registration
```

## Naming Conventions

### Modules
- **Namespace**: `TresPontosTech\{ModuleName}`
- **Vendor**: `3pontos-tech/{module-name}`
- **Directory**: `app-modules/{module-name}`

### Filament Panels
- **Admin**: System administration (`FilamentPanel::Admin`)
- **App**: End-user interface (`FilamentPanel::User`)
- **Company**: Company management (`FilamentPanel::Company`)
- **Consultant**: Consultant interface (`FilamentPanel::Consultant`)
- **Guest**: Public interface (`FilamentPanel::Guest`)

### File Organization
- **Models**: Singular, PascalCase (`User.php`, `Appointment.php`)
- **Actions**: Descriptive verb + Action (`BookAppointmentAction.php`)
- **DTOs**: Descriptive + DTO suffix (`BookAppointmentDTO.php`)
- **Enums**: Descriptive + Enum suffix (`AppointmentStatus.php`)
- **Policies**: Model + Policy suffix (`UserPolicy.php`)

## Key Architectural Patterns

### Service Providers
Each module has its own service provider that:
- Registers Filament resources for appropriate panels
- Loads translations
- Configures panel-specific resources

### State Machines
Appointments use state machine pattern with dedicated step classes:
- `AbstractAppointmentStep` - Base step class
- `AppointmentDraftStep`, `AppointmentPendingStep`, etc.

### Multi-Panel Architecture
Resources are organized by panel type, allowing different interfaces for different user roles while sharing core business logic.

### Repository Pattern
Used for complex data access, particularly in billing module:
- `PlanRepository` interface
- `EloquentPlanRepository` implementation
- `ConfigPlanRepository` for configuration-based plans