<?php

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Join Omnipedia block.
 *
 * @Block(
 *   id           = "omnipedia_join",
 *   admin_label  = @Translation("Join Omnipedia"),
 *   category     = @Translation("Omnipedia"),
 * )
 *
 * @todo Refactor this as a generic product link block, with configurable
 *   product entity reference field and link text.
 *
 * @todo Make this visible to authenticated users who haven't purchased the
 *   product. It'll be necessary to figure out how abstract that without hard
 *   coding by loading the configured product entity and checking if the user
 *   has access to all the episode tiers it would grant.
 */
class Join extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    AccountInterface $currentUser
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    // Save dependencies.
    $this->currentUser = $currentUser;
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'join_omnipedia';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    if ($this->currentUser->isAuthenticated()) {
      return [];
    }

    return [
      'container' => [
        '#type'       => 'html_tag',
        '#tag'        => 'p',
        '#attributes' => ['class' => ['join-omnipedia']],

        'link'  => [
          '#type'       => 'link',
          '#title'      => $this->t('Join Omnipedia'),
          '#url'        => Url::fromUserInput('/join'),
          '#attributes' => ['class' => ['join-omnipedia__link']],
        ],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), [
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
