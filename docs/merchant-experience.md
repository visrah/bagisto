# Merchant experience audit and implementation

## Repository evidence

The source identifies itself as `2.5.x-dev` in `packages/Webkul/Core/src/Core.php`; this is a development checkout, not a verified upstream 2.5 release. Root Composer configuration requires PHP 8.4 and Laravel 13. The packages are PSR-4 autoloaded from `packages/Webkul`, with service providers in `bootstrap/providers.php` and Concord model registration in `config/concord.php`. Data access uses repositories, contracts and model proxies. Product data is EAV; product types own persistence, pricing and stock behaviour. Admin routes use web sessions and the admin middleware, including role authorization and two-factor authentication. Shop routes apply theme, locale and currency middleware. The installed runtime is PHP 8.5.12 and Node 25.9.0.

Admin and Shop render Blade with Vue 3 components, VeeValidate, Tailwind 4 and independent Vite 6 builds. Admin already has a collapsible desktop sidebar and a mobile drawer. Views, render events, configuration merging, container bindings, theme registration and package providers are supported extension mechanisms. `ShopSetup` already supplies a Shop owner role, hidden system configuration, a setup checklist, simplified taxes and theme section copying. Theme gallery and homepage section editing already work; there is no reason to replace them.

Sales repositories retain order, invoice, shipment and refund transitions. Customer repositories retain account/address management and default groups. Payment methods extend `Webkul\Payment\Payment\Payment`; shipping carriers extend `AbstractShipping`. Checkout has guest support, address/shipping/payment steps, cart validation and total collection. Product media is validated and re-encoded by existing repositories. Image cache templates and lazy image components already exist. Hidden menu entries alone do not enforce security: the Shop owner role and route ACL remain the enforcement boundary. Full administrators deliberately retain support access.

Pest 5/PHPUnit 13 suites cover Admin, Shop, Product, Sales, Installer, Theme and ShopSetup. Playwright suites exist in Admin, Shop and Installer. Tests use transactions against a seeded database, not automatic migration. The configured MySQL database was unreachable before changes; verification uses a separate PostgreSQL database under `/tmp`. Frontend package lockfiles and node_modules were absent at audit time.

## Feature audit before implementation

| Area and existing menu/configuration | Classification | Merchant treatment and dependencies |
| --- | --- | --- |
| Dashboard, sales reporting, customer/product reports | Simplify | Daily overview, recent orders, stock attention and useful sales summaries; retain reporting services and support routes. |
| Sales orders, invoices, shipments, refunds | Keep | Orders navigation; retain separate payment and fulfilment state and all stock/accounting transitions. |
| Transactions, bookings | Hide | Remove from merchant navigation; payment records and booking product support remain in the engine. |
| RMA and EU withdrawal | Keep | Preserve existing returns/legal workflows; do not disable an existing policy based on the merchant's country. |
| Products, categories, images, prices, quantities | Simplify | Simple product creation with generated defaults; edit through existing validated persistence. |
| Attribute families and attribute administration | Internal | Default family for new simple products; retain custom families, EAV tables and existing product data. |
| Configurable, grouped, bundle, virtual, downloadable and booking products | Hide | Simple products first; retain existing specialised editors and advanced creation. |
| Related products, group prices, SEO, video, dimensions, custom options | Simplify | Collapsed advanced sections; controls still submit so existing data is preserved. |
| Customer list, details, reviews | Keep | Customer navigation and existing authorization. |
| Customer groups | Internal | Keep default groups and price relationships; no ordinary merchant management. |
| GDPR requests and consent configuration | Keep | Preserve customer privacy workflows; no legal assumptions. |
| CMS, appearance themes, homepage sections | Simplify | Store Design links to working theme editor and content pages. |
| Cart discounts and coupons | Keep | Retain existing discount workflows outside the compact primary navigation. |
| Catalog price rules, campaigns, templates, newsletter automation, URL rewrites, search synonyms | Hide | Preserve engine rules, SEO behaviour and jobs; omit primary navigation. |
| Channels, inventory sources, locales, currencies and exchange rates | Internal | One configured default for new products; retain all records and associations for existing stores. |
| Store information, payments, delivery, tax, notifications, account/security | Simplify | Settings hub links directly to working configuration pages and simplified tax setup. |
| Storage/R2, AI credentials, Elasticsearch, cache controls and system information | Internal | Existing hidden configuration remains inaccessible through configuration GET/POST; operator-managed. |
| Order/invoice numbering, rich snippets, technical catalogue settings | Hide | Defaults continue to run; support can use full admin mode. |
| Social login, CAPTCHA, customer address requirements, checkout settings | Keep | Existing behaviour remains; no automatic weakening of authentication or validation. |
| Marketplace or extra sales-channel administration | Remove | No marketplace package is present; omit channel administration from merchant navigation, without removing core channels. |
| Database tables, providers, services, public APIs | Keep | No deletion or replacement. |

