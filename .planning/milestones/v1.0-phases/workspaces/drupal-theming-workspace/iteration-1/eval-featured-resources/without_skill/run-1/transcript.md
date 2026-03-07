Module enabled successfully. Here's what was created:

```
html/modules/custom/featured_resources/
├── featured_resources.info.yml          # core_version_requirement: ^10 || ^11
├── featured_resources.module            # hook_theme() registration
├── featured_resources.libraries.yml     # CSS library definition
├── css/
│   └── featured-resources.css          # BEM-style component styles
├── templates/
│   └── featured-resources-list.html.twig  # Theme template (dash-separated per convention)
└── src/Plugin/Block/
    └── FeaturedResourcesBlock.php      # Block plugin with hardcoded resources
```

**Key implementation details:**

- `hook_theme()` registers `featured_resources_list` → maps to `featured-resources-list.html.twig` (Drupal's underscore-to-dash convention)
- `FeaturedResourcesBlock::build()` returns a render array with `#theme`, `#resources`, `#heading`, and `#attached` (library) — no raw HTML
- `attributes` is declared in the `hook_theme()` variables array so `template_preprocess()` converts it to an `Attribute` object, enabling `{{ attributes.addClass(...) }}` in the template
- 5 hardcoded sample resources with types: Article, Video, Guide — each type gets a distinct color badge via CSS
- The block is available to place at **Admin > Structure > Block layout** under the "Custom" category as "Featured Resources"
