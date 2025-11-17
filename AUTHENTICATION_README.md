# Authentication System Documentation

This directory contains comprehensive documentation of the Signature API's authentication system.

## Documentation Files

### 1. AUTHENTICATION_QUICK_REFERENCE.md
**Best for**: Quick lookups, common tasks, API endpoints, error codes
- API endpoint reference with request/response examples
- Token details and lifetimes
- Authentication flow summaries
- Security features overview
- Database table schemas
- Configuration reference
- Curl examples for testing

### 2. AUTHENTICATION_ARCHITECTURE.md
**Best for**: Understanding the complete system, detailed implementation
- Complete authentication framework overview
- JWT token implementation details
- All authentication methods (Email/Password, OAuth, MFA, Password Reset)
- Middleware architecture
- Route protection and rate limiting
- User model and database schema
- Input validation rules
- Security measures explained
- Error handling
- Design patterns used
- Suggestions for security improvements

### 3. AUTHENTICATION_DIAGRAMS.md
**Best for**: Visual learners, system design understanding
- Email/Password login flow
- Token refresh flow
- Logout flow
- JWT middleware request flow
- MFA setup and verification flow
- Password reset flow
- OAuth authentication flow
- Database schema relationships
- Token lifecycle timeline
- Security validation layers
- Error response flow
- Configuration dependency diagram
- Key code snippets with annotations

## Quick Navigation

### I want to...

**Make an API request**
- See: AUTHENTICATION_QUICK_REFERENCE.md > Testing Locally
- Try the curl examples to register, login, and refresh tokens

**Understand the authentication flow**
- See: AUTHENTICATION_DIAGRAMS.md > Section 1-5
- Follow the ASCII diagrams showing client-server interactions

**Set up JWT authentication**
- See: AUTHENTICATION_ARCHITECTURE.md > Section 2
- Review JWT token generation and verification details

**Implement OAuth**
- See: AUTHENTICATION_ARCHITECTURE.md > Section 3 (Method 2)
- Check AUTHENTICATION_DIAGRAMS.md > Section 5

**Set up MFA/2FA**
- See: AUTHENTICATION_ARCHITECTURE.md > Section 4
- Review AUTHENTICATION_DIAGRAMS.md > Section 3

**Understand password reset**
- See: AUTHENTICATION_ARCHITECTURE.md > Section 5
- Check AUTHENTICATION_DIAGRAMS.md > Section 4

**Add a new middleware**
- See: AUTHENTICATION_ARCHITECTURE.md > Section 6
- File: /bootstrap/app.php shows middleware registration

**Configure JWT settings**
- See: AUTHENTICATION_QUICK_REFERENCE.md > Configuration
- File: /config/jwt.php

**Review security implementation**
- See: AUTHENTICATION_ARCHITECTURE.md > Section 10
- See: AUTHENTICATION_DIAGRAMS.md > Section 8

**See the database design**
- See: AUTHENTICATION_ARCHITECTURE.md > Section 8
- See: AUTHENTICATION_DIAGRAMS.md > Section 6

**Find error codes and responses**
- See: AUTHENTICATION_QUICK_REFERENCE.md > Error Codes
- See: AUTHENTICATION_ARCHITECTURE.md > Section 11

**Understand the code structure**
- See: AUTHENTICATION_QUICK_REFERENCE.md > Key Components
- See: AUTHENTICATION_QUICK_REFERENCE.md > File Structure Reference

## Key Files in Codebase

### Services
- `/app/Services/JwtService.php` - JWT token generation and verification

### Controllers
- `/app/Http/Controllers/Api/V1/AuthController.php` - All auth endpoints

### Middleware
- `/app/Http/Middleware/JwtAuthMiddleware.php` - JWT validation for protected routes
- `/app/Http/Middleware/CheckPremiumAccess.php` - Premium subscription validation

### Actions (Business Logic)
- `/app/Actions/Auth/RegisterUserAction.php`
- `/app/Actions/Auth/LoginUserAction.php`
- `/app/Actions/Auth/RefreshTokenAction.php`
- `/app/Actions/Auth/LogoutUserAction.php`
- `/app/Actions/Auth/ForgotPasswordAction.php`
- `/app/Actions/Auth/ResetPasswordAction.php`
- `/app/Actions/Auth/SetupMfaAction.php`
- `/app/Actions/Auth/VerifyMfaAction.php`
- `/app/Actions/Auth/DisableMfaAction.php`
- `/app/Actions/Auth/OauthGoogleAction.php`
- `/app/Actions/Auth/OauthAppleAction.php`

