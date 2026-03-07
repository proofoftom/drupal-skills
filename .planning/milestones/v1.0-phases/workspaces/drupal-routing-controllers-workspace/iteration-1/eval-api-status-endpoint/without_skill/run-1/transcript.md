Module enabled successfully. Here's a summary of what was created:

**Files created under `html/modules/custom/api_status_endpoint_baseline/`:**

- **`api_status_endpoint_baseline.info.yml`** — `core_version_requirement: ^10 || ^11` for D11 compat
- **`api_status_endpoint_baseline.routing.yml`** — route `/api/system/status` with `_access: 'TRUE'`
- **`src/Controller/StatusController.php`** — controller with proper DI

**Key D11 compatibility note:** `ControllerBase` already declares `protected $configFactory` as non-readonly, so we can't use promoted constructor properties for it. Instead, `$moduleHandler` is declared explicitly and `$configFactory` is assigned in the constructor body (reusing the parent's property slot). The `create()` factory uses `static` return type for forward compatibility.
