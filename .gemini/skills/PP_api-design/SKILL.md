---
name: PP_api-design
description: Design and implement Pixely Platform REST API endpoints with OpenAPI generation
---

# PP_api-design

Design and implement REST API endpoints for Pixely Platform with OpenAPI generation.

## API Base URL
```
/api/v1/...
```

## Design Rules

### Response Structure
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

### Error Structure
```json
{
  "message": "Human readable error message",
  "code": "MACHINE_READABLE_CODE",
  "details": { ... }
}
```

### HTTP Status Codes
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

## Endpoint Naming
- Collections: `GET /api/v1/photos` (index)
- Single resource: `GET /api/v1/photos/{id}` (show)
- Creation: `POST /api/v1/photos` (store)
- Update: `PUT/PATCH /api/v1/photos/{id}` (update)
- Delete: `DELETE /api/v1/photos/{id}` (destroy)

## OpenAPI Documentation
- Generated via `dedoc/scramble` package
- Route annotations: `@Scramble\Tag("Photos")`, `@Scramble\Summary`, `@Scramble\Description`
- `docs/api/openapi.json` is a generated artifact — never manually edit
- Swagger UI served at `/docs/api`

## Authentication
- SPA mode with Laravel Sanctum
- Session cookies + `X-XSRF-TOKEN` header
- Protected routes: `auth:sanctum` middleware
- Public endpoints: no middleware (e.g., login)

## Example Controller
```php
use App\Core\Photos\Services\PhotoService;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function __construct(protected PhotoService $service) {}

    #[Scramble\Summary('Get all photos')]
    #[Scramble\Tag('Photos')]
    public function index(Request $request)
    {
        return $this->service->getList($request);
    }

    #[Scramble\Summary('Create a photo')]
    #[Scramble\Tag('Photos')]
    public function store(Request $request)
    {
        $photo = $this->service->create($request->validated());
        return response()->json($photo, 201);
    }
}
```