# Changelog

## [0.93] – 2025-0-14

### bugfix
- Edited controller to match episode field names

## [0.92] – 2025-05-22

### changed
- Edited the field names to indicate they are part of the "podcast" module for  easier determination of which fields belong to this module

## [0.91] – 2025-05-20

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
