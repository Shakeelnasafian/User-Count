# Codex Agent Plan: User-Count WordPress Plugin Refactor

## Goal

Refactor this beginner WordPress plugin into a production-quality plugin that:
- Uses the WordPress `WP_List_Table` class for the admin table view
- Follows WordPress coding standards and best practices
- Is secure (nonces, sanitization, escaping, capability checks)
- Is clean and DRY (no duplicate code)

---

## Context

### What the plugin does
Displays a table in the WordPress admin listing all **Editor-role users** with their post counts:
- Total Posts (published + scheduled)
- Published post count
- Scheduled (future) post count
- Optional date-range filter via a form

### Current file structure
```
user-count/
├── user_count.php              # Main plugin file — CountUsers class
├── index.php                   # Security silence file (keep as-is)
├── templates/admin.php         # Active admin page template (replace)
├── templates/date_search.php   # Unused, delete it
├── assets/myscript.js          # Empty, remove enqueue or delete
├── assets/mystyle.css          # Keep, but rename/update handles
└── LICENSE                     # Keep as-is
```

---

## Tasks

### Task 1 — Rename and clean up assets

1. Rename `assets/mystyle.css` → `assets/user-count.css` (keep its contents, they are fine)
2. Delete `assets/myscript.js` (empty, unused)
3. Delete `templates/date_search.php` (unused, has a bug)

---

### Task 2 — Create `includes/class-user-count-list-table.php`

Create a new file `includes/class-user-count-list-table.php`.

This file defines `User_Count_List_Table` extending `WP_List_Table`.

Requirements:
- Load `WP_List_Table` via `if ( ! class_exists( 'WP_List_Table' ) ) { require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php'; }`
- **Columns**: `name`, `total`, `publish`, `future`
- **`get_columns()`** returns:
  ```php
  [
      'name'    => __( 'Name', 'user-count' ),
      'total'   => __( 'Total Posts', 'user-count' ),
      'publish' => __( 'Published', 'user-count' ),
      'future'  => __( 'Scheduled', 'user-count' ),
  ]
  ```
- **`get_sortable_columns()`** returns `name` and `total` as sortable
- **`prepare_items( $from_date = '', $to_date = '' )`**:
  - Calls `get_users( [ 'role' => 'editor', 'orderby' => 'display_name', 'order' => 'ASC' ] )`
  - For each user, runs two `get_posts()` calls (publish + future), optionally with `date_query` when `$from_date` and `$to_date` are non-empty valid dates
  - Builds `$this->items` as an array of associative arrays: `[ 'id', 'name', 'total', 'publish', 'future' ]`
  - Sets up pagination: `$this->set_pagination_args()`; use 20 items per page
- **`column_name( $item )`** — outputs a link to `edit.php?post_type=post&author={id}` using `esc_url()` and `esc_html()`
- **`column_default( $item, $column_name )`** — returns `esc_html( $item[ $column_name ] )`
- **`column_total( $item )`** — returns `esc_html( $item['total'] )`

---

### Task 3 — Rewrite `templates/admin.php`

Replace the entire file with a clean template:

Requirements:
- Add a capability check at the top: `if ( ! current_user_can( 'manage_options' ) ) { wp_die( __( 'Insufficient permissions.', 'user-count' ) ); }`
- Render the page title using `<h1 class="wp-heading-inline">` with `esc_html_e()`
- Render a date-range search form:
  - Use `method="get"` (not POST — keeps state in URL, easier to share/reload)
  - Include a nonce field: `wp_nonce_field( 'user_count_search', 'user_count_nonce' )`
  - Include a hidden `page` field with value `editor_counter`
  - Two `<input type="date">` fields: `from_date` and `to_date`, values populated from `$_GET` (sanitized via `sanitize_text_field()` and validated as dates)
  - A submit button using `<input type="submit">`
- Validate and sanitize the date inputs before passing to the table:
  - Check nonce only when the form is submitted (when `from_date` or `to_date` is set)
  - Validate dates using `DateTime::createFromFormat('Y-m-d', $value)` — set to empty string if invalid
- Instantiate `User_Count_List_Table`, call `prepare_items()`, call `display()`
- Wrap everything in `<div class="wrap">`

---

### Task 4 — Rewrite `user_count.php`

Rewrite the main plugin file with these requirements:

