---
name: drupal-entities-fields
description: |
  Create Drupal content and config entity types with proper annotations/attributes,
  base field definitions, and entity handlers. Use when asked to create a custom
  entity type, define entity fields, set up entity forms and listings, or work
  with Drupal's Entity API.
---

# Drupal Entities and Fields

## What kind of entity do you need?

Drupal has two kinds of entity types. Choose based on what you are storing.

**Stores user-created data (nodes, products, orders, messages)?**
YES -> Create a **content entity type** (ContentEntityType). Stored in database tables. Supports fields, revisions, translations.

**Stores admin-defined configuration (content types, importers, workflows)?**
YES -> Create a **config entity type** (ConfigEntityType). Stored in YAML config files. Exported with config system.

### Content entity follow-up questions

**Does it need revisions?**
YES -> Add revision keys (`revision` in entity_keys), extend `EditorialContentEntityBase`, implement `RevisionLogInterface`.
NO -> Extend `ContentEntityBase`.

**Does it need bundles (subtypes like "article" and "page" for nodes)?**
YES -> Add `bundle_entity_type`, `bundle_label`, `bundle_of` (on the config entity), and `field_ui_base_route`. Create a companion ConfigEntityType to define bundles.
NO -> Skip bundle configuration.

**Does it need an admin UI?**
YES -> Add handlers (list_builder, form, route_provider) and links (canonical, add-form, edit-form, delete-form, collection).
NO -> Skip handlers and links. Entity is managed programmatically only.

## Content entity type definition

The entity type class goes in `src/Entity/` and must have either an annotation (D10) or attribute (D11.1+) at the top.

### D10 annotation syntax

```php
namespace Drupal\products\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Product entity.
 *
 * @ContentEntityType(
 *   id = "product",
 *   label = @Translation("Product"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\products\ProductListBuilder",
 *     "form" = {
 *       "default" = "Drupal\products\Form\ProductForm",
 *       "add" = "Drupal\products\Form\ProductForm",
 *       "edit" = "Drupal\products\Form\ProductForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "product",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/product/{product}",
 *     "add-form" = "/admin/structure/product/add",
 *     "edit-form" = "/admin/structure/product/{product}/edit",
 *     "delete-form" = "/admin/structure/product/{product}/delete",
 *     "collection" = "/admin/structure/product",
 *   }
 * )
 */
class Product extends ContentEntityBase implements ProductInterface {

  use EntityChangedTrait;

  // ... baseFieldDefinitions() and interface methods
}
```

### D11.1+ attribute syntax

```php
namespace Drupal\products\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\products\Form\ProductForm;
use Drupal\products\ProductListBuilder;

#[ContentEntityType(
  id: 'product',
  label: new TranslatableMarkup('Product'),
  handlers: [
    'view_builder' => EntityViewBuilder::class,
    'list_builder' => ProductListBuilder::class,
    'form' => [
      'default' => ProductForm::class,
      'add' => ProductForm::class,
      'edit' => ProductForm::class,
      'delete' => ContentEntityDeleteForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  base_table: 'product',
  admin_permission: 'administer site configuration',
  entity_keys: [
    'id' => 'id',
    'label' => 'name',
    'uuid' => 'uuid',
  ],
  links: [
    'canonical' => '/admin/structure/product/{product}',
    'add-form' => '/admin/structure/product/add',
    'edit-form' => '/admin/structure/product/{product}/edit',
    'delete-form' => '/admin/structure/product/{product}/delete',
    'collection' => '/admin/structure/product',
  ],
)]
class Product extends ContentEntityBase implements ProductInterface {

  use EntityChangedTrait;

  // ... baseFieldDefinitions() and interface methods
}
```

### Key syntax differences

| Feature | D10 annotation | D11.1+ attribute |
|---------|---------------|-----------------|
| Delimiter | `=` | `:` |
| Translation | `@Translation("Product")` | `new TranslatableMarkup('Product')` |
| Arrays | `{ "key" = "value" }` | `['key' => 'value']` |
| Class refs | `"Drupal\...\ClassName"` | `ClassName::class` |
| Wrapper | `/** @ContentEntityType(...) */` | `#[ContentEntityType(...)]` |

> WRONG: Using `=` signs inside `#[ContentEntityType(...)]` attribute syntax (e.g., `id = 'product'`). Attributes use PHP named parameters with `:` syntax.
> RIGHT: Use `id: 'product'` with colons inside attributes. The `=` syntax belongs to annotation docblocks only.

> WRONG: Using `@Translation("Product")` inside `#[ContentEntityType(...)]` attribute syntax. The `@Translation` annotation helper does not work in PHP attributes.
> RIGHT: Use `new TranslatableMarkup('Product')` in attributes. Import `Drupal\Core\StringTranslation\TranslatableMarkup` at the top of the file.

## Config entity type definition

Config entities store admin-defined settings and are exported to YAML. They extend `ConfigEntityBase` and define fields as class properties (not `baseFieldDefinitions()`).

### D10 annotation syntax

