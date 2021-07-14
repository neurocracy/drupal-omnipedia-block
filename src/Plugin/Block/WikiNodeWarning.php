<?php

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\omnipedia_core\Entity\NodeInterface;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Drupal\omnipedia_core\Service\WikiNodeRouteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Wiki node warning message block.
 *
 * If a wiki node is found in the current context and contains one or more
 * warning messages, this will output them using the Drupal status messages
 * theme element. Note that this bypasses Drupal's messaging system for two
 * reasons:
 *
 * - This can be cached, while messages must be set on each request.
 *
 * - This can be placed in a different region or order than the core messages
 *   block.
 *
 * @Block(
 *   id           = "omnipedia_wiki_node_warning",
 *   admin_label  = @Translation("Wiki page warning message"),
 *   category     = @Translation("Omnipedia"),
 *   context      = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class WikiNodeWarning extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The Drupal current route match service.
   *
   * @var \Drupal\Core\Routing\StackedRouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The Omnipedia wiki node resolver service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeResolverInterface
   */
  protected $wikiNodeResolver;

  /**
   * The Omnipedia wiki node route service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeRouteInterface
   */
  protected $wikiNodeRoute;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Routing\StackedRouteMatchInterface $currentRouteMatch
   *   The Drupal current route match service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeResolverInterface $wikiNodeResolver
   *   The Omnipedia wiki node resolver service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeRouteInterface $wikiNodeRoute
   *   The Omnipedia wiki node route service.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    StackedRouteMatchInterface  $currentRouteMatch,
    WikiNodeResolverInterface   $wikiNodeResolver,
    WikiNodeRouteInterface      $wikiNodeRoute
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    // Save dependencies.
    $this->currentRouteMatch  = $currentRouteMatch;
    $this->wikiNodeResolver   = $wikiNodeResolver;
    $this->wikiNodeRoute      = $wikiNodeRoute;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginId, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginId, $pluginDefinition,
      $container->get('current_route_match'),
      $container->get('omnipedia.wiki_node_resolver'),
      $container->get('omnipedia.wiki_node_route')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'wiki_page_warning';
  }

  /**
   * Get the wiki node to display a warning for.
   *
   * @return \Drupal\omnipedia_core\Entity\NodeInterface|null
   *   A node object if the current context has a wiki node as a parameter or
   *   null otherwise.
   */
  protected function getWikiNode(): ?NodeInterface {

    // If there's a 'node' context value, attempt to resolve it to a wiki node.
    return $this->wikiNodeResolver->getWikiNode(
      $this->getContextValue('node')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    /** @var array[] */
    $messages = [];

    // Bail if this is not a node route.
    if (!$this->wikiNodeRoute->isWikiNodeViewRouteName(
      $this->currentRouteMatch->getRouteName()
    )) {
      return $messages;
    }

    /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $node = $this->getWikiNode();

    if (
      !\is_object($node) ||
      !$node->hasField('field_warning')
    ) {
      return $messages;
    }

    foreach ($node->get('field_warning') as $fieldItem) {
      $messages[] = $fieldItem->view();
    }

    if (empty($messages)) {
      return $messages;
    }

    return [
      '#theme'            => 'status_messages',
      '#message_list'     => ['warning' => $messages],
      '#status_headings'  => [
        'warning' => $this->t('Warning message'),
      ],
    ];

  }

  /**
   * {@inheritdoc}
   *
   * Note that we don't include 'route' as that's added for us because of the
   * node context in this block's annotation.
   *
   * Note that we don't need to vary by the date, as each date is a different
   * wiki node which is handled by the 'omnipedia_wiki_node' context.
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'omnipedia_wiki_node',
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
   *
   * This ensures that this block's cache is invalidated whenever the
   * Permissions by Term cache is invalidated, which occurs when a user's
   * content permissions change.
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [
      'permissions_by_term:access_result_cache',
    ]);
  }

}
