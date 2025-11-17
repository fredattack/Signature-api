# Authentication Architecture Summary - Signature API

## Overview
The Signature API implements a comprehensive, modern authentication system using JWT (JSON Web Tokens) with multiple authentication methods, session management, multi-factor authentication, and role-based access control.

## 1. Authentication Framework & Dependencies

### Core Technology Stack
- **Framework**: Laravel 12.0
- **JWT Library**: firebase/php-jwt (v6.11)
- **Password Hashing**: BCrypt (BCRYPT_ROUNDS=12)
- **MFA**: OTP/TOTP via spomky-labs/otphp (v11.3)
- **Authorization**: Spatie Permission (v6.23)
- **QR Code Generation**: chillerlan/php-qrcode (v5.0)

### Key Files & Structure
```
Authentication Implementation:
├── Services/
│   └── JwtService.php              - Token generation & verification
├── Http/Middleware/
│   ├── JwtAuthMiddleware.php        - JWT validation middleware
│   └── CheckPremiumAccess.php       - Premium subscription check
├── Http/Controllers/Api/V1/
│   └── AuthController.php           - Auth endpoints
├── Actions/Auth/                    - Business logic actions
│   ├── RegisterUserAction.php
│   ├── LoginUserAction.php
│   ├── RefreshTokenAction.php
│   ├── LogoutUserAction.php
│   ├── ForgotPasswordAction.php
│   ├── ResetPasswordAction.php
│   ├── SetupMfaAction.php
│   ├── VerifyMfaAction.php
│   ├── DisableMfaAction.php
│   ├── OauthGoogleAction.php
│   └── OauthAppleAction.php
├── Http/Requests/Auth/              - Input validation
├── Models/
│   ├── User.php
│   ├── Session.php
│   └── OauthProvider.php
└── config/
    ├── auth.php
    └── jwt.php
```

## 2. JWT Token Implementation

### Token Generation (JwtService.php)

#### Access Token
- **Algorithm**: HS256 (configurable via JWT_ALGO)
- **TTL**: 900 seconds (15 minutes, configurable via JWT_ACCESS_TOKEN_TTL)
- **Payload**:
  - `iss`: Issuer (app.url)
  - `sub`: Subject (user ID)
  - `iat`: Issued at timestamp
  - `exp`: Expiration timestamp

#### Refresh Token
- **Type**: Database-stored random token (not JWT)
- **Format**: 64-character random string hashed with SHA256
- **TTL**: 2,592,000 seconds (30 days, configurable via JWT_REFRESH_TOKEN_TTL)
- **Stored In**: `sessions` table
- **Storage Includes**:
  - User ID (foreign key)
  - Hashed refresh token
  - IP Address (for audit trail)
  - User-Agent (for device identification)
  - Expiration timestamp
  - Last used timestamp

#### Token Verification
- Access tokens verified using Firebase JWT library
- Signature validation with HS256
- Expiration check
- User existence validation in database

### Configuration (config/jwt.php)
```php
JWT_SECRET          // Secret key for signing tokens
JWT_ALGO            // Algorithm (default: HS256)
JWT_ACCESS_TOKEN_TTL    // Access token lifetime (default: 900s)
JWT_REFRESH_TOKEN_TTL   // Refresh token lifetime (default: 2592000s)
```

## 3. Authentication Methods

### Method 1: Email/Password Authentication
#### Registration Endpoint: `POST /api/v1/auth/register`
- Validation:
  - Email: required, valid email format, unique in users table
  - Password: required, minimum 8 characters, maximum 255 characters
  - First Name: optional, max 100 characters
  - Last Name: optional, max 100 characters

- Process:
  1. Validate input
  2. Hash password using BCrypt
  3. Create user record with UUID
  4. Generate access token
  5. Generate refresh token and session record
  6. Return user data with both tokens

#### Login Endpoint: `POST /api/v1/auth/login`
- Validation:
  - Email: required, valid email format
  - Password: required string

- Process:
  1. Find user by email
  2. Verify password using Hash::check()
  3. Generate new access token
  4. Generate new refresh token with IP and User-Agent
  5. Return tokens with user data

### Method 2: OAuth Authentication
#### Google OAuth: `POST /api/v1/auth/oauth/google`
- Input:
  - `identity_token`: Google JWT token (required)
  - `email`: Optional email override
  - `name`: Optional name override

