# Bundled Entity Types

Content entities with subtypes (like nodes having "article" and "page" types) need TWO entity types working together: a content entity that declares `bundle_entity_type`, and a config entity that declares `bundle_of`.

## The bundle_of pattern

### D11.1+ attribute syntax (preferred)

Content entity (e.g., Message):

```php
#[ContentEntityType(
  id: 'message',
  label: new TranslatableMarkup('Message'),
  bundle_entity_type: 'message_type',
  bundle_label: new TranslatableMarkup('Message type'),
  entity_keys: [
    'id' => 'id',
    'bundle' => 'bundle',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  field_ui_base_route: 'entity.message_type.edit_form',
)]
class Message extends ContentEntityBase { }
```

Config entity -- the bundle definition:

```php
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

#[ConfigEntityType(
  id: 'message_type',
  label: new TranslatableMarkup('Message type'),
  bundle_of: 'message',
  config_prefix: 'type',
  config_export: ['id', 'label'],
)]
class MessageType extends ConfigEntityBundleBase { }
```

### D10 annotation syntax (fallback)

```php
/**
 * @ContentEntityType(
 *   id = "message",
 *   bundle_entity_type = "message_type",
 *   bundle_label = @Translation("Message type"),
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   field_ui_base_route = "entity.message_type.edit_form",
 * )
 */
class Message extends ContentEntityBase { }

/**
 * @ConfigEntityType(
 *   id = "message_type",
 *   bundle_of = "message",
 *   config_prefix = "type",
 *   config_export = { "id", "label" },
 * )
 */
class MessageType extends ConfigEntityBundleBase { }
```

> WRONG: Creating a content entity with `bundle_entity_type = "message_type"` but forgetting `bundle_of = "message"` on the config entity. The content entity declares it has bundles, but the config entity does not know it provides bundles. Result: Field UI has no bundle-specific field management.
> RIGHT: Both sides MUST be configured. The content entity has `bundle_entity_type` and `entity_keys.bundle`, and the config entity has `bundle_of` pointing back. The bundle config entity MUST extend `ConfigEntityBundleBase` (NOT `ConfigEntityBase`) -- it provides bundle-aware deletion and field cleanup methods.

## hook_update_N() for adding base fields

When adding a new base field to an existing entity type via `baseFieldDefinitions()`, you MUST also provide a `hook_update_N()` that installs the field storage. Without this, existing sites get "Unknown column" SQL errors.

```php
/**
 * Add the 'priority' base field to the task entity.
 */
function my_module_update_10001() {
  $field_storage_definition = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Priority'))
    ->setDescription(t('Task priority level.'))
    ->setSettings([
      'allowed_values' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
      ],
    ])
    ->setDefaultValue('medium')
    ->setDisplayOptions('form', [
      'type' => 'options_select',
    ]);

  \Drupal::entityDefinitionUpdateManager()
    ->installFieldStorageDefinition(
      'priority',
      'task',
      'my_module',
      $field_storage_definition
    );
}
```

> WRONG: Adding a new field to `baseFieldDefinitions()` without a `hook_update_N()`. Works on fresh installs, but existing sites get "Unknown column" SQL errors because the database table lacks the new column.
> RIGHT: ALWAYS pair `baseFieldDefinitions()` changes with a `hook_update_N()` that calls `\Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition()`. Repeat the field definition in the update hook -- do not reference the entity class, because entity class definitions may change between updates.
