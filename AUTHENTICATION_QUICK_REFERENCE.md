# Authentication Quick Reference - Signature API

## Quick Summary

The Signature API uses a **JWT-based authentication system** with multiple methods:
- Email/Password authentication with BCrypt hashing
- OAuth (Google & Apple)
- TOTP-based Multi-Factor Authentication (MFA)
- Secure password reset flow
- Session tracking with IP/User-Agent

## Key Components

| Component | Location | Purpose |
|-----------|----------|---------|
| JwtService | `app/Services/JwtService.php` | Token generation & verification |
| JwtAuthMiddleware | `app/Http/Middleware/JwtAuthMiddleware.php` | Protects API endpoints |
| AuthController | `app/Http/Controllers/Api/V1/AuthController.php` | Auth endpoint handlers |
| Auth Actions | `app/Actions/Auth/` | Business logic (11 action classes) |
| User Model | `app/Models/User.php` | User entity with auth fields |
| Session Model | `app/Models/Session.php` | Refresh token tracking |
| OauthProvider | `app/Models/OauthProvider.php` | OAuth account linking |

## API Endpoints

### Public Endpoints (Rate Limited: 60 req/min)

```
POST /api/v1/auth/register
  Input: {email, password, first_name?, last_name?}
  Output: {access_token, refresh_token, token_type, expires_in, user}
  
POST /api/v1/auth/login
  Input: {email, password}
  Output: {access_token, refresh_token, token_type, expires_in, user}
  
POST /api/v1/auth/refresh
  Input: {refresh_token}
  Output: {access_token, refresh_token, expires_in}
  
POST /api/v1/auth/logout
  Input: {refresh_token}
  Output: {message: "Successfully logged out"}
  
POST /api/v1/auth/forgot-password
  Input: {email}
  Output: {message: "..."}
  
POST /api/v1/auth/reset-password
  Input: {token, email, password, password_confirmation}
  Output: {message: "..."}
  
POST /api/v1/auth/oauth/google
  Input: {identity_token, email?, name?}
  Output: {access_token, refresh_token, token_type, expires_in, user}
  
POST /api/v1/auth/oauth/apple
  Input: {identity_token, email?, name?}
  Output: {access_token, refresh_token, token_type, expires_in, user}
```

### Protected Endpoints (Requires JWT, Rate Limited: 60 req/min)

```
POST /api/v1/auth/mfa/setup
  Input: {user_id}
  Output: {secret, qr_code}
  
POST /api/v1/auth/mfa/verify
  Input: {user_id, code}
  Output: {message: "..."}
  
POST /api/v1/auth/mfa/disable
  Input: {user_id}
  Output: {message: "..."}
```

## Token Details

### Access Token (JWT)
- **Format**: HS256 signed JWT
- **TTL**: 900 seconds (15 minutes)
- **Payload**: 
  - `iss`: Issuer URL
  - `sub`: User ID
  - `iat`: Issued timestamp
  - `exp`: Expiration timestamp
- **Verification**: Signature checked, expiry validated, user existence verified

### Refresh Token (Database-Stored)
- **Format**: 64-character random string (not JWT)
- **Storage**: Hashed with SHA256 in `sessions` table
- **TTL**: 2,592,000 seconds (30 days)
- **Tracking**: IP address, User-Agent, last used timestamp
- **Rotation**: Old token revoked when new one issued

## Authentication Flows

### 1. Email/Password Login
```
1. POST /auth/login (email, password)
2. Server validates credentials (Hash::check)
3. Generates JWT access token (15 min)
4. Creates refresh token in DB (30 days)
5. Returns both tokens + user data
6. Client stores tokens locally
7. Future requests use: Authorization: Bearer {access_token}
```

### 2. Token Refresh
```
1. Access token expires
2. Client POSTs refresh token to /auth/refresh
3. Server validates refresh token (DB lookup, expiry check)
4. Revokes old refresh token (delete from DB)
5. Issues new access token (15 min)
6. Issues new refresh token (30 days)
7. Client updates stored tokens
```

### 3. Logout
```
1. POST /auth/logout with refresh_token
2. Server deletes refresh token from sessions table
3. Both access and refresh tokens become invalid
4. Client clears stored tokens
```