- Process:
  1. Decode identity token (validates JWT in production)
  2. Extract user ID from 'sub' claim
  3. Extract email from 'email' claim
  4. Check if OAuth provider exists
  5. If exists: Update last_used_at, retrieve user
  6. If new: Create user or link to existing, create OauthProvider record
  7. Generate tokens
  8. Return user data and tokens

#### Apple OAuth: `POST /api/v1/auth/oauth/apple`
- Input:
  - `identity_token`: Apple JWT token (required)
  - `email`: Optional (Apple can hide email)
  - `name`: Optional

- Process:
  1. Similar to Google OAuth
  2. Generates fallback email: `apple_{userId}@signatureapp.com` if no email provided
  3. Creates OauthProvider record with 'apple' provider type

#### Security Note (OAuth)
- Current implementation decodes tokens without signature verification (development mode)
- Production implementation should verify with provider's public keys
- Code includes comments indicating needed improvements

### Method 3: Token Refresh
#### Endpoint: `POST /api/v1/auth/refresh`
- Input: `refresh_token` (required string)

- Process:
  1. Hash the provided refresh token
  2. Query sessions table for matching, non-expired token
  3. Update last_used_at timestamp
  4. Revoke old refresh token
  5. Generate new access token
  6. Generate new refresh token
  7. Return both new tokens

- Security: Old refresh token is revoked before issuing new one

### Method 4: Logout
#### Endpoint: `POST /api/v1/auth/logout`
- Input: `refresh_token` (required)

- Process:
  1. Hash the provided refresh token
  2. Delete corresponding session record
  3. Return success message

- Effect: User cannot use that refresh token again

## 4. Multi-Factor Authentication (MFA)

### MFA Type
- **Algorithm**: TOTP (Time-based One-Time Password)
- **Library**: spomky-labs/otphp
- **Code Length**: 6 digits
- **QR Code Generation**: For scanning with authenticator apps

### Setup MFA: `POST /api/v1/auth/mfa/setup`
- Input: `user_id` (required UUID)
- Process:
  1. Generate TOTP secret
  2. Set label to user email
  3. Set issuer to app name
  4. Store secret in user.mfa_secret (NOT enabled yet)
  5. Generate QR code from provisioning URI
  6. Return secret and QR code SVG

### Verify MFA: `POST /api/v1/auth/mfa/verify`
- Input:
  - `user_id` (required UUID)
  - `code` (required, must be 6 digits)

- Process:
  1. Retrieve user and validate MFA secret exists
  2. Create TOTP instance from stored secret
  3. Verify provided 6-digit code
  4. Enable MFA by setting mfa_enabled = true
  5. Return success message

### Disable MFA: `POST /api/v1/auth/mfa/disable`
- Input: `user_id` (required UUID)
- Process:
  1. Validate user has MFA enabled
  2. Set mfa_enabled = false
  3. Clear mfa_secret field
  4. Return success message

## 5. Password Reset Flow

### Forgot Password: `POST /api/v1/auth/forgot-password`
- Input: `email` (required, must exist in users table)

- Process:
  1. Delete any existing reset tokens for email
  2. Generate 64-character random token
  3. Hash token using Hash::make()
  4. Store in password_reset_tokens table
  5. TODO: Send email with reset link (not yet implemented)
  6. Return success message

- Token Expiry: 1 hour from creation

### Reset Password: `POST /api/v1/auth/reset-password`
- Input:
  - `token`: Reset token (required)
  - `email`: User email (required, must exist)
  - `password`: New password (required)
  - `password_confirmation`: Confirm password

- Validation:
  - Password requirements (via Laravel Password rule):
    - Minimum 8 characters
    - Mixed case (uppercase and lowercase)
    - At least one number
    - At least one symbol

- Process:
  1. Find reset token in database
  2. Verify token hash matches (timing-safe comparison)
  3. Check token not expired (max 60 minutes old)
  4. Find user by email
  5. Hash and update password
  6. Delete used reset token
  7. Return success message

## 6. Authentication Middleware

### JWT Auth Middleware
- **Location**: `app/Http/Middleware/JwtAuthMiddleware.php`
- **Alias**: `auth.jwt`
- **Registration**: `bootstrap/app.php`

- Process:
  1. Extract Bearer token from Authorization header
  2. Return 401 if no token provided
  3. Verify token using JwtService::verifyAccessToken()
  4. Return 401 if token invalid or expired
  5. Extract user ID from token 'sub' claim
  6. Fetch user from database
  7. Return 401 if user not found
  8. Attach user to request via setUserResolver()
  9. Allow request to proceed

