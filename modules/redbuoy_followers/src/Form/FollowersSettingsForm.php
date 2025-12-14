<?php

namespace Drupal\redbuoy_followers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FollowersSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redbuoy_followers_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // We’ll update per-feed configs directly, not this one.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $feeds = \Drupal::config('redbuoy_media_pod.settings')->get('feeds') ?? [];
    $form['feeds'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Podcast Feeds'),
      '#tree' => TRUE,
    ];

    foreach ($feeds as $feed_id) {
      $feed_config = \Drupal::config("redbuoy_media_pod.settings.$feed_id");
      $enabled = (bool) $feed_config->get('followers_comments_enabled');
      $label = function_exists('redbuoy_media_pod_feed_label') ? redbuoy_media_pod_feed_label($feed_id) : ucfirst($feed_id);

      $form['feeds'][$feed_id] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable comments for %feed', ['%feed' => $label]),
        '#default_value' => $enabled,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $feeds = \Drupal::config('redbuoy_media_pod.settings')->get('feeds') ?? [];
    $values = $form_state->getValue(['feeds']) ?: [];
    foreach ($feeds as $feed_id) {
      $enable = !empty($values[$feed_id]);
      \Drupal::configFactory()->getEditable("redbuoy_media_pod.settings.$feed_id")
        ->set('followers_comments_enabled', $enable)
        ->save();
    }
    parent::submitForm($form, $form_state);
    $this->messenger()->addMessage($this->t('Feed comment settings saved.'));
  }
}