### 4. MFA Setup
```
1. User calls POST /auth/mfa/setup with user_id
2. Server generates TOTP secret
3. Returns secret + QR code SVG
4. User scans QR code with authenticator app
5. Secret stored in database but NOT yet enabled
```

### 5. MFA Verification
```
1. User gets 6-digit code from authenticator app
2. Calls POST /auth/mfa/verify with code
3. Server verifies code (TOTP with ±30s window)
4. Enables MFA (sets mfa_enabled = true)
5. User now required to use MFA codes for login
```

### 6. Password Reset
```
1. User calls POST /auth/forgot-password (email)
2. Server generates 64-char token
3. Hashes and stores in password_reset_tokens table (1 hour expiry)
4. Sends email with reset link (TODO: not yet implemented)
5. User clicks link and calls POST /auth/reset-password
6. Server validates token hash (timing-safe), checks expiry
7. Updates password_hash with Hash::make()
8. Deletes reset token from DB
```

### 7. OAuth Login (Google/Apple)
```
1. Client gets identity_token from OAuth provider
2. POSTs to /oauth/google or /oauth/apple with token
3. Server decodes JWT (validates in production)
4. Extracts provider user ID and email
5. Checks if OAuth provider record exists
6. If exists: Retrieves linked user
7. If new: Creates user (email) or links to existing user
8. Creates/updates OauthProvider record
9. Generates JWT access token + refresh token
10. Returns tokens + user data
```

## Security Features

### Password Security
- BCrypt hashing with 12 rounds
- Passwords hidden from JSON responses
- Min 8 characters, max 255 characters
- Password reset requires: mixed case, numbers, symbols
- Timing-safe hash comparison (Hash::check)

### Token Security
- Short-lived access tokens (15 minutes)
- Refresh tokens hashed in database (not plaintext or JWT)
- Token revocation on logout
- Session tracking with IP + User-Agent
- Bearer token in Authorization header
- All operations error-wrapped

### Request Security
- Rate limiting: 60 requests per minute on auth routes
- Input validation via FormRequest classes
- Email uniqueness checks
- User existence validation
- Custom error messages (no information leakage)

### Session Management
- IP address tracking per session
- User-Agent tracking per session
- Session expiration tracking
- Last used timestamp tracking
- Automatic cleanup on logout

### Authorization
- JWT middleware for all protected routes
- Premium subscription checking (CheckPremiumAccess middleware)
- Spatie Permission integration available
- Role-based access control support

## Database Tables

### users
```sql
id (UUID), email (UNIQUE), password_hash, first_name, last_name,
is_premium, premium_expires_at, mfa_enabled, mfa_secret,
locale, timezone, avatar_url, created_at, updated_at, deleted_at (soft)
```

### sessions
```sql
id (UUID), user_id (FK), refresh_token (UNIQUE, HASHED),
ip_address, user_agent, expires_at, last_used_at,
created_at, updated_at
```

### oauth_providers
```sql
id (UUID), user_id (FK), provider (enum: apple/google),
provider_user_id, created_at, updated_at
UNIQUE(provider, provider_user_id)
```

### password_reset_tokens
```sql
email (PK), token (HASHED), created_at
```

## Configuration

### Environment Variables
```
JWT_SECRET              # Secret key for JWT signing (REQUIRED)
JWT_ALGO               # Algorithm (default: HS256)
JWT_ACCESS_TOKEN_TTL   # Seconds (default: 900 = 15 min)
JWT_REFRESH_TOKEN_TTL  # Seconds (default: 2592000 = 30 days)
BCRYPT_ROUNDS          # Rounds (default: 12)
APP_URL                # Used in JWT issuer claim
```

### Configuration Files
- `/config/auth.php` - Laravel auth configuration
- `/config/jwt.php` - JWT settings
- `/bootstrap/app.php` - Middleware registration

## Error Codes

| Code | Status | Meaning |
|------|--------|---------|
| 401 | Unauthorized | Missing/invalid/expired token, user not found |
| 403 | Forbidden | Premium subscription required |
| 422 | Unprocessable | Input validation failed |
| 429 | Too Many Requests | Rate limit exceeded (60 req/min) |

## Testing Locally

