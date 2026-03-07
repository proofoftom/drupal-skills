Module installed successfully. Here's what was created under `html/modules/custom/view_analytics/`:

| File | Purpose |
|------|---------|
| `view_analytics.info.yml` | Module metadata, D10/D11 compatible |
| `view_analytics.install` | `hook_schema()` defining the `view_analytics` table |
| `view_analytics.module` | `hook_node_view()` — records a view on `full` view mode only |
| `view_analytics.services.yml` | Registers `ViewAnalyticsTracker` with `@database` + `@request_stack` |
| `view_analytics.routing.yml` | Route for `/admin/reports/view-analytics` |
| `view_analytics.links.menu.yml` | Menu link under Admin > Reports |
| `src/ViewAnalyticsTracker.php` | Service: `recordView()` inserts a row; `getTopViewed()` aggregates with `COUNT(*) GROUP BY nid ORDER BY count DESC LIMIT 10` |
| `src/Controller/ViewAnalyticsController.php` | Fetches top 10 nids via DB aggregation, loads node entities for titles, renders a table |

Key design decisions per the skill:
- **No raw SQL** — all queries use the dynamic query builder (`select()`, `insert()`, `addExpression()`, `groupBy()`)
- **No Entity API for tracking data** — the `view_analytics` table is queried directly
- **Entity API only for titles** — `loadMultiple()` is used solely to fetch node labels from the nids returned by the DB query
- **DI throughout** — `Connection` and `RequestStack` injected into the service; controller uses `ContainerInterface::create()`
