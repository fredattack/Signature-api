# Authentication Architecture Diagrams - Signature API

## 1. Authentication Flow Diagrams

### Email/Password Login Flow
```
Client                          API Server                      Database
  |                               |                                |
  |--1. POST /auth/login-------->|                                |
  |   (email, password)           |                                |
  |                               |--2. Find user by email------->|
  |                               |                                |
  |                               |<---3. Return user record------|
  |                               |                                |
  |                               |--4. Hash::check(password)     |
  |                               |   (BCrypt verification)        |
  |                               |                                |
  |                               |--5. Generate JWT access token |
  |                               |                                |
  |                               |--6. Create session record---->|
  |                               |    (hash token, store IP)      |
  |                               |                                |
  |                               |<---7. Confirm session--------|
  |                               |                                |
  |<--8. Return auth response-----|
  |   {access_token, refresh_token}
  |   {token_type: Bearer}
  |   {expires_in: 900}
  |
  |--9. Store tokens locally--
  |
  |--10. Use access_token in future requests
  |    Header: Authorization: Bearer {access_token}
```

### Token Refresh Flow
```
Client                          API Server                      Database
  |                               |                                |
  |--1. POST /auth/refresh------->|                                |
  |   (refresh_token)              |                                |
  |                               |--2. Hash refresh_token------->|
  |                               |                                |
  |                               |--3. Find & verify session---->|
  |                               |   (check expiry, not deleted)  |
  |                               |                                |
  |                               |<---4. Return session record---|
  |                               |                                |
  |                               |--5. Update last_used_at------>|
  |                               |                                |
  |                               |--6. Delete old session------->|
  |                               |    (revoke old token)          |
  |                               |                                |
  |                               |--7. Create new session------->|
  |                               |    (hash new token)            |
  |                               |                                |
  |                               |--8. Generate new JWT token    |
  |                               |                                |
  |<--9. Return new tokens--------|
  |   (new access_token, new refresh_token)
```

### Logout Flow
```
Client                          API Server                      Database
  |                               |                                |
  |--1. POST /auth/logout-------->|                                |
  |   (refresh_token)              |                                |
  |                               |--2. Hash refresh_token------->|
  |                               |                                |
  |                               |--3. Find session record------->|
  |                               |                                |
  |                               |<---4. Confirm found-----------|
  |                               |                                |
  |                               |--5. DELETE session record---->|
  |                               |    (revoke refresh token)      |
  |                               |                                |
  |                               |<---6. Confirm deletion--------|
  |                               |                                |
  |<--7. Return success message---|
  |
  |--8. Clear tokens locally--
```

## 2. Request Protection with JWT Middleware

```
Client Request                  JwtAuthMiddleware               Database
  |                               |                                |
  |--1. GET /api/v1/protected---->|                                |
  |   Authorization: Bearer {token}
  |                               |                                |
  |                               |--2. Extract Bearer token      |
  |                               |                                |
  |                               |--3. Verify JWT signature      |
  |                               |   (HS256, check exp, iat)      |
  |                               |                                |
  |                               |--4. Extract 'sub' (user_id)   |
  |                               |                                |
  |                               |--5. Find user in database----->|
  |                               |                                |
  |                               |<---6. Return user record------|
  |                               |                                |
  |                               |--7. Attach user to request    |
  |                               |                                |
  |<--8. Proceed to controller----|
  |   (with auth()->user() available)
  |
  | On Failure:
  |--1. Missing token ----------->|--Return 401 Unauthorized
  |--2. Invalid token ----------->|--Return 401 Unauthorized
  |--3. Expired token ----------->|--Return 401 Unauthorized
  |--4. User not found ---------->|--Return 401 Unauthorized
```

## 3. Multi-Factor Authentication (MFA) Setup & Verification

