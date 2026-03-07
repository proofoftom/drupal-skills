<?php

namespace Drupal\event_enrollment\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the event enrollment entity edit forms.
 */
class EventEnrollmentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%id' => $entity->id()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New event enrollment %id has been created.', $message_arguments));
    }
    else {
      $this->messenger()->addStatus($this->t('The event enrollment %id has been updated.', $message_arguments));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