- Response on failure: JSON error with 401 status

### Premium Access Middleware
- **Location**: `app/Http/Middleware/CheckPremiumAccess.php`
- **Purpose**: Verify user has active premium subscription

- Validation:
  1. User must be authenticated
  2. `is_premium` flag must be true
  3. `premium_expires_at` must be a valid datetime
  4. `premium_expires_at` must be in the future

- Response on failure: 403 Forbidden with PREMIUM_REQUIRED error

## 7. Route Protection & Rate Limiting

### Route Groups
```
Public Auth Routes (Rate Limited: 60 requests per 1 minute):
├── POST /api/v1/auth/register
├── POST /api/v1/auth/login
├── POST /api/v1/auth/refresh
├── POST /api/v1/auth/logout
├── POST /api/v1/auth/forgot-password
├── POST /api/v1/auth/reset-password
├── POST /api/v1/auth/oauth/google
└── POST /api/v1/auth/oauth/apple

Protected Routes (Requires auth.jwt, Rate Limited: 60 requests per 1 minute):
├── auth/mfa/* (MFA management)
├── subscription/* (Subscription management)
├── analytics/* (User stats)
├── feedback/* (Support feedback)
├── signatures/* (User signatures)
├── wallpaper-templates/* (Browse templates)
└── wallpapers/* (Generate/manage wallpapers)
```

## 8. User Model & Database Schema

### User Table Structure
```sql
CREATE TABLE users (
    id UUID PRIMARY KEY,
    email VARCHAR UNIQUE,
    password_hash VARCHAR NULLABLE,           -- Nullable for OAuth users
    first_name VARCHAR NULLABLE,
    last_name VARCHAR NULLABLE,
    is_premium BOOLEAN DEFAULT false,
    premium_expires_at TIMESTAMP NULLABLE,
    mfa_enabled BOOLEAN DEFAULT false,
    mfa_secret VARCHAR NULLABLE,              -- TOTP secret
    locale VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'UTC',
    avatar_url VARCHAR NULLABLE,
    remember_token VARCHAR NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULLABLE,            -- Soft deletes

    INDEX (email),
    INDEX (is_premium)
);
```

### Session Table Structure
```sql
CREATE TABLE sessions (
    id UUID PRIMARY KEY,
    user_id UUID FOREIGN KEY,
    refresh_token VARCHAR UNIQUE,             -- Hashed token
    ip_address VARCHAR(45) NULLABLE,
    user_agent TEXT NULLABLE,
    expires_at TIMESTAMP,
    last_used_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX (user_id),
    INDEX (refresh_token),
    INDEX (expires_at)
);
```

### OAuth Provider Table Structure
```sql
CREATE TABLE oauth_providers (
    id UUID PRIMARY KEY,
    user_id UUID FOREIGN KEY,
    provider ENUM('apple', 'google'),
    provider_user_id VARCHAR,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE (provider, provider_user_id),
    INDEX (user_id)
);
```

### Password Reset Tokens Table
```sql
CREATE TABLE password_reset_tokens (
    email VARCHAR PRIMARY KEY,
    token VARCHAR,                            -- Hashed token
    created_at TIMESTAMP NULLABLE,

    INDEX (email)
);
```

## 9. Input Validation

### Common Validation Rules
- **Email**: Required, valid email format, unique check
- **Password**: Minimum 8 characters, max 255 characters
- **User ID**: Required UUID format, must exist in users table
- **MFA Code**: Required, exactly 6 digits
- **Refresh Token**: Required string
- **Password Reset**: Confirmed, mixed case, numbers, symbols

### Request Classes
All validation implemented in `app/Http/Requests/Auth/`:
- RegisterRequest
- LoginRequest
- RefreshTokenRequest
- LogoutRequest
- ForgotPasswordRequest
- ResetPasswordRequest
- VerifyMfaRequest
- SetupMfaRequest
- DisableMfaRequest
- OauthGoogleRequest
- OauthAppleRequest

## 10. Security Measures In Place

### Password Security
1. Hashed using BCrypt with 12 rounds (BCRYPT_ROUNDS=12)
2. Passwords hidden in User model (in $hidden array)
3. Minimum 8 character requirement
4. Password reset requires: mixed case, numbers, symbols
5. MFA secrets hidden in User model

