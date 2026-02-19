-- ============================================================
-- Education Resources Manager — Database Schema
-- Table: {prefix}erm_resource_meta
--
-- NOTE: This file is for documentation and manual inspection.
-- The actual table is created via dbDelta() in class-erm-activator.php
-- on plugin activation. The {prefix} placeholder represents the
-- WordPress $wpdb->prefix value (default: wp_).
-- ============================================================

CREATE TABLE `{prefix}erm_resource_meta` (
    -- Primary key
    `id`               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Foreign key — references wp_posts.ID
    `post_id`          BIGINT(20) UNSIGNED NOT NULL,

    -- External or internal URL for the resource asset
    `resource_url`     VARCHAR(2083) NOT NULL DEFAULT '',

    -- Controlled vocabulary: article | video | podcast | pdf |
    --                        course | book | infographic | tool | other
    `resource_type`    VARCHAR(50)   NOT NULL DEFAULT '',

    -- Controlled vocabulary: beginner | intermediate | advanced
    `difficulty_level` VARCHAR(20)   NOT NULL DEFAULT 'beginner',

    -- Estimated time to consume the resource, in minutes (0 = unknown)
    `duration_minutes` INT(11) UNSIGNED NOT NULL DEFAULT 0,

    -- Cumulative number of times the download endpoint was called
    `download_count`   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,

    -- Boolean flag — 1 = featured on the site
    `is_featured`      TINYINT(1)   NOT NULL DEFAULT 0,

    -- Arbitrary extra metadata stored as a JSON string
    `meta_json`        LONGTEXT DEFAULT NULL,

    -- Audit columns managed by MySQL
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,

    -- Constraints & indices
    PRIMARY KEY  (`id`),
    UNIQUE KEY   `uq_post_id`         (`post_id`),
    KEY          `idx_resource_type`   (`resource_type`),
    KEY          `idx_difficulty`      (`difficulty_level`),
    KEY          `idx_is_featured`     (`is_featured`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- ============================================================
-- Column notes
-- ============================================================

-- post_id         Links this row to a post of type `erm_resource`.
--                 The application enforces referential integrity on
--                 delete (see Admin::cleanup_on_delete).

-- resource_url    Max length follows RFC 7230 practical limit (2083
--                 chars). Stored as VARCHAR rather than TEXT so the
--                 UNIQUE index on post_id remains efficient.

-- meta_json       Optional bag-of-properties for future extensibility.
--                 Applications should validate JSON before writing.
--                 Example: {"isbn":"978-0-06-112008-4","edition":3}

-- download_count  Incremented atomically via SQL:
--                 UPDATE ... SET download_count = download_count + 1
--                 Never decremented; reset only via direct admin action.
