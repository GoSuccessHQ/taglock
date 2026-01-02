# TagLock Pro: Rules (Custom Table) – Design Sketch

This document sketches a Pro-oriented configuration model (“rules”) that lets users centrally manage TagLock behavior and reference it from content via a stable ID.

## Why a Custom Table (vs. CPT)

A WordPress CPT typically stores complex configuration in `postmeta` (key/value rows). That has common downsides:

- Queries across multiple fields require meta joins and are often slow at scale.
- Values are strings; non-trivial structures often end up serialized/JSON anyway.
- Indexing/searching on meta values is limited.

A custom table is usually the better fit when:

- You need fast search/filtering across many fields.
- You want strict types, explicit columns, and reliable indexes.
- You want predictable performance beyond a handful of records.

Tradeoffs of a custom table:

- You must own schema creation + migrations.
- Multisite needs careful table naming and activation handling.
- Backups/export/import aren’t “free” like posts.

For TagLock rules specifically (redirect target, teaser HTML, engagement tags, etc.), a custom table is a good fit.

## High-Level UX Goal

Instead of configuring behavior per-shortcode attribute (e.g. `tag="123"`), users create “TagLocks” (rules) in the admin UI, then use:

- `[taglock id="1"]Protected content[/taglock]`

This enables:

- Central management (change settings without editing many posts)
- Searchable post/page redirect selection
- Multi-tag selection (required tags, engagement tags)
- Better guidance for Lite users (Pro options visible but disabled)

## Data Model

### Tables

Assuming `$wpdb->prefix` is `wp_`.

#### 1) `wp_taglock_rule`

Stores rule configuration.

**Columns**

