Module installed successfully. The `$form_state` diagnostic is a false positive — it's required by the parent method signature but unused here, which is standard in Drupal filter plugins.

Final structure:

```
html/modules/custom/resource_directory/
├── resource_directory.info.yml          # D10/D11, depends on views
├── resource_directory.install           # hook_schema() — creates resource_links table
├── resource_directory.module            # hook_views_data() — exposes all columns + group key
└── src/Plugin/views/filter/
    └── ResourceCategoryFilter.php       # @ViewsFilter("resource_category") — dropdown by category
```

Key details:
- **`resource_links` table** — `id` (serial PK), `title`, `url`, `category`, `created`, indexed on `category`
- **Views group** — `Resource Directory` set via `table.group`
- **Category field** wired to `resource_category` filter plugin ID
- **`ResourceCategoryFilter`** — extends `FilterPluginBase`, injects `database` service, queries distinct categories dynamically for the dropdown, skips the `WHERE` clause when no category is selected
- **`core_version_requirement: ^10 || ^11`** for D11 compatibility
