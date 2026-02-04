<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Founder message hooks.
 */
class FounderMessageBlockHooks {

  /**
   * Implements hook_theme.
   *
   * @param array $existing
   *   An array of existing theme implementations.
   *
   * @param string $type
   *   The extension type being processed, such as a module, theme, or profile.
   *
   * @param string $theme
   *   The machine name of the extension being processed.
   *
   * @param string $path
   *   The path to the extension being processed.
   *
   * @return array
   *   An associative array of theme implementations to add.
   *
   * @see \hook_theme()
   */
  // #[Hook('theme')]
  public function theme(
    array $existing, string $type, string $theme, string $path,
  ): array {

    return [
      'omnipedia_founder_message' => [
        'variables' => [
          'body'  => '',
        ],
        'template'  => 'omnipedia-founder-message',
      ],
      'omnipedia_founder_message_join' => [
        'variables' => [
          'body'        => '',
          'join_label'  => '',
          'join_url'    => '',
        ],
        'template'  => 'omnipedia-founder-message-join',
      ],
    ];

  }

}