```
Setup Phase:
Client                          API Server                      Database
  |                               |                                |
  |--1. POST /auth/mfa/setup----->|                                |
  |   (user_id)                    |                                |
  |                               |--2. Generate TOTP secret      |
  |                               |   (random 32-byte base32)      |
  |                               |                                |
  |                               |--3. Store secret in user------>|
  |                               |                                |
  |                               |--4. Generate QR code SVG      |
  |                               |   (provisioning URI)           |
  |                               |                                |
  |<--5. Return QR code----------|
  |   {secret, qr_code}
  |
  |--6. Scan QR code with authenticator app
  |--7. Save secret locally

Verification Phase:
  |                               |                                |
  |--1. POST /auth/mfa/verify---->|                                |
  |   (user_id, 6-digit code)      |                                |
  |                               |--2. Retrieve user------>|
  |                               |                          |
  |                               |<---User record-------|
  |                               |                                |
  |                               |--3. TOTP verify code  |
  |                               |   (time-window window: ±30s)   |
  |                               |                                |
  |                               |--4. If valid, enable MFA--->|
  |                               |                                |
  |                               |<---Confirm enabled--------|
  |                               |                                |
  |<--5. MFA enabled message------|
```

## 4. Password Reset Flow

```
Forgot Password Request:
Client                          API Server                      Database
  |                               |                                |
  |--1. POST /forgot-password---->|                                |
  |   (email)                      |                                |
  |                               |--2. Delete old tokens-------->|
  |                               |                                |
  |                               |--3. Generate 64-char token    |
  |                               |                                |
  |                               |--4. Hash token (Hash::make)   |
  |                               |                                |
  |                               |--5. Store in DB with time---->|
  |                               |                                |
  |                               |--6. Send email (TODO)         |
  |                               |                                |
  |<--7. Success message---------|

Reset Password:
  |                               |                                |
  |--1. POST /reset-password----->|                                |
  |   (token, email, password)     |                                |
  |                               |--2. Find reset token-------->|
  |                               |                                |
  |                               |<---Return token record-----|
  |                               |                                |
  |                               |--3. Hash::check token        |
  |                               |   (timing-safe comparison)     |
  |                               |                                |
  |                               |--4. Check expiry (<60 min)    |
  |                               |                                |
  |                               |--5. Find user by email------->|
  |                               |                                |
  |                               |<---Return user record-----|
  |                               |                                |
  |                               |--6. Hash password, update---->|
  |                               |    password_hash field         |
  |                               |                                |
  |                               |--7. Delete reset token------->|
  |                               |                                |
  |<--8. Success message---------|
```

## 5. OAuth Authentication Flow (Google/Apple)

```
OAuth Login:
Client                      Identity Provider        API Server         Database
  |                               |                      |                  |
  |--1. User clicks "Login"------>|                      |                  |
  |     with Google/Apple         |                      |                  |
  |                               |                      |                  |
  |<--2. Redirect to provider-----|                      |                  |
  |     login page                |                      |                  |
  |                               |                      |                  |
  |--3. User authenticates------->|                      |                  |
  |                               |                      |                  |
  |<--4. Redirect + identity_token|                      |                  |
  |                               |                      |                  |
  |--5. POST /oauth/google-------->|                      |                  |
  |    {identity_token}            |                      |                  |
  |                               |                      |                  |
  |                               |      6. Decode token |                  |
  |                               |      (extract sub, email)               |
  |                               |                      |                  |
  |                               |      7. Find OAuth provider----->|
  |                               |                      |                  |
  |                               |<---------Oauth record (or null)--|
  |                               |                      |                  |
  |                               |      8. Find/create user------>|
  |                               |                      |                  |
  |                               |<---------User record---------|
  |                               |                      |                  |
  |                               |      9. Create/update OAuth provider
  |                               |      & session records------>|
  |                               |                      |                  |
  |                               |<----------Confirm creation--|
  |                               |                      |                  |
  |<--10. Return tokens-----------|                      |                  |
  |    {access_token, refresh_token}
  |    {token_type: Bearer}
  |    {user, expires_in}
```

## 6. Database Schema Relationships

