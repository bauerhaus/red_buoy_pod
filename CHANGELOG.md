# Changelog

## [0.9.9] – 2025-06-25
### Changed
Moved input sanitization logic into a centralized static utility class FeedTextSanitizer, consolidating character cleaning across both channel and episode metadata processing.

Improved HTML handling in RSS <content:encoded> fields by avoiding unnecessary escaping (htmlspecialchars), resolving issues with literal <p> tags appearing in Pocket Casts and other players.

Standardized character sanitation to strip smart quotes, em dashes, ellipses, non-breaking spaces, and other non-ASCII characters to improve RSS compatibility across podcast apps.

### Fixed
Sanitization logic in FeedSettingsForm now correctly handles formatted text fields (text_format) by extracting and replacing only the value, preserving the original format.

RSS episode and channel descriptions now consistently cleaned before being saved to config and content, preventing malformed XML in podcast feeds.

sanitizeAscii() function now properly handles null inputs and warns users only once per request when special characters are stripped.

### Added
Implemented hook_entity_presave() for podcast_episode nodes to sanitize all relevant text fields (field_podcast_author, field_podcast_descp, field_podcast_duration, field_podcast_keywords, field_podcast_subtitle) at save time, regardless of whether they are submitted via UI or programmatically.

Logger entries to track execution of presave sanitization, aiding debug and validation.

One-time warning message to the Drupal messenger UI when user-entered characters are removed for RSS compatibility.


## [0.9.8] – 2025-06-23

### Fix
- Replaced description sanitization logic: previous approach removed smart quotes at render time, which caused encoding issues in some feeds.
- New implementation now filters input during feed settings form submission, ensuring stored values are ASCII-safe and avoiding silent corruption.


## [0.9.5] – 2025-18-14

### bug fix
- Added function to clean smart quotes in description for Feed
- edited headers to include utf8

## [0.9.4] – 2025-18-14

### Feature addition
- Added setting for Apple podcast "Update Frequency"

## [0.9.3] – 2025-06-14

### bugfix
- Edited controller to match episode field names

## [0.9.2] – 2025-05-22

### changed
- Edited the field names to indicate they are part of the "podcast" module for  easier determination of which fields belong to this module

## [0.9.1] – 2025-05-20

### Added
- New field `field_transcript` added to `podcast_episode` content type.
  - Stored as `text_long`.
  - Automatically placed in the default form and view displays.
  - Used for full-text episode transcripts.
- Included update hook (`redbuoy_media_pod_update_9001`) to add the field on existing installations.

## [0.9.0] – 2025-05-16
### Status
Initial beta release. This module is feature-complete and ready for real-world testing. Core functionality is stable, but internal architecture and configuration structure may still change before 1.0.0.

### What’s Included
- Custom podcast feed generation with iTunes-compatible RSS.
- Admin settings page for per-feed metadata.
- Field mapping between content type fields and RSS tags.
- Automatic enclosure tag generation based on attached audio file.
- Basic error handling for missing data.

### What’s Not Included (Yet)
- Automated test coverage.
- Enhanced UI preview (currently a "View Feed" link is provided, but raw XML may not render cleanly in all browsers).
- Validation or warnings for incomplete episode metadata.

### Intent
This release marks a development milestone. The module is now stable enough for real-world use. Feedback, bug reports, and pull requests are welcome as we work toward a 1.0.0 release.
