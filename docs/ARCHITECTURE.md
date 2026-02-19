# Architecture Overview

## Plugin: Education Resources Manager (ERM)

**Namespace:** `GlobalAuthenticity\EducationManager`
**Minimum WordPress:** 6.0
**Minimum PHP:** 8.0

---

## Directory Structure

```
education-resources-manager/
├── education-resources-manager.php   # Entry point, constants, autoloader, Plugin class
├── admin/
│   ├── css/admin-styles.css          # Admin-only stylesheet
│   ├── js/admin-scripts.js           # Admin-only JavaScript
│   └── views/
│       └── admin-page.php            # Settings page template
├── database/
│   └── schema.sql                    # Schema reference (not executed directly)
├── docs/
│   ├── ARCHITECTURE.md               # This file
│   ├── DATABASE.md                   # Database schema documentation
│   └── API.md                        # REST API documentation
├── includes/
│   ├── class-erm-activator.php       # Plugin activation handler
│   ├── class-erm-admin.php           # Admin menus, assets, AJAX
│   ├── class-erm-database.php        # Database abstraction layer
│   ├── class-erm-deactivator.php     # Plugin deactivation handler
│   ├── class-erm-post-type.php       # Custom post type + meta boxes
│   ├── class-erm-rest-api.php        # REST API route registration
│   ├── class-erm-shortcode.php       # [education_resources] shortcode
│   └── class-erm-taxonomy.php        # Custom taxonomies
└── public/
    ├── css/public-styles.css         # Front-end stylesheet
    └── js/public-scripts.js          # Front-end JavaScript
```

---

## Class Responsibilities

### `Plugin` (main file)
The singleton entry point. Registers activation/deactivation hooks, bootstraps the autoloader, and wires all sub-components together via WordPress action hooks.

### `Activator`
Runs once on plugin activation:
- Creates the `{prefix}erm_resource_meta` table via `dbDelta()`.
- Sets default options.
- Registers post types/taxonomies temporarily to flush rewrite rules.

### `Deactivator`
Runs on plugin deactivation:
- Flushes rewrite rules.
- Clears any scheduled cron events.
- **Does not** delete data (that is reserved for an `uninstall.php` file).

### `Post_Type`
Registers the `erm_resource` custom post type and its meta box. Handles saving meta box fields with nonce verification, capability checks, and sanitization.

### `Taxonomy`
Registers two taxonomies against `erm_resource`:
- `erm_category` — hierarchical (like categories).
- `erm_tag` — flat (like tags).

### `Database`
All direct SQL interactions. Uses `$wpdb->prepare()` for every query with variable input. Exposes:
- `get_resource_meta( int $post_id )`
- `upsert_resource_meta( int $post_id, array $data )`
- `delete_resource_meta( int $post_id )`
- `increment_download_count( int $post_id )`
- `get_resources( array $args )`
- `count_resources( array $args )`

### `Admin`
- Adds a Settings submenu under the post type menu.
- Enqueues admin CSS/JS only on relevant screens.
- Handles AJAX actions (`erm_save_settings`, `erm_get_stats`).
- Adds a "Settings" shortcut to the plugins list page.
- Cleans up database rows when an `erm_resource` post is permanently deleted.

### `Rest_Api`
Registers routes under the `erm/v1` namespace:

| Method | Endpoint | Auth |
|--------|----------|------|
| GET | `/erm/v1/resources` | Public |
| GET | `/erm/v1/resources/:id` | Public |
| POST | `/erm/v1/resources/:id/download` | Public |
| GET | `/erm/v1/stats` | `manage_options` |

### `Shortcode`
Registers `[education_resources]` and renders a CSS grid of resource cards using a WP_Query + Database row. Assets are enqueued lazily — only when the shortcode is actually used on the page.

---

## Data Flow

```
User visits page with [education_resources]
         │
         ▼
   Shortcode::render()
         │  WP_Query (post type + taxonomy filters)
         ▼
   Database::get_resource_meta()   ← SELECT from erm_resource_meta
         │
         ▼
   Shortcode::render_resource_card()
         │
         ▼
   HTML output (escaped with esc_html / esc_url / esc_attr)
```

```
Admin saves meta box
         │
         ▼
   Post_Type::save_meta_box()
         │  wp_verify_nonce + capability check + sanitize
         ▼
   Database::upsert_resource_meta()  ← INSERT or UPDATE
```

---

## Security Model

| Concern | Approach |
|---------|----------|
| SQL injection | `$wpdb->prepare()` + `$wpdb->insert()` / `$wpdb->update()` for all queries |
| XSS | `esc_html()`, `esc_attr()`, `esc_url()` on all output |
| CSRF (admin forms) | `wp_nonce_field()` + `wp_verify_nonce()` |
| CSRF (AJAX) | `check_ajax_referer()` |
| Capability checks | `current_user_can()` before all write operations |
| Direct file access | `if ( ! defined( 'ABSPATH' ) ) { exit; }` in every file |

---

## Extending the Plugin

### Adding a new resource type
1. Add the slug ⟶ label pair to `Post_Type::get_resource_types()`.
2. Add a CSS rule for `.erm-card__type--{slug}` in `public/css/public-styles.css`.

### Adding a new REST endpoint
1. Call `register_rest_route()` inside `Rest_Api::register_routes()`.
2. Follow the existing pattern: define args, permission callback, and a handler method.

### Adding a new option
1. Add a default in `Activator::set_default_options()`.
2. Add the field to `admin/views/admin-page.php`.
3. Sanitize and save in `Admin::ajax_save_settings()`.
