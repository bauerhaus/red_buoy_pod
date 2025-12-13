# Red Buoy Followers (v1)

Episode-linked follower comments as nodes + a community comments page, without touching Drupal's Core Comment system.

## What it does
- Adds a "Leave a comment" link to full **podcast_episode** pages.
- Serves a pre-filled add form that creates **follower_comment** nodes linked to that episode (unpublished by default).
- Provides a public listing at **/community/comments** (20 per page) with latest published comments, linking back to episodes.

## Install
1. Copy this module folder into `web/modules/custom/redbuoy_followers`.
2. Enable it: `drush en redbuoy_followers -y && drush cr`
3. Permissions:
   ```bash
   drush role:perm:add anonymous 'create follower_comment content'
   drush role:perm:add authenticated 'create follower_comment content'
   drush role:perm:add editor 'moderate follower comments'   # adjust role names
   ```
4. Visit any published `podcast_episode` in full view. You should see **Leave a comment**.

## Fields & content type
On install, the module creates bundle **follower_comment** with fields:
- `body` (required)
- `field_episode` (required, reference to `podcast_episode`)
- `field_author_name` (string)
- `field_author_email` (email, private—hidden on display)
- `field_notify` (boolean)

Comments are **unpublished** by default. Only users with permission can publish them.

## Community page
- Browse **/community/comments** for all published comments.
- Filter by episode with `?episode=NID`.

## Podcast app show-notes (optional integration)
To surface the comment link inside podcast apps (Apple/Spotify/etc.), append a CTA in your feed builder when this module is enabled:



## Uninstall / rollback
- Disable and uninstall: `drush pmu redbuoy_followers -y && drush cr`
- This will remove the content type but **will not** delete existing follower_comment content automatically. Delete manually if desired.

## Notes
- Honeypot protection is added automatically if the **honeypot** module is enabled.
- For a more advanced moderation flow, enable **Content Moderation** and add a workflow for the `follower_comment` bundle. This v1 uses the core Published flag as a simple moderation gate.
