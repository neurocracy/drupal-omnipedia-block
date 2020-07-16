<?php

namespace Drupal\omnipedia_block\EventSubscriber\Block;

use Drupal\Core\Cache\Cache;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Block\BlockBuildAlterEvent;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds Omnipedia date cache context and tags to 'system_branding_block' block.
 *
 * @see omnipedia_site_preprocess_block__system_branding_block()
 *   Requires these changes to cache contexts and tags but cannot do so as
 *   preprocess functions are too late in the rendering process.
 */
class SystemBrandingBlockDateCacheEventSubscriber implements EventSubscriberInterface {

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_core\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   */
  public function __construct(
    TimelineInterface $timeline
  ) {
    $this->timeline = $timeline;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::BLOCK_BUILD_ALTER => 'blockBuildAlter',
    ];
  }

  /**
   * Alter the 'system_branding_block' build array.
   *
   * @param \Drupal\core_event_dispatcher\Event\Block\BlockBuildAlterEvent $event
   *   The event object.
   */
  public function blockBuildAlter(BlockBuildAlterEvent $event): void {
    if (
      $event->getBlock()->getConfiguration()['id'] !== 'system_branding_block'
    ) {
      return;
    }

    /** @var array */
    $build = &$event->getBuild();

    // Vary by the Omnipedia date cache context.
    $build['#cache']['contexts'] = Cache::mergeContexts(
      $build['#cache']['contexts'],
      ['omnipedia_dates']
    );

    // Add a cache tag for the current date that's being cached in this context.
    $build['#cache']['tags'] = Cache::mergeTags(
      $build['#cache']['tags'],
      [
        'omnipedia_dates:' .
        $this->timeline->getDateFormatted('current', 'storage')
      ]
    );
  }

}
