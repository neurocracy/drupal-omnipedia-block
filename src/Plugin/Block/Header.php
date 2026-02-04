<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\omnipedia_core\Service\WikiNodeAccessInterface;
use Drupal\omnipedia_date\Service\TimelineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Omnipedia header block.
 *
 * This displays the current Omnipedia date and provides an in-page anchor
 * link for the Omnipedia theme's sidebar menu on small screens. Several of
 * our modules and the theme alter this block and its template to add
 * additional features.
 */
#[Block(
  id: 'omnipedia_header',
  admin_label:  new TranslatableMarkup('Header'),
  category:     new TranslatableMarkup('Omnipedia'),
)]
class Header extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\omnipedia_date\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeAccessInterface $wikiNodeAccess
   *   The Omnipedia wiki node access service.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    protected readonly TimelineInterface $timeline,
    protected readonly WikiNodeAccessInterface $wikiNodeAccess,
  ) {

    parent::__construct($configuration, $pluginId, $pluginDefinition);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginId, $pluginDefinition,
  ) {
    return new static(
      $configuration, $pluginId, $pluginDefinition,
      $container->get('omnipedia.timeline'),
      $container->get('omnipedia.wiki_node_access'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'omnipedia_header';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $renderArray = [
      'header'  => [
        '#theme'  => 'omnipedia_header',

        '#current_date'    => [
          '#type'         => 'html_tag',
          '#tag'          => 'time',
          '#attributes'   => new Attribute([
            'class'         => [
              'omnipedia-current-date',
            ],
            'datetime'      => $this->timeline
              ->getDateFormatted('current', 'html'),
          ]),
          '#value'        => $this->timeline
            ->getDateFormatted('current', 'long'),
        ],

        '#menu_link'  => [
          '#type'       => 'link',
          '#title'      => $this->t('Menu'),
          '#url'        => Url::fromUserInput('#menu'),
          '#attributes' => new Attribute(),
          '#wrapper_attributes' => new Attribute(),
        ],
      ],
    ];

    return $renderArray;

  }

  /**
   * {@inheritdoc}
   *
   * @todo Can/should we vary this per wiki date?
   */
  protected function blockAccess(AccountInterface $account) {

    return AccessResult::allowedIf(
      $this->wikiNodeAccess->canUserAccessAnyWikiNode($account),
    );

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {

    return Cache::mergeContexts(parent::getCacheContexts(), [
      'omnipedia_dates',
      'user.permissions',
      'user.node_grants:view',
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
      'omnipedia_dates:' . $this->timeline->getDateFormatted(
        'current', 'storage',
      ),
    ]);

  }

}
