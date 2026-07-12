# AssetFlow Dashboard

A simple, professional asset-management dashboard built with **PHP + HTML + CSS** (no framework, no database setup required for the demo).

## What's included

- **Dashboard** (`index.php`) — today's overview stats, overdue alert banner, quick actions, recent activity, and a "Reset demo data" control
- **Assets** (`assets.php`) — searchable asset registry, with a link into direct allocation or transfer depending on status
- **Allocation & Transfer** (`transfer.php`) — the double-allocation block: selecting an already-allocated asset blocks direct re-assignment and routes you to a transfer request form instead; available assets can be allocated directly. Pending transfers can now be **approved or rejected** — approving actually moves the asset to the new holder
- **Resource Booking** (`booking.php`) — book rooms/equipment, see today's bookings, blocked from double-booking the exact same room/time slot
- **Asset Audit** (`audit.php`) — audit cycle info (department, date range, auditors), a per-asset verification checklist (Verified / Missing / Damaged), an auto-calculated discrepancy banner, and a Close audit cycle action that logs the result and lets you start a fresh cycle
- **Reports & Analytics** (`reports.php`) — utilization-by-department bar chart, maintenance-frequency line chart, most-used/idle asset lists, assets due for maintenance or nearing retirement, and a working **Export report** button that downloads a CSV
- **Notifications** (`notifications.php`) — activity log with filter tabs (All / Alerts / Approvals / Bookings)
- **Organization Setup** (`org.php`) — admin screen with Departments / Categories / Employee tabs, a context-aware "+ Add" form for each, and status badges. Adding or deactivating a department feeds the department picklist on Allocation & Transfer; categories feed the category picklist when registering a new asset on the Dashboard
- **User Roles & Permissions** — a demo Admin/Staff switcher in the topbar. Admin-only actions (registering assets, direct allocation, approving/rejecting transfers, closing/starting audit cycles, Organization Setup, and resetting demo data) are hidden from Staff in the UI *and* rejected server-side if posted directly. Staff can still do day-to-day work: submit transfer requests, book resources, and mark audit checklist items.
- Placeholder pages for Maintenance (`page.php`)

### What's new in this version
- **Asset lifecycle timeline** (`asset.php?id=…`) — a visual, chronological history per asset (Registered → Allocated → Transferred → Maintenance → Retired), reachable via a "Timeline" link on the Assets table. Admins can send an asset to Maintenance, resolve it back to Available, or retire it — every action is logged to the timeline and to Recent Activity.
- **Utilization / idle-time insights** — idle days are now computed for real from each asset's last activity date instead of a static demo string. The Assets table flags anything idle 30+ days with an "idle Nd" pill, the Dashboard surfaces the worst offender in a callout banner ("Epson EB-Projector has been unbooked for 45 days"), and Reports lists all idle assets ranked worst-first, each linking to its timeline.
- **Booking calendar view** — Resource Booking is now a real week grid (Mon–Sun, 8am–6pm) instead of just a form + list. Bookings render as blocks positioned by time; click any empty slot to prefill a booking form at that day/time. Prev/next week navigation included.
- **Smart conflict detection** — booking overlap checking is now interval-aware (catches partial overlaps, not just exact-match times), and Allocation & Transfer warns admins before they compound a problem: submitting a transfer for an already-overdue asset shows an explicit "days overdue" warning, and allocating/transferring an asset to someone who already holds a different overdue asset flags that too.
- **Sky blue & white theme.** Reworked the visual design: a light white/sky-tinted workspace, a sky-blue gradient sidebar, and matching blue accents on primary buttons, active nav/tabs, and focus states. The Reports & Analytics charts carry the sky-blue panel through with warm amber bars and a white "cloud" line for contrast. Real interactivity throughout — hover lifts on buttons and cards, table row highlighting, smooth focus rings, and an animated slide for the Register Asset / Add drawers.
- **User roles & permissions.** A topbar "Viewing as" switcher toggles between Admin and Staff for the current session (there's no real login system, so this simulates one). Admin-only screens and actions are hidden from Staff and also blocked server-side if requested directly — see the feature list above for exactly what's gated.
- **Transfers now actually resolve.** Previously a transfer request just sat as "Pending" forever with no way to close it out. There's now Approve/Reject on each pending row, and approving reassigns the asset's holder and status.
- **Direct allocation for available assets.** Assets with no holder can now be assigned straight from Assets → Allocate or Transfer & Allocation, instead of only showing a "go check Assets page" message.
- **Duplicate asset ID guard.** Registering an asset ID that already exists is now rejected with a clear error instead of silently creating a duplicate.
- **Booking conflicts caught.** Booking the same room for the exact same start/end as an existing booking is blocked.
- **Basic CSRF protection** on every POST form (register, allocate, transfer, approve/reject, booking, reset).
- **Reset demo data** button on the dashboard, so you can start over without restarting the PHP server or clearing cookies manually.
- **Command palette (⌘K / Ctrl+K)** — jump to any page or any asset from anywhere in the app. Click the search bar in the topbar or press the shortcut, type to fuzzy-filter, arrow keys + Enter to navigate.

## How to run it

You need PHP installed (PHP 8+ recommended). No database, no `composer install`.

```bash
cd assetflow
php -S localhost:8000
```

Then open **http://localhost:8000** in your browser.

## How data works

This is a self-contained demo: all data (assets, transfers, bookings, activity log) lives in `includes/data.php` and is stored in the PHP session, so it resets when the session ends. To wire it up to a real database, replace the functions in `includes/data.php` (e.g. `af_assets()`, `af_submit_transfer()`) with PDO/MySQL queries — the rest of the app already calls through those functions, so no other file needs to change.

## Structure

```
assetflow/
├── index.php          Dashboard
├── assets.php          Asset registry
├── transfer.php         Allocation & Transfer (double-allocation block)
├── booking.php          Resource booking
├── audit.php             Asset Audit (checklist + discrepancy report)
├── reports.php           Reports & Analytics (charts + lists)
├── export_report.php     CSV export used by the Reports page
├── notifications.php     Activity logs & Notifications
├── org.php               Organization Setup (departments, categories, employees)
├── page.php             Placeholder pages
├── style.css             All styling
└── includes/
    ├── data.php          Mock data layer (session-based)
    ├── header.php         Shared sidebar + topbar
    └── footer.php         Shared closing markup
```
