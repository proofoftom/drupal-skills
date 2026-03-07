Module installed successfully. Here's what was created:

**Files:**
```
view_analytics/
├── view_analytics.info.yml          # D10/D11 compatible
├── view_analytics.install           # hook_schema() — creates view_analytics table
├── view_analytics.module            # view_analytics_record_view() + hook_node_view()
├── view_analytics.routing.yml       # /admin/reports/view-analytics route
├── view_analytics.links.menu.yml    # Adds link under Admin > Reports
└── src/Controller/
    └── ViewAnalyticsController.php  # Aggregation query, top 10 report
```

**Key design decisions:**

- **No Entity API** — the controller joins `node_field_data` directly via the DB abstraction layer to get node titles, skipping entity loading entirely
- **D11 bracket syntax** — the JOIN condition uses `[va].[nid]` bracket quoting for database portability
- **hook_node_view()** wires up auto-recording on `view_mode = 'full'` page views; `view_analytics_record_view()` is also callable directly from anywhere
- **`[n].[default_langcode] = 1`** in the JOIN prevents duplicate rows for multi-language sites
- The table has indexes on `nid`, `uid`, and `viewed_at` for efficient aggregation as the table grows
