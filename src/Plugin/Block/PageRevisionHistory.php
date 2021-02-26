<?php

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\Core\Url;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Page revision history block.
 *
 * This displays a list of all available revisions of a page, with links to view
 * each revision.
 *
 * @Block(
 *   id           = "omnipedia_page_revision_history",
 *   admin_label  = @Translation("Page revision history"),
 *   category     = @Translation("Omnipedia"),
 * )
 */
class PageRevisionHistory extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The Drupal access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The Drupal current route match service.
   *
   * @var \Drupal\Core\Routing\StackedRouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_core\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * The Omnipedia wiki node resolver service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeResolverInterface
   */
  protected $wikiNodeResolver;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Access\AccessManagerInterface $accessManager
   *   The Drupal access manager service.
   *
   * @param \Drupal\Core\Routing\StackedRouteMatchInterface $currentRouteMatch
   *   The Drupal current route match service.
   *
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeResolverInterface $wikiNodeResolver
   *   The Omnipedia wiki node resolver service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    AccessManagerInterface      $accessManager,
    StackedRouteMatchInterface  $currentRouteMatch,
    TimelineInterface           $timeline,
    WikiNodeResolverInterface   $wikiNodeResolver
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->accessManager      = $accessManager;
    $this->currentRouteMatch  = $currentRouteMatch;
    $this->timeline           = $timeline;
    $this->wikiNodeResolver   = $wikiNodeResolver;
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
      $container->get('access_manager'),
      $container->get('current_route_match'),
      $container->get('omnipedia.timeline'),
      $container->get('omnipedia.wiki_node_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // If a label has been set by the user, defer to that.
    if (!empty($this->configuration['label'])) {
      return $this->configuration['label'];
    }

    // Otherwise we use this.
    return $this->t('Revision history');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'page_revision_history';
  }

  /**
   * Get all revisions of the current route's wiki node, if one is found.
   *
   * @return array
   *   An array of node revision data. If the current route does not contain a
   *   wiki node as a parameter, this will be an empty array.
   *
   * @see \Drupal\omnipedia_core\Service\WikiNodeRevisionInterface::getWikiNodeRevisions()
   *   Describes the returned array structure. Note that we add an 'access' key
   *   to each entry to indicate if the current user has access to the node.
   */
  protected function getWikiNodeRevisions(): array {
    /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $node = $this->currentRouteMatch->getParameter('node');

    if (!$this->wikiNodeResolver->isWikiNode($node)) {
      return [];
    }

    // Data for this wiki node and its revisions.
    /** @var array */
    $nodeRevisions = $node->getWikiNodeRevisions();

    foreach ($nodeRevisions as &$nodeRevision) {
      // Add an 'access' key with the access result for whether this user role
      // can access this node..
      $nodeRevision['access'] = $this->accessManager->checkNamedRoute(
        'entity.node.canonical',
        ['node' => $nodeRevision['nid']]
      );
    }

    return $nodeRevisions;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $node = $this->currentRouteMatch->getParameter('node');

    // Return an empty render array if the current route doesn't contain a wiki
    // node as a parameter.
    if (!$this->wikiNodeResolver->isWikiNode($node)) {
      return [];
    }

    // Data for this wiki node and its revisions, if the current route contains
    // a wiki node parameter.
    /** @var array */
    $nodeRevisions = $this->getWikiNodeRevisions();

    // The base class for the revision list.
    /** @var string */
    $listClass = 'omnipedia-wiki-page-revisions';

    // This contains the render array for the block.
    /** @var array */
    $renderArray = [
      'revision_list' => [
        '#theme'        => 'item_list',
        '#list_type'    => 'ol',
        '#items'        => [],
        '#attributes'   => [
          'class'         => [$listClass],
        ],
      ]
    ];

    foreach ($nodeRevisions as $nodeRevision) {
      // Skip displaying this revision if the user doesn't have access to it.
      if ($nodeRevision['access'] === false) {
        continue;
      }

      /** @var array */
      $item = [
        // #attributes on item_list items applies the attributes to list item
        // content, not the list item itself, but #wrapper_attributes applies
        // attributes to the list item itself.
        '#wrapper_attributes' => [
          'class' => [$listClass . '__item'],
        ],
      ];

      // The revision node content, containing the date in a <time> element.
      /** @var array */
      $itemContent = [
        'date'  => [
          '#type'         => 'html_tag',
          '#tag'          => 'time',
          '#attributes'   => [
            'class'         => [$listClass . '__item-date'],
            'datetime'      => $this->timeline->getDateFormatted(
              $nodeRevision['date'], 'html'
            ),
          ],
          '#value'        => $this->timeline->getDateFormatted(
            $nodeRevision['date'], 'short'
          ),
        ],
      ];

      if ($nodeRevision['published'] === false) {
        // Add a line break between the date and the unpublished indicator.
        $itemContent['break'] = [
          '#type'   => 'html_tag',
          '#tag'    => 'br',
          '#value'  => '',
          '#attributes' => [
            'class'       => [$listClass . '__item-break'],
          ],
        ];
        // The unpublished indicator.
        $itemContent['unpublished'] = [
          '#type'   => 'html_tag',
          '#tag'    => 'em',
          '#value'  => $this->t('(unpublished)'),
          '#attributes' => [
            'class'       => [$listClass . '__item-status'],
          ],
        ];

        $item['#wrapper_attributes']['class'][] =
          $listClass . '__item--unpublished';
      }

      // Is this the current route's node? If so, only output the content
      // without a link.
      if ($nodeRevision['nid'] === (int) $node->nid->getString()) {
        $item = NestedArray::mergeDeep($item, $itemContent);

        $item['#wrapper_attributes']['class'][] =
          $listClass . '__item--current';

      // If this isn't the current node, output a link.
      } else {
        $item['#type']  = 'link';
        $item['#url']   = Url::fromRoute(
          'entity.node.canonical', ['node' => $nodeRevision['nid']]
        );
        $item['#title'] = $itemContent;
      }

      $renderArray['revision_list']['#items'][] = $item;
    }

    return $renderArray;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      // Note that we don't need to vary by the date, as each date is a
      // different wiki node which is handled by this context.
      'omnipedia_wiki_node',
      'user.permissions',
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
    // Data for this wiki node and its revisions, if the current route contains
    // a wiki node parameter.
    /** @var array */
    $nodeRevisions = $this->getWikiNodeRevisions();

    /** @var array */
    $tags = [
      // This ensures that this block's cache is invalidated whenever the
      // Permissions by Term cache is invalidated, which occurs when a user's
      // content permissions change.
      'permissions_by_term:access_result_cache',
    ];

    // Add a cache tag for every node revision so that this block is invalidated
    // if/when the node changes. Note that these are added even if the user does
    // not have access to the node for when/if access is granted so that the
    // block cache is correctly invalidated and rebuilt.
    foreach ($nodeRevisions as $nodeRevision) {
      $tags[] = 'node:' . $nodeRevision['nid'];
    }

    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

}
