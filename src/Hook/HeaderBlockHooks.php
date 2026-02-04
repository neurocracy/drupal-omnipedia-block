<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Header block hooks.
 */
class HeaderBlockHooks {

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
      'omnipedia_header' => [
        'variables' => [
          'base_class'    => 'omnipedia-header',
          'current_date'  => [],
          'menu_link'     => [],
        ],
        'template'  => 'omnipedia-header',
      ],
    ];

  }

}
