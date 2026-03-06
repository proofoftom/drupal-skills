<?php

namespace Drupal\knowledge_resource\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Knowledge Resource entity.
 *
 * @ContentEntityType(
 *   id = "knowledge_resource",
 *   label = @Translation("Knowledge Resource"),
 *   label_collection = @Translation("Knowledge Resources"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\knowledge_resource\KnowledgeResourceListBuilder",
 *     "access" = "Drupal\knowledge_resource\KnowledgeResourceAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\knowledge_resource\Form\KnowledgeResourceForm",
 *       "add" = "Drupal\knowledge_resource\Form\KnowledgeResourceForm",
 *       "edit" = "Drupal\knowledge_resource\Form\KnowledgeResourceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "knowledge_resource",
 *   admin_permission = "administer knowledge resource entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/knowledge-resource/{knowledge_resource}",
 *     "add-form" = "/admin/content/knowledge-resource/add",
 *     "edit-form" = "/admin/content/knowledge-resource/{knowledge_resource}/edit",
 *     "delete-form" = "/admin/content/knowledge-resource/{knowledge_resource}/delete",
 *     "collection" = "/admin/content/knowledge-resource",
 *   }
 * )
 */
class KnowledgeResource extends ContentEntityBase implements KnowledgeResourceInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['related_topic'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related Topic'))
      ->setDescription(t('The node this resource is related to.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['author'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user who authored this resource.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resource_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Resource Type'))
      ->setDescription(t('The type of resource.'))
      ->setSettings([
        'allowed_values' => [
          'article' => 'Article',
          'research_paper' => 'Research Paper',
          'tool' => 'Tool',
          'documentation' => 'Documentation',
        ],
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the resource was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the resource was last edited.'));

    return $fields;
  }

}
