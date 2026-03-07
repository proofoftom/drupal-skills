Module enabled successfully. Here's what was created:

```
site_announcements_baseline/
├── site_announcements_baseline.info.yml       # core_version_requirement: ^10 || ^11
├── site_announcements_baseline.routing.yml    # /admin/config/system/announcements-baseline
├── config/
│   ├── install/
│   │   └── site_announcements_baseline.settings.yml  # default values
│   └── schema/
│       └── site_announcements_baseline.schema.yml    # typed config schema
└── src/Form/
    └── AnnouncementsSettingsForm.php          # ConfigFormBase settings form
```

Key points:
- `config/install/` YAML is imported once on `drush en` and sets the three defaults
- Schema uses `config_object` type with proper `string`/`boolean`/`integer` mappings
- `AnnouncementsSettingsForm` extends `ConfigFormBase` — the D10/D11-standard approach
- `core_version_requirement: ^10 || ^11` for D11 compatibility
- Settings form available at `/admin/config/system/announcements-baseline` with `administer site configuration` permission
