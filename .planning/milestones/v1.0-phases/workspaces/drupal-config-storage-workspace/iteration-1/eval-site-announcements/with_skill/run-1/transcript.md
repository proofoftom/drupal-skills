Module installed successfully. Here's what was created:

```
site_announcements/
  site_announcements.info.yml          # D10/D11 core_version_requirement: ^10 || ^11
  site_announcements.routing.yml       # /admin/config/system/announcements
  site_announcements.permissions.yml   # 'administer site announcements'
  config/install/
    site_announcements.settings.yml    # defaults: announcement_text, display_enabled, max_display_count
  config/schema/
    site_announcements.schema.yml      # types: label, boolean, integer
  src/Form/
    AnnouncementsSettingsForm.php      # extends ConfigFormBase
```

Key decisions from the skill:
- `announcement_text` uses schema type `label` (not bare `string`) since it's user-facing translatable content
- `config/install/` defaults ensure the config object exists immediately on install — no NULL reads before first form save
- `getEditable()` in `submitForm()` so admins always edit the real stored values, bypassing any overrides
- `core_version_requirement: ^10 || ^11` for D11 compatibility