### Request Validation
- `/app/Http/Requests/Auth/RegisterRequest.php`
- `/app/Http/Requests/Auth/LoginRequest.php`
- `/app/Http/Requests/Auth/RefreshTokenRequest.php`
- `/app/Http/Requests/Auth/LogoutRequest.php`
- `/app/Http/Requests/Auth/ForgotPasswordRequest.php`
- `/app/Http/Requests/Auth/ResetPasswordRequest.php`
- `/app/Http/Requests/Auth/SetupMfaRequest.php`
- `/app/Http/Requests/Auth/VerifyMfaRequest.php`
- `/app/Http/Requests/Auth/DisableMfaRequest.php`
- `/app/Http/Requests/Auth/OauthGoogleRequest.php`
- `/app/Http/Requests/Auth/OauthAppleRequest.php`

### Models
- `/app/Models/User.php` - User entity
- `/app/Models/Session.php` - Session/refresh token tracking
- `/app/Models/OauthProvider.php` - OAuth account linking

### Configuration
- `/config/auth.php` - Laravel auth configuration
- `/config/jwt.php` - JWT settings
- `/bootstrap/app.php` - Middleware registration

### Routes
- `/routes/api.php` - All API routes with middleware assignments

### Database
- `/database/migrations/2025_11_06_191154_create_users_table.php`
- `/database/migrations/2025_11_06_191155_create_sessions_table.php`
- `/database/migrations/2025_11_06_191158_create_oauth_providers_table.php`
- `/database/migrations/2025_11_06_194933_create_password_reset_tokens_table.php`

## Authentication Methods Summary

### Email/Password
- Register new users
- Login with credentials
- Password reset via email token
- Secure password hashing (BCrypt with 12 rounds)

### OAuth
- Google OAuth integration
- Apple OAuth integration
- Automatic user creation or linking
- Email verification via provider

### Multi-Factor Authentication (MFA)
- Time-based One-Time Password (TOTP)
- QR code generation for authenticator apps
- 6-digit code verification
- Enable/disable at any time

### Token Management
- Short-lived access tokens (JWT, 15 minutes)
- Long-lived refresh tokens (database-stored, 30 days)
- Token rotation on refresh
- Token revocation on logout
- Session tracking with IP and User-Agent

## Architecture Highlights

### Security Features Implemented
- BCrypt password hashing (12 rounds)
- JWT with HS256 signature
- Refresh token rotation
- Short-lived access tokens
- Session tracking
- Rate limiting (60 req/min)
- Input validation
- Error message sanitization
- Premium subscription checks

### Design Patterns
- Action Pattern for business logic
- Service Pattern for reusable operations
- Resource Pattern for API responses
- Middleware Pattern for cross-cutting concerns
- Form Request Pattern for input validation

### Database Design
- UUID primary keys
- Foreign key relationships
- Unique constraints (email, tokens)
- Indexes for performance
- Soft deletes for data retention
- Timestamp tracking (created, updated, last_used)

## Common Tasks

### Adding New Auth Endpoint
1. Create request validation class in `/app/Http/Requests/Auth/`
2. Create action class in `/app/Actions/Auth/`
3. Add method to `/app/Http/Controllers/Api/V1/AuthController.php`
4. Register route in `/routes/api.php`

### Customizing Token TTL
1. Edit environment variables: JWT_ACCESS_TOKEN_TTL, JWT_REFRESH_TOKEN_TTL
2. Or modify `/config/jwt.php`

### Protecting New Routes
1. Add route to protected group in `/routes/api.php`
2. Apply `auth.jwt` middleware
3. Add `CheckPremiumAccess` middleware if premium-only

### Adding New OAuth Provider
1. Create OauthAction class
2. Add validation request class
3. Update OauthProvider model enum
4. Add route to AuthController
5. Register route in api.php

## Integration Checklist

Before using this authentication system in production:

- [ ] Set JWT_SECRET environment variable (minimum 32 characters)
- [ ] Configure JWT_ALGO if not using HS256
- [ ] Adjust token TTLs for your use case
- [ ] Set up email service for password reset notifications
- [ ] Implement OAuth token verification with provider public keys
- [ ] Configure rate limiting per your needs
- [ ] Set up SSL/HTTPS for all endpoints
- [ ] Configure CORS policies appropriately
- [ ] Implement comprehensive audit logging
- [ ] Add brute-force protection for login attempts
- [ ] Test all authentication flows thoroughly
- [ ] Document custom modifications

## Support

For questions about specific implementations:
- Check the relevant documentation file above
- Review the code in the files listed
- Examine the migration files for database structure
- Look at the request classes for validation rules
- Study the action classes for business logic

---

Last Updated: 2025-11-17
Documentation Generated for Signature API Authentication System
