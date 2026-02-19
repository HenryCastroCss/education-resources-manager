# Database Documentation

## Table: `{prefix}erm_resource_meta`

Created on plugin activation via `dbDelta()` in `Activator::create_tables()`.

---

## Column Reference

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | `BIGINT UNSIGNED` | No | AUTO_INCREMENT | Surrogate primary key |
| `post_id` | `BIGINT UNSIGNED` | No | — | FK → `wp_posts.ID` (enforced in application code) |
| `resource_url` | `VARCHAR(2083)` | No | `''` | URL of the resource asset |
| `resource_type` | `VARCHAR(50)` | No | `''` | Controlled vocabulary — see below |
| `difficulty_level` | `VARCHAR(20)` | No | `'beginner'` | Controlled vocabulary — see below |
| `duration_minutes` | `INT UNSIGNED` | No | `0` | Estimated consumption time; 0 = unknown |
| `download_count` | `BIGINT UNSIGNED` | No | `0` | Monotonically increasing counter |
| `is_featured` | `TINYINT(1)` | No | `0` | Boolean: 1 = featured |
| `meta_json` | `LONGTEXT` | Yes | NULL | Optional JSON bag for future attributes |
| `created_at` | `DATETIME` | No | `CURRENT_TIMESTAMP` | Row creation timestamp |
| `updated_at` | `DATETIME` | No | `CURRENT_TIMESTAMP ON UPDATE …` | Last modified timestamp |

---

## Indices

| Name | Type | Columns | Purpose |
|------|------|---------|---------|
| `PRIMARY KEY` | UNIQUE | `id` | Fast row lookup by surrogate key |
| `uq_post_id` | UNIQUE | `post_id` | One meta row per WordPress post |
| `idx_resource_type` | INDEX | `resource_type` | Filter queries by type |
| `idx_difficulty` | INDEX | `difficulty_level` | Filter queries by difficulty |
| `idx_is_featured` | INDEX | `is_featured` | Quick featured resource queries |

---

## Controlled Vocabularies

### `resource_type`
| Value | Label |
|-------|-------|
| `article` | Article |
| `video` | Video |
| `podcast` | Podcast |
| `pdf` | PDF / Document |
| `course` | Online Course |
| `book` | Book |
| `infographic` | Infographic |
| `tool` | Tool / Software |
| `other` | Other |

### `difficulty_level`
| Value | Label |
|-------|-------|
| `beginner` | Beginner |
| `intermediate` | Intermediate |
| `advanced` | Advanced |

---

## Query Patterns

### Fetch meta for a post
```sql
SELECT *
FROM wp_erm_resource_meta
WHERE post_id = %d
LIMIT 1;
```

### Upsert meta
The application uses `$wpdb->insert()` for new rows and `$wpdb->update()` for existing ones, determined by a prior SELECT.

### Increment download counter (atomic)
```sql
UPDATE wp_erm_resource_meta
SET download_count = download_count + 1
WHERE post_id = %d;
```

### Paginated filtered query
```sql
SELECT *
FROM wp_erm_resource_meta
WHERE resource_type = %s
  AND difficulty_level = %s
ORDER BY created_at DESC
LIMIT %d OFFSET %d;
```

---

## Data Lifecycle

1. **Creation** — A row is inserted when an `erm_resource` post is saved for the first time via the meta box.
2. **Update** — Subsequent saves update the existing row.
3. **Deletion** — When an `erm_resource` post is permanently deleted from WordPress, `Admin::cleanup_on_delete()` removes the corresponding row via `Database::delete_resource_meta()`.
4. **Deactivation** — The table and data are preserved on deactivation. Only uninstall should remove data.

---

## Character Set

The table uses `utf8mb4` with `utf8mb4_unicode_520_ci` collation (matches WordPress defaults set via `$wpdb->get_charset_collate()`).

---

## Backup Considerations

This table stores supplemental metadata that mirrors information entered through the WordPress admin. It should be included in any full-database backup. The table can be safely recreated from the schema in `database/schema.sql` if lost, but `download_count` values would be reset to 0.