```
┌─────────────────────┐
│      USERS          │
├─────────────────────┤
│ id (PK, UUID)       │
│ email (UNIQUE)      │◄──────────┐
│ password_hash       │           │
│ first_name          │           │
│ last_name           │           │
│ is_premium          │           │
│ premium_expires_at  │           │
│ mfa_enabled         │           │
│ mfa_secret          │           │
│ locale              │           │
│ timezone            │           │
│ avatar_url          │           │
│ created_at          │           │
│ updated_at          │           │
│ deleted_at          │           │
└─────────────────────┘           │
         ▲                         │
         │ 1:N                     │
         │                         │
    ┌─────────────────────────┐   │
    │     SESSIONS            │   │
    ├─────────────────────────┤   │
    │ id (PK, UUID)           │   │
    │ user_id (FK) ───────────┘   │
    │ refresh_token (UNIQUE)       │
    │ ip_address                   │
    │ user_agent                   │
    │ expires_at                   │
    │ last_used_at                 │
    │ created_at                   │
    │ updated_at                   │
    └─────────────────────────┘   
                                   │
    ┌────────────────────────┐    │
    │   PASSWORD_RESET_TOKENS │   │
    ├────────────────────────┤    │
    │ email (PK) ────────────┼────┘
    │ token (HASHED)         │
    │ created_at             │
    └────────────────────────┘

    ┌────────────────────────┐
    │   OAUTH_PROVIDERS      │
    ├────────────────────────┤
    │ id (PK, UUID)          │
    │ user_id (FK) ──┐       │
    │ provider       │       │
    │ provider_user_id
    │ created_at     │       │
    │ updated_at     │       │
    └────────────────┼───────┘
                     │
                ┌────┘
                │
           ┌────▼──────────┐
           │     USERS     │
           └──────────────┘
           (same as above)

RELATIONSHIPS:
- Users 1:N Sessions (one user has many sessions)
- Users 1:N OauthProviders (one user can have multiple OAuth providers)
- Email points to Users via password reset tokens
```

## 7. Token Lifecycle Timeline

```
Registration/Login:
Time 0:00
  ├─ Access Token Generated
  │  ├─ Issued At: 0:00
  │  ├─ Expiry: 0:15 (15 minutes)
  │  └─ Used for: Protected API requests
  │
  └─ Refresh Token Created
     ├─ Stored in Sessions table
     ├─ Hash stored in DB (not JWT)
     ├─ Issued At: 0:00
     ├─ Expiry: 30 days from issue
     └─ Used for: Getting new access tokens

Time 0:10
  ├─ Request with access token
  │  └─ Validation: ✓ Signature OK, ✓ Not expired
  │
  └─ Request succeeds

Time 0:15 (Access token expires)
  ├─ Request with old access token
  │  └─ Validation: ✗ Token expired
  │
  ├─ Client calls /auth/refresh with refresh token
  │
  ├─ Old refresh token revoked (deleted)
  │
  ├─ New tokens generated:
  │  ├─ New access token (expires 0:30)
  │  └─ New refresh token (expires 30 days + 1)
  │
  └─ Client receives new tokens

Time 0:30 (New access token expires)
  ├─ Similar process repeats

Time 30 days (Refresh token expires)
  ├─ Refresh token no longer valid in DB
  ├─ Cannot generate new access token
  └─ User must login again

Logout at any time:
  ├─ Client sends /auth/logout with refresh token
  ├─ Refresh token is deleted from sessions table
  ├─ Both access and refresh tokens become invalid
  └─ Session ends
```

## 8. Security Validation Layers

