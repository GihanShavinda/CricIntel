# CricIntel P19 — Security Review

## Authentication

Keep Sanctum stateful SPA authentication with CSRF-cookie flow.

Production:
- HTTPS only;
- secure session cookie enabled;
- exact stateful domains;
- exact CORS origins;
- session database or Redis store;
- rotate compromised credentials/secrets.

## Authorization

Server authorization remains authoritative. React role checks are navigation convenience only.

Every organization resource must be scoped to an organization the user can access.

## Uploads

P19 `SecureUploadRules` provides allow-listed extensions/types and size limits.

Additional production rules:
- generate server-side filenames;
- never trust original filename as a storage path;
- store private documents outside the public web root;
- serve private files through authorized controller actions;
- consider malware scanning before public deployment.

## Mass assignment

Models must keep explicit `$fillable` lists or guarded domain methods. Never use `$request->all()` in create/update logic.

Use:

```php
Model::create($request->validated());
```

only when the FormRequest field set exactly matches allowed model fields.

## SQL injection

Continue using:
- Eloquent;
- Query Builder;
- parameter binding;
- controlled P16 schemas.

Never interpolate natural-language input, sort expressions or AI output into raw SQL.

## XSS

React escapes string interpolation by default. Avoid `dangerouslySetInnerHTML`.

Blade templates should use `{{ }}` escaped rendering unless a value is intentionally trusted and sanitized.

## CSRF / session

State-changing SPA requests must use Sanctum CSRF protection. Do not change authenticated write routes to unauthenticated tokenless endpoints.

## Secrets

Never commit:
- `.env`;
- database passwords;
- Reverb secrets;
- mail passwords;
- cloud keys;
- LLM API keys.

CI secrets belong in repository/environment secret stores.

## AI boundary

P15 already validates evidence-backed strategy responses.
P16 already blocks arbitrary SQL/prompt injection patterns.
P19 adds a shared length/injection boundary guard for new AI entry points.

The LLM must not:
- receive DB credentials;
- execute arbitrary SQL;
- invent CVEs, cricket statistics or authoritative decisions;
- bypass server authorization;
- mutate domain state without validated application actions.