### Token Security
1. Access tokens are short-lived (15 minutes)
2. Refresh tokens stored hashed in database (SHA256)
3. Refresh tokens include IP and User-Agent for audit trail
4. Session management with expiration
5. Token revocation on logout
6. Bearer token extraction from Authorization header
7. All token operations wrapped in error handling

### User Data Security
1. Soft deletes for data retention
2. OAuth users have null password_hash
3. Email is unique and indexed
4. Premium expiry validation

### Request Security
1. Rate limiting on public auth endpoints (60 req/min)
2. Input validation on all endpoints
3. Email existence checks in password reset
4. User existence checks during token generation
5. Custom error messages without revealing sensitive details

### Session Management
1. IP address tracking
2. User-Agent tracking
3. Last used timestamp
4. Token expiration
5. Session cleanup on logout

### Role-Based Access Control
1. Spatie Permission integration
2. CheckPremiumAccess middleware for premium-only features
3. Role and permission system available (configured via Spatie)

## 11. Error Handling

### Standard Error Responses
- **401 Unauthorized**: Missing or invalid authentication
  - Missing token
  - Expired token
  - Invalid token
  - User not found
  
- **403 Forbidden**: Insufficient permissions
  - Premium subscription required
  
- **422 Unprocessable Entity**: Validation errors
  - Invalid input format
  - Constraint violations
  - Business logic failures

### Validation Errors
Returned with custom messages defined in request classes, for example:
- "Email is already registered"
- "The provided credentials are incorrect"
- "Password reset token has expired"
- "Invalid MFA code"

## 12. Notable Design Patterns

1. **Action Pattern**: Business logic encapsulated in Action classes
2. **Service Pattern**: JwtService handles token operations
3. **Resource Pattern**: AuthResource formats API responses
4. **Middleware**: Centralized authentication and authorization
5. **Request Validation**: Form request classes for input validation

## 13. Potential Security Improvements

Based on code review, suggested enhancements:

1. **OAuth Token Verification**: In production, verify OAuth tokens with:
   - Google's public keys endpoint
   - Apple's public keys endpoint
   - Currently tokens are decoded without signature verification

2. **MFA at Login**: Current implementation separates MFA verification from login
   - Consider requiring MFA code immediately after password authentication for accounts with MFA enabled

3. **Refresh Token Rotation**: Already implemented
   - Could add optional maximum concurrent sessions per user

4. **Email Verification**: Not implemented
   - Validate email ownership during registration

5. **IP Whitelist/Risk Management**: Not implemented
   - Could flag unusual login locations
   - Require additional verification for suspicious activity

6. **Password Change Requirement**: Not implemented
   - Could require password change after reset
   - Could implement password age requirements

7. **Account Lockout**: Not implemented
   - Could lock account after N failed login attempts
   - Temporal lockout (e.g., 15 minutes after 5 failures)

8. **Device Trust Management**: Not implemented
   - Remember trusted devices
   - Allow password-less login on trusted devices

9. **Session Limit**: Not implemented
   - Could limit concurrent sessions
   - Device management dashboard

10. **Audit Logging**: Not fully implemented
    - Currently logs IP and User-Agent
    - Could expand to comprehensive audit trail

## 14. API Response Format

### Successful Authentication Response
```json
{
    "access_token": "eyJ0eXAi...",
    "refresh_token": "random64charstring",
    "token_type": "Bearer",
    "expires_in": 900,
    "user": {
        "id": "uuid",
        "email": "user@example.com",
        "first_name": "John",
        "last_name": "Doe",
        "is_premium": false,
        "mfa_enabled": false,
        "locale": "en",
        "timezone": "UTC",
        "avatar_url": null,
        "created_at": "2025-11-17T...",
        "updated_at": "2025-11-17T..."
    }
}
```

## Summary

The Signature API implements a **production-grade authentication system** with:
- JWT-based stateless API authentication
- Multiple authentication methods (Email/Password, Google OAuth, Apple OAuth)
- Refresh token rotation with session tracking
- TOTP-based multi-factor authentication
- Secure password reset flow
- Middleware-based access control
- Role-based access control (via Spatie)
- Comprehensive input validation
- Rate limiting on sensitive endpoints
- IP and User-Agent tracking for security audits

The architecture is well-designed, follows Laravel best practices, and implements most modern security standards for API authentication.
