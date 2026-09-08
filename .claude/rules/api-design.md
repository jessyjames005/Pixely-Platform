# API Design Rules

Path-scoped rules for `app/**/Http/Controllers/**/*.php` and API routes.

## API Base URL
```
/api/v1/...
```

## Response Structure
Collection responses expose pagination metadata:
```json
{
  "data": { ... },
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 75
    }
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": "...",
    "next": "..."
  }
}
```

## Error Structure
```json
{
  "message": "Human readable error message",
  "code": "MACHINE_READABLE_CODE",
  "details": { ... }
}
```

## HTTP Status Codes
- `200` — OK
- `201` — Created
- `204` — No Content
- `400` — Bad Request
- `401` — Unauthorized
- `403` — Forbidden
- `404` — Not Found
- `422` — Unprocessable Entity
- `429` — Too Many Requests
- `500` — Internal Server Error

## Controller Rules
- Controllers orchestrate requests, do not contain business logic
- Validate input at the API boundary
- Delegate work to the appropriate service/query layer
- Return appropriate API response
- Use explicit HTTP status codes
- Do not return database models blindly when a dedicated API representation is required

## OpenAPI Documentation
- Use Scramble annotations: `#[Scramble\Summary]`, `#[Scramble\Tag]`
- `docs/api/openapi.json` is a generated artifact — never manually edit
- Swagger UI served at `/docs/api`