Module installed successfully. Here's what was created:

```
search_settings/
├── search_settings.info.yml          # module metadata, core ^10 || ^11
├── search_settings.routing.yml       # /admin/config/search/search-settings
├── src/Form/
│   └── SearchSettingsForm.php        # ConfigFormBase with 3 number fields
└── config/schema/
    └── search_settings.schema.yml    # typed config schema (float + 2x integer)
```

Key decisions from the skill:
- **`ConfigFormBase`** — correct choice for a settings form saving to config
- **`_form`** (not `_controller`) in routing — proper form builder routing
- **`_admin_route: TRUE`** — uses admin theme at `/admin/config/search/search-settings`
- **`parent::buildForm()`** adds the submit button; **`parent::submitForm()`** adds the "saved" message
- **Config schema** typed as `float` for `similarity_threshold`, `integer` for the other two — required for config export/import to work correctly
- **`$this->messenger()`** — D11-compatible (no `drupal_set_message()`)