- `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `name` VARCHAR(190) NOT NULL
- `is_active` TINYINT(1) NOT NULL DEFAULT 1

**Access requirements**

- `access_mode` VARCHAR(20) NOT NULL DEFAULT 'tag_any'  
  (examples: `tag_any`, `tag_all`)

**Denied behavior**

- `deny_mode` VARCHAR(20) NOT NULL DEFAULT 'message'  
  (examples: `message`, `teaser`, `redirect`)
- `deny_message` TEXT NULL  
  (human readable fallback)
- `teaser_html` LONGTEXT NULL  
  (HTML + shortcodes, executed on output; sanitized)

**Redirect target**

- `redirect_post_id` BIGINT UNSIGNED NULL
- `redirect_post_type` VARCHAR(20) NULL  
  (`post` / `page` – kept for UI grouping)

**Pro behavior toggles**

- `admin_bypass_enabled` TINYINT(1) NOT NULL DEFAULT 0
- `engagement_tagging_enabled` TINYINT(1) NOT NULL DEFAULT 0

**Timestamps**

- `created_at` DATETIME NOT NULL
- `updated_at` DATETIME NOT NULL

**Indexes**

- `KEY is_active (is_active)`
- `KEY name (name)`
- `KEY deny_mode (deny_mode)`
- `KEY redirect_post_id (redirect_post_id)`
- `KEY updated_at (updated_at)`

Notes:
- We keep `redirect_post_type` primarily for admin grouping; the canonical reference is `redirect_post_id`.
- For searching by name, an index on `name` is enough for prefix matches; if you want contains-search at scale, consider FULLTEXT (depends on MySQL version/collation).

#### 2) `wp_taglock_rule_required_tag`

Maps a rule to **required access tags** (KlickTipp).

- `rule_id` BIGINT UNSIGNED NOT NULL
- `tag_id` BIGINT UNSIGNED NOT NULL
- `PRIMARY KEY (rule_id, tag_id)`
- `KEY tag_id (tag_id)`

#### 3) `wp_taglock_rule_engagement_tag`

Maps a rule to **engagement tags** applied after successful view.

- `rule_id` BIGINT UNSIGNED NOT NULL
- `tag_id` BIGINT UNSIGNED NOT NULL
- `PRIMARY KEY (rule_id, tag_id)`
- `KEY tag_id (tag_id)`

Why normalized tables instead of JSON:
- Efficient “find all rules using tag X”
- Easy to enforce uniqueness
- Avoids parsing JSON in SQL

## Execution Semantics (Later Pro)

### Teaser HTML

- Stored in DB as raw string.
- Output pipeline recommendation:

1. Execute shortcodes: `do_shortcode( $teaserHtml )`
2. Sanitize: `wp_kses_post( $executedHtml )`

This makes it shortcode-capable while keeping output safe.

### Redirect Selection

- Store `redirect_post_id` per rule.
- In the frontend access check response, produce the final URL via `get_permalink( $postId )`.
- Because it’s per rule, each shortcode instance can reference a different rule (and therefore different redirect).

### Engagement Tagging

- Allow multiple engagement tags (join table).
- The available tags (id + name) come from KlickTipp API and are used for a searchable selection UI.

## REST API (Pro)

Namespace stays `taglock/v1`.

### Rules CRUD

- `GET /taglock/v1/rules?search=&page=&per_page=`
  - List rules (admin only)
  - Supports server-side search by `name`

- `GET /taglock/v1/rules/{id}`
  - Fetch one rule (admin only)

- `POST /taglock/v1/rules`
  - Create rule (admin only)

- `PUT /taglock/v1/rules/{id}`
  - Update rule (admin only)

- `DELETE /taglock/v1/rules/{id}`
  - Delete rule (admin only)

### Supporting data endpoints

- `GET /taglock/v1/klicktipp/tags?search=`
  - Returns list of tags `{ id, name }`
  - Admin only
  - Cached server-side for a short TTL to avoid repeated API calls

Notes:
- Post/page search can use core endpoints (`wp/v2/search` / `wp/v2/posts` / `wp/v2/pages`). If you need a unified result model and permission handling, add a small TagLock proxy endpoint.

## Admin UI Structure (Pro)

Single-page (current TagLock settings screen) with 2 areas:

1) **KlickTipp Connection** (already exists)
2) **TagLocks (Rules)**

### Rules list

- Table of rules with columns: `Name`, `Active`, `Denied mode`, `Updated`
- Actions: `Create`, `Edit`, `Duplicate`, `Delete`

### Rule editor

- **Required tags**: searchable multi-select
  - UI: token field or multi-combobox
  - Backed by `GET /klicktipp/tags`

- **Denied behavior**:
  - Toggle: teaser mode
  - Toggle: redirect
  - Teaser editor: code editor (WP CodeMirror) with shortcode support (later)
  - Redirect picker: combobox with search across posts/pages

- **Engagement tagging**:
  - Toggle
  - Multi-select tags

- **Admin bypass**:
  - Toggle

### Lite behavior

- In Lite: show the whole rules UI *as disabled* (like the current Pro preview toggles)
- No data is saved; no rules endpoints exposed

## Plugin Integration Points

### Shortcode

Future Pro behavior (conceptual):

- `[taglock id="<ruleId>"]...[/taglock]` resolves the rule and uses its configuration.
- Existing attribute-based usage can remain for Lite, but introducing `id` should be treated as a new configuration path.

### Access check route

- The existing `check-access` endpoint already supports `redirect_url` and `teaser_html`.
- With rules, the denied response is derived from the rule settings instead of a per-shortcode filter.

## Implementation Notes (When We Build It)

- Schema creation: `dbDelta()` on activation (and on version bump/migration path).
- Use `$wpdb->get_charset_collate()`.
- Use repository/service layer for CRUD.
- Add strict capability checks (`manage_options`) to all admin endpoints.
- Add request-local caching for KlickTipp tags.

---

This is intentionally a sketch. Next step would be to decide which part becomes the first Pro slice:

1) rule storage + list UI (still disabled in Lite)
2) tag picker endpoint + caching
3) redirect picker UX
4) teaser editor integration
