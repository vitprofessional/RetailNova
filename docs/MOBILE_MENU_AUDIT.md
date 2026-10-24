# Mobile menu and destination audit

Reviewed against `App\Services\BusinessNavigation`, `App\Support\MobileNavigation`, and `resources/views/partials/mobile-shell.blade.php` on 2026-10-24. The source permission menu remains authoritative; a mobile destination is rendered only when its named route exists and the signed-in actor has the corresponding desktop menu entry.

## Navigation map

| Surface | Destination(s) | Notes |
|---|---|---|
| Bottom: Home | `dashboard` | Home shortcuts omit every destination already represented by a bottom tab. |
| Bottom: Stock | `stockProduct` | Product catalog remains a separate route in More, grouped under the Stock label. Product list cards keep the existing create FAB. |
| Bottom: Sale | `newsale` | The central sale action is the sole intentional create-action tab. |
| Bottom: Reports | `reports.index` | One report hub; the report cards link to every authorized report destination, including account and expense reports. |
| Bottom: More | `#rn-more` | Opens the grouped destination sheet, not an application route. |
| More / Sales | `saleList`, `returnSaleList`, quotations when enabled | New Sale is not repeated. |
| More / Purchases | `purchaseList`, `returnPurchaseList` | New Purchase is reached from the purchase-list FAB. |
| More / Inventory | `productlist`, brand/category/unit/barcode, damage history, and inventory destinations | Product catalog uses the Stock label. New Product and Record Damage are list FAB actions. |
| More / People | `addCustomer`, `addSupplier` | Customer entry is labeled “Customers & due”; its existing `due=with` filter is the due-list entry point. |
| More / Finance | account chart/transactions, expense history/categories, audit history | New Expense is the expense-list FAB. |
| More / Settings | authorized business settings, users, and documentation | Role and route checks are preserved. |
| List FABs | `addPurchase`, `quotation.create`, `addProduct`, `damageProduct`, `provideService`, `expense.create`, plus existing list-specific actions | Create destinations are not repeated as generic More menu items. Service and damage lists expose their already-authorized create actions. |
| More utility controls | locale, view mode, sign out | These are forms/actions, not route destinations. |

## De-duplication and access rules

- `MobileNavigation::moreSections()` removes bottom-tab destinations, report destinations, and list-FAB creation destinations before grouping.
- Home shortcuts omit bottom-tab, report, and create-FAB destinations.
- The Reports tab opens the report hub if the actor has at least one authorized report destination. The hub is the single mobile entry point for report links.
- The customer list's existing due filter remains business-scoped; no new due-collection endpoint or mutation was added.
- Routes are never deleted by this navigation change. Existing business-role/permission filtering and route existence checks remain in force.
- `MobileShellTest` asserts no repeated route destination across tabs, Home shortcuts, and More, and checks that report/create links are surfaced on their designated surfaces.

## Known boundary

The “Stock” area has distinct product-catalog and purchase-lot routes because they expose different records. The bottom tab opens the existing lot-stock screen; the catalog remains one separately named Stock destination so product search and its create FAB remain reachable. This does not duplicate a route.