```
Request Flow Through Security Layers:

1. RATE LIMITING LAYER (throttle:60,1)
   └─ Allow 60 requests per 1 minute per IP
   └─ Applied to: Auth routes

2. INPUT VALIDATION LAYER (FormRequest)
   ├─ Email format validation
   ├─ Password requirements
   ├─ UUID validation
   ├─ Field presence checks
   └─ Custom error messages

3. AUTHENTICATION LAYER (JwtAuthMiddleware)
   ├─ Extract Bearer token
   ├─ Verify JWT signature (HS256)
   ├─ Check expiration
   ├─ Validate user exists
   └─ Attach user to request

4. AUTHORIZATION LAYER (Middleware)
   ├─ CheckPremiumAccess
   ├─ Role-based access control (Spatie)
   └─ Custom permission checks

5. BUSINESS LOGIC LAYER (Action classes)
   ├─ Password hashing (BCrypt)
   ├─ TOTP verification
   ├─ Token hash comparison
   └─ Database constraints

6. DATABASE LAYER
   ├─ Foreign key constraints
   ├─ Unique constraints
   ├─ Index optimization
   └─ Soft deletes
```

## 9. Error Response Flow

```
Client Request
     │
     ▼
Rate Limit Check
     │
  ┌──┴──┐
  │     │
 YES   NO ──────────────────────────────┐
  │                                      │
429                              Input Validation
Too Many                                │
Requests                         ┌──────┴──────┐
                                │              │
                              PASS           FAIL
                                │              │
                                │            422
                                │       Validation Errors
                                │       {errors: {...}}
                                ▼
                        Authentication Check
                                │
                         ┌──────┴──────┐
                         │             │
                       PASS           FAIL
                         │             │
                         │           401
                         │      Unauthorized
                         │      {message: "..."}
                         ▼
                    Authorization Check
                         │
                    ┌────┴────┐
                    │          │
                  PASS        FAIL
                    │          │
                    │        403
                    │      Forbidden
                    │      {message: "...", error: "..."}
                    ▼
              Business Logic
                    │
                ┌───┴───┐
                │       │
              PASS     FAIL
                │       │
                │     422/500
                │   Error Response
                │
              200
         Success
         {data: {...}}
```

## 10. Configuration Dependencies

```
Environment Variables:
├── JWT_SECRET              (Required for token signing)
├── JWT_ALGO                (Default: HS256)
├── JWT_ACCESS_TOKEN_TTL    (Default: 900 seconds)
├── JWT_REFRESH_TOKEN_TTL   (Default: 2592000 seconds)
├── BCRYPT_ROUNDS           (Default: 12)
├── DATABASE_URL            (For sessions, users, oauth_providers)
├── APP_URL                 (Used in JWT issuer claim)
└── MAIL_MAILER             (For password reset emails - TODO)

Auth Configuration Files:
├── config/auth.php
│  ├─ Default guard: 'web'
│  ├─ User provider: 'eloquent'
│  ├─ User model: App\Models\User
│  └─ Password reset settings
│
├── config/jwt.php
│  ├─ Secret key
│  ├─ Algorithm
│  ├─ TTL settings
│  └─ Environment-based overrides
│
└── bootstrap/app.php
   └─ Middleware registration
      └─ 'auth.jwt' => JwtAuthMiddleware::class
```

## 11. Code Snippet: Key Implementation Details

### JWT Token Generation (JwtService.php)
```php
// Access Token (JWT)
$payload = [
    'iss' => config('app.url'),
    'sub' => $user->id,
    'iat' => time(),
    'exp' => time() + 900, // 15 minutes
];
JWT::encode($payload, config('jwt.secret'), 'HS256');

// Refresh Token (Database-stored hash)
$token = Str::random(64);
Session::create([
    'refresh_token' => hash('sha256', $token),
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'expires_at' => Carbon::now()->addSeconds(2592000), // 30 days
]);
```

### Token Verification (JwtAuthMiddleware.php)
```php
$payload = JWT::decode($token, new Key(
    config('jwt.secret'),
    config('jwt.algo', 'HS256')
));
// payload contains: iss, sub, iat, exp
```

### Password Hashing (RegisterUserAction.php)
```php
User::create([
    'password_hash' => Hash::make($data['password']), // BCrypt
]);
```

### MFA TOTP (SetupMfaAction.php)
```php
$totp = TOTP::generate();
$totp->setLabel($user->email);
$totp->setIssuer(config('app.name'));
$secret = $totp->getSecret();
$qrCode = (new QRCode)->render($totp->getProvisioningUri());
```
