<?php

namespace Drupal\omnipedia_block\EventSubscriber\Block;

use Drupal\Core\Cache\Cache;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Block\BlockBuildAlterEvent;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Drupal\omnipedia_core\Service\WikiNodeMainPageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds cache contexts and tags to the 'system_branding_block' block.
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
   * The Omnipedia wiki node main page service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeMainPageInterface
   */
  protected $wikiNodeMainPage;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeMainPageInterface $wikiNodeMainPage
   *   The Omnipedia wiki node main page service.
   */
  public function __construct(
    TimelineInterface         $timeline,
    WikiNodeMainPageInterface $wikiNodeMainPage
  ) {
    $this->timeline         = $timeline;
    $this->wikiNodeMainPage = $wikiNodeMainPage;
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

    // Vary by the Omnipedia date and user cache contexts. The user context
    // allows us to vary per user and not have to know exactly how access is to
    // be granted to any main page linked in this block, i.e. whether it's by
    // role or something else.
    //
    // @todo Can this be changed to vary per group of users that have access to
    //   a given main page?
    //
    // @see https://git.drupalcode.org/project/permissions_by_term/-/blob/3.0.x-dev/src/Cache/AccessResultCache.php
    //   When we start using this module, we can use these tags and contexts.
    $build['#cache']['contexts'] = Cache::mergeContexts(
      $build['#cache']['contexts'],
      ['omnipedia_dates', 'user']
    );

    foreach ([
      // Current date cache tag.
      ['omnipedia_dates:' . $this->timeline
        ->getDateFormatted('current', 'storage')],
      // Cache tags for all main pages and relevant data.
      $this->wikiNodeMainPage->getMainPagesCacheTags(),
    ] as $tags) {
      $build['#cache']['tags'] = Cache::mergeTags(
        $build['#cache']['tags'],
        $tags
      );
    }
  }

}
