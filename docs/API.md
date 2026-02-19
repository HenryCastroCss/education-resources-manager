# REST API Documentation

## Base URL

```
{site_url}/wp-json/erm/v1/
```

The API can be disabled via the plugin settings page. When disabled, all endpoints return a 404.

---

## Authentication

Public endpoints require no authentication.

Protected endpoints (marked **Admin**) require a WordPress user with the `manage_options` capability. Send authentication via:
- Cookie + nonce header (`X-WP-Nonce: {nonce}`) — for same-origin requests.
- Application Passwords — for external clients (WordPress 5.6+).

---

## Endpoints

### `GET /erm/v1/resources`

Returns a paginated list of published resources.

**Auth:** Public

**Query Parameters**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | `1` | Page number |
| `per_page` | integer | `12` | Items per page (max 100) |
| `resource_type` | string | — | Filter by type slug (e.g. `video`) |
| `difficulty_level` | string | — | Filter by difficulty (`beginner`, `intermediate`, `advanced`) |
| `category` | string | — | Filter by category slug |
| `featured` | boolean | — | Set `true` to return only featured resources |
| `orderby` | string | `created_at` | Sort field: `created_at`, `download_count`, `duration_minutes`, `id` |
| `order` | string | `DESC` | Sort direction: `ASC` or `DESC` |

**Response Headers**

| Header | Description |
|--------|-------------|
| `X-WP-Total` | Total number of matching resources |
| `X-WP-TotalPages` | Total number of pages |

**Response Body** — `200 OK`

```json
[
  {
    "id": 42,
    "title": "Introduction to Mindful Communication",
    "excerpt": "A beginner-friendly guide to...",
    "permalink": "https://example.com/education-resources/intro-mindful-communication/",
    "thumbnail": "https://example.com/wp-content/uploads/2024/01/cover.jpg",
    "date": "2024-01-15 10:30:00",
    "modified": "2024-03-20 14:22:00",
    "resource_url": "https://example.com/resources/mindful-comm.pdf",
    "resource_type": "pdf",
    "difficulty_level": "beginner",
    "duration_minutes": 30,
    "download_count": 247,
    "is_featured": true,
    "categories": [
      { "id": 5, "name": "Communication", "slug": "communication" }
    ],
    "tags": [
      { "id": 12, "name": "Mindfulness", "slug": "mindfulness" },
      { "id": 17, "name": "Beginner", "slug": "beginner" }
    ]
  }
]
```

---

### `GET /erm/v1/resources/:id`

Returns a single published resource by post ID.

**Auth:** Public

**Path Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | WordPress post ID |

**Response Body** — `200 OK`

Same structure as a single item in the collection response above.

**Error Responses**

| Code | Reason |
|------|--------|
| `404` | Resource not found or not published |

```json
{
  "code": "erm_resource_not_found",
  "message": "Resource not found.",
  "data": { "status": 404 }
}
```

---

### `POST /erm/v1/resources/:id/download`

Records a download event for a resource, incrementing its `download_count`.

**Auth:** Public

**Path Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | WordPress post ID |

**Response Body** — `200 OK`

```json
{ "recorded": true }
```

Returns `{ "recorded": false }` when download tracking is disabled in plugin settings.

**Notes**
- This is fire-and-forget. The counter is never decremented via the API.
- There is no rate limiting built in. For high-traffic sites, consider adding a caching layer or moving to a queue.

---

### `GET /erm/v1/stats`

Returns aggregate statistics for the resource library.

**Auth:** Admin (`manage_options`)

**Response Body** — `200 OK`

```json
{
  "total_meta_records": 138,
  "published": 121,
  "draft": 17
}
```

---

## Error Format

All errors follow the standard WordPress REST API error envelope:

```json
{
  "code": "erm_error_code",
  "message": "Human-readable error description.",
  "data": { "status": 403 }
}
```

---

## Usage Examples

### Fetch the first page of video resources

```bash
curl "https://example.com/wp-json/erm/v1/resources?resource_type=video&per_page=6"
```

### Fetch featured beginner resources

```bash
curl "https://example.com/wp-json/erm/v1/resources?featured=true&difficulty_level=beginner"
```

### Record a download (JavaScript)

```javascript
fetch( wpApiSettings.root + 'erm/v1/resources/42/download', {
    method: 'POST',
    headers: { 'X-WP-Nonce': wpApiSettings.nonce },
} );
```

### Fetch stats (authenticated)

```bash
curl -u username:application_password \
     "https://example.com/wp-json/erm/v1/stats"
```

---

## Versioning

The namespace `erm/v1` is versioned. Breaking changes will be introduced under a new major version namespace (e.g. `erm/v2`) with a deprecation notice period.
