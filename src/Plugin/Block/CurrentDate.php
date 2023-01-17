<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\omnipedia_date\Service\TimelineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Current date block.
 *
 * This displays a <time> element with the current Omnipedia date.
 *
 * @Block(
 *   id           = "omnipedia_current_date",
 *   admin_label  = @Translation("Current date"),
 *   category     = @Translation("Omnipedia"),
 * )
 */
class CurrentDate extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_date\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\omnipedia_date\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    TimelineInterface $timeline
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->timeline = $timeline;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition,
      $container->get('omnipedia.timeline')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'current_date';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      // This needs to be wrapped in its own key so that #attributes doesn't get
      // removed by the render/block system for some reason.
      'current_date'  => [
        '#type'         => 'html_tag',
        '#tag'          => 'time',
        '#attributes'   => [
          'class'         => ['omnipedia-current-date'],
          'datetime'      => $this->timeline
            ->getDateFormatted('current', 'html'),
        ],
        '#value'        => $this->timeline->getDateFormatted('current', 'long'),
      ],
    ];
  }

 /**
   * {@inheritdoc}
   *
   * We're using the 'access content' permission to determine if the user can
   * view this block for convenience, rather than creating a new permission.
   * In most cases, whether this block is shown should go hand-in-hand with
   * content being publicly accessible or not, so this keeps things simple.
   */
  public function access(AccountInterface $account, $returnAsObject = false) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      // This varies by the storage-formatted date, i.e. different for each
      // date.
      'omnipedia_dates',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [
      'omnipedia_dates:' . $this->timeline->getDateFormatted('current', 'storage'),
    ]);
  }

}
