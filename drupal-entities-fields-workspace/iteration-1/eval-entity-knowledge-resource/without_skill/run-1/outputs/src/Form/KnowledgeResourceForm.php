<?php

namespace Drupal\knowledge_resource\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the Knowledge Resource add/edit forms.
 */
class KnowledgeResourceForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('Created the Knowledge Resource.'));
    }
    else {
      $this->messenger()->addMessage($this->t('Saved the Knowledge Resource.'));
    }

    $form_state->setRedirect('entity.knowledge_resource.canonical', [
      'knowledge_resource' => $entity->id(),
    ]);

    return $status;
  }

}
