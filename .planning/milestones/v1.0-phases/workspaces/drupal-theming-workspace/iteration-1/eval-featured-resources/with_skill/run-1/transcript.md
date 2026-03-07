Module installed successfully. Here's what was created:

```
html/modules/custom/featured_resources/
  featured_resources.info.yml          # core_version_requirement: ^10 || ^11
  featured_resources.module            # hook_theme() + template_preprocess_*
  featured_resources.libraries.yml     # CSS library definition
  src/Plugin/Block/
    FeaturedResourcesBlock.php         # Block plugin (PHP attribute, D10.2+/D11)
  templates/
    featured-resources-list.html.twig  # underscore→hyphen naming convention
  css/
    featured_resources.css             # BEM-style component styles
```

**Key implementation details:**

- **Hook name** `featured_resources_list` → template `featured-resources-list.html.twig` (underscores to hyphens)
- **`hook_theme()` variables**: `items` (defaults to `[]`) and `heading` (defaults to `NULL`) — all declared with defaults per the contract
- **Render array** uses `#theme => 'featured_resources_list'`, `#items`, `#heading` — no raw HTML strings
- **CSS attached** via `#attached => ['library' => ['featured_resources/featured_resources']]` on the render array itself
- **Block plugin** uses PHP `#[Block(...)]` attribute (D10.2+/D11 style) instead of docblock annotations
- **Preprocess function** adds the `featured-resources` class to `$variables['attributes']`, which the template renders via `{{ attributes }}`

The block is now available in Drupal's block layout UI under "Custom" category as "Featured Resources".
