# Kiosk and Canteen Operations

## Access setup

The module uses the existing authenticated user and school tenant. It does not add a separate login. The additive `KioskAccessSeeder` creates the `kiosk-manager` and `kiosk-cashier` roles and their permissions without replacing permissions on the other existing roles. After reviewed deployment, an authorised operator can run:

```bash
php artisan db:seed --class=KioskAccessSeeder
```

Then assign the appropriate role through the existing user/role management screen. Cashiers receive POS, product lookup, till open/close and their Z reading. Managers receive back-office reporting, product/stock controls, stock takes and cross-cashier readings. School admin, headmaster, bursar and accountant roles receive the manager permissions when this additive seeder runs. The existing `RolePermissionSeeder` also defines the roles for fresh installations, but it synchronises all built-in role permissions; use `KioskAccessSeeder` for a targeted production setup.

## Cashier shift

1. Sign in with an active kiosk cashier or manager account.
2. Open **Kiosk & Canteen → Cashier Shift** and record the till label and physically counted opening float.
3. Use **Open POS** and record sales. The server uses the configured product price, locks stock rows and rejects a sale unless the cashier has an open till.
4. At shift end, physically count the cash and close the till. The system snapshots cash, mobile money, bank transfer and card totals and calculates expected cash and variance.
5. Print the Z reading. It reports the existing kiosk sales and does not post another receipt, cashbook line, or journal.

Sales continue to use the current `AccountingService::postKioskSale()` workflow once per sale. The till is a reconciliation summary and does not replace accounting.

## Stock controls

Products & Stock is the kiosk product master. Initial quantities, sales, product-form adjustments and approved stock-take differences are recorded in the kiosk stock movement ledger. Kiosk stock remains separate from the general stores/inventory module; no automatic cross-module quantities are implied.

A stock take snapshots active tracked items and submits counts as pending review. A different user with the stock-take approval permission must approve it. Approval applies the counted quantities only if the live quantity still matches the snapshot, preventing stale counts from overwriting intervening stock movements.

## Reporting

The back office shows sales for the selected date range, daily/monthly totals, best-selling items for that range, low stock alerts, open till status and the sales transaction list. Z readings are available to their cashier and authorised managers.

## Schema

The two additive migrations create till/stock-control tables and add a nullable till-session reference to kiosk sales. They do not reset, truncate or rewrite existing student, sale, or accounting records. Existing kiosk sales retain a null till reference and remain in historical sales reports.