**Plugin header** (update these fields):
```
Plugin Name: User Count
Plugin URI:
Description: Displays a post count summary for all Editor-role users in the WordPress admin.
Version: 2.0.0
Author: Pixako
Author URI:
License: GPLv2 or later
Text Domain: user-count
Domain Path: /languages
```

**Security check** (keep):
```php
defined( 'ABSPATH' ) || exit;
```

**Define a plugin version constant**:
```php
define( 'USER_COUNT_VERSION', '2.0.0' );
```

**Class: `User_Count_Plugin`** (rename from `CountUsers`):

- `__construct()` — calls `$this->init()`
- `init()` — registers hooks:
  - `add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] )`
  - `add_action( 'admin_menu', [ $this, 'register_admin_menu' ] )`
- `enqueue_assets( $hook )`:
  - Only enqueue on the plugin's own page: check `$hook === 'toplevel_page_editor_counter'`
  - `wp_enqueue_style( 'user-count-style', plugin_dir_url( __FILE__ ) . 'assets/user-count.css', [], USER_COUNT_VERSION )`
  - Do NOT enqueue myscript.js (it is deleted)
- `register_admin_menu()`:
  - `add_menu_page( __( 'User Count', 'user-count' ), __( 'User Count', 'user-count' ), 'manage_options', 'editor_counter', [ $this, 'render_admin_page' ], 'dashicons-admin-users', 110 )`
- `render_admin_page()`:
  - `require_once plugin_dir_path( __FILE__ ) . 'includes/class-user-count-list-table.php';`
  - `require_once plugin_dir_path( __FILE__ ) . 'templates/admin.php';`

**Instantiation** — use the recommended WP pattern:
```php
function user_count_plugin_init() {
    new User_Count_Plugin();
}
add_action( 'plugins_loaded', 'user_count_plugin_init' );
```

Do NOT instantiate the class directly at the file level.

---

### Task 5 — Create `index.php` in `includes/`

Create `includes/index.php` with contents:
```php
<?php
// Silence is golden.
```

This prevents directory listing.

---

### Task 6 — Update `assets/user-count.css`

Keep existing styles. Add or update:
- Replace the `.user_count` selector with `.wp-list-table` scoped styles if needed, OR keep `.user_count` as a wrapping `div` class on the `<div class="wrap">` — do not break the existing visual
- Since `WP_List_Table` renders with its own `.wp-list-table` class and built-in WordPress admin styles, the custom CSS is now mostly optional. Keep the file but remove any rules that conflict with WP admin styles (e.g., remove the `font-family` override so it inherits WP admin fonts)

---

## Acceptance Criteria

After all tasks are complete:

- [ ] Plugin activates without PHP errors or warnings
- [ ] Admin menu item "User Count" appears under the WP admin menu
- [ ] Page displays a `WP_List_Table` with columns: Name, Total Posts, Published, Scheduled
- [ ] Each user name is a clickable link to their posts in the edit screen
- [ ] Date range form filters results correctly
- [ ] Nonce is verified when the search form is submitted
- [ ] All `$_GET` inputs are sanitized and validated before use
- [ ] All output uses appropriate escaping (`esc_html`, `esc_url`, `esc_attr`)
- [ ] Assets only load on the plugin's own admin page
- [ ] No PHP notices or deprecated warnings
- [ ] `date_search.php` and `myscript.js` are deleted
- [ ] License header in `user_count.php` says GPLv2 (the LICENSE file may remain Apache for the repo, the plugin header is what WordPress reads)

---

## File Structure After Refactor

```
user-count/
├── user_count.php                          # Rewritten main file
├── index.php                               # Silence is golden
├── includes/
│   ├── index.php                           # NEW — silence is golden
│   └── class-user-count-list-table.php    # NEW — WP_List_Table subclass
├── templates/
│   ├── index.php                           # Keep existing
│   └── admin.php                           # Rewritten
├── assets/
│   └── user-count.css                      # Renamed from mystyle.css
└── LICENSE
```

---

## Notes for Codex

- Do not use `get_posts()` with `numberposts => -1` without a `fields` limiter — use `'fields' => 'ids'` when you only need a count, then use `count()` on the result. This avoids loading full post objects.
- WordPress coding standards use `snake_case` for variables and functions, `PascalCase` for class names.
- All translatable strings must use the `user-count` text domain.
- Do not add features beyond what is listed here.
