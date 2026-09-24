=== Store Maintenance Checklist for WooCommerce ===
Contributors: storecrafted
Tags: woocommerce, checklist, maintenance, health, store
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Find and fix WooCommerce operational problems with a prioritized, evidence-backed maintenance checklist.

== Description ==

Store Maintenance Checklist for WooCommerce helps merchants find and fix catalog, checkout, email, order, and environment issues before customers do.

Busy store owners rarely have time to walk every WooCommerce screen after an update, a plugin change, or a busy sales week. Small gaps — a gateway left in test mode, a checkout page that lost its shortcode, downloadable products with no files, or customer emails turned off — quietly cost orders and support time. This plugin turns that scattered checklist into one prioritized scan you can re-run whenever you need confidence that the shop is still operational.

It runs a hybrid scan (fast sync checks plus Action Scheduler catalog batches), scores open findings, and gives every issue a free WooCommerce or WordPress fix path. Optional StoreCrafted product links may appear after the free steps on matching findings — never as Critical “plugin missing” alerts.

**This is not a maintenance-mode or coming-soon plugin.** It does not close your shop or hide the storefront. It is a recurring ops checklist you re-run after updates or on a monthly cadence.

= Why store owners use it =

* Catch checkout and payment blockers before shoppers hit them
* Keep the catalog sellable (prices, downloads, stock, required pages)
* Spot email and fulfilment gaps that drive “where is my order?” tickets
* See queue, HTTPS, memory, and update debt that slow admin and risk downtime
* Work from evidence and deep links, not vague health scores
* Stay private: scans run on your site with no remote scan service or telemetry

= What the scan checks (50 checks) =

**Catalog** — Missing or invalid prices, incomplete variation prices, missing images or descriptions, downloadable products without files, out-of-stock listings, missing or duplicate SKUs, virtual products that still require shipping, required WooCommerce pages, unpublished privacy/refunds pages, checkout page signals, and expired coupons still published.

**Payments** — No enabled gateways, gateways left in test/sandbox mode (when detectable), and taxes enabled with no tax rates configured.

**Shipping** — No methods configured, empty default or regional zones, and missing delivery-time messaging for physical shipping.

**Email** — Weak From addresses, disabled customer or admin order emails, and missing new-order recipients.

**Orders & jobs** — Orders stuck in pending, on-hold, or failed; failed or overdue Action Scheduler jobs; HPOS compatibility mode still on; and large volumes of aged completed or cancelled orders.

**Environment** — HTTPS off on production, debug display on production, plain permalinks, incomplete store address, fatal error logs, PHP/WordPress/database or memory below WooCommerce recommendations, available updates, Coming soon mode, unset currency/timezone, plus soft nudges to Site Health and WooCommerce Status.

= What you get =

* Prioritized findings (critical / warning / info) with plain-language explanations
* Evidence counts and sample admin deep-links where useful
* Ignore with reason, history markers, and CSV export of open/ignored findings
* Settings for force-production severity, further-tools visibility, and catalog scan bounds
* Scans that stay on your site — no remote scan service, no usage telemetry, no account required

The score is informational only (not a security, PCI, or legal certification). Use Critical findings first, then Warnings, then Info.

= Requirements =

* WordPress 7.0+
* PHP 8.1+
* WooCommerce 10.0+

Author: [StoreCrafted](https://storecrafted.com)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/store-maintenance-checklist`, or install via Plugins → Add New.
2. Activate the plugin through the Plugins screen (WooCommerce must be active).
3. Go to **WooCommerce → Maintenance Checklist**.
4. Run a scan and work through Critical findings first.

== Frequently Asked Questions ==

= Is this a maintenance mode plugin? =

No. It does not put your store in maintenance mode, show a coming-soon page, or close checkout. It is a read-only operations checklist for WooCommerce.

= Do I need a StoreCrafted account or paid plugin? =

No. Every finding includes a free/core remediation path in WooCommerce or WordPress. You never need to buy anything to clear a Critical finding.

= What are “further tools” links? =

Optional links to related StoreCrafted products that may help after you have used the free fix. They are clearly labeled, can be turned off in Settings, and never raise severity because a product is missing.

= Does this phone home or collect data? =

No. Scans run on your WordPress site. There is no remote scan service and no usage telemetry.

= Will a scan slow down my store? =

Sync checks run quickly in the request. Catalog product checks use Action Scheduler in small batches so large catalogs do not block the admin.

= Can I ignore findings I do not care about? =

Yes. Ignore a finding with an optional reason. Ignored checks are excluded from the score and appear in History / CSV as ignored.

== Screenshots ==

1. Checklist overview with score, severity filters, and prioritized findings.
2. Finding detail with evidence, primary free action, and optional further tools.
3. Settings for production severity, further tools, and catalog scan bounds.
4. About page clarifying that this is not a maintenance-mode plugin.

== Changelog ==

= 1.0.0 =
* First public release for WordPress.org.

== Upgrade Notice ==

= 1.0.0 =
First public release. Run a full scan after updating.
