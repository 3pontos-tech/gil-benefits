# Authorization and Access Control System

## Overview

This document describes the comprehensive authorization and access control system implemented in the application. The system provides multi-layered security with role-based access control, tenant isolation, policy-based authorization, and comprehensive audit logging.

## Architecture

### Core Components

1. **Base Policy System** - Standardized policy classes with tenant isolation
2. **Gate-Based Authorization** - Complex business rule authorization
3. **Middleware Stack** - Multi-layered security enforcement
4. **Audit Logging** - Comprehensive authorization decision tracking
5. **Role-Based Access Control** - Company-based role management

### Security Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    Request Flow                             │
├─────────────────────────────────────────────────────────────┤
│ 1. Authentication Middleware                                │
│ 2. Security Headers Middleware                              │
│ 3. Authorization Audit Middleware                           │
│ 4. Tenant Isolation Middleware                              │
│ 5. Role-Based Access Control Middleware                     │
│ 6. Enhanced Authorization Middleware                        │
│ 7. Policy-Based Authorization                               │
│ 8. Gate-Based Authorization                                 │
└─────────────────────────────────────────────────────────────┘
```

## Role-Based Access Control

### Company Roles

The system uses a hierarchical role structure within companies:

- **Owner** - Full access to all company resources and settings
- **Manager** - Management access with some restrictions
- **Employee** - Basic access to assigned resources
- **Partner Collaborator** - Restricted access for external partners

### Role Hierarchy

```
Owner (Full Access)
├── Company Management
├── Billing & Financial Data
├── User Management
├── System Administration
└── All Manager/Employee Permissions

Manager (Management Access)
├── Team Management
├── Analytics & Reporting
├── Integration Management
└── All Employee Permissions

Employee (Basic Access)
├── Personal Profile
├── Assigned Tasks
├── Basic Analytics
└── Resource Creation

Partner Collaborator (Restricted Access)
├── Partner Company Data Only
├── Limited Analytics
├── No Administrative Access
└── Audit Trail Monitoring
```

## Policy System

### Base Policy

All policies extend the `BasePolicy` class which provides:

- **Tenant Isolation** - Automatic company-based data filtering
- **Audit Logging** - All authorization decisions are logged
- **Role Checking** - Standardized role validation methods
- **Security Validation** - Partner collaborator restrictions

### Policy Structure

```php
abstract class BasePolicy
{
    // Standard CRUD methods
    public function viewAny(User $user): bool
    public function view(User $user, Model $model): bool
    public function create(User $user): bool
    public function update(User $user, Model $model): bool
    public function delete(User $user, Model $model): bool
    public function restore(User $user, Model $model): bool
    public function forceDelete(User $user, Model $model): bool
    
    // Tenant isolation
    protected function canAccessTenant(User $user, Model $model): bool
    
    // Role checking helpers
    protected function hasRole(User $user, CompanyRoleEnum|array $roles): bool
    protected function isOwnerOrManager(User $user): bool
    protected function isOwner(User $user): bool
    
    // Audit logging
    protected function logAuthorizationAttempt(User $user, string $action, ?Model $model): void
    protected function logAuthorizationDecision(User $user, string $action, ?Model $model, bool $granted, ?string $reason = null): void
}
```

### Available Policies

- **UserPolicy** - User management authorization
- **CompanyPolicy** - Company resource authorization
- **AppointmentPolicy** - Appointment management authorization
- **ConsultantPolicy** - Consultant profile authorization
- **SubscriptionPolicy** - Billing subscription authorization
- **SubscriptionItemPolicy** - Subscription item management authorization
- **PlanPolicy** - Billing plan authorization
- **PricePolicy** - Pricing authorization

## Gate System

### Panel Access Gates

```php
// Panel access control
Gate::define('access-admin-panel', function (User $user) { ... });
Gate::define('access-company-panel', function (User $user) { ... });
Gate::define('access-consultant-panel', function (User $user) { ... });
Gate::define('access-user-panel', function (User $user) { ... });
Gate::define('access-guest-panel', function (?User $user) { ... });
```

### Business Rule Gates

```php
// Complex business logic
Gate::define('manage-company-members', function (User $user, Company $company) { ... });
Gate::define('manage-company-settings', function (User $user, Company $company) { ... });
Gate::define('view-company-analytics', function (User $user, Company $company) { ... });
Gate::define('access-billing-information', function (User $user, Company $company) { ... });
Gate::define('tenant-isolation', function (User $user, $model) { ... });
```

### Advanced Authorization Gates

```php
// Advanced security features
Gate::define('emergency-access', function (User $user, string $reason = '') { ... });
Gate::define('impersonate-user', function (User $user, User $targetUser) { ... });
Gate::define('cross-tenant-data-access', function (User $user, Company $source, Company $target) { ... });
Gate::define('bulk-operations', function (User $user, string $operation, ?Company $company = null) { ... });
Gate::define('access-sensitive-data', function (User $user, ?string $dataType = null) { ... });
```

## Middleware System

### Tenant Isolation Middleware

Automatically applies global scopes to ensure users can only access data from their authorized companies.

**Features:**
- Global query scoping
- Route model binding validation
- Partner collaborator restrictions
- Violation detection and logging

**Usage:**
```php
// Applied globally in bootstrap/app.php
$middleware->web(append: [
    \App\Http\Middleware\TenantIsolationMiddleware::class,
]);
```

### Role-Based Access Control Middleware

Validates user roles for specific routes.

**Usage:**
```php
Route::middleware(['role.access:owner'])->group(function () {
    // Owner-only routes
});

