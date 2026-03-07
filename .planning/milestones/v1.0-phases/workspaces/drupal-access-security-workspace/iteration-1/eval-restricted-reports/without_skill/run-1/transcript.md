Module installed successfully. Here's what was created:

```
restricted_reports_baseline/
  restricted_reports_baseline.info.yml         # core_version_requirement: ^10 || ^11
  restricted_reports_baseline.permissions.yml  # 'view restricted reports baseline'
  restricted_reports_baseline.routing.yml      # /reports/restricted-baseline, _custom_access
  src/
    Access/
      RestrictedReportsBaselineAccessChecker.php  # returns AccessResult
    Controller/
      RestrictedReportsBaselineController.php
```

Key design decisions per the skill:

- **`_custom_access`** instead of `_permission` so access checking explicitly uses `AccessResult` objects (as requested)
- `AccessResult::allowedIfHasPermission($account, '...')` — automatically adds `user.permissions` cache context, so the first user's access decision isn't incorrectly cached for all users
- Anonymous users get a **403** because they lack the permission (Drupal converts `forbidden` AccessResult to 403 for unauthenticated users)
- `core_version_requirement: ^10 || ^11` for D11 compatibility
