Module enabled successfully. Here's what was created:

**Files:**
- `restricted_reports.info.yml` — module definition with `core_version_requirement: ^10 || ^11` for D11 compat
- `restricted_reports.permissions.yml` — defines `view restricted reports` permission
- `restricted_reports.routing.yml` — route at `/reports/restricted` using `_custom_access`
- `src/Access/RestrictedReportsAccessChecker.php` — returns `AccessResult::allowedIfHasPermission()`, which automatically adds the `user.permissions` cache context (no manual `addCacheContexts()` needed)
- `src/Controller/RestrictedReportsController.php` — simple page controller

**Key access control decisions per the skill:**
- Used `_custom_access` + `AccessResult::allowedIfHasPermission()` rather than bare `_permission` in routing.yml, satisfying the "use AccessResult objects" requirement
- `allowedIfHasPermission()` is preferred over `allowedIf($account->hasPermission(...))` because it automatically adds `user.permissions` cache context — preventing the cache poisoning bug where one user's access decision gets applied to all users
- Anonymous users get 403 because they lack the permission; Drupal enforces this automatically when `AccessResult::forbidden()` is returned