### 1. Register User
```bash
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePassword123!",
    "first_name": "John",
    "last_name": "Doe"
  }'
```

### 2. Login
```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePassword123!"
  }'
```

### 3. Use Protected Endpoint
```bash
curl -X GET http://localhost/api/v1/auth/mfa/setup \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{"user_id": "uuid-here"}'
```

### 4. Refresh Token
```bash
curl -X POST http://localhost/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "token-here"}'
```

### 5. Logout
```bash
curl -X POST http://localhost/api/v1/auth/logout \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "token-here"}'
```

## Known Limitations & Improvements Needed

1. **OAuth Token Verification** (Development Mode)
   - Tokens decoded without signature verification
   - Production must verify against provider public keys

2. **Email Sending** (Not Implemented)
   - Password reset emails not sent (TODO comment in code)
   - Requires Mail configuration

3. **MFA at Login** (Enhancement)
   - MFA verification separated from login process
   - Could require MFA code immediately after password auth

4. **Account Lockout** (Not Implemented)
   - No brute-force protection for failed logins
   - Could add temporary lockout after N failures

5. **Device Trust** (Not Implemented)
   - No device management or trusted device feature
   - Could reduce MFA requirement for trusted devices

6. **Audit Logging** (Partial)
   - IP/User-Agent tracked but not comprehensive audit trail
   - Could expand for security monitoring

## File Structure Reference

```
Authentication Files:
app/
├── Services/JwtService.php           # Token ops (gen, verify)
├── Http/
│   ├── Controllers/Api/V1/
│   │   └── AuthController.php        # Endpoints
│   ├── Middleware/
│   │   ├── JwtAuthMiddleware.php      # JWT validation
│   │   └── CheckPremiumAccess.php     # Premium check
│   ├── Requests/Auth/
│   │   ├── RegisterRequest.php        # Validation: register
│   │   ├── LoginRequest.php           # Validation: login
│   │   ├── RefreshTokenRequest.php    # Validation: refresh
│   │   ├── LogoutRequest.php          # Validation: logout
│   │   ├── ForgotPasswordRequest.php  # Validation: forgot
│   │   ├── ResetPasswordRequest.php   # Validation: reset
│   │   ├── SetupMfaRequest.php        # Validation: MFA setup
│   │   ├── VerifyMfaRequest.php       # Validation: MFA verify
│   │   ├── DisableMfaRequest.php      # Validation: MFA disable
│   │   ├── OauthGoogleRequest.php     # Validation: Google
│   │   └── OauthAppleRequest.php      # Validation: Apple
│   └── Resources/
│       └── AuthResource.php           # Response formatting
├── Actions/Auth/
│   ├── RegisterUserAction.php         # Register logic
│   ├── LoginUserAction.php            # Login logic
│   ├── RefreshTokenAction.php         # Token refresh logic
│   ├── LogoutUserAction.php           # Logout logic
│   ├── ForgotPasswordAction.php       # Forgot password logic
│   ├── ResetPasswordAction.php        # Reset password logic
│   ├── SetupMfaAction.php             # MFA setup logic
│   ├── VerifyMfaAction.php            # MFA verify logic
│   ├── DisableMfaAction.php           # MFA disable logic
│   ├── OauthGoogleAction.php          # Google OAuth logic
│   └── OauthAppleAction.php           # Apple OAuth logic
└── Models/
    ├── User.php                       # User entity
    ├── Session.php                    # Session entity
    └── OauthProvider.php              # OAuth provider entity

config/
├── auth.php                           # Auth configuration
└── jwt.php                            # JWT configuration

bootstrap/
└── app.php                            # Middleware registration

routes/
└── api.php                            # Route definitions

database/migrations/
├── *_create_users_table.php
├── *_create_sessions_table.php
├── *_create_oauth_providers_table.php
└── *_create_password_reset_tokens_table.php
```

## Related Documentation

For more details, see:
- `/AUTHENTICATION_ARCHITECTURE.md` - Full architecture documentation
- `/AUTHENTICATION_DIAGRAMS.md` - Flow diagrams and visual representations
- `/composer.json` - Dependencies (firebase/php-jwt, spatie/laravel-permission, etc.)
- `/routes/api.php` - Route definitions and middleware assignments
