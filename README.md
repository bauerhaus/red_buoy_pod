CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Features
 * Installation
 * Usage
 * Recommendations
 * Caching & Headers
 * License
 * Maintainer

INTRODUCTION
------------

A lightweight podcasting module for Drupal 10/11 that generates RSS feeds
compatible with Apple Podcasts, Spotify, and other major podcast platforms.

FEATURES
--------

- Customizable RSS feeds per podcast
- One content type (`Podcast Episode`) with fields for audio file, duration,
    summary, and metadata
- Standards-compliant `<rss>` output with `<itunes:*>` tags
- Episode types (`full`, `trailer`, `bonus`)
- Conditional caching using `ETag` and `Last-Modified` headers
- Feed validator–friendly XML output

INSTALLATION
------------

Install via Composer:


composer require drupal/redbuoy_media_pod
drush en redbuoy_media_pod

USAGE
-----

- Configure your podcast feeds at: /admin/config/media/redbuoy-media-pod
- Create podcast episodes using the Podcast Episode content type.
- Assign each episode to a feed using the Podcast Feed field.
- Access your feed at: /podcast/feed/[feed_name]
- Example: /podcast/feed/default
- Validate your feed using: https://www.castfeedvalidator.com

RECOMMENDATIONS
---------------

- Use JPEG artwork at 2048×2048 pixels, under 500 KB
- Export MP3s with CBR (Constant Bitrate) encoding to pass audio validation
- Use the Episode Type field meaningfully:
  - trailer for previews
  - bonus for Patreon teasers
- Include a link to full content (e.g., Patreon) inside the Episode Description
- Fill in all fields; many apps ignore episodes missing
    <duration>, <author>, or <summary>

CACHING & HEADERS
-----------------

- This module automatically sends:
- Last-Modified headers (based on latest episode update)
- ETag headers (based on feed hash)
- Clients like Apple Podcasts and Spotify will use these to cache intelligently
   and reduce bandwidth with 304 Not Modified responses.

LICENSE
-------

- GPL-2.0 or later

MAINTAINER
----------

- Adam Bauer
- https://redbuoy.media
