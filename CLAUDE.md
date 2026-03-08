# Drupal Skills Plugin

When generating Drupal module code:
- Target compatibility: `core_version_requirement: ^10 || ^11`
- Prefer PHP attributes (D11) with annotation fallback shown for D10
- Machine names: lowercase with underscores (e.g., my_module)
- All code must pass `phpcs --standard=Drupal,DrupalPractice`
