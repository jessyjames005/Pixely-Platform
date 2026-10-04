# Pixely Platform API Examples

This document shows how to call the Pixely Platform API using the
[JSON:API](https://jsonapi.org/) specification with the strict media type
**`application/vnd.api+json`**.

- API base URL: `http://localhost:8080/api/v1`
- All JSON request and response bodies use the `data`, `errors`, `meta` and
  `links` envelopes defined by JSON:API.
- `Content-Type` and `Accept` headers must be set to `application/vnd.api+json`
  for regular JSON:API requests. The only exception is multipart upload, which
  uses `multipart/form-data` while still `Accept`-ing `application/vnd.api+json`.
- Write actions (`store`, `update`, `destroy`) are protected by Sanctum
  session authentication. Authenticate first with `/auth/login`.

---

## Authentication

### Log in

Start a Sanctum session and receive the authenticated user as a `users`
resource.

**Request**

```http
POST /auth/login
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "users",
    "attributes": {
      "email": "ada@example.net",
      "password": "secret"
    }
  }
}
```

**Response** — `200 OK`

```json
{
  "jsonapi": { "version": "1.0" },
  "links": { "self": "http://localhost:8080/api/v1/auth/me" },
  "data": {
    "type": "users",
    "id": "1",
    "attributes": {
      "name": "Ada Lovelace",
      "email": "ada@example.net",
      "permissions": ["users.read", "gallery.photos.manage"],
      "roles": ["admin"]
    }
  }
}
```

The session cookie is set on the response; subsequent requests reuse it.

### Current user

**Request**

```http
GET /auth/me
Accept: application/vnd.api+json
```

**Response** — same `users` resource shape as the login response.

### Log out

**Request**

```http
POST /auth/logout
Accept: application/vnd.api+json
```

**Response** — `204 No Content` with no body.

---

## Gallery

Gallery photos are JSON:API `photos` resources. Listing and retrieval are
public; create/update/delete require authentication.

### List photos

**Request**

```http
GET /photos?include=comments&page[size]=20&page[number]=1&sort=-created_at
Accept: application/vnd.api+json
```

**Response** — `200 OK`

```json
{
  "jsonapi": { "version": "1.0" },
  "links": {
    "self": "http://localhost:8080/api/v1/photos?page%5Bsize%5D=20&page%5Bnumber%5D=1",
    "first": "http://localhost:8080/api/v1/photos?page%5Bnumber%5D=1",
    "next": "http://localhost:8080/api/v1/photos?page%5Bnumber%5D=2"
  },
  "meta": { "total": 34, "page": 1, "per_page": 20 },
  "data": [
    {
      "type": "photos",
      "id": "1",
      "attributes": {
        "title": "Sunset",
        "filename": "gallery/sunset.jpg",
        "thumbnail_filename": "gallery/thumbnails/sunset_thumb.jpg"
      }
    }
  ]
}
```

### Get a single photo

**Request**

```http
GET /photos/1
Accept: application/vnd.api+json
```

**Response** — `200 OK`

```json
{
  "jsonapi": { "version": "1.0" },
  "links": { "self": "http://localhost:8080/api/v1/photos/1" },
  "data": {
    "type": "photos",
    "id": "1",
    "attributes": {
      "title": "Sunset",
      "filename": "gallery/sunset.jpg",
      "thumbnail_filename": "gallery/thumbnails/sunset_thumb.jpg"
    }
  }
}
```

### Upload a photo

File uploads are multipart; the `image` part is the binary and `title` a JSON:API
attribute of the resource to create. File handling is delegated to the Files
extension (`gallery/sunset.jpg` is stored there).

**Request**

```http
POST /photos/upload
Accept: application/vnd.api+json
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary

------WebKitFormBoundary
Content-Disposition: form-data; name="title"

Sunset
------WebKitFormBoundary
Content-Disposition: form-data; name="image"; filename="sunset.jpg"
Content-Type: image/jpeg

<binary data>
------WebKitFormBoundary--
```

Example using cURL:

```bash
curl -X POST http://localhost:8080/api/v1/photos/upload \
  -H "Accept: application/vnd.api+json" \
  -H "Authorization: Bearer <session-cookie>" \
  -F "title=Sunset" \
  -F "image=@sunset.jpg"
```

**Response** — `201 Created` with a `Location` header pointing at `/photos/1`.

```json
{
  "jsonapi": { "version": "1.0" },
  "links": { "self": "http://localhost:8080/api/v1/photos/1" },
  "data": {
    "type": "photos",
    "id": "1",
    "attributes": {
      "title": "Sunset",
      "filename": "gallery/sunset.jpg",
      "thumbnail_filename": "gallery/thumbnails/sunset_thumb.jpg"
    }
  }
}
```

### Update a photo

**Request**

```http
PUT /photos/1
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "photos",
    "id": "1",
    "attributes": { "title": "Beautiful Sunset" }
  }
}
```

**Response** — `200 OK`, the updated resource.

### Delete a photo

**Request**

```http
DELETE /photos/1
Accept: application/vnd.api+json
```

**Response** — `204 No Content` with no body. The referenced file is deleted on
disk via the Files extension.

---

## Users

### View or update the current profile

**Request**

```http
PUT /profile
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "users",
    "id": "1",
    "attributes": {
      "name": "Ada Lovelace",
      "bio": "Mathematician and analyst.",
      "timezone": "Europe/Paris"
    }
  }
}
```

**Response** — `200 OK`, the updated `users` resource.

---

## Tuleap

The Tuleap extension proxies a team's Tuleap instance. Responses are JSON:API
documents whose `type` is namespaced per domain (for example
`tuleap-projects`). They require authentication and a configured Tuleap token.

### Ping

**Request**

```http
GET /tuleap/ping
Accept: application/vnd.api+json
```

**Response** — `200 OK`

```json
{
  "jsonapi": { "version": "1.0" },
  "links": { "self": "http://localhost:8080/api/v1/tuleap/ping" },
  "data": {
    "type": "tuleap-ping-snapshots",
    "id": "current",
    "attributes": { "available": true, "version": "14.0" }
  }
}
```

### List projects

**Request**

```http
GET /tuleap/projects?range=6m
Accept: application/vnd.api+json
```

**Response** — `200 OK`

```json
{
  "jsonapi": { "version": "1.0" },
  "meta": { "total": 2 },
  "data": [
    {
      "type": "tuleap-projects",
      "id": "42",
      "attributes": { "id": 42, "label": "Pixely Platform" }
    }
  ]
}
```

### Get a milestone burndown

**Request**

```http
GET /tuleap/milestones/1001/burndown
Accept: application/vnd.api+json
```

**Response** — `200 OK`

```json
{
  "jsonapi": { "version": "1.0" },
  "data": {
    "type": "tuleap-milestone-burndowns",
    "id": "1001",
    "attributes": { "dates": ["2026-09-01", "2026-09-08"], "points": [42, 30] }
  }
}
```

> Cache: Tuleap responses are cached. `GET /tuleap/cache-info` lists cache
> entries with their TTL; `DELETE /tuleap/cache` (optionally with a key) clears
> them.

---

## System Tooling

### Run an ad-hoc SQL query

**Request**

```http
POST /sql-queries
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "sql-queries",
    "attributes": { "sql": "SELECT COUNT(*) AS total FROM photos" }
  }
}
```

**Response** — `200 OK`

```json
{
  "jsonapi": { "version": "1.0" },
  "data": {
    "type": "sql-queries",
    "id": "0",
    "attributes": { "total": 34 }
  }
}
```

### Update extension configuration

**Request**

```http
PUT /extension-configurations
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "extension-configurations",
    "id": "",
    "attributes": { "values": ["tuleap", "gallery"] }
  }
}
```

---

## Error responses

Errors are returned as a JSON:API `errors` array.

### 401 Unauthorized

**Response**

```json
{
  "jsonapi": { "version": "1.0" },
  "errors": [
    {
      "status": "401",
      "code": "INVALID_CREDENTIALS",
      "title": "Unauthorized",
      "detail": "The provided credentials are incorrect."
    }
  ]
}
```

### 403 Forbidden

**Response**

```json
{
  "jsonapi": { "version": "1.0" },
  "errors": [
    {
      "status": "403",
      "code": "ACCOUNT_DISABLED",
      "title": "Forbidden",
      "detail": "This account has been disabled."
    }
  ]
}
```

### 404 Not Found

**Response**

```json
{
  "jsonapi": { "version": "1.0" },
  "errors": [
    {
      "status": "404",
      "code": "RESOURCE_NOT_FOUND",
      "title": "Not Found",
      "detail": "No photo exists with the given identifier.",
      "source": { "pointer": "/data" }
    }
  ]
}
```

### 422 Unprocessable Content

**Request**

```http
POST /photos/upload
Accept: application/vnd.api+json
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary

------WebKitFormBoundary
Content-Disposition: form-data; name="title"

Sunset
------WebKitFormBoundary--
```

Without the required `image` part:

**Response** — `422 Unprocessable Content`

```json
{
  "jsonapi": { "version": "1.0" },
  "errors": [
    {
      "status": "422",
      "code": "VALIDATION_ERROR",
      "title": "Validation Error",
      "detail": "The image field is required.",
      "source": { "pointer": "/data/attributes/image" }
    }
  ]
}
```

---

## API conventions

The Pixely Platform API follows these conventions across every extension.

| Convention          | Description                              |
| ------------------- | ---------------------------------------- |
| Base path           | `/api/v1`                                |
| Media type          | `application/vnd.api+json`              |
| Upload media type   | `multipart/form-data`                   |
| Request envelope    | `data` (`type`, `id`, `attributes`)     |
| Success envelope    | `jsonapi`, `data`, `meta`, `links`      |
| Error envelope      | `jsonapi`, `errors`                     |
| Pagination          | `page[number]`, `page[size]`            |
| Sorting             | `sort=field`, `-field` for descending   |
| Filtering           | `filter[field]=value`                   |
| Successful create   | HTTP `201` + `Location` header          |
| Successful delete   | HTTP `204`                              |
| Auth                | Sanctum session cookie                  |
