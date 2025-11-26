# Design Document

## Overview

The partner registration feature will create a new public-facing registration page at `/partners` that allows partners to register collaborators for their companies. The system will validate partner codes against a new `partner_code` field in the companies table and automatically associate registered users with the appropriate company while restricting their access to only the User Panel.

## Architecture

### Route Structure
- **New Route**: `/partners` - Public registration page (no authentication required)
- **Redirect Target**: `/app/login` - User Panel login after successful registration
- **Integration**: Extends existing Guest Panel infrastructure

### Database Schema Changes

#### Companies Table Migration
Add a new `partner_code` field to the existing companies table:
```sql
ALTER TABLE companies ADD COLUMN partner_code VARCHAR(50) UNIQUE NULL;
```

#### User Details Enhancement
Extend the existing user details structure to store RG (document_id field can be used for RG):
- `document_id` field in `user_details` table will store RG
- `tax_id` field will store CPF
- `company_id` will link to the partner's company

## Components and Interfaces

### 1. Filament Page Component
**Class**: `App\Filament\Guest\Pages\PartnerRegistrationPage`
- Extends Filament's `Page` class
- Implements form handling with validation
- Provides real-time validation feedback
- Handles success/error states

### 2. Form Schema
**Fields**:
- `name` (TextInput) - Full name, required, max 255 chars
- `rg` (TextInput) - RG document, required, formatted input
- `cpf` (TextInput) - CPF with mask and validation, required, unique
- `email` (TextInput) - Email with validation, required, unique
- `password` (TextInput) - Password with confirmation, required, min 8 chars
- `partner_code` (TextInput) - Partner code validation, required

### 3. Validation Rules
**Real-time Validation**:
- CPF format validation using Brazilian CPF algorithm
- Email format and uniqueness validation
- Password strength requirements
- Partner code existence validation
- RG format validation

### 4. Registration Action
**Class**: `App\Actions\RegisterPartnerCollaboratorAction`
- Validates partner code against companies table
- Creates user record with hashed password
- Creates user detail record with RG and CPF
- Associates user with company via company_employees table
- Assigns appropriate role and permissions

### 5. Route Registration
**File**: `routes/web.php` or dedicated route file
- Register `/partners` route pointing to PartnerRegistrationPage
- Ensure route is accessible without authentication

## Data Models

### Company Model Enhancement
```php
// Add to Company model
protected $fillable = [
    'user_id',
    'name', 
    'slug',
    'tax_id',
    'partner_code', // New field
];

public function findByPartnerCode(string $code): ?Company
{
    return $this->where('partner_code', $code)->first();
}
```

### User Registration DTO
```php
class PartnerRegistrationDTO
{
    public function __construct(
        public string $name,
        public string $rg,
        public string $cpf,
        public string $email,
        public string $password,
        public string $partnerCode,
    ) {}
}
```

### Registration Response
```php
class RegistrationResult
{
    public function __construct(
        public bool $success,
        public ?User $user = null,
        public ?string $error = null,
        public ?Company $company = null,
    ) {}
}
```

## Error Handling

### Validation Errors
- **Invalid Partner Code**: "Código de parceiro inválido ou não encontrado"
- **Duplicate Email**: "Este email já está cadastrado no sistema"
- **Duplicate CPF**: "Este CPF já está cadastrado no sistema"
- **Invalid CPF Format**: "CPF inválido. Verifique o formato"
- **Password Requirements**: "A senha deve ter pelo menos 8 caracteres"

### System Errors
- Database connection failures
- Transaction rollback on partial registration
- Logging of registration attempts and failures

### Success Handling
- Success message with clear next steps
- Automatic redirect option to login page
- Email confirmation (optional future enhancement)

## Testing Strategy

### Unit Tests
- Partner code validation logic
- CPF validation algorithm
- User creation and association logic
- Permission assignment verification

### Integration Tests
- Complete registration flow
- Database transaction integrity
- Form validation scenarios
- Error handling paths

### Feature Tests
- End-to-end registration process
- Panel access restrictions
- Tenant isolation verification
- User authentication flow

### Browser Tests (Pest/Playwright)
- Form interaction and validation
- Real-time validation feedback
- Success and error message display
- Navigation flow testing

## Security Considerations

### Input Validation
- Sanitize all input fields
- Validate CPF algorithm integrity
- Prevent SQL injection through Eloquent ORM
- CSRF protection via Filament forms

### Access Control
- Restrict registered users to User Panel only
- Implement tenant isolation at database level
- Prevent access to other company data
- Role-based permission enforcement

### Data Protection
- Hash passwords using Laravel's default hasher
- Validate unique constraints at database level
- Implement soft deletes for audit trail
- Log registration attempts for security monitoring

## Performance Considerations

### Database Optimization
- Index on `partner_code` field for fast lookups
- Index on `cpf` field in user_details for uniqueness checks
- Efficient query structure for company association

### Caching Strategy
- Cache partner code validation results (short TTL)
- Implement form validation caching
- Optimize database queries with eager loading

### Rate Limiting
- Implement rate limiting on registration endpoint
- Prevent brute force partner code attacks
- Monitor and alert on suspicious registration patterns

## Integration Points

### Existing Systems
- **Filament Panels**: Integrate with existing panel structure
- **Authentication**: Use Laravel's built-in authentication
- **Tenant System**: Leverage existing tenant isolation
- **User Management**: Extend current user/company relationship

### Future Enhancements
- Email verification workflow
- Welcome email automation
- Partner dashboard for managing collaborators
- Bulk registration capabilities
- Integration with CRM systems