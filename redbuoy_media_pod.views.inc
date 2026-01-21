<?php

/**
 * @file
 * Provides Views integration for podcast download tracking.
 */

/**
 * Implements hook_views_data().
 */
function redbuoy_media_pod_views_data() {
  $data = [];

  // Define the base table.
  $data['redbuoy_podcast_downloads']['table']['group'] = t('Podcast Downloads');
  $data['redbuoy_podcast_downloads']['table']['base'] = [
    'field' => 'id',
    'title' => t('Podcast Downloads'),
    'help' => t('Track podcast episode downloads.'),
  ];

  // ID field.
  $data['redbuoy_podcast_downloads']['id'] = [
    'title' => t('Download ID'),
    'help' => t('The unique ID of the download log entry.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Timestamp field.
  $data['redbuoy_podcast_downloads']['timestamp'] = [
    'title' => t('Download Time'),
    'help' => t('When the download occurred.'),
    'field' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
  ];

  // Node ID field with relationship.
  $data['redbuoy_podcast_downloads']['node_id'] = [
    'title' => t('Episode Node ID'),
    'help' => t('The node ID of the podcast episode.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'relationship' => [
      'base' => 'node_field_data',
      'base field' => 'nid',
      'id' => 'standard',
      'label' => t('Podcast Episode'),
    ],
  ];

  // File ID field with relationship.
  $data['redbuoy_podcast_downloads']['file_id'] = [
    'title' => t('Audio File ID'),
    'help' => t('The file ID of the downloaded MP3.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'relationship' => [
      'base' => 'file_managed',
      'base field' => 'fid',
      'id' => 'standard',
      'label' => t('Audio File'),
    ],
  ];

  // Feed name field.
  $data['redbuoy_podcast_downloads']['feed_name'] = [
    'title' => t('Feed Name'),
    'help' => t('The podcast feed name.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // IP address field.
  $data['redbuoy_podcast_downloads']['ip_address'] = [
    'title' => t('IP Address'),
    'help' => t('The IP address of the downloader.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // User agent field.
  $data['redbuoy_podcast_downloads']['user_agent'] = [
    'title' => t('User Agent'),
    'help' => t('The user agent string of the downloader.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];

  // Referer field.
  $data['redbuoy_podcast_downloads']['referer'] = [
    'title' => t('Referer'),
    'help' => t('The HTTP referer header.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];

  return $data;
}
