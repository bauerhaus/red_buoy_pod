<?php

namespace Drupal\redbuoy_media_pod\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\node\Entity\Node;

/**
 * Returns RSS feed output for a specific podcast feed.
 */
class PodcastFeedController extends ControllerBase {

  /**
   * Render the RSS feed for a given feed name.
   *
   * @param string $feed
   *   The feed name (e.g. 'default', 'harmonia').
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function renderFeed($feed): Response {
    $feeds = \Drupal::config('redbuoy_media_pod.settings')->get('feeds') ?? [];
    if (!in_array($feed, $feeds)) {
      throw new NotFoundHttpException("Podcast feed '$feed' not found.");
    }

    // Load per-feed config and schema.
    $config = \Drupal::config("redbuoy_media_pod.settings.$feed");

    $settings = [];



    // Query all episode nodes matching the feed.
    $nids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('type', 'podcast_episode')
      ->condition('field_podcast_feed', $feed)
      ->sort('created', 'DESC')
      ->range(0, 100)
      ->execute();

    $nodes = Node::loadMultiple($nids);
    $rss_items = "";
    $last_modified_ts = 0;
    foreach ($nodes as $node) {
      // Get variables from this node.
      $file = $node->get('field_audio_file')->entity ?? null;
      $size = filesize($file->getFileUri());
      $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
      $ts = strtotime($node->get('field_podcast_date')->value ?? '') ?: $node->getCreatedTime();
      $duration = htmlspecialchars($node->get('field_duration')->value ?? '');
      $explicit = htmlspecialchars($node->get('field_explicit')->value ?? '');
      $episode_number = htmlspecialchars($node->get('field_episode_number')->value ?? '');
      $keywords = htmlspecialchars($node->get('field_keywords')->value ?? '');
      $subtitle = htmlspecialchars($node->get('field_subtitle')->value ?? '');
      $desc_html = $node->get('field_podcast_description')->value ?? '';
      $desc_html = str_replace(']]>', ']]&gt;', $desc_html);
      $desc_plain = $this->stripHtmlToPlainText($desc_html);
      $season = htmlspecialchars($node->get('field_season_number')->value ?? '');
      $type = htmlspecialchars($node->get('field_episode_type')->value ?? '');
      $author = htmlspecialchars($node->get('field_author')->value ?? '');
      $changed = $node->getChangedTime();
      if ($changed > $last_modified_ts) {
        $last_modified_ts = $changed;
      }
      // Write the item XML
      $rss_items .= "<item>\n";
      $rss_items .= "<title>{$node->label()}</title>\n";
      $rss_items .= "<link>{$node->toUrl('canonical', ['absolute' => TRUE])->toString()}</link>\n";
      $rss_items .= "<guid isPermaLink=\"false\">{$node->uuid()}</guid>\n";
      $rss_items .= "<enclosure url=\"{$url}\" length=\"{$size}\" type=\"audio/mpeg\" />\n";
      $rss_items .= "<pubDate>{$this->rfc2822($ts)}</pubDate>\n";
      $rss_items .= "<itunes:duration>$duration</itunes:duration>\n";
      $rss_items .= "<itunes:author>$author</itunes:author>\n";
      $rss_items .= "<itunes:explicit>$explicit</itunes:explicit>\n";
      $rss_items .= "<itunes:episode>$episode_number</itunes:episode>\n";
      $rss_items .= "<itunes:keywords>$keywords</itunes:keywords>\n";
      $rss_items .= "<itunes:subtitle>$subtitle</itunes:subtitle>\n";
      $rss_items .= "<itunes:summary><![CDATA[{$desc_plain}]]></itunes:summary>\n";
      $rss_items .= "<description>{$desc_plain}</description>\n";
      $rss_items .= "<content:encoded><![CDATA[{$desc_html}]]></content:encoded>\n";
      $rss_items .= "<itunes:season>$season</itunes:season>\n";
      $rss_items .= "<itunes:episodeType>$type</itunes:episodeType>\n";
      $rss_items .= "</item>\n";
    }
    if ($last_modified_ts === 0) {
        $last_modified_ts = time();
    }

    // Gather the channel data.
    $config = \Drupal::config("redbuoy_media_pod.settings.$feed");
    $settings = $config->getRawData();
    $pod_title = htmlspecialchars($settings['podcast_title'] ?? '');
    $pod_keywords = htmlspecialchars($settings['podcast_keywords'] ?? '');
    $desc_raw = $config->get('podcast_description') ?? '';
    $desc_value = is_array($desc_raw) ? ($desc_raw['value'] ?? '') : $desc_raw;
    $desc_plain = $this->stripHtmlToPlainText($desc_value);
    $desc_cdata = str_replace(']]>', ']]&gt;', $desc_value);
    $pod_lang = htmlspecialchars($settings['podcast_language'] ?? '');
    $pod_expl = htmlspecialchars($settings['itunes_explicit'] ?? '');
    $pod_auth = htmlspecialchars($settings['itunes_author'] ?? '');
    $pod_own_name = htmlspecialchars($settings['podcast_owner_name'] ?? '');
    $pod_own_emai = htmlspecialchars($settings['podcast_owner_email'] ?? '');
    $pod_category = htmlspecialchars($config->get('podcast_category') ?? '');
    $podcast_link = htmlspecialchars($config->get('podcast_link') ?? '');
    $pod_subcategory = htmlspecialchars($config->get('podcast_sub_category') ?? '');
    $pod_image_url = htmlspecialchars($config->get('podcast_image_url') ?? '');
    $podcast_type = htmlspecialchars($config->get('itunes_type') ?? 'episodic');
    $podcast_copyright = htmlspecialchars($config->get('podcast_copyright') ?? 'episodic');

    $rss_channel = "";
    $rss_channel .= "<title>$pod_title</title>\n";
    $rss_channel .= "<itunes:keywords>$pod_keywords</itunes:keywords>\n";
    $rss_channel .= "<description><![CDATA[{$desc_plain}]]></description>\n";
    $rss_channel .= "<itunes:summary><![CDATA[{$desc_plain}]]></itunes:summary>\n";
    $rss_channel .= "<content:encoded><![CDATA[{$desc_cdata}]]></content:encoded>\n";
    $rss_channel .= "<language>$pod_lang</language>\n";
    $rss_channel .= "<itunes:explicit>$pod_expl</itunes:explicit>\n";
    $rss_channel .= "<itunes:author>$pod_auth</itunes:author>\n";
    $rss_channel .= "<itunes:owner>\n";
    $rss_channel .= "<itunes:name>$pod_own_name</itunes:name>\n";
    $rss_channel .= "<itunes:email>$pod_own_emai</itunes:email>\n";
    $rss_channel .= "</itunes:owner>\n";
    if ($pod_category !== '') {
      $rss_channel .= "<itunes:category text=\"{$pod_category}\">";
      if ($pod_subcategory !== '') {
        $rss_channel .= "<itunes:category text=\"{$pod_subcategory}\" />";
      }
      $rss_channel .= "</itunes:category>\n";
    }
    if ($pod_image_url !== '') {
      $rss_channel .= "<itunes:image href=\"{$pod_image_url}\" />\n";
    }
    if ($podcast_link !== '') {
      $rss_channel .= "<link>{$podcast_link}</link>\n";
    }
    if ($podcast_type !== '') {
      $rss_channel .= "<itunes:type>{$podcast_type}</itunes:type>\n";
    }
    if ($podcast_copyright !== '') {
      $rss_channel .= "<copyright>{$podcast_copyright}</copyright>\n";
    }

    $rss = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
     xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
     xmlns:content="http://purl.org/rss/1.0/modules/content/">

  <channel>
    {$rss_channel}
    {$rss_items}
  </channel>
</rss>
XML;

  return $this->checkClientCacheAndHeaders($rss, $last_modified_ts);

  }


  private function rfc2822($timestamp): string {
    return gmdate(DATE_RSS, $timestamp);
  }

  private function joinItems(array $items): string {
    return implode("\n", $items);
  }

  // `stripHtmlToPlainText()` remains in case you want to use it for <description>
  private function stripHtmlToPlainText(string $html): string {
    $html = preg_replace('/<(br|\/p|\/div|\/li)>/i', "\n", $html);
    $text = strip_tags($html);
    $text = preg_replace("/[\r\n]+/", "\n", $text);
    return trim($text);
  }

/**
 * Adds caching headers (ETag and Last-Modified) and returns 304 if not modified.
 *
 * @param string $rss
 *   The full RSS XML string.
 * @param int $last_modified_ts
 *   The Unix timestamp of the most recently changed content.
 *
 * @return \Symfony\Component\HttpFoundation\Response|null
 *   A 304 response if client cache is valid, or null to proceed with normal response.
 */
private function checkClientCacheAndHeaders(string $rss, int $last_modified_ts): ?Response {
  $etag = md5($rss);
  $last_modified_http = gmdate('D, d M Y H:i:s', $last_modified_ts) . ' GMT';

  $request = \Drupal::request();
  $if_modified_since = $request->headers->get('If-Modified-Since');
  $if_none_match = $request->headers->get('If-None-Match');

  if ($if_modified_since === $last_modified_http || $if_none_match === $etag) {
    return new Response('', 304);
  }

  $response = new Response($rss);
  $response->headers->set('Content-Type', 'application/rss+xml');
  $response->headers->set('ETag', $etag);
  $response->headers->set('Last-Modified', $last_modified_http);
  $response->setPrivate();  // Avoids interference from page cache
  $response->setMaxAge(0); // Disable HTTP caching, but still allows 304 logic
  return $response;
}

}
