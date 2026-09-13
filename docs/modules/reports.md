# Reports

Tenant reporting for CRM metrics.

## Access

- View: permission `view.reports`  
- Export: permission `export.reports`  
- Routes: `reports.index`, `reports.export` (`GET /reports/export/{type}`)  
- Controller: `App\Http\Controllers\ReportController`

Sales role includes view + export by default.

## UI

**Reports** in the CRM sidebar opens the reports index. Filters and available charts/tables depend on the current `ReportController` implementation and Blade views under `resources/views/reports/`.

## Exports

Export endpoints are throttled (see route middleware `throttle:30,1` on export). Use exports for offline analysis; prefer CSV imports/exports modules for bulk record movement.

## Operational notes

- All report data is **company-scoped** via tenancy.
- Heavy exports should be run off-peak; consider queueing in a future iteration if files grow large (**Recommended** enhancement).

## Related

- [Modules overview](overview.md)
- [End-user manual](../user-manual/overview.md)
