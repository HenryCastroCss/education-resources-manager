# Education Resources Manager

A WordPress plugin for managing, displaying, and tracking educational resources. Built with an OOP architecture, custom post types, REST API, and a dynamic front-end powered by native `fetch()` — no page reloads required.

---

## Table of Contents

- [Description](#description)
- [Requirements](#requirements)
- [Installation](#installation)
- [Shortcode](#shortcode)
- [REST API](#rest-api)
- [Admin Dashboard](#admin-dashboard)
- [Screenshots](#screenshots)
- [Changelog](#changelog)

---

## Description

**Education Resources Manager** provides a complete toolkit for publishing and tracking educational content on any WordPress site:

- **Custom post type** `erm_resource` with a dedicated meta box for resource URL, type, difficulty level, and duration.
- **Two custom taxonomies** — hierarchical categories (`erm_category`) and flat tags (`erm_tag`) — both exposed in the Block Editor and REST API.
- **Custom database tables** — `wp_erm_resource_meta` for resource metadata and `wp_erm_tracking` for anonymised view/download event logs.
- **Dynamic filter UI** rendered by the `[education_resources]` shortcode: live search (debounced 300 ms), resource type, difficulty, and category selects — all driven by the REST API without page reload.
- **REST API** under the `erm/v1` namespace for headless or JavaScript-driven integrations.
- **Admin dashboard** with real-time stats (published resources, total views, total downloads), a pure-CSS bar chart of publications per month, and a top-5 most-viewed resources table.
- **GDPR-conscious tracking** — visitor IPs are anonymised before storage (last IPv4 octet / last 80 IPv6 bits zeroed).

---

## Requirements

| Requirement | Minimum version |
|---|---|
| WordPress | 6.0 |
| PHP | 7.4 (8.0+ recommended) |
| MySQL / MariaDB | 5.7 / 10.3 |

---

## Installation

### Manual

1. Download or clone this repository into your `wp-content/plugins/` directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/HenryCastroCss/education-resources-manager.git
   ```
2. Log in to your WordPress admin and go to **Plugins → Installed Plugins**.
3. Find **Education Resources Manager** and click **Activate**.
4. On activation the plugin automatically creates two database tables:
   - `wp_erm_resource_meta`
   - `wp_erm_tracking`
5. Go to **Education Manager** (top-level admin menu) to verify the dashboard and configure settings.

### Via ZIP

1. Download the repository as a `.zip` file from GitHub.
2. In WordPress admin go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and click **Install Now**, then **Activate**.

### Sample data (optional)

A SQL file with 10 sample resources in Spanish is included:

```
database/sample-data.sql
```

Replace `{prefix}` with your WordPress table prefix (default `wp_`) and run the file against your database before importing.

---

## Shortcode

Place the shortcode on any page or post to render a filterable resource grid:

```
[education_resources]
```

### Available attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `per_page` | integer | `12` (from settings) | Number of resources to display per page |
| `category` | string | *(empty)* | Pre-filter by category slug |
| `difficulty` | string | *(empty)* | Pre-filter by difficulty: `beginner`, `intermediate`, `advanced` |
| `featured` | string | *(empty)* | Set `true` to show only featured resources |
| `orderby` | string | `date` | Sort field: `date`, `title`, `modified`, `id` |
| `order` | string | `DESC` | Sort direction: `ASC` or `DESC` |

### Examples

```
[education_resources]

[education_resources per_page="6" difficulty="beginner"]

[education_resources category="liderazgo" featured="true"]

[education_resources orderby="title" order="ASC" per_page="9"]
```

### Front-end behaviour

Once rendered, the shortcode outputs:

- A **search input** (debounced 300 ms) — searches post titles and content.
- A **Type** select — filters by resource type (`course`, `tutorial`, `ebook`, `video`).
- A **Difficulty** select — filters by level.
- A **Category** select — populated from the `erm_category` taxonomy.
- A **resource grid** — cards include thumbnail, title, excerpt, type/difficulty chips, duration, and a **Ver recurso** button that opens the resource URL and records a view event.
- **Pagination** controls with smooth scroll back to the grid top.

All filtering communicates with the REST API — no full page reloads.

---

## REST API

Base URL: `{site_url}/wp-json/erm/v1/`

The API can be enabled or disabled from the plugin settings page.

### Endpoints

#### `GET /erm/v1/resources`

Returns a paginated, filterable list of published resources.

**Auth:** Public

**Query parameters**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `page` | integer | `1` | Page number |
| `per_page` | integer | `12` | Results per page (max 100) |
| `search` | string | — | Keyword search (post title and content) |
| `resource_type` | string | — | Filter by type slug |
| `difficulty_level` | string | — | Filter by difficulty |
| `category` | string | — | Filter by category slug |
| `featured` | boolean | — | `true` to return featured only |
| `orderby` | string | `date` | `date`, `title`, `modified`, `id` |
| `order` | string | `DESC` | `ASC` or `DESC` |

**Response headers**

| Header | Description |
|---|---|
| `X-WP-Total` | Total matching resources |
| `X-WP-TotalPages` | Total pages |

**Example**
```bash
curl "https://example.com/wp-json/erm/v1/resources?difficulty_level=beginner&per_page=6"
```

---

#### `GET /erm/v1/resources/:id`

Returns a single published resource by post ID.

**Auth:** Public

**Example**
```bash
curl "https://example.com/wp-json/erm/v1/resources/42"
```

---

#### `POST /erm/v1/resources/:id/download`

Increments the download counter stored in `wp_erm_resource_meta`.

**Auth:** Public

**Example**
```bash
curl -X POST "https://example.com/wp-json/erm/v1/resources/42/download" \
     -H "X-WP-Nonce: {nonce}"
```

**Response**
```json
{ "recorded": true }
```

---

#### `POST /erm/v1/resources/:id/track`

Logs a view event to `wp_erm_tracking` with anonymised IP and optional user ID.

**Auth:** Public

**Example**
```javascript
fetch(`/wp-json/erm/v1/resources/${id}/track`, {
  method: 'POST',
  headers: { 'X-WP-Nonce': wpNonce },
});
```

**Response**
```json
{ "recorded": true }
```

---

#### `GET /erm/v1/stats`

Returns aggregate resource and tracking statistics.

**Auth:** WordPress user with `manage_options` capability (Administrator)

**Response**
```json
{
  "total_meta_records": 10,
  "published": 10,
  "draft": 2
}
```

---

### Resource object schema

```json
{
  "id": 42,
  "title": "Introducción a la Comunicación No Violenta",
  "excerpt": "Aprende los fundamentos de la CNV…",
  "permalink": "https://example.com/education-resources/introduccion-cnv/",
  "thumbnail": "https://example.com/wp-content/uploads/cover.jpg",
  "date": "2025-09-01 09:00:00",
  "modified": "2025-09-01 09:00:00",
  "resource_url": "https://example.com/recursos/cnv",
  "resource_type": "course",
  "difficulty_level": "beginner",
  "duration_minutes": 90,
  "download_count": 312,
  "is_featured": true,
  "categories": [{ "id": 1, "name": "Comunicación", "slug": "comunicacion" }],
  "tags": [{ "id": 5, "name": "CNV", "slug": "cnv" }]
}
```

---

## Admin Dashboard

Navigate to **Education Manager** in the WordPress admin sidebar.

**Stats cards**
- Published Resources — total published `erm_resource` posts.
- Total Views Tracked — cumulative rows in `wp_erm_tracking` where `action_type = 'view'`.
- Total Downloads Tracked — cumulative rows where `action_type = 'download'`.

**Bar chart** — pure CSS, no external libraries. Shows the number of resources published each month for the last 6 months.

**Top 5 Most Viewed** — a table of the five resources with the highest view event count, with direct edit links.

**Settings**
- Resources per page (shortcode + REST API default).
- Default difficulty level.
- Enable / disable the REST API.
- Enable / disable download tracking.

---

## Screenshots

> _Screenshots will be added once the plugin is deployed to a staging environment._

| # | Description |
|---|---|
| 1 | Admin dashboard — stats cards, bar chart, top-5 table |
| 2 | Resource edit screen — "Resource Details" meta box |
| 3 | Front-end shortcode — filter bar and resource grid |
| 4 | Front-end shortcode — resource card with "Ver recurso" button |
| 5 | Admin settings page |

---

## Changelog

### 1.0.0
- Initial release.
- Custom post type `erm_resource` with meta box (URL, type, difficulty, duration, featured flag).
- Custom taxonomies `erm_category` and `erm_tag`.
- Custom database tables `wp_erm_resource_meta` and `wp_erm_tracking`.
- REST API under `erm/v1` with collection, single, download, track, and stats endpoints.
- `[education_resources]` shortcode with live AJAX filtering, pagination, and "Ver recurso" view tracking.
- Admin dashboard with real-time stats, CSS bar chart, and top-5 most-viewed table.
- GDPR-conscious IP anonymisation on all tracking events.
- Sample data SQL with 10 Spanish-language educational resources.
