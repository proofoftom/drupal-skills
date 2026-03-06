Module installed successfully. Here's what was created:

```
html/modules/custom/resource_directory/
├── resource_directory.info.yml          # core_version_requirement: ^10 || ^11
├── resource_directory.install           # hook_schema() → resource_links table
├── resource_directory.views.inc         # hook_views_data() with group key
├── config/schema/
│   └── resource_directory.schema.yml   # views.filter + views.filter_value schemas
└── src/Plugin/views/filter/
    └── CategoryFilter.php               # #[ViewsFilter("resource_directory_category")]
```

**Key design decisions following the skill:**

- `hook_views_data()` is in `.views.inc` (Drupal lazy-loads this file only when Views needs it)
- `table['group'] = t('Resource Directory')` — always set, groups all fields in the Views UI
- `category` field points to `'id' => 'resource_directory_category'` for the custom filter
- `CategoryFilter` extends `InOperator` → renders as a multi-select dropdown
- D11 attribute syntax: `#[ViewsFilter("resource_directory_category")]` with `use Drupal\views\Attribute\ViewsFilter`
- `?array &$options = NULL` — explicit nullable to avoid PHP 8.4 deprecation warning
- Schema file covers both `views.filter.*` and `views.filter_value.*` — required for InOperator filters to survive config import/export
