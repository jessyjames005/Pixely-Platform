# Pixely Platform API Examples

This document provides practical examples for interacting with the Pixely Platform API.

All Gallery API endpoints are currently exposed under the `/api/v1` prefix.

---

## Gallery API

### List gallery photos

Retrieve a paginated list of gallery photos.

**Request**

```http
GET /api/v1/gallery
Accept: application/json
```

**Response**

```json
{
  "data": [
    {
      "id": 1,
      "title": "Sunset",
      "filename": "gallery/sunset.jpg"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

---

### Pagination

The `per_page` parameter controls the number of photos returned per page.

The value must be between `1` and `100`.

**Request**

```http
GET /api/v1/gallery?per_page=20
Accept: application/json
```

The API limits larger values to the maximum supported page size.

---

### Filter photos by title

Gallery photos can be filtered using the API filter syntax.

**Request**

```http
GET /api/v1/gallery?filter[title]=Sunset
Accept: application/json
```

Filtering is case-insensitive.

**Example**

```http
GET /api/v1/gallery?filter[title]=sunset
Accept: application/json
```

Both requests can match a photo with the title `Sunset`.

---

### Sort photos

Photos can be sorted using the `sort` parameter.

**Ascending**

```http
GET /api/v1/gallery?sort=title
Accept: application/json
```

**Descending**

```http
GET /api/v1/gallery?sort=-title
Accept: application/json
```

A leading `-` indicates descending order.

---

## Get a gallery photo

Retrieve a single gallery photo by its identifier.

**Request**

```http
GET /api/v1/gallery/1
Accept: application/json
```

**Response**

```json
{
  "data": {
    "id": 1,
    "title": "Sunset",
    "filename": "gallery/sunset.jpg"
  }
}
```

---

## Upload a gallery photo

Upload a new image to the gallery.

The endpoint uses `multipart/form-data`.

**Request**

```http
POST /api/v1/gallery/upload
Content-Type: multipart/form-data
Accept: application/json
```

Example using cURL:

```bash
curl -X POST http://localhost/api/v1/gallery/upload \
  -H "Accept: application/json" \
  -F "title=Sunset" \
  -F "image=@sunset.jpg"
```

**Response**

```json
{
  "data": {
    "id": 1,
    "title": "Sunset",
    "filename": "gallery/sunset.jpg"
  }
}
```

The endpoint returns HTTP `201 Created` when the upload succeeds.

---

## Update a gallery photo

Update the title of an existing photo.

**Request**

```http
PUT /api/v1/gallery/1
Content-Type: application/json
Accept: application/json
```

```json
{
  "title": "Beautiful Sunset"
}
```

**Response**

```json
{
  "data": {
    "id": 1,
    "title": "Beautiful Sunset",
    "filename": "gallery/sunset.jpg"
  }
}
```

---

## Delete a gallery photo

Delete an existing gallery photo.

**Request**

```http
DELETE /api/v1/gallery/1
Accept: application/json
```

**Response**

The API returns HTTP `204 No Content`.

---

## Error responses

Pixely Platform uses a consistent error structure for API errors.

### Resource not found

When a requested resource does not exist, the API returns HTTP `404 Not Found`.

**Response**

```json
{
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "The requested resource was not found."
  }
}
```

---

### Validation error

When request data fails validation, the API returns HTTP `422 Unprocessable Content`.

**Example**

```http
POST /api/v1/gallery/upload
Accept: application/json
Content-Type: multipart/form-data
```

Without the required `image` field:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "image": [
        "The image field is required."
      ]
    }
  }
}
```

The `details` object contains validation errors grouped by field.

---

## API conventions

The Gallery API follows these conventions:

| Convention          | Description                    |
| ------------------- | ------------------------------ |
| Base path           | `/api/v1`                      |
| Format              | JSON                           |
| Pagination          | `page` / `per_page`            |
| Maximum page size   | `100`                          |
| Filtering           | `filter[...]`                  |
| Sorting             | `sort`                         |
| Descending sort     | `sort=-field`                  |
| Resource errors     | `error.code` + `error.message` |
| Validation errors   | `error.details`                |
| Successful creation | HTTP `201`                     |
| Successful deletion | HTTP `204`                     |

These conventions are intended to become the standard API conventions for future Pixely Platform extensions.
