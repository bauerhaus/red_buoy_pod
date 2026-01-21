<?php

namespace Drupal\redbuoy_media_pod\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Prevents Drupal's RssResponseRelativeUrlFilter from processing our feeds.
 */
class BypassRssFilterSubscriber implements EventSubscriberInterface {

  /**
   * Marks our RSS responses to bypass Drupal's filter.
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();

    // Only process RSS responses from our module
    if (stripos($response->headers->get('Content-Type', ''), 'application/rss+xml') === FALSE) {
      return;
    }

    // Check if this is our feed by looking at the route
    $request = $event->getRequest();
    $route_name = $request->attributes->get('_route');

    if ($route_name === 'redbuoy_media_pod.podcast_feed') {
      // Change Content-Type temporarily to bypass Drupal's filter
      $response->headers->set('X-Original-Content-Type', 'application/rss+xml');
      $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
    }
  }

  /**
   * Restores the original Content-Type after Drupal's filter runs.
   */
  public function onResponseLate(ResponseEvent $event) {
    $response = $event->getResponse();

    // Restore original Content-Type if we changed it
    if ($response->headers->has('X-Original-Content-Type')) {
      $response->headers->set('Content-Type', $response->headers->get('X-Original-Content-Type'));
      $response->headers->remove('X-Original-Content-Type');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    // Run before Drupal's RssResponseRelativeUrlFilter (priority -512)
    $events[KernelEvents::RESPONSE][] = ['onResponse', -511];
    // Run after Drupal's filter to restore Content-Type
    $events[KernelEvents::RESPONSE][] = ['onResponseLate', -513];
    return $events;
  }

}
