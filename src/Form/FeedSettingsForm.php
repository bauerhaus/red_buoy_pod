<?php

namespace Drupal\redbuoy_media_pod\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\redbuoy_media_pod\FeedTextSanitizer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides settings for an individual podcast feed.
 */
class FeedSettingsForm extends ConfigFormBase {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $injectedRouteMatch;

  /**
   * The entity storage manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityTypeManager;

  /**
   * The services config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * DependencyInjectionDemonstration constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $injectedRouteMatch
   *   The matching routes.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The Module Extension List.
   */
  final public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    RouteMatchInterface $injectedRouteMatch,
    ModuleExtensionList $moduleExtensionList,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->routeMatch = $injectedRouteMatch;
    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('current_route_match'),
      $container->get('extension.list.module')
    );
  }

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
    $feed = $this->routeMatch->getParameter('feed');
    return ["redbuoy_media_pod.settings.$feed"];
  }

  /**
   * Loads field definitions from feed_settings.json.
   */
  private function getFieldDefinitions(): array {
    static $fields = NULL;
    if ($fields === NULL) {
      $path = DRUPAL_ROOT . '/' . $this->moduleExtensionList->getPath('redbuoy_media_pod') . '/config/feed_settings.json';
      if (file_exists($path)) {
        $fields = json_decode(file_get_contents($path), TRUE);
      }
    }
    return $fields ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $feed = $this->routeMatch->getParameter('feed');
    $config = $this->config("redbuoy_media_pod.settings.$feed");
    // Build the feed URL.
    $feed_url = Url::fromUri("https://www.castfeedvalidator.com/validate.php", [
      'query' => [
        'url' => Url::fromRoute('redbuoy_media_pod.render_feed', ['feed' => $feed], ['absolute' => TRUE])->toString(),
      ],
    ])->toString();

    // Check for existing episodes.
    /** @var \Drupal\node\NodeStorage $storage */
    $storage = $this->entityTypeManager->getStorage('node');
    $nids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('type', 'podcast_episode')
      ->condition('field_podcast_feed', $feed)
      ->range(0, 1)
      ->execute();
    $has_content = !empty($nids);

    /* import added fields from JSON file */
    // These labels come from site config and are not translatable.
    foreach ($this->getFieldDefinitions() as $def) {

      // Handle group fields.
      if ($def['type'] === 'group' && !empty($def['children'])) {
        foreach ($def['children'] as $child) {
          $name = $child['name'];
          $form[$name] = [
            '#type' => 'textfield',
            '#title' => ucwords(str_replace('_', ' ', $name)),
            '#default_value' => $config->get($name) ?? '',
          ];
        }

        // Handle the group own attribute if it's mapped to a separate setting.
        foreach ($def['attribute'] as $attr => $source) {
          $form[$source] = [
            '#type' => 'textfield',
            '#title' => ucwords(str_replace('_', ' ', $source)),
            '#default_value' => $config->get($source) ?? '',
          ];
        }

        continue;
      }

      if ($def['type'] === 'text_format') {
        $value = $config->get($def['name']) ?? $def['default'] ?? ['value' => '', 'format' => 'full_html'];
        $form[$def['name']] = [
          '#type' => 'text_format',
          '#title' => $def['label'],
          '#format' => $value['format'] ?? 'full_html',
          '#default_value' => $value['value'] ?? '',
          '#description' => $def['description'] ?? '',
        ];
        continue;
      }

      $element = [
        '#type' => $def['type'],
        '#title' => $def['label'],
        '#default_value' => $config->get($def['name']) ?? ($def['default'] ?? ''),
      ];

      if (!empty($def['description'])) {
        $element['#description'] = $def['description'];
      }

      if ($def['type'] === 'select' && isset($def['options'])) {
        $element['#options'] = $def['options'];
      }

      if ($def['type'] === 'checkbox') {
        $element['#default_value'] = (bool) ($config->get($def['name']) ?? $def['default'] ?? FALSE);
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
    $feed = $this->routeMatch->getParameter('feed');
    $config = $this->configFactory()->getEditable("redbuoy_media_pod.settings.$feed");

    foreach ($this->getFieldDefinitions() as $def) {
      // Handle text_format fields.
      if ($def['type'] === 'text_format') {
        $value = $form_state->getValue($def['name']);
        if (is_array($value) && isset($value['value'])) {
          $cleaned_value = FeedTextSanitizer::sanitizeAscii($value['value']);
          $config->set($def['name'], [
            'value' => $cleaned_value,
            'format' => $value['format'] ?? 'basic_html',
          ]);
        }

        continue;
      }

      // Handle group fields and their children.
      if ($def['type'] === 'group') {
        if (!empty($def['attribute'])) {
          foreach ($def['attribute'] as $attr => $source) {
            $config->set($source, FeedTextSanitizer::sanitizeAscii($form_state->getValue($source)));

          }
        }

        if (!empty($def['children'])) {
          foreach ($def['children'] as $child) {
            $name = $child['name'];
            $config->set($name, FeedTextSanitizer::sanitizeAscii($form_state->getValue($name)));

          }
        }
        continue;
      }

      // Default: scalar fields.
      $value = $form_state->getValue($def['name']);
      if (is_string($value)) {
        $value = FeedTextSanitizer::sanitizeAscii($value);
      }
      $config->set($def['name'], $value);

    }

    $config->save();
  }

}
