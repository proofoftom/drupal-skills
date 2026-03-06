<?php

namespace Drupal\site_announcements\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for site announcement configuration.
 */
class AnnouncementsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'site_announcements_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['site_announcements.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('site_announcements.settings');

    $form['announcement_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Announcement text'),
      '#description' => $this->t('The text to display as the site announcement.'),
      '#default_value' => $config->get('announcement_text'),
      '#required' => TRUE,
    ];

    $form['display_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable display'),
      '#description' => $this->t('Whether the announcement should be displayed to users.'),
      '#default_value' => $config->get('display_enabled'),
    ];

    $form['max_display_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum display count'),
      '#description' => $this->t('The maximum number of times the announcement is shown per user.'),
      '#default_value' => $config->get('max_display_count'),
      '#min' => 1,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable('site_announcements.settings')
      ->set('announcement_text', $form_state->getValue('announcement_text'))
      ->set('display_enabled', (bool) $form_state->getValue('display_enabled'))
      ->set('max_display_count', (int) $form_state->getValue('max_display_count'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
