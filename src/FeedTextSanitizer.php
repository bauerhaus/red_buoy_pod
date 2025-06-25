<?php

namespace Drupal\redbuoy_media_pod;

/**
 * Provides static utility for ASCII sanitization.
 */
class FeedTextSanitizer {

  /**
   * Prevents multiple warnings.
   *
   * @var bool
   */
  protected static $hasWarned = FALSE;

  /**
   * Sanitizes a string to ASCII-compatible characters.
   *
   * @param string $input
   *   The input text.
   *
   * @return string
   *   The sanitized ASCII string.
   */
  public static function sanitizeAscii(?string $input): string {
    $input = $input ?? '';
    $original = $input;
    // Convert to UTF-8, strip non-printables.
    $input = mb_convert_encoding($input, 'UTF-8', 'UTF-8');
    $input = preg_replace('/[^\PC\s]/u', '', $input);

    // Replace smart quotes, dashes, ellipses, etc.
    $replacements = [
      "\xE2\x80\x98" => "'",
      "\xE2\x80\x99" => "'",
      "\xE2\x80\x9C" => '"',
      "\xE2\x80\x9D" => '"',
      "\xE2\x80\xB2" => "'",
      "\xE2\x80\xB3" => '"',
      "\xE2\x80\x93" => '-',
      "\xE2\x80\x94" => '--',
      "\xE2\x80\xA6" => '...',
      "\xC2\xA0" => ' ',
    ];

    $input = strtr($input, $replacements);

    // Strip anything still non-ASCII.
    $input = preg_replace('/[^\x20-\x7E]/', '', $input);

    // Warn only once per request if sanitization occurred.
    if ($original !== $input && !self::$hasWarned) {
      \Drupal::messenger()->addWarning(t('Some special characters (like emoji or smart quotes) were removed for compatibility with podcast platforms.'));
      self::$hasWarned = TRUE;
    }

    return $input;
  }

}
