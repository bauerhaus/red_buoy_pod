<?php

namespace Drupal\redbuoy_media_pod\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form to manage the list of podcast feeds.
 */
class FeedListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redbuoy_media_pod_feed_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFeedSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement()['#name'];
    if (preg_match('/^delete_(.+)$/', $trigger, $matches)) {
      $feed_to_delete = $matches[1];

      // Remove from the feed list.
      $config = \Drupal::configFactory()->getEditable('redbuoy_media_pod.settings');
      $feeds = array_filter($config->get('feeds') ?? [], fn($f) => $f !== $feed_to_delete);
      $config->set('feeds', array_values($feeds))->save();

      // Delete per-feed config.
      \Drupal::configFactory()->getEditable("redbuoy_media_pod.settings.$feed_to_delete")->delete();

      $this->messenger()->addStatus("Feed '$feed_to_delete' deleted.");
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('redbuoy_media_pod.settings');
    $feeds = $config->get('feeds') ?? [];

    $form['help_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Help: Podcast Feed Setup'),
      '#url' => Url::fromUri('internal:/admin/help/redbuoy_media_pod'),
      '#attributes' => ['class' => ['button', 'button--small']],
      '#weight' => -10,
    ];

    $form['feeds'] = [
      '#type' => 'table',
      '#header' => ['Feed Name', 'Feed URL', 'Edit', 'Delete'],
      '#empty' => 'No podcast feeds defined yet.',
    ];

    foreach ($feeds as $feed) {
      $label = \Drupal::config("redbuoy_media_pod.settings.$feed")->get('label') ?? $feed;
      $form['feeds'][$feed]['name'] = [
        '#markup' => $label,
      ];

      $form['feeds'][$feed]['view'] = [
        '#type' => 'link',
        '#title' => $this->t('View Feed'),
        '#url' => Url::fromRoute('redbuoy_media_pod.render_feed', ['feed' => $feed]),
        '#attributes' => ['target' => '_blank'],
      ];

      $form['feeds'][$feed]['edit'] = [
        '#type' => 'link',
        '#title' => $this->t('Edit'),
        '#url' => Url::fromRoute('redbuoy_media_pod.feed_settings', ['feed' => $feed]),
      ];
      $form['feeds'][$feed]['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#name' => "delete_$feed",
        '#submit' => ['::deleteFeedSubmit'],
        '#limit_validation_errors' => [],
      ];
    }

    $form['new_feed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add new feed'),
      '#required' => FALSE,
    ];

    $form['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Feed'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $raw_feed = trim($form_state->getValue('new_feed'));
    // Machine name sanitization.
    $transliteration = \Drupal::service('transliteration');
    $new_feed = $transliteration->transliterate($raw_feed, 'en');
    $new_feed = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $new_feed));
    $new_feed = trim($new_feed, '_');
    if (!$new_feed) {
      $this->messenger()->addError('Please enter a feed name.');
      return;
    }
    // Store a human readable title.
    \Drupal::configFactory()->getEditable("redbuoy_media_pod.settings.$new_feed")
      ->set('label', $raw_feed)
      ->save();

    $config = \Drupal::configFactory()->getEditable('redbuoy_media_pod.settings');
    $feeds = $config->get('feeds') ?? [];

    if (in_array($new_feed, $feeds)) {
      $this->messenger()->addWarning("Feed '$new_feed' already exists.");
      return;
    }

    $feeds[] = $new_feed;
    $config->set('feeds', $feeds)->save();

    $this->messenger()->addStatus("Feed '$new_feed' added.");
  }

}
