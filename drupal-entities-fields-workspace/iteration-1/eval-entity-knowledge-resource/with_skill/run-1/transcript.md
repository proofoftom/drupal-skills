# Transcript: KnowledgeResource Entity Module (with_skill, run-1)

## Task

Create a custom content entity type called `KnowledgeResource` in a module called `knowledge_resource` for an Open Social Drupal 10 site. The entity needs:
- `related_topic` — entity_reference to node
- `author` — entity_reference to user
- `resource_type` — list_string (article, research_paper, tool, documentation)
- `created` — timestamp

Include all handlers: list_builder, form, access, route_provider.

## Skill Used

`drupal-entities-fields` — Read SKILL.md and `references/files-images.md` before starting.

## Decision Points from Skill

1. **Content entity, not config entity** — stores user-created data (learning resources), not admin config. Extend `ContentEntityBase`.
2. **No revisions needed** — simple use case, no `revision` entity key or `RevisionLogInterface`.
3. **No bundles** — single entity type, no companion ConfigEntityType needed.
4. **Admin UI needed** — included `list_builder`, `form`, `access`, and `route_provider` handlers plus all CRUD links.
5. **D10 annotation syntax** — the site is Drupal 10, so used `@ContentEntityType(...)` docblock annotation with `=` signs and `@Translation()` wrappers (not D11 attribute syntax with `:` and `new TranslatableMarkup()`).
6. **ContentEntityForm** — used for add/edit; it auto-builds forms from `baseFieldDefinitions()` display options. Only overrode `save()` for redirect and status messages.
7. **Custom access handler** — extended `EntityAccessControlHandler` and overrode `checkAccess()` / `checkCreateAccess()` with granular permissions (view, add, edit, delete, administer).
8. **AdminHtmlRouteProvider** — used as route_provider so all CRUD routes auto-generate from the `links` definition. No manual `.routing.yml` entries needed.

## Files Created

```
knowledge_resource/
  knowledge_resource.info.yml
  knowledge_resource.permissions.yml
  knowledge_resource.links.menu.yml
  knowledge_resource.links.action.yml
  src/
    Entity/
      KnowledgeResource.php          # Content entity with @ContentEntityType annotation
      KnowledgeResourceInterface.php # Extends ContentEntityInterface + EntityChangedInterface
    KnowledgeResourceListBuilder.php # Extends EntityListBuilder, shows id/type/author/topic/created
    KnowledgeResourceAccessControlHandler.php  # Granular per-operation permissions
    Form/
      KnowledgeResourceForm.php      # Extends ContentEntityForm, overrides save()
```

## Base Field Definitions

- `related_topic`: `entity_reference` → `node`, widget: `entity_reference_autocomplete`
- `author`: `entity_reference` → `user`, widget: `entity_reference_autocomplete`
- `resource_type`: `list_string` with allowed values `article`, `research_paper`, `tool`, `documentation`; widget: `options_select`
- `created`: `created` (auto-set timestamp)
- `changed`: `changed` (auto-updated timestamp, from `EntityChangedTrait`)

## Verification

```
$ ddev drush cr
[success] Cache rebuild complete.

$ ddev drush en knowledge_resource -y
[success] Module knowledge_resource has been installed. (Permissions)

$ ddev drush php-eval "echo \Drupal::entityTypeManager()->hasDefinition('knowledge_resource') ? 'installed' : 'missing';"
installed

$ ddev drush php-eval "\$s = \Drupal::entityTypeManager()->getStorage('knowledge_resource'); \$e = \$s->create(['resource_type' => 'article']); \$e->save(); echo 'id:' . \$e->id();"
id:1
```

All three verification steps passed on the first attempt.

## Notes

- `EntityChangedTrait` is used on the entity class to implement `EntityChangedInterface` (required by `getChangedTime()` / `setChangedTime()`).
- The `admin_permission` in the annotation (`administer knowledge resource entities`) is the fallback permission checked by `EntityAccessControlHandler` for any operation if no custom check matches.
- Menu link parent is `system.admin_content` (the standard Content admin page in Open Social/Drupal), which is more appropriate than `system.admin_structure` for a content entity.
