---
globs: "**/*.php"
---

# Security Rules

## Pre-Commit Security Checks

Before any commit, verify:

1. No hardcoded secrets (API keys, passwords, tokens)
2. All user inputs validated via Form Requests
3. SQL injection prevention (Eloquent or parameterized queries)
4. XSS prevention (sanitize HTML content before storage - TipTap editor content is HTML)
5. CSRF protection via Sanctum (SPA cookie auth sends XSRF-TOKEN header automatically)
6. Authentication/authorization verified (middleware `auth:sanctum` on all API routes)
7. Rate limiting on public endpoints
8. Error messages don't leak sensitive data (no stack traces in production JSON responses)

## Secret Management

```php
// BAD - Never do this
$apiKey = "sk-proj-xxxxx";

// GOOD - Use environment variables
$apiKey = config('services.api.key');
if (!$apiKey) {
    throw new RuntimeException('API key not configured');
}
```

## Common Vulnerabilities to Check

| Vulnerability            | Prevention                                   |
|--------------------------|----------------------------------------------|
| SQL Injection            | Use Eloquent or prepared statements          |
| XSS                      | Sanitize HTML content (editor stores HTML)   |
| CSRF                     | Sanctum handles via XSRF-TOKEN cookie/header |
| Mass Assignment          | Define `$fillable` or `$guarded`             |
| Path Traversal           | Validate file paths, use Storage facade      |
| Insecure Deserialization | Never `unserialize()` user input             |

## Incident Response

When a vulnerability is discovered:

1. Stop current work immediately
2. Assess severity and impact
3. Fix critical vulnerabilities before any other work
4. Rotate any exposed credentials
5. Audit codebase for similar issues
6. Document the fix and prevention measures