## Implementation plan

1. Phase 1: extend ShopSetup with compact merchant menu and a settings hub; preserve ACL keys and full mode; test menu destinations and restricted routes.
2. Phase 2: add a single-submit simple product form using the default family/channel/active stock source and existing repositories/events; progressively disclose existing editor controls rather than omit fields; add a bounded dashboard summary and distinguish invoice payment from shipment fulfilment.
3. Phase 3: link store information, branding, payments, delivery, taxes, notifications and security to supported screens; default new installations to MUR without converting existing monetary data; document the Absa/Fiserv gateway boundary.
4. Phase 4: retain existing theme sections, search, variations, image optimization and checkout calculations; improve card readability, stock discovery and checkout failure/double-submit feedback.
5. Phase 5: run formatting, affected Pest suites, translation checks and frontend builds; inspect mobile/desktop in a browser where available; record exact failures, performance evidence and remaining limitations here.

## Design and upgrade approach

Use existing neutral surfaces and form components with restrained action colours. Keep layouts responsive through existing breakpoints; use native details/summary for progressive disclosure. The alternative of replacing the admin framework would duplicate authentication, ACL and commerce workflows and make upgrades harder. A separate partial product persistence layer would risk clearing media and associations. Reuse complete existing update forms instead. New PHP behaviour and views live in ShopSetup where practical; any small core template hooks are listed in the final change report. No new dependencies or database schema are needed.

## Verification ledger

Before changes, 141 ShopSetup tests failed before assertions because the configured MySQL connection was refused. An isolated temporary PostgreSQL 17 database is used for additional evidence; this does not replace the project's MySQL/MariaDB CI matrix.

### Final verification (isolated PostgreSQL 17, PHP 8.5.12)

- One full pass of `/usr/bin/php vendor/bin/pest` over the final source with nothing excluded: 4,139 tests, 10,830 assertions, exit 0, no failures, with dummy `AWS_*` and `R2_*` storage settings exported (see below). Each testsuite was also run separately earlier.
- PHP binary: three PayGlocal unit tests (the ones that initiate a signed request) kill the herd-lite PHP binary on this machine with `Illegal instruction` (exit 132), which also ends any single-process `vendor/bin/pest` run midway. All 29 PayGlocal tests pass under the system `/usr/bin/php` (PHP 8.5.12), so this is that binary, not the code; run the suite with `/usr/bin/php`.
- Storage-URL tests need `APP_URL` set. MySQL and MariaDB were not available, so only PostgreSQL is verified.
- Pint, `git diff --check` and `bagisto:translations:check` pass. Admin and Shop production builds pass.
- Quick product form: a default family with extra required attributes now shows a warning, and the store request redirects without creating a product. The collapsed edit form keeps every named field of the full form (simple and virtual products) and renders customer-group prices and the category and related-product editors. Related, up-sell and cross-sell links are loaded by the unchanged core Vue component, so they are not covered server-side. Custom options and variants have no new coverage.
- Order labels cover partial payment, refund, partial shipment, cancelled, closed, virtual and unregistered product types. All eight merchant routes are blocked for guests and unprivileged staff.
- Browser (Shop Playwright `simple-checkout.spec.ts`, MUR store, full mode, sync queue): guest checkout, new-address checkout and cash on delivery pass. The signed-in and flat-rate cases placed correct orders (₨199.00, ₨209.00) and fail only on the suite's hardcoded `$`. At 375 px wide, the storefront home, search and cart and the admin dashboard, order list and new-product form have no horizontal overflow. The only console errors are 403s for seeded theme images, because `public/storage` is not linked in this checkout.
- Merchant fulfilment in simple mode, driven in Chromium on one MUR order: Awaiting payment / Preparing order, then Create Invoice gives Paid, then Create Shipment (source Default) gives Shipped and status Completed, then Refund gives Refunded and status Closed (Total Refund ₨199.00). Cancelling a pending order shows Delivery: Cancelled. The existing `sales.spec.ts` order-management specs (9 tests: place, comment, invoice, ship, refund, cancel, reorder, overdue, print) all pass once the preview runs with `PHP_CLI_SERVER_WORKERS=6`. With the default single-threaded `php artisan serve` they fail: after the order POST succeeds, the create page re-fetches the consumed cart, gets a 404 and redirects to the order list, aborting the navigation to the new order. The admin sales code is unmodified, so this is upstream behaviour that only a concurrent server hides. Payment failure at checkout is covered in the browser by the new `packages/Webkul/Shop/tests/e2e-pw/tests/checkout/checkout-failure.spec.ts` (full mode, MUR store): a stubbed 500 from the order API keeps the guest on checkout with the gateway message shown and Place Order re-enabled, and a retry then reaches the success page. `OrderResubmissionTest` covers the server side.
- The ordered product must still exist and have stock, otherwise the Create Shipment source list is empty. The Shop spec's cleanup deletes its products, so orders left behind cannot be shipped.
- The existing Playwright page objects click the original Create Product button, which simple mode moves under More product types, so catalogue specs need `SHOP_ADMIN_MODE=full`. Run a queue worker or `QUEUE_CONNECTION=sync`, otherwise new products are never indexed and show as unavailable.