```php
namespace Drupal\products\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Importer entity.
 *
 * @ConfigEntityType(
 *   id = "importer",
 *   label = @Translation("Importer"),
 *   handlers = {
 *     "list_builder" = "Drupal\products\ImporterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\products\Form\ImporterForm",
 *       "edit" = "Drupal\products\Form\ImporterForm",
 *       "delete" = "Drupal\products\Form\ImporterDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "importer",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/importer/add",
 *     "edit-form" = "/admin/structure/importer/{importer}/edit",
 *     "delete-form" = "/admin/structure/importer/{importer}/delete",
 *     "collection" = "/admin/structure/importer"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "url",
 *     "plugin",
 *     "update_existing",
 *     "source"
 *   }
 * )
 */
class Importer extends ConfigEntityBase implements ImporterInterface {

  protected $id;
  protected $label;
  protected $url;
  protected $plugin;
  protected $update_existing = TRUE;
  protected $source;

  // ... getter methods from ImporterInterface
}
```

### D11.1+ attribute syntax

```php
namespace Drupal\products\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\products\Form\ImporterDeleteForm;
use Drupal\products\Form\ImporterForm;
use Drupal\products\ImporterListBuilder;

#[ConfigEntityType(
  id: 'importer',
  label: new TranslatableMarkup('Importer'),
  handlers: [
    'list_builder' => ImporterListBuilder::class,
    'form' => [
      'add' => ImporterForm::class,
      'edit' => ImporterForm::class,
      'delete' => ImporterDeleteForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  config_prefix: 'importer',
  admin_permission: 'administer site configuration',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  links: [
    'add-form' => '/admin/structure/importer/add',
    'edit-form' => '/admin/structure/importer/{importer}/edit',
    'delete-form' => '/admin/structure/importer/{importer}/delete',
    'collection' => '/admin/structure/importer',
  ],
  config_export: [
    'id',
    'label',
    'url',
    'plugin',
    'update_existing',
    'source',
  ],
)]
class Importer extends ConfigEntityBase implements ImporterInterface {

  protected $id;
  protected $label;
  protected $url;
  protected $plugin;
  protected $update_existing = TRUE;
  protected $source;

  // ... getter methods from ImporterInterface
}
```

> WRONG: Creating a ConfigEntityType without a `config/schema/*.schema.yml` file. The entity may appear to work initially, but config export, translation, and validation all require schema. This is the most commonly forgotten file for config entities.
> RIGHT: ALWAYS create a schema file alongside any config entity. Place it at `config/schema/module_name.schema.yml`.

> WRONG: Omitting the `config_export` list from the ConfigEntityType definition. Without it, the config entity cannot be exported and will not appear in `drush config:export` output.
> RIGHT: List every property that should be persisted in `config_export`. This includes `id`, `label`, and all custom fields.

### Config schema file (REQUIRED)

Every config entity MUST have a schema file at `config/schema/module_name.schema.yml`:

```yaml
products.importer.*:
  type: config_entity
  label: 'Importer config'
  mapping:
    id:
      type: string
      label: 'ID'
    label:
      type: label
      label: 'Label'
    url:
      type: uri
      label: 'Uri'
    plugin:
      type: string
      label: 'Plugin ID'
    update_existing:
      type: boolean
      label: 'Whether to update existing products'
    source:
      type: string
      label: 'The source of the products'
```

The wildcard `*` matches all instances. The `config_entity` type inherits standard entity properties (uuid, langcode, status, dependencies).

## Entity handlers -- what do you need?

Handlers provide the UI and behavior for entity types. Use defaults when possible.

**Do you need a custom list page?**
NO -> Use `EntityListBuilder` (content) or `ConfigEntityListBuilder` (config) as-is, or omit for no listing.
YES -> Extend `EntityListBuilder` and override `buildHeader()` + `buildRow()`.

**Do you need custom add/edit forms?**
NO for content entities -> Use `ContentEntityForm` directly. It auto-builds forms from base field definitions.
NO for config entities -> You MUST create a form class extending `EntityForm` because config entity fields are class properties, not base fields.
YES -> Extend `ContentEntityForm` (content) or `EntityForm` (config) and override `form()` / `save()`.

**Do you need custom routes?**
NO -> Use `AdminHtmlRouteProvider` in the `route_provider` handler. It auto-generates routes from your `links` definition.
YES -> Extend `DefaultHtmlRouteProvider` and override specific route methods.

**Do you need custom access control?**
NO -> Use `EntityAccessControlHandler` (default). It checks `admin_permission`.
YES -> Extend `EntityAccessControlHandler` and override `checkAccess()` / `checkCreateAccess()`.

> WRONG: Hand-writing routes in `.routing.yml` for entity CRUD operations (add, edit, delete, list) when `AdminHtmlRouteProvider` is specified as a handler. The route provider generates all routes from the `links` definition automatically.
> RIGHT: Use `AdminHtmlRouteProvider` as the route provider handler. Only create custom `.routing.yml` entries for non-standard routes that the provider does not generate.

