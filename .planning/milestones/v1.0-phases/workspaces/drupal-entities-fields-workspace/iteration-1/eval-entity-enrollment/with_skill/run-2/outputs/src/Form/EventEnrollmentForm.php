<?php

namespace Drupal\event_enrollment\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the EventEnrollment entity add/edit forms.
 */
class EventEnrollmentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('Event enrollment %id has been created.', [
        '%id' => $entity->id(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('Event enrollment %id has been updated.', [
        '%id' => $entity->id(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $status;
  }

}
