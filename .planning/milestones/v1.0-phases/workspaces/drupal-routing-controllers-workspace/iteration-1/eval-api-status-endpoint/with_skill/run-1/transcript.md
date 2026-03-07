Module enabled successfully. Here's what was created:

```
api_status_endpoint/
  api_status_endpoint.info.yml       -- D10/D11 module declaration
  api_status_endpoint.routing.yml    -- Route at /api/system/status
  src/Controller/
    ApiStatusController.php          -- JsonResponse controller with DI
```

Key patterns used from the skill:

- **`core_version_requirement: ^10 || ^11`** for D11 compatibility
- **`_access: 'TRUE'`** — appropriate for a public health-check/status endpoint
- **`create()` factory** pulls `config.factory` and `module_handler` from the container; constructor stores them as typed properties
- **`JsonResponse`** return type (not a render array) since this is an API endpoint
- No `\Drupal::` static calls inside the class — all services injected via DI