Route::middleware(['role.access:owner,manager'])->group(function () {
    // Owner or manager routes
});
```

### Enhanced Authorization Middleware

Provides advanced authorization features with multiple permission types.

**Permission Types:**
- `gate:gate-name` - Gate-based permissions
- `role:role-name` - Role-based permissions
- `company:permission` - Company-specific permissions
- `panel:panel-name` - Panel access permissions
- `feature:feature-name` - Feature-based permissions
- `subscription:level` - Subscription-based permissions
- `data_access:level` - Data sensitivity permissions

**Usage:**
```php
Route::middleware(['enhanced.auth:gate:access-admin-panel'])->group(function () {
    // Admin panel routes
});

Route::middleware(['enhanced.auth:role:owner', 'enhanced.auth:feature:billing'])->group(function () {
    // Owner-only billing routes
});
```

### Authorization Audit Middleware

Logs all authorization decisions for security monitoring.

**Features:**
- Request/response logging
- Gate check logging
- Error response logging
- Performance metrics
- Security incident detection

## Tenant Isolation

### Automatic Data Filtering

The system automatically applies tenant isolation through:

1. **Global Scopes** - Applied to all model queries
2. **Route Model Binding** - Validates access to route parameters
3. **Policy Checks** - Enforced in all policy methods
4. **Middleware Validation** - Additional security layer

### Partner Collaborator Restrictions

Partner collaborators have special restrictions:

- **Single Company Access** - Can only access their designated partner company
- **Limited Operations** - Cannot perform administrative operations
- **Enhanced Monitoring** - All actions are closely monitored
- **Rate Limiting** - Stricter rate limits applied

## Audit Logging

### Authorization Audit Service

Comprehensive logging of all authorization decisions:

```php
// Log authorization decisions
$auditService->logAuthorizationDecision($user, $action, $model, $granted, $reason, $context);

// Log policy checks
$auditService->logPolicyCheck($user, $policy, $method, $model, $granted, $context);

// Log gate checks
$auditService->logGateCheck($user, $gate, $arguments, $granted, $context);

// Log panel access
$auditService->logPanelAccess($user, $panelId, $granted, $redirectedTo, $context);

// Log tenant isolation checks
$auditService->logTenantIsolationCheck($user, $model, $granted, $context);
```

### Security Monitoring

The system includes advanced security monitoring:

- **Suspicious Activity Detection** - Identifies unusual authorization patterns
- **Rate Limit Monitoring** - Tracks and prevents abuse
- **Cross-Tenant Violations** - Detects unauthorized access attempts
- **High-Risk User Tracking** - Enhanced monitoring for flagged users

### Audit Reports

Generate comprehensive authorization reports:

```php
// Generate authorization report
$report = $auditService->generateAuthorizationReport($startDate, $endDate, $userId);

// Get user authorization statistics
$stats = $auditService->getUserAuthorizationStats($user, $days);

// Detect suspicious activity
$suspiciousActivity = $auditService->detectSuspiciousActivity($user, $hours);
```

## Usage Examples

### Basic Policy Usage

```php
// Check if user can view a company
if ($user->can('view', $company)) {
    // User has access
}

// Check if user can manage company members
if ($user->can('manageMembers', $company)) {
    // User can manage members
}
```

### Gate Usage

```php
// Check panel access
if (Gate::allows('access-admin-panel')) {
    // User can access admin panel
}

// Check business rule with parameters
if (Gate::allows('manage-company-settings', $company)) {
    // User can manage company settings
}

// Check advanced authorization
if (Gate::allows('bulk-operations', 'delete', $company)) {
    // User can perform bulk delete operations
}
```

### Middleware Usage

```php
// Protect routes with role-based access
Route::middleware(['role.access:owner'])->group(function () {
    Route::get('/admin/settings', [AdminController::class, 'settings']);
});

// Protect routes with enhanced authorization
Route::middleware(['enhanced.auth:feature:billing'])->group(function () {
    Route::resource('subscriptions', SubscriptionController::class);
});

// Multiple authorization requirements
Route::middleware([
    'enhanced.auth:role:owner',
    'enhanced.auth:feature:financial_data',
    'enhanced.auth:data_access:financial'
])->group(function () {
    Route::get('/financial/reports', [FinancialController::class, 'reports']);
});
```

### Filament Integration

```php
// In Filament resources
class CompanyResource extends Resource
{
    public static function canViewAny(): bool
    {
        return Gate::allows('access-company-panel');
    }
    
    public static function canCreate(): bool
    {
        return Gate::allows('manage-company-settings');
    }
}
```

## Security Best Practices

### 1. Principle of Least Privilege

- Users are granted minimum necessary permissions
- Role hierarchy enforces progressive access levels
- Partner collaborators have restricted access by default

### 2. Defense in Depth

- Multiple authorization layers (middleware, policies, gates)
- Tenant isolation at multiple levels
- Comprehensive audit logging

### 3. Fail-Safe Defaults

- Default deny for all operations
- Explicit permission grants required
- Partner collaborator restrictions by default

### 4. Monitoring and Alerting

- All authorization decisions logged
- Suspicious activity detection
- Security incident reporting
- Performance monitoring

## Testing

### Authorization Tests

Comprehensive test coverage for:

- Policy authorization decisions
- Gate-based permissions
- Middleware functionality
- Tenant isolation
- Role-based access control
- Partner collaborator restrictions

### Test Structure

```
tests/Feature/Authorization/
├── AuthorizationSystemTest.php      # Gate and business rule tests
├── MiddlewareAuthorizationTest.php  # Middleware functionality tests
└── PolicyAuthorizationTest.php      # Policy-based authorization tests
```

### Running Tests

```bash
# Run all authorization tests
php artisan test tests/Feature/Authorization/

# Run specific test suite
php artisan test tests/Feature/Authorization/AuthorizationSystemTest.php

# Run with coverage
php artisan test tests/Feature/Authorization/ --coverage
```

## Configuration

### Environment Variables

```env
# Authorization settings
AUTH_AUDIT_ENABLED=true
AUTH_RATE_LIMITING_ENABLED=true
AUTH_SUSPICIOUS_ACTIVITY_DETECTION=true

# Partner collaborator settings
PARTNER_COLLABORATOR_RATE_LIMIT=100
PARTNER_COLLABORATOR_MONITORING=true

# Security settings
SECURITY_HEADERS_ENABLED=true
TENANT_ISOLATION_STRICT=true
```

### Cache Configuration

The authorization system uses caching for:

- Rate limiting counters
- Suspicious activity tracking
- Authorization decision caching
- User role caching

## Troubleshooting

### Common Issues

1. **Access Denied Errors**
   - Check user roles and company membership
   - Verify tenant isolation settings
   - Review audit logs for decision context

2. **Partner Collaborator Issues**
   - Ensure partner company is properly set
   - Check partner-specific restrictions
   - Review rate limiting settings

3. **Performance Issues**
   - Monitor query performance with tenant isolation
   - Check cache configuration
   - Review audit logging overhead

### Debug Tools

```php
// Check user permissions
$user->can('action', $model);

// Check gate permissions
Gate::forUser($user)->allows('gate-name', $arguments);

// Get authorization statistics
app(\App\Services\AuthorizationAuditService::class)->getUserAuthorizationStats($user);

// Detect suspicious activity
app(\App\Services\AuthorizationAuditService::class)->detectSuspiciousActivity($user);
```

## Maintenance

### Regular Tasks

1. **Audit Log Cleanup** - Remove old audit logs periodically
2. **Performance Monitoring** - Monitor authorization performance
3. **Security Review** - Regular security assessment
4. **Role Review** - Periodic review of user roles and permissions

### Monitoring Metrics

- Authorization success/failure rates
- Policy check performance
- Gate check performance
- Tenant isolation violations
- Suspicious activity incidents
- Rate limiting violations

## Future Enhancements

### Planned Features

1. **Dynamic Permissions** - Runtime permission configuration
2. **Advanced Analytics** - Enhanced security analytics
3. **Integration APIs** - External system integration
4. **Mobile Security** - Mobile-specific security features
5. **AI-Powered Detection** - Machine learning for threat detection

### Extensibility

The authorization system is designed for extensibility:

- Custom policy classes
- Additional middleware layers
- Custom gate definitions
- Extended audit logging
- Custom security rules

## Conclusion

This authorization system provides comprehensive security for the application with:

- **Multi-layered Security** - Defense in depth approach
- **Flexible Authorization** - Policy and gate-based permissions
- **Tenant Isolation** - Automatic data segregation
- **Comprehensive Auditing** - Full authorization tracking
- **Partner Security** - Special restrictions for external users
- **Performance Optimization** - Efficient authorization checks
- **Extensible Design** - Easy to extend and customize

The system ensures that users can only access data and perform operations they are explicitly authorized for, while maintaining comprehensive audit trails for security monitoring and compliance.