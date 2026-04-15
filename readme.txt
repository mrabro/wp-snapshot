=== WP Snapshot - Site Audit & Handoff Report ===
Contributors: mrabro
Tags: audit, site-health, handoff, report, snapshot
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate a complete site audit report — plugins, themes, server info, database, cron, security & more. Export as PDF or share via temporary link.

== Description ==

WP Snapshot generates a comprehensive "snapshot" of your WordPress site's current state. Perfect for:

* **Agency handoffs** — Give clients a professional summary of what's installed and configured
* **Site audits** — Quickly assess any WordPress site's health, security, and performance
* **Developer onboarding** — New developer inheriting a site? Give them the full picture in one click
* **Pre-upgrade documentation** — Record the site state before major updates

**What's included in a snapshot:**

* WordPress & PHP environment details
* Complete plugin inventory (active, inactive, updates available, auto-update status)
* Theme information (active theme, child/parent detection, block theme detection)
* Database statistics (table sizes, autoloaded data, revisions, orphaned data)
* Scheduled cron jobs (with overdue detection)
* Registered custom post types & taxonomies (with counts)
* User roles & user counts
* REST API endpoint summary by namespace
* Security posture check (11 key indicators with traffic-light status)
* Media library statistics (MIME type breakdown, upload directory size)
* Performance indicators (object cache, OPcache, page cache, image editor)

**Export & share:**

* Print / save as PDF (browser print dialog — no server-side library required)
* Create a temporary shareable link (expires in 72 hours by default; configurable up to 30 days)
* Export raw JSON data for programmatic use
* Sensitive data (database credentials, filesystem paths) is automatically redacted from share links

**Snapshot data is stored as a JSON file** in `wp-content/uploads/wp-snapshot/` — not in the database — avoiding `wp_options` overflow on large sites.

== Installation ==

1. Upload the `wp-snapshot` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Tools → WP Snapshot**
4. Click **Generate Snapshot**

== Frequently Asked Questions ==

= Does this plugin slow down my site? =

No. WP Snapshot only collects data when you explicitly click "Generate Snapshot". It adds zero frontend overhead.

= Is the shared link secure? =

Share links use 256-bit cryptographically random tokens (via `random_bytes(32)`) and expire automatically. Sensitive data (database host, database name, absolute file paths) is automatically redacted from shared snapshots. Tokens are validated using `hash_equals()` to prevent timing attacks.

= Where is snapshot data stored? =

Snapshots are stored as JSON files in `wp-content/uploads/wp-snapshot/`. The directory is protected by an `index.php` drop-in. On uninstall, all files are removed.

= Can I extend it with custom data collectors? =

Yes. Use the `wps_collectors` filter to add your own collector classes that implement `WPSnapshot\Collector_Interface`.

= Can I modify the snapshot data before it is saved? =

Yes. Use the `wps_snapshot_data` filter.

= What hooks does the plugin expose? =

See the **Hooks Reference** section in the plugin's source code. Key hooks:

* `wps_collectors` (filter) — add/remove/reorder data collectors
* `wps_snapshot_data` (filter) — modify snapshot data before storage
* `wps_before_generate` (action) — fires before generation starts
* `wps_after_generate` (action) — fires after generation, receives full snapshot
* `wps_share_created` (action) — fires when a share link is created
* `wps_share_accessed` (action) — fires when a share link is accessed

== Screenshots ==

1. Admin dashboard with tabbed snapshot view
2. Security tab with traffic-light indicators
3. Plugins tab with sortable table
4. Public share page

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade steps required.
