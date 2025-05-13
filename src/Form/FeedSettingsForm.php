<?php

namespace Drupal\redbuoy_media_pod\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides settings for an individual podcast feed.
 */
class FeedSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redbuoy_media_pod_feed_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $feed = \Drupal::routeMatch()->getParameter('feed');
    return ["redbuoy_media_pod.settings.$feed"];
  }

  /**
   * Loads field definitions from feed_settings.json.
   */
  private function getFieldDefinitions(): array {
    static $fields = NULL;
    if ($fields === NULL) {
      $path = DRUPAL_ROOT . '/' . \Drupal::service('extension.list.module')->getPath('redbuoy_media_pod') . '/config/feed_settings.json';
      if (file_exists($path)) {
        $fields = json_decode(file_get_contents($path), true);
      }
    }
    return $fields ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $feed = \Drupal::routeMatch()->getParameter('feed');
    $config = $this->config("redbuoy_media_pod.settings.$feed");
    // Build the feed URL.
    $feed_url = Url::fromUri("https://www.castfeedvalidator.com/validate.php", [
      'query' => [
        'url' => Url::fromRoute('redbuoy_media_pod.render_feed', ['feed' => $feed], ['absolute' => TRUE])->toString(),
      ],
    ])->toString();

    // Check for existing episodes.
    $nids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('type', 'podcast_episode')
      ->condition('field_podcast_feed', $feed)
      ->range(0, 1)
      ->execute();
    $has_content = !empty($nids);

    /* import added fields from JSON file */
    foreach ($this->getFieldDefinitions() as $def) {

      // Handle group fields.
      if ($def['type'] === 'group' && !empty($def['children'])) {
        foreach ($def['children'] as $child) {
          $name = $child['name'];
          $form[$name] = [
            '#type' => 'textfield',
            '#title' => $this->t(ucwords(str_replace('_', ' ', $name))),
            '#default_value' => $config->get($name) ?? '',
          ];
        }

        // Handle the group’s own attribute if it's mapped to a separate setting.
        foreach ($def['attribute'] as $attr => $source) {
          $form[$source] = [
            '#type' => 'textfield',
            '#title' => $this->t(ucwords(str_replace('_', ' ', $source))),
            '#default_value' => $config->get($source) ?? '',
          ];
        }

        continue;
      }

      if ($def['type'] === 'text_format') {
        $value = $config->get($def['name']) ?? $def['default'] ?? ['value' => '', 'format' => 'full_html'];
        $form[$def['name']] = [
          '#type' => 'text_format',
          '#title' => $this->t($def['label']),
          '#format' => $value['format'] ?? 'full_html',
          '#default_value' => $value['value'] ?? '',
          '#description' => $this->t($def['description'] ?? ''),
        ];
        continue;
      }

      $element = [
        '#type' => $def['type'],
        '#title' => $this->t($def['label']),
        '#default_value' => $config->get($def['name']) ?? ($def['default'] ?? ''),
      ];

      if (!empty($def['description'])) {
        $element['#description'] = $this->t($def['description']);
      }

      if ($def['type'] === 'select' && isset($def['options'])) {
        $element['#options'] = $def['options'];
      }

      if ($def['type'] === 'checkbox') {
        $element['#default_value'] = (bool) ($config->get($def['name']) ?? $def['default'] ?? false);
      }

      $form[$def['name']] = $element;
    }
    // Add a fieldset.
    $form['validator_tools'] = [
      '#type' => 'fieldset',
      '#title' => t('Podcast Feed Tools'),
      '#weight' => 100,
    ];

    $form['validator_tools']['castfeed_link'] = [
      '#type' => 'item',
      '#markup' => '<p><a href="' . $feed_url . '" target="_blank">Validate this feed on CastFeedValidator.com</a></p>',
    ];

    if (!$has_content) {
      $form['validator_tools']['empty_warning'] = [
        '#type' => 'markup',
        '#markup' => '<p style="color: red;"><strong>Warning:</strong> This feed currently has no published podcast episodes. The validator will report errors until at least one episode is available.</p>',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $feed = \Drupal::routeMatch()->getParameter('feed');
    $config = $this->configFactory()->getEditable("redbuoy_media_pod.settings.$feed");

    foreach ($this->getFieldDefinitions() as $def) {
      // Handle text_format fields
      if ($def['type'] === 'text_format') {
        $value = $form_state->getValue($def['name']);
        $config->set($def['name'], [
          'value' => $value['value'],
          'format' => $value['format'],
        ]);
        continue;
      }

      // Handle group fields and their children
      if ($def['type'] === 'group') {
        if (!empty($def['attribute'])) {
          foreach ($def['attribute'] as $attr => $source) {
            $config->set($source, $form_state->getValue($source));
          }
        }

        if (!empty($def['children'])) {
          foreach ($def['children'] as $child) {
            $name = $child['name'];
            $config->set($name, $form_state->getValue($name));
          }
        }
        continue;
      }

      // Default: scalar fields
      $config->set($def['name'], $form_state->getValue($def['name']));
    }

    $config->save();
  }


}
