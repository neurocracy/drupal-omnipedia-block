<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\EventSubscriber\Theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Omnipedia site header hook_theme() event subscriber.
 */
class ThemeOmnipediaHeaderEventSubscriber implements EventSubscriberInterface {

  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::THEME => 'onTheme',
    ];
  }

  /**
   * Defines the Omnipedia site header theme element.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event object.
   */
  public function onTheme(ThemeEvent $event): void {
    $event->addNewTheme('omnipedia_header', [
      'variables' => [
        'current_date'  => [],
        'menu_link'     => [],
        'search_form'   => [],
      ],
      'template'  => 'omnipedia-header',
      // Path is required.
      //
      // @see https://www.drupal.org/project/hook_event_dispatcher/issues/3038311
      'path'      => $this->moduleHandler
        ->getModule('omnipedia_block')->getPath() . '/templates',
    ]);
  }

}
