# Menu Links, Local Tasks, Local Actions, and Contextual Links

Drupal provides four types of navigation links, each defined in its own YAML file. This reference covers when and how to use each type.

## Menu links (.links.menu.yml)

Menu links appear in site menus (main navigation, footer, admin menu, etc.). Define them to add your module's pages to the site navigation.

### When to create menu links

- Your module has a user-facing page that should appear in site navigation
- Your module has an admin settings page that should appear in the admin menu
- You want to add a link to an existing menu

### module_name.links.menu.yml

```yaml
my_module.main_page:
  title: 'My Module'
  description: 'View the main page of My Module.'
  route_name: my_module.main_page
  menu_name: main
  weight: 0

my_module.settings:
  title: 'My Module Settings'
  description: 'Configure My Module behavior.'
  route_name: my_module.settings
  menu_name: admin
  parent: system.admin_config_system
  weight: 10
```

### Menu link properties

| Property | Purpose | Required |
|----------|---------|----------|
| `title` | Link text displayed in the menu | Yes |
| `description` | Tooltip/title attribute on the link | No |
| `route_name` | Route this link points to | Yes |
| `menu_name` | Machine name of the target menu (e.g., `main`, `admin`, `footer`) | Yes |
| `parent` | Plugin ID of the parent menu link for hierarchy | No |
| `weight` | Ordering weight (lower = higher in list) | No |
| `enabled` | Whether the link is enabled by default (`1` or `0`) | No |

### Menu link hierarchy

Build hierarchical menus using the `parent` property. The value is the plugin ID of the parent link.

```yaml
my_module.parent_link:
  title: 'Products'
  route_name: my_module.products
  menu_name: main

my_module.child_link:
  title: 'All Products'
  route_name: my_module.products.list
  menu_name: main
  parent: my_module.parent_link
```

For admin pages, use existing Drupal admin menu parents:
- `system.admin` -- top-level admin
- `system.admin_config` -- Configuration section
- `system.admin_config_system` -- Configuration > System
- `system.admin_structure` -- Structure section
- `system.admin_content` -- Content section

> WRONG: Using `hook_menu()` to define menu links. This is the Drupal 7 approach and has been removed since Drupal 8.
> RIGHT: Define menu links in `module_name.links.menu.yml`. Each link specifies a route name, menu name, and optional hierarchy via `parent`.

## Local tasks (.links.task.yml)

Local tasks appear as tabs on a page. They group related pages together -- for example, "View" and "Edit" tabs on a node page.

### When to create local tasks

- You have related pages that should be grouped with tabs
- You want to add a tab to an existing page (e.g., adding a "Statistics" tab to a node page)
- An admin page has a settings form and you want a tab switching between the page and its configuration

### module_name.links.task.yml

```yaml
my_module.main_tab:
  route_name: my_module.main_page
  title: 'View'
  base_route: my_module.main_page

my_module.settings_tab:
  route_name: my_module.settings
  title: 'Settings'
  base_route: my_module.main_page
  weight: 100
```

### Local task properties

| Property | Purpose | Required |
|----------|---------|----------|
| `route_name` | Route this tab links to | Yes |
| `title` | Tab label | Yes |
| `base_route` | Route where these tabs appear (all tabs sharing a `base_route` are grouped) | Yes |
| `weight` | Tab ordering (lower = further left) | No |

### Key concept: base_route

All local tasks with the same `base_route` appear together as tabs on that page. The `base_route` value is a route name, not a path. One of the tabs should point to the base route itself (the "default" tab).

```yaml
# These three tabs all appear on the /products page
products.list:
  route_name: products.list
  title: 'List'
  base_route: products.list

products.import:
  route_name: products.import
  title: 'Import'
  base_route: products.list
  weight: 10

products.settings:
  route_name: products.settings
  title: 'Settings'
  base_route: products.list
  weight: 20
```

### Secondary tabs

For nested tabs (sub-tabs), use `parent_id` to reference a primary tab:

```yaml
products.settings.general:
  route_name: products.settings.general
  title: 'General'
  parent_id: products.settings
  weight: 0

products.settings.display:
  route_name: products.settings.display
  title: 'Display'
  parent_id: products.settings
  weight: 10
```

## Local actions (.links.action.yml)

Local actions are action buttons (typically "+ Add" buttons) that appear at the top of collection/listing pages. They represent the primary action available on that page.

### When to create local actions

- A listing page needs an "Add new item" button
- You want to provide a primary action on a specific route

### module_name.links.action.yml

```yaml
my_module.add_item:
  route_name: my_module.item.add
  title: 'Add item'
  appears_on:
    - my_module.item.collection
```

### Local action properties

| Property | Purpose | Required |
|----------|---------|----------|
| `route_name` | Route the action button links to | Yes |
| `title` | Button text (typically starts with "Add") | Yes |
| `appears_on` | List of route names where this action shows | Yes |

### Key concept: appears_on

Unlike local tasks (which use `base_route`), local actions use `appears_on` -- an array of route names. This means one action can appear on multiple pages.

```yaml
my_module.add_product:
  route_name: my_module.product.add
  title: 'Add product'
  appears_on:
    - my_module.product.collection
    - system.admin_content
```

## Contextual links (.links.contextual.yml)

Contextual links appear in the contextual links dropdown (the pencil icon) on rendered components. They provide quick access to related actions without navigating away.

### When to create contextual links

- A rendered component (block, field, view) should link to its configuration page
- You want to provide edit/configure shortcuts on rendered output

### module_name.links.contextual.yml

```yaml
my_module.configure:
  title: 'Configure greeting'
  route_name: my_module.settings
  group: my_module
```

### Contextual link properties

| Property | Purpose | Required |
|----------|---------|----------|
| `title` | Link text in the contextual menu | Yes |
| `route_name` | Route the link points to | Yes |
| `group` | Groups contextual links together | Yes |
| `route_parameters` | Static route parameters | No |

### Attaching contextual links to render arrays

Contextual links must be attached to the render array of the component they apply to:

```php
$build['#contextual_links']['my_module'] = [
  'route_parameters' => [],
];
```

The key (`my_module`) matches the `group` defined in `.links.contextual.yml`. The `route_parameters` array passes any dynamic values needed by the linked route.

The template must print the `title_suffix` variable for contextual links to appear:

```twig
{{ title_suffix }}
```

## Summary of YAML files

| File | Purpose | Key Property | Shows As |
|------|---------|--------------|----------|
| `.links.menu.yml` | Site navigation links | `menu_name` | Menu items |
| `.links.task.yml` | Page tabs | `base_route` | Tabs above content |
| `.links.action.yml` | Action buttons | `appears_on` | "+ Add" buttons |
| `.links.contextual.yml` | Quick-access links | `group` | Pencil dropdown |

Each file is independent -- create only the ones your module needs. All menu link types are defined declaratively in YAML and cached by Drupal. Clear caches after changes.
