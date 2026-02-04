<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\Hook;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\omnipedia_block\Hook\FounderMessageBlockHooks;
use Drupal\omnipedia_block\Hook\HeaderBlockHooks;

/**
 * Theme hooks.
 */
class ThemeHooks {

  /**
   * Constructor; saves dependencies.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver service.
   */
  public function __construct(
    protected readonly ClassResolverInterface $classResolver,
  ) {}

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
   *
   * Drupal core currently only supports one implementation of an OOP theme
   * hook per extension (module, theme, profile) and will throw a fatal error
   * if we try to provide more than one. This method currently exists to work
   * around that.
   *
   * @see https://www.drupal.org/project/drupal/issues/3558998
   *   Drupal core issue regarding this problem and why it's not intuitive.
   *
   * @todo Remove this and uncomment the attributes on the called methods
   *   if/when that's fixed.
   */
  #[Hook('theme')]
  public function theme(
    array $existing, string $type, string $theme, string $path,
  ): array {

    $founderMessageBlockHooks = $this->classResolver->getInstanceFromDefinition(
      FounderMessageBlockHooks::class,
    );

    $headerBlockHooks = $this->classResolver->getInstanceFromDefinition(
      HeaderBlockHooks::class,
    );

    return (
      $founderMessageBlockHooks->theme($existing, $type, $theme, $path) +
      $headerBlockHooks->theme($existing, $type, $theme, $path)
    );

  }

}
