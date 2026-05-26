# Security Audit Report
## Employee Directory with QR System (NSIAI)

**Date:** 2026-05-26
**Status:** Issues Found and Fixed

---

## 1. Hardcoded Credentials (CRITICAL - FIXED)

### Issues Found:
- **config.php** contained hardcoded database credentials (`sql309.infinityfree.com`, username: `if0_40195878`, password: `Im37WoESkqG`)
- **config.php** had hardcoded `ADMIN_PASSWORD = 'northcom'`
- **config.php** had hardcoded `ENCRYPTION_KEY` and `ENCRYPTION_IV`

### Fix Applied:
- All sensitive values moved to `.env` file (gitignored)
- `config.php` now loads credentials via `getenv()` function
- `.env.example` provided as template without real values
- `.gitignore` blocks `.env` from being committed

---

## 2. Password Exposure in Frontend (HIGH - FIXED)

### Issues Found:
- **qr_codes.php** displayed `ADMIN_PASSWORD` in plain text on the page
- **generate_qr.php** printed `ADMIN_PASSWORD` in the print output
- **view_employee.php** passed password via GET parameter (visible in browser history and server logs)
- **setup.php** echoed `ADMIN_PASSWORD` directly

### Fix Applied:
- Removed all `ADMIN_PASSWORD` references from frontend HTML/JavaScript
- Changed `view_employee.php` from GET to POST for password submission
- `setup.php` now says "[stored securely - check .env file]"

---

## 3. No Rate Limiting (HIGH - FIXED)

### Issues Found:
- Login endpoint had no rate limiting, allowing unlimited brute-force attempts
- No rate limiting on any other endpoint

### Fix Applied:
- Created `rate_limiter.php` with file-based rate limiting
- Login route: max **5 attempts per 15 minutes** per IP
- View employee password: max **5 attempts per 15 minutes** per IP
- Authenticated endpoints: max **60 requests per minute** per IP
- Rate limit counter resets on successful login

---

## 4. CSRF Vulnerability (HIGH - FIXED)

### Issues Found:
- CSRF tokens were generated but **never validated** in forms
- Delete operations used GET requests (vulnerable to CSRF)

### Fix Applied:
- Login form now includes and validates CSRF tokens
- Employee deletion changed from GET to POST with CSRF token validation
- Reset password operations use CSRF token validation
- CSRF tokens regenerated after sensitive actions

---

## 5. Session Security (MEDIUM - FIXED)

### Issues Found:
- No session fixation prevention
- Session cookies not marked HttpOnly or SameSite

### Fix Applied:
- `session_regenerate_id(true)` called on successful login
- `session.cookie_httponly = 1` enabled
- `session.cookie_samesite = 'Strict'` enabled
- `session.use_strict_mode = 1` enabled

---

## 6. Missing Security Headers (MEDIUM - FIXED)

### Issues Found:
- No security headers set

### Fix Applied:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- Content Security Policy via `.htaccess`

---

## 7. Input Validation (MEDIUM - FIXED)

### Issues Found:
- No input length validation
- No file upload type/size validation (only checked `error == 0`)
- No email format validation

### Fix Applied:
- Created `input_validator.php` with comprehensive validation
- Max length enforcement on all fields
- File upload validation: type (jpg/jpeg/png/gif only), size (5MB max), MIME type verification
- Email format validation
- Phone number format validation
- Date format validation
- Numeric field validation
- Request body size limit (10MB max)
- Added `maxlength` attributes on all frontend input fields

---

## 8. Debug Files Exposed (MEDIUM - FIXED)

### Issues Found:
- `debug_users.php` - exposed database structure and user data
- `test_login.php` - exposed login credentials and password hashes
- `create_hash.php` - exposed password hashing mechanism

### Fix Applied:
- `.htaccess` blocks access to `debug_users.php`, `test_login.php`, `create_hash.php`, and `setup.php`
- `.gitignore` added to prevent committing these files in future
- Error display disabled in production (`display_errors = off`)

---

## 9. File Upload Security (MEDIUM - FIXED)

### Issues Found:
- `uploads/` directory allowed PHP execution
- No MIME type verification
- Directory listing enabled

### Fix Applied:
- `.htaccess` disables PHP execution in `uploads/` directory
- `.htaccess` only allows image file access in `uploads/`
- Directory browsing disabled (`Options -Indexes`)
- File upload validation with MIME type checking

---

## 10. Information Disclosure (LOW - FIXED)

### Issues Found:
- PHP version exposed via headers
- Server signature exposed
- Error messages displayed to users

### Fix Applied:
- `expose_php = off` in `.htaccess`
- `ServerSignature Off` in `.htaccess`
- `display_errors = off` in `.htaccess`
- Generic error message for database connection failures

---

## Remaining Recommendations

1. **HTTPS**: Enable SSL/TLS certificate on the hosting server
2. **Password Policy**: Consider enforcing stronger passwords (currently min 6 chars)
3. **Two-Factor Authentication**: Consider adding 2FA for admin accounts
4. **Database Encryption**: Consider encrypting sensitive employee data at rest
5. **Audit Logging**: Activity logs are in place - consider periodic review
6. **Backup**: Set up regular database backups
7. **Update Dependencies**: Keep Font Awesome and QRCode.js libraries updated

---

## How to Use the Security Features

### Environment Variables (.env file)
Your sensitive data is stored in the `.env` file in the project root. To update credentials:

1. Edit the `.env` file on your server
2. Change the values for `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME`
3. Change `ADMIN_PASSWORD` to your desired password
4. Change `ENCRYPTION_KEY` to a random 32+ character string
5. Change `ENCRYPTION_IV` to a random 16-character string
6. **Never commit the `.env` file to git** (it's already in `.gitignore`)

### Rate Limiting
- Login is limited to **5 attempts per 15 minutes** per IP address
- After 5 failed attempts, the user must wait 15 minutes
- Successful login resets the counter
- Rate limit data is stored in the system temp directory

### Input Validation
- All form inputs are validated for length, format, and type
- File uploads are restricted to images (JPG, PNG, GIF) under 5MB
- Oversized requests (>10MB) are rejected automatically

### .htaccess Protection
- Sensitive files (`.env`, `.git/`, debug files) are blocked from web access
- PHP execution is disabled in the `uploads/` directory
- Security headers are automatically applied
- Directory browsing is disabled

### Password Reset
- Navigate to **Reset Password** in the sidebar menu
- Click "Reset Admin Password" to reset to `admin / admin1234`
- Or use "Change User Password" to set a custom password for any user