### Dashboard performance (same MUR database, Chromium, 3 runs each)

| Data set | Mode | Load time | Requests | Stats requests |
|---|---|---|---|---|
| Empty | Simple | about 870 ms | 12 | 1 |
| Empty | Full | 1850-2220 ms | 32 | 13 |
| 1,500 products, 1,506 orders, 753 paid invoices (cloned rows) | Simple | 820-862 ms | 10 | 0 |
| 1,500 products, 1,506 orders, 753 paid invoices (cloned rows) | Full | 1305-1335 ms | 23 | 6 |

The seeded orders are clones of one order, so they test volume, not realistic variety. The Admin bundle still warns about a roughly 640 kB main chunk; no splitting was done.

### Remaining scope and limits

- Added late: a Payments page (`admin/settings/merchant/payments`) listing each method with its saved Enabled or Disabled state, whether it is online or offline, and the standing note that enabling does not verify an account; and an Inventory page (`admin/catalog/inventory`) listing managed-stock products at the default stock location, lowest quantity first, with paging and links into the product editor. Both reuse existing ACL keys (`configuration`, `catalog.products`) and are in the guest and unprivileged-staff route matrix.
- Added for the customer and order-detail areas: the dashboard shows a Customers count beside orders and products, and the order detail shows one next step (create an invoice while payment is unrecorded, ship while items remain unshipped, nothing once cancelled, closed or completed), derived only from recorded invoices and shipments. Both have tests and translations in all 23 locales.
- Orders list: a Payment filter (awaiting payment or paid) beside the existing search and status filters, based on recorded paid invoices, with a test.
- Dark mode and tablet pass (820 px and 1280 px, real `dark_mode` cookie): no horizontal overflow on the dashboard, orders, new product, payments, inventory, settings, store information or tax pages. Screenshots found and fixed unreadable near-black text on dark cards: section headings, Advanced options summaries, the image file input, table text, order filter labels and Back buttons. The default status option now reads All orders instead of Orders.
- Regression found and fixed while checking theme sections: the keyboard-accessible drawer close control is now a `<button>`, which broke the `span.icon-cross` selector in `SectionsPage.ts`; the selector is now `.icon-cross`. All 11 section specs pass. The `themes.spec.ts` gallery spec fails because it looks for a "Buy Themes" label that no view in this branch renders; it came in with an upstream merge and is unrelated to this change.
- Storefront journeys re-run in MUR on a multi-worker server: configurable (variation) checkout 5 of 5, search 2 of 2, sorting 4 of 4.
- Decision on the rest of the original scope: customer management, theme sections, homepage search and filtering, variation selection and the order-detail workflows keep their existing, working core screens. The merchant shell reaches them (All customers, Store Design, Orders) and they pass their tests and the browser journeys above. A scripted browser audit in simple mode (desktop 1280 px and mobile 375 px) of customers, themes, store design, CMS pages, categories, orders, settings, store information, tax, storefront search and the storefront home found no HTTP errors, no JavaScript errors and no horizontal overflow. Redesigning them would be new product work with no brief in this repository, so it is deliberately not done here. Delivery still links to existing carrier configuration.
- Absa/Fiserv is a documented boundary only. Pickup, delivery zones, payment verification and refunds are not offered beyond installed capabilities.
- Localisation: all 23 `merchant.php` files are translated. French is the one aimed at Mauritius. The other 21 translations were written without a native-speaker review, so have a reviewer read the less common languages (Sinhala, Swahili, Bengali, Persian, Hebrew, Arabic) before release. Every translation is checked to keep the same `:placeholders` as English.
- No commit, push or deployment has been made.