> WRONG: Building entity forms manually with Form API elements when using content entities. `ContentEntityForm` auto-builds forms from `baseFieldDefinitions()` display options, so you only need to override `save()` for custom messages/redirects.
> RIGHT: For content entities, extend `ContentEntityForm` and let base field display options drive the form. Only override `form()` if you need non-field form elements.

## Base field definitions

Content entities define their fields in the `baseFieldDefinitions()` static method. Always call the parent method first to inherit id, uuid, and other standard fields.

```php
public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
  $fields = parent::baseFieldDefinitions($entity_type);

  $fields['name'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Name'))
    ->setDescription(t('The name of the Product.'))
    ->setSettings(['max_length' => 255])
    ->setDefaultValue('')
    ->setDisplayOptions('view', [
      'label' => 'hidden',
      'type' => 'string',
      'weight' => -4,
    ])
    ->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  $fields['created'] = BaseFieldDefinition::create('created')
    ->setLabel(t('Created'))
    ->setDescription(t('The time that the entity was created.'));

  $fields['changed'] = BaseFieldDefinition::create('changed')
    ->setLabel(t('Changed'))
    ->setDescription(t('The time that the entity was last edited.'));

  return $fields;
}
```

### Common base field types

| Type | Purpose | Widget | Formatter |
|------|---------|--------|-----------|
| `string` | Short text (255 chars) | `string_textfield` | `string` |
| `text_long` | Long text with format | `text_textarea` | `text_default` |
| `integer` | Whole number | `number` | `number_integer` |
| `boolean` | True/false | `boolean_checkbox` | `boolean` |
| `entity_reference` | Reference to another entity | `entity_reference_autocomplete` | `entity_reference_label` |
| `created` | Auto-set creation timestamp | (none needed) | `timestamp` |
| `changed` | Auto-set modification timestamp | (none needed) | `timestamp` |
| `email` | Email address | `email_default` | `email_mailto` |
| `uri` | URL/URI | `uri` | `uri_link` |

Display options control how the field appears. `setDisplayOptions('form', ...)` sets the form widget. `setDisplayOptions('view', ...)` sets the view formatter. `setDisplayConfigurable('form', TRUE)` allows admin UI configuration.

For file and image fields, see `references/files-images.md` in this skill directory.

## Entity interface and complete file ecosystem

Every entity type should have an interface. Content entity interfaces extend `ContentEntityInterface`. Config entity interfaces extend `ConfigEntityInterface`.

```php
namespace Drupal\products\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

interface ProductInterface extends ContentEntityInterface, EntityChangedInterface {

  public function getName();
  public function setName($name);
  public function getCreatedTime();
  public function setCreatedTime($timestamp);
}
```

### Complete content entity file ecosystem

```
modules/custom/products/
  products.info.yml
  products.links.menu.yml
  products.links.action.yml
  products.permissions.yml          # If defining custom permissions
  src/
    Entity/
      Product.php                   # Content entity class
      ProductInterface.php          # Entity interface (optional but recommended)
    ProductListBuilder.php          # List builder handler
    Form/
      ProductForm.php               # Add/edit form handler
```

Every PHP handler class in the annotation/attribute MUST exist as a file. If you reference `ProductListBuilder` in handlers, the file must exist.

### Complete config entity file ecosystem

```
modules/custom/products/
  products.info.yml
  products.links.menu.yml
  products.links.action.yml
  config/
    schema/
      products.schema.yml           # REQUIRED for config export
  src/
    Entity/
      Importer.php                  # Config entity class
      ImporterInterface.php         # Entity interface
    ImporterListBuilder.php         # List builder handler
    Form/
      ImporterForm.php              # Add/edit form (MUST build form manually)
      ImporterDeleteForm.php        # Delete confirmation form
```

Config entities require a delete form handler (extend `EntityConfirmFormBase`) because there is no `ContentEntityDeleteForm` equivalent for config entities.

### Menu and action links

```yaml
# products.links.menu.yml
entity.product.collection:
  title: 'Product list'
  route_name: entity.product.collection
  description: 'List Product entities'
  parent: system.admin_structure
  weight: 100
```

```yaml
# products.links.action.yml
entity.product.add_form:
  route_name: 'entity.product.add_form'
  title: 'Add Product'
  appears_on:
    - entity.product.collection
```

## Cross-references

See also: **drupal-module-scaffold** (if installed) for module creation, .info.yml setup, and PSR-4 namespace structure. If not available, ensure you create a `module_name.info.yml` with `core_version_requirement: ^10 || ^11` and place all PHP classes under `src/`.

See also: **drupal-routing-controllers** (if installed) for custom routes beyond entity route providers, controller patterns, and service injection. If not available, use `AdminHtmlRouteProvider` for entity CRUD routes and only add `.routing.yml` entries for non-entity routes.

See also: **drupal-forms-api** (if installed) for form customization beyond default entity forms. If not available, extend `ContentEntityForm` for content entities (auto-builds from base fields) or `EntityForm` for config entities (manual form building required).

For file and image field handling, see `references/files-images.md` in this skill directory.
