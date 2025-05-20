
CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Features
 * Quick Start
 * Usage
 * Customization
 * Recommendations
 * Caching & Headers
 * License
 * Maintainer

INTRODUCTION
------------

Red Buoy Media Podcast lets you publish one or more podcast streams using a single content type. It’s designed for simplicity, flexibility, and minimal setup.

If you want to use it this way, this module allows you to focus on content, creating a podcast stream as a secondary aspect of your feed.

Instead of creating a separate content type for each podcast subject (e.g. "Kids Podcast", "Weekly Update", "Sermons"), you use a shared content type (`Podcast Episode`) with a dropdown field called **Podcast Feed**. This allows you to categorize episodes by stream while keeping your structure clean.

Any content with a valid MP3 and a Podcast Feed value becomes part of a valid podcast RSS stream.

FEATURES
--------

- Customizable RSS feeds per podcast
- One content type (`Podcast Episode`) with fields for audio file, duration,
  summary, and metadata
- Standards-compliant `<rss>` output with `<itunes:*>` tags
- Episode types (`full`, `trailer`, `bonus`)
- Conditional caching using `ETag` and `Last-Modified` headers
- Feed validator–friendly XML output
- Supports multiple podcast subjects from a single content type
- Does not require Views (but supports them)

QUICK START
-----------

1. **Install the module** as normal:
   ```bash
   composer require drupal/redbuoy_media_pod
   drush en redbuoy_media_pod
   ```

2. Visit `/admin/config/media/podcasts` or **Configuration → Media → Podcast Feeds**

3. Use the default podcast feed, or click **"Add Feed"** to create a new one:
   - Choose a meaningful name (e.g. `kids-corner`)
   - Set artwork, copyright, language, and other options

4. Create a **Podcast Episode**:
   - Upload an MP3 to the **Podcast Audio File** field
   - Select your podcast stream in the **Podcast Feed** field
   - Fill in title, summary, and any other relevant metadata

5. Return to the config page to find your **Feed URL** under the "Feed URL" column:
   - Example: `/podcast/feed/kids-corner`

6. Click **“Validate this feed on CastFeedValidator.com”** to check for RSS/iTunes compliance.

USAGE
-----

- Feeds are generated automatically from any Podcast Episode node tagged with a Podcast Feed and an MP3
- If you put content into the transcript field in the episode, a transcript back link to the episode will be created. Be sure to expose the transcript field in the node display to endure it is available.
- You do not need to create Views, although Views may help you display episode lists
- Multiple feeds can be created without separate content types
- The module’s routing system handles feed generation at `/podcast/feed/{feed-id}`

CUSTOMIZATION
-------------

You may add additional fields to the `Podcast Episode` content type as needed. All fields created by this module are prefixed with **"Podcast"** for clarity.

You are free to:
- Adjust field labels
- Add your own taxonomy or reference fields
- Build Views to dispaly podcast episodes by stream, date, or any custom logic

RECOMMENDATIONS
---------------

- Keep feed IDs URL-safe and consistent
- Include at least one episode before validating a feed
- Use the Podcast Feed field to group episodes by topic/series
- Optional: Use Views to present curated episode lists or archives

CACHING & HEADERS
-----------------

The feed controller uses conditional HTTP headers:
- `ETag`
- `Last-Modified`

This allows clients (and validators) to cache efficiently.

LICENSE
-------

GPL-2.0-or-later

MAINTAINER
----------

Adam Bauer
Red Buoy Media
https://redbuoy.media
