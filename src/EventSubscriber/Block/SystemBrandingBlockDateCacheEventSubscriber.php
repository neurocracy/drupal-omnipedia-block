<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\EventSubscriber\Block;

use Drupal\Core\Cache\Cache;
use Drupal\core_event_dispatcher\BlockHookEvents;
use Drupal\core_event_dispatcher\Event\Block\BlockBuildAlterEvent;
use Drupal\omnipedia_core\Service\WikiNodeMainPageInterface;
use Drupal\omnipedia_date\Service\TimelineInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds cache contexts and tags to the 'system_branding_block' block.
 *
 * @see \omnipedia_site_theme_preprocess_block__system_branding_block()
 *   Requires these changes to cache contexts and tags but cannot do so as
 *   preprocess functions are too late in the rendering process.
 */
class SystemBrandingBlockDateCacheEventSubscriber implements EventSubscriberInterface {

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_date\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeMainPageInterface $wikiNodeMainPage
   *   The Omnipedia wiki node main page service.
   */
  public function __construct(
    protected readonly TimelineInterface          $timeline,
    protected readonly WikiNodeMainPageInterface  $wikiNodeMainPage,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      BlockHookEvents::BLOCK_BUILD_ALTER => 'blockBuildAlter',
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

    // Vary by the Omnipedia date, user permissions, and user node grants cache
    // contexts.
    //
    // @todo Can most or all of these be fetched from the loaded main page for
    //   the current date?
    $build['#cache']['contexts'] = Cache::mergeContexts(
      $build['#cache']['contexts'],
      ['omnipedia_dates', 'user.permissions', 'user.node_grants:view']
    );

    // Add the current date cache tag and cache tags from all main pages.
    foreach ([
      ['omnipedia_dates:' . $this->timeline
        ->getDateFormatted('current', 'storage')],
      $this->wikiNodeMainPage->getMainPagesCacheTags()
    ] as $tags) {

      $build['#cache']['tags'] = Cache::mergeTags(
        $build['#cache']['tags'],
        $tags
      );

    }

  }

}
