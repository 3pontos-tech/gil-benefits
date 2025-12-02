# API Documentation

## Overview

This document provides comprehensive documentation for all API endpoints in the Laravel application. The application primarily uses Filament panels for UI interactions, with some custom endpoints for specific functionality.

## Base URL

```
https://your-domain.com
```

## Authentication

The application uses Laravel's built-in authentication system with Filament panels. Authentication is handled through:

- **Session-based authentication** for web interfaces
- **Multi-panel authentication** with different access levels
- **CSRF protection** for all form submissions

## Rate Limiting

All endpoints are protected by rate limiting:

- **General requests**: 60 requests per minute per IP
- **Authentication attempts**: 5 attempts per minute per IP
- **Partner registration**: 3 attempts per minute per IP
- **Password reset**: 2 attempts per minute per IP

## Response Format

All API responses follow a consistent JSON format:

```json
{
    "success": true,
    "data": {},
    "message": "Operation completed successfully",
    "errors": []
}
```

### Error Response Format

```json
{
    "success": false,
    "data": null,
    "message": "Validation failed",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Internal Server Error

## Endpoints

### Public Endpoints

#### Partner Registration Page
- **URL**: `/partners`
- **Method**: `GET`
- **Description**: Display partner registration form
- **Authentication**: None required
- **Rate Limit**: 60 requests per minute

**Response:**
```html
<!-- HTML page with partner registration form -->
```

#### Landing Page
- **URL**: `/`
- **Method**: `GET`
- **Description**: Application landing page
- **Authentication**: None required

**Response:**
```html
<!-- HTML landing page -->
```

### Authentication Endpoints

#### Admin Login
- **URL**: `/admin/login`
- **Method**: `GET`
- **Description**: Display admin login form
- **Authentication**: None required

**Response:**
```html
<!-- Admin login form -->
```

#### User Panel Login
- **URL**: `/app/login`
- **Method**: `GET`
- **Description**: Display user login form
- **Authentication**: None required

**Response:**
```html
<!-- User login form -->
```

#### Company Panel Login
- **URL**: `/company/login`
- **Method**: `GET`
- **Description**: Display company login form
- **Authentication**: None required

**Response:**
```html
<!-- Company login form -->
```

#### Logout (All Panels)
- **URL**: `/admin/logout`, `/app/logout`, `/company/logout`
- **Method**: `POST`
- **Description**: Logout from respective panel
- **Authentication**: Required (session)

**Request:**
```json
{
    "_token": "csrf_token_here"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully",
    "redirect": "/login"
}
```

### Dashboard Endpoints

#### Admin Dashboard
- **URL**: `/admin`
- **Method**: `GET`
- **Description**: Admin panel dashboard
- **Authentication**: Required (admin role)

**Response:**
```html
<!-- Admin dashboard with widgets and statistics -->
```

#### User Dashboard
- **URL**: `/app/{tenant}`
- **Method**: `GET`
- **Description**: User dashboard for specific tenant
- **Authentication**: Required (user session)
- **Parameters**:
  - `tenant` (string): Company slug or ID

**Response:**
```html
<!-- User dashboard with appointments and company info -->
```

#### Company Dashboard
- **URL**: `/company/{tenant}`
- **Method**: `GET`
- **Description**: Company management dashboard
- **Authentication**: Required (company admin)
- **Parameters**:
  - `tenant` (string): Company slug or ID

**Response:**
```html
<!-- Company dashboard with employee management -->
```

### Resource Management Endpoints

#### Appointments

##### List Appointments (Admin)
- **URL**: `/admin/appointments`
- **Method**: `GET`
- **Description**: List all appointments (admin view)
- **Authentication**: Required (admin role)

**Query Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (default: 15)
- `search` (string): Search term
- `status` (string): Filter by appointment status
- `date_from` (date): Filter appointments from date
- `date_to` (date): Filter appointments to date

**Response:**
```html
<!-- Filament table with appointments list -->
```

##### Create Appointment (Admin)
- **URL**: `/admin/appointments/create`
- **Method**: `GET`
- **Description**: Display appointment creation form
- **Authentication**: Required (admin role)

**Response:**
```html
<!-- Appointment creation form -->
```

##### View Appointment (Admin)
- **URL**: `/admin/appointments/{record}`
- **Method**: `GET`
- **Description**: View specific appointment details
- **Authentication**: Required (admin role)
- **Parameters**:
  - `record` (integer): Appointment ID

**Response:**
```html
<!-- Appointment details view -->
```

##### Edit Appointment (Admin)
- **URL**: `/admin/appointments/{record}/edit`
- **Method**: `GET`
- **Description**: Edit appointment form
- **Authentication**: Required (admin role)
- **Parameters**:
  - `record` (integer): Appointment ID

**Response:**
```html
<!-- Appointment edit form -->
```

#### Companies

##### List Companies (Admin)
- **URL**: `/admin/companies`
- **Method**: `GET`
- **Description**: List all companies (admin view)
- **Authentication**: Required (admin role)

**Query Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (default: 15)
- `search` (string): Search term
- `status` (string): Filter by company status
- `partner_code` (string): Filter by partner code

**Response:**
```html
<!-- Filament table with companies list -->
```

##### Create Company (Admin)
- **URL**: `/admin/companies/create`
- **Method**: `GET`
- **Description**: Display company creation form
- **Authentication**: Required (admin role)

**Response:**
```html
<!-- Company creation form -->
```

##### Edit Company (Admin)
- **URL**: `/admin/companies/{record}/edit`
- **Method**: `GET`
- **Description**: Edit company form
- **Authentication**: Required (admin role)
- **Parameters**:
  - `record` (integer): Company ID

**Response:**
```html
<!-- Company edit form -->
```

#### Users

##### List Users (Admin)
- **URL**: `/admin/users`
- **Method**: `GET`
- **Description**: List all users (admin view)
- **Authentication**: Required (admin role)

**Query Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (default: 15)
- `search` (string): Search term
- `role` (string): Filter by user role
- `company` (string): Filter by company

**Response:**
```html
<!-- Filament table with users list -->
```

##### Create User (Admin)
- **URL**: `/admin/users/create`
- **Method**: `GET`
- **Description**: Display user creation form
- **Authentication**: Required (admin role)

**Response:**
```html
<!-- User creation form -->
```

##### Edit User (Admin)
- **URL**: `/admin/users/{record}/edit`
- **Method**: `GET`
- **Description**: Edit user form
- **Authentication**: Required (admin role)
- **Parameters**:
  - `record` (integer): User ID

**Response:**
```html
<!-- User edit form -->
```

### Billing Endpoints

#### Billing Dashboard (User)
- **URL**: `/app/{tenant}/billing`
- **Method**: `GET`
- **Description**: User billing dashboard
- **Authentication**: Required (user session)
- **Parameters**:
  - `tenant` (string): Company slug or ID

**Response:**
```html
<!-- Billing dashboard with subscription info -->
```

#### Available Subscriptions (User)
- **URL**: `/app/{tenant}/available-subscriptions`
- **Method**: `GET`
- **Description**: Display available subscription plans
- **Authentication**: Required (user session)
- **Parameters**:
  - `tenant` (string): Company slug or ID

**Response:**
```html
<!-- Available subscription plans -->
```

#### Billing Dashboard (Company)
- **URL**: `/company/{tenant}/billing`
- **Method**: `GET`
- **Description**: Company billing dashboard
- **Authentication**: Required (company admin)
- **Parameters**:
  - `tenant` (string): Company slug or ID

**Response:**
```html
<!-- Company billing dashboard -->
```

#### Available Subscriptions (Company)
- **URL**: `/company/{tenant}/available-subscriptions`
- **Method**: `GET`
- **Description**: Display available subscription plans for company
- **Authentication**: Required (company admin)
- **Parameters**:
  - `tenant` (string): Company slug or ID

**Response:**
```html
<!-- Available subscription plans for company -->
```

### Webhook Endpoints

#### Stripe Webhook
- **URL**: `/stripe/webhook`
- **Method**: `POST`
- **Description**: Handle Stripe webhook events
- **Authentication**: Stripe signature verification
- **Headers**:
  - `Stripe-Signature`: Webhook signature

**Request:**
```json
{
    "id": "evt_1234567890",
    "object": "event",
    "type": "invoice.payment_succeeded",
    "data": {
        "object": {
            // Stripe event data
        }
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": "Webhook processed successfully"
}
```

#### Resend Webhook
- **URL**: `/resend/webhook`
- **Method**: `POST`
- **Description**: Handle Resend email service webhooks
- **Authentication**: Webhook signature verification

**Request:**
```json
{
    "type": "email.delivered",
    "data": {
        "email_id": "abc123",
        "to": "user@example.com",
        "subject": "Email subject"
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": "Webhook processed successfully"
}
```

### File Management Endpoints

#### File Upload (Livewire)
- **URL**: `/livewire/upload-file`
- **Method**: `POST`
- **Description**: Handle file uploads via Livewire
- **Authentication**: Required (session)
- **Content-Type**: `multipart/form-data`

**Request:**
```
Content-Type: multipart/form-data
file: [binary file data]
```

**Response:**
```json
{
    "success": true,
    "data": {
        "filename": "uploaded_file.jpg",
        "path": "/tmp/livewire-tmp/abc123.jpg"
    }
}
```

#### File Preview
- **URL**: `/livewire/preview-file/{filename}`
- **Method**: `GET`
- **Description**: Preview uploaded file
- **Authentication**: Required (session)
- **Parameters**:
  - `filename` (string): Temporary filename

**Response:**
```
Content-Type: image/jpeg
[binary file data]
```

#### Storage File Access
- **URL**: `/storage/{path}`
- **Method**: `GET`
- **Description**: Access stored files
- **Authentication**: Varies by file visibility
- **Parameters**:
  - `path` (string): File path in storage

**Response:**
```
Content-Type: [file mime type]
[binary file data]
```

### Export/Import Endpoints

#### Export Download
- **URL**: `/filament/exports/{export}/download`
- **Method**: `GET`
- **Description**: Download exported data
- **Authentication**: Required (appropriate panel access)
- **Parameters**:
  - `export` (string): Export ID

**Response:**
```
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Disposition: attachment; filename="export.xlsx"
[binary file data]
```

#### Import Failed Rows Download
- **URL**: `/filament/imports/{import}/failed-rows/download`
- **Method**: `GET`
- **Description**: Download failed import rows
- **Authentication**: Required (appropriate panel access)
- **Parameters**:
  - `import` (string): Import ID

**Response:**
```
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Disposition: attachment; filename="failed-rows.xlsx"
[binary file data]
```

## Error Handling

### Common Error Responses

#### Validation Error (422)
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

#### Authentication Error (401)
```json
{
    "success": false,
    "message": "Unauthenticated.",
    "errors": []
}
```

#### Authorization Error (403)
```json
{
    "success": false,
    "message": "This action is unauthorized.",
    "errors": []
}
```

#### Not Found Error (404)
```json
{
    "success": false,
    "message": "The requested resource was not found.",
    "errors": []
}
```

#### Rate Limit Error (429)
```json
{
    "success": false,
    "message": "Too many requests. Please try again later.",
    "errors": [],
    "retry_after": 60
}
```

## Testing

### Example API Tests

```php
// Test partner registration page access
it('displays partner registration page', function () {
    $response = $this->get('/partners');
    
    $response->assertSuccessful()
        ->assertSee('Partner Registration');
});

// Test authentication required endpoints
it('requires authentication for admin dashboard', function () {
    $response = $this->get('/admin');
    
    $response->assertRedirect('/admin/login');
});

// Test rate limiting
it('applies rate limiting to partner registration', function () {
    for ($i = 0; $i < 4; $i++) {
        $this->get('/partners');
    }
    
    $response = $this->get('/partners');
    $response->assertStatus(429);
});
```

## Security Considerations

1. **CSRF Protection**: All forms include CSRF tokens
2. **Rate Limiting**: Prevents abuse and brute force attacks
3. **Authentication**: Multi-panel authentication system
4. **Authorization**: Role-based access control
5. **Input Validation**: Comprehensive validation rules
6. **File Upload Security**: Validated file types and sizes
7. **Webhook Verification**: Signature verification for webhooks

## Monitoring and Logging

All API endpoints are monitored for:
- Response times
- Error rates
- Authentication failures
- Rate limit violations
- File upload activities
- Webhook processing

Logs are structured and include:
- Request ID
- User information
- Endpoint accessed
- Response status
- Processing time
- Error details (if any)