# Comprehensive Pest 4 Browser Testing Framework

This document outlines the comprehensive browser testing framework implemented using Pest 4 and Playwright for the Laravel application.

## Overview

The browser testing framework provides end-to-end testing capabilities covering:
- Partner registration flow
- User authentication and panel access
- Company management workflows
- Appointment booking and management
- Mobile-responsive testing scenarios
- Complete user journey testing

## Framework Structure

```
tests/Browser/
├── README.md                           # This documentation
├── BasicBrowserTest.php               # Basic framework verification tests
├── UserJourneyTest.php               # Complete user journey tests
├── PartnerRegistration/
│   └── PartnerRegistrationFlowTest.php # Partner registration flow tests
├── Authentication/
│   └── UserAuthenticationTest.php     # Authentication and panel access tests
├── CompanyManagement/
│   └── CompanyManagementTest.php      # Company management workflow tests
├── AppointmentBooking/
│   └── AppointmentBookingTest.php     # Appointment booking and management tests
└── Mobile/
    └── MobileResponsiveTest.php       # Mobile-responsive testing scenarios
```

## Test Categories

### 1. Partner Registration Flow Tests
**File:** `tests/Browser/PartnerRegistration/PartnerRegistrationFlowTest.php`

**Coverage:**
- Complete partner registration form submission
- Form validation and error handling
- Data preservation on validation errors
- Loading states during submission
- Success notifications and redirects
- Real-time field validation
- Keyboard navigation and shortcuts

**Key Features:**
- Tests all form fields (name, RG, CPF, email, password, partner code)
- Validates Brazilian document formats (CPF, RG)
- Tests partner code validation against company database
- Verifies form state preservation on errors
- Tests accessibility features

### 2. User Authentication Tests
**File:** `tests/Browser/Authentication/UserAuthenticationTest.php`

**Coverage:**
- Multi-panel authentication (App, Company, Admin, Consultant)
- Login/logout functionality
- Session management
- Password reset flow
- Panel-specific access control
- Multi-panel navigation

**Key Features:**
- Tests authentication across all Filament panels
- Validates role-based access restrictions
- Tests session persistence and timeout handling
- Verifies "remember me" functionality
- Tests password reset workflow

### 3. Company Management Tests
**File:** `tests/Browser/CompanyManagement/CompanyManagementTest.php`

**Coverage:**
- Company CRUD operations
- Company registration flow
- Profile management
- User management within companies
- Bulk operations
- Data filtering and searching

**Key Features:**
- Admin panel company management
- Company registration workflow
- Profile updates and validation
- Employee management
- Export functionality
- Soft delete and restore operations

### 4. Appointment Booking Tests
**File:** `tests/Browser/AppointmentBooking/AppointmentBookingTest.php`

**Coverage:**
- Appointment booking flow
- Consultant availability management
- Appointment status updates
- Calendar integration
- Notification systems
- Admin appointment management

**Key Features:**
- End-to-end booking process
- Consultant dashboard functionality
- Appointment state management
- Conflict resolution
- Reminder systems
- Analytics and reporting

### 5. Mobile-Responsive Tests
**File:** `tests/Browser/Mobile/MobileResponsiveTest.php`

**Coverage:**
- Mobile form interactions
- Responsive layout testing
- Touch gesture support
- Mobile navigation
- Cross-device compatibility
- Performance optimization

**Key Features:**
- Tests multiple screen sizes and orientations
- Validates touch interactions
- Tests mobile-specific input types
- Verifies accessibility on mobile
- Performance testing on mobile networks
- Dark mode support

### 6. Complete User Journey Tests
**File:** `tests/Browser/UserJourneyTest.php`

**Coverage:**
- End-to-end user workflows
- Multi-session testing
- Error recovery scenarios
- Network interruption handling
- Cross-browser compatibility

**Key Features:**
- Complete registration to appointment booking flow
- Simultaneous user session testing
- Graceful error handling
- Network resilience testing
- Performance under load

## Configuration

### Pest Configuration
The framework is configured in `tests/Pest.php`:

```php
pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'E2E', 'Browser', '../app-modules/*/tests');

pest()->group('browser')
    ->in('E2E', 'Browser');

pest()
    ->in('Browser')
    ->beforeEach(function () {
        // Set up browser testing environment
        config(['app.url' => 'http://localhost:8000']);
        
        // Configure Filament panels for browser testing
        if (str_contains($this->name(), 'admin')) {
            filament()->setCurrentPanel('admin');
        } elseif (str_contains($this->name(), 'company')) {
            filament()->setCurrentPanel('company');
        } elseif (str_contains($this->name(), 'consultant')) {
            filament()->setCurrentPanel('consultant');
        } else {
            filament()->setCurrentPanel('app');
        }
    });
```

### Browser Groups
Tests are organized into groups for easy execution:
- `browser` - All browser tests
- `partner-registration` - Partner registration specific tests
- `authentication` - Authentication related tests
- `company-management` - Company management tests
- `appointment-booking` - Appointment booking tests
- `mobile-responsive` - Mobile responsive tests
- `user-journey` - Complete user journey tests
- `cross-device` - Cross-device compatibility tests

## Running Tests

### All Browser Tests
```bash
php artisan test --group=browser
```

### Specific Test Categories
```bash
# Partner registration tests
php artisan test --group=partner-registration

# Authentication tests
php artisan test --group=authentication

# Company management tests
php artisan test --group=company-management

# Appointment booking tests
php artisan test --group=appointment-booking

# Mobile responsive tests
php artisan test --group=mobile-responsive

# User journey tests
php artisan test --group=user-journey
```

### Individual Test Files
```bash
php artisan test tests/Browser/PartnerRegistration/PartnerRegistrationFlowTest.php
```

## Prerequisites

### System Requirements
- PHP 8.4+
- Laravel 12
- Pest 4
- Playwright (installed via npm)

### Installation
```bash
# Install Playwright
npm install playwright@latest
npx playwright install
npx playwright install-deps

# Verify installation
php artisan test tests/Browser/BasicBrowserTest.php
```

## Test Data Management

### Database Seeding
Tests use Laravel factories and seeders for consistent test data:

```php
beforeEach(function () {
    $this->company = Company::factory()->create([
        'name' => 'Test Company',
        'partner_code' => 'TEST123',
    ]);
    
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
});
```

### Data Cleanup
Tests use `RefreshDatabase` trait to ensure clean state between tests.

## Debugging

### Screenshots
Failed tests automatically capture screenshots saved to:
```
tests/Browser/Screenshots/
```

### Browser Logs
Access browser console logs for debugging:
```php
$page->assertNoJavaScriptErrors();
```

### Debugging Tools
- Use `$page->pause()` to pause test execution
- Use `$page->screenshot('debug.png')` for manual screenshots
- Enable verbose logging in Pest configuration

## Best Practices

### Test Organization
- Group related tests in describe blocks
- Use descriptive test names
- Keep tests focused and atomic
- Use proper setup and teardown

### Performance
- Use `skipOnCI()` for resource-intensive tests
- Implement proper timeouts
- Use efficient selectors
- Minimize database operations

### Reliability
- Handle asynchronous operations properly
- Use proper wait conditions
- Implement retry mechanisms for flaky tests
- Test error scenarios

### Maintenance
- Keep tests up to date with UI changes
- Use page object patterns for complex workflows
- Document test dependencies
- Regular test review and cleanup

## Integration with CI/CD

### GitHub Actions Example
```yaml
name: Browser Tests
on: [push, pull_request]
jobs:
  browser-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
      - name: Install dependencies
        run: |
          composer install
          npm install
          npx playwright install --with-deps
      - name: Run browser tests
        run: php artisan test --group=browser
```

## Troubleshooting

### Common Issues

1. **Playwright Installation**
   ```bash
   npx playwright install-deps
   ```

2. **Port Conflicts**
   - Ensure no other services are running on test ports
   - Configure different ports in test environment

3. **Timeout Issues**
   - Increase timeout values for slow operations
   - Use proper wait conditions

4. **Element Not Found**
   - Verify selectors are correct
   - Check for dynamic content loading
   - Use proper wait strategies

### Performance Issues
- Use headless mode for faster execution
- Implement parallel test execution
- Optimize database operations
- Use efficient test data setup

## Future Enhancements

### Planned Features
- Visual regression testing
- API testing integration
- Performance monitoring
- Accessibility testing automation
- Multi-language testing support

### Scalability
- Parallel test execution
- Distributed testing
- Cloud browser testing
- Test result analytics

This comprehensive browser testing framework provides robust end-to-end testing capabilities ensuring the application works correctly across all user workflows and device types.