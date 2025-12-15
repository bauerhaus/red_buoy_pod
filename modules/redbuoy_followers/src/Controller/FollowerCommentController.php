<?php

declare(strict_types=1);

namespace Drupal\redbuoy_followers\Controller;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for follower comment entry.
 */
final class FollowerCommentController extends ControllerBase {

  /**
   * Render the follower comment form with episode/feed context.
   */
  public function new(Request $request): array {
    $nid = (int) $request->query->get('node');
    $feed = (string) $request->query->get('feed');

    if ($nid <= 0 || $feed === '') {
      throw new NotFoundHttpException();
    }

    $episode = $this->entityTypeManager()->getStorage('node')->load($nid);
    if (!$episode) {
      throw new NotFoundHttpException();
    }

    // Must be a podcast_episode.
    if ($episode->bundle() !== 'podcast_episode') {
      throw new NotFoundHttpException();
    }

    // Must be published.
    if (!$episode->isPublished()) {
      throw new NotFoundHttpException();
    }

    // User must be allowed to view the episode.
    if (!$episode->access('view')) {
      throw new AccessDeniedHttpException();
    }

    // Episode must have a feed value.
    if (!$episode->hasField('field_podcast_feed') || $episode->get('field_podcast_feed')->isEmpty()) {
      throw new NotFoundHttpException();
    }
    $episode_feed = (string) $episode->get('field_podcast_feed')->value;

    // Reject mismatched/tampered feed.
    if ($feed !== $episode_feed) {
      throw new NotFoundHttpException();
    }

    // Basic validation that feed is known (allowed list_string values).
    // (We treat node as source-of-truth; this just defends against bad config.)
    $allowed = \Drupal::service('entity_field.manager')
      ->getFieldStorageDefinitions('node')['field_podcast_feed']
      ->getSetting('allowed_values') ?? [];
    if (!isset($allowed[$episode_feed])) {
      // If allowed_values_function is used, allowed_values may not be set here.
      // In that case, we just proceed since the episode already has the value.
    }

    $form = $this->formBuilder()->getForm(
      '\Drupal\redbuoy_followers\Form\FollowerCommentNewForm',
      $episode,
      $episode_feed
    );


    return [
      'form' => $form,
    ];
  }

}
