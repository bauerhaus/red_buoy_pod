<?php

namespace Drupal\redbuoy_media_pod\Form;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to manage the list of podcast feeds.
 */
class FeedListForm extends FormBase {

  /**
   * Transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new FeedListForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger,
    TransliterationInterface $transliteration,
  ) {
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new FeedListForm(
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('transliteration'),
    );
  }

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
      $config = $this->configFactory->getEditable('redbuoy_media_pod.settings');
      $feeds = array_filter($config->get('feeds') ?? [], fn($f) => $f !== $feed_to_delete);
      $config->set('feeds', array_values($feeds))->save();

      // Delete per-feed config.
      $this->configFactory->getEditable("redbuoy_media_pod.settings.$feed_to_delete")->delete();

      $this->messenger->addStatus("Feed '$feed_to_delete' deleted.");
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('redbuoy_media_pod.settings');
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
      $label = $this->configFactory->get("redbuoy_media_pod.settings.$feed")->get('label') ?? $feed;
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
    $new_feed = $this->transliteration->transliterate($raw_feed, 'en');
    $new_feed = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $new_feed));
    $new_feed = trim($new_feed, '_');
    if (!$new_feed) {
      $this->messenger->addError('Please enter a feed name.');
      return;
    }
    // Store a human readable title.
    $this->configFactory->getEditable("redbuoy_media_pod.settings.$new_feed")
      ->set('label', $raw_feed)
      ->save();

    $config = $this->configFactory->getEditable('redbuoy_media_pod.settings');
    $feeds = $config->get('feeds') ?? [];

    if (in_array($new_feed, $feeds)) {
      $this->messenger->addWarning("Feed '$new_feed' already exists.");
      return;
    }

    $feeds[] = $new_feed;
    $config->set('feeds', $feeds)->save();

    $this->messenger->addStatus("Feed '$new_feed' added.");
  }

}
