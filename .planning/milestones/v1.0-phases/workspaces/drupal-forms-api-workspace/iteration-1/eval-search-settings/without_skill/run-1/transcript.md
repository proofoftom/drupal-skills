Module enabled successfully. Here's a summary of what was created:

```
search_settings_baseline/
├── search_settings_baseline.info.yml       # Module definition (D10/D11 compat)
├── search_settings_baseline.routing.yml    # Route: /admin/config/search/search-settings-baseline
├── search_settings_baseline.links.menu.yml # Admin menu link under Search
├── config/
│   ├── install/
│   │   └── search_settings_baseline.settings.yml  # Defaults: 0.7, 5, 300
│   └── schema/
│       └── search_settings_baseline.schema.yml     # float + integer types
└── src/Form/
    └── SearchSettingsBaselineForm.php      # ConfigFormBase with 3 number fields
```

Key details:
- Extends `ConfigFormBase` — standard Drupal config form pattern, works on D10 and D11
- `core_version_requirement: ^10 || ^11` for D11 compatibility
- `similarity_threshold` typed as `float` in schema, cast on save; `result_limit` and `cache_ttl` as `integer`
- Schema uses `config_object` type so `drush config:export` works correctly
