<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\omnipedia_main_page\Service\MainPageCacheInterface;
use Drupal\omnipedia_main_page\Service\MainPageRouteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Founder message block.
 *
 * @Block(
 *   id           = "omnipedia_founder_message",
 *   admin_label  = @Translation("Founder message"),
 *   category     = @Translation("Omnipedia"),
 * )
 */
class FounderMessage extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageCacheInterface $mainPageCache
   *   The Omnipedia main page cache service.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageRouteInterface $mainPageRoute
   *   The Omnipedia main page route service interface.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    protected readonly MainPageCacheInterface $mainPageCache,
    protected readonly MainPageRouteInterface $mainPageRoute,
  ) {

    parent::__construct($configuration, $pluginId, $pluginDefinition);

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
      $container->get('omnipedia_main_page.cache'),
      $container->get('omnipedia_main_page.route'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'founder_message';
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {

    /** @var array */
    $form = parent::blockForm($form, $formState);

    /** @var array */
    $config = $this->getConfiguration();

    /** @var array */
    $form['body'] = [
      '#type'           => 'text_format',
      '#title'          => $this->t('Body'),
      '#default_value'  => '',
      '#required'       => true,
    ];

    if (isset($config['body']['value'])) {
      $form['body']['#default_value'] = $config['body']['value'];
    }

    if (isset($config['body']['format'])) {
      $form['body']['#format'] = $config['body']['format'];
    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->setConfigurationValue('body', $formState->getValue('body'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // If the current route is not a main page, return an empty render array.
    // The getCacheContexts() and getCacheMaxAge() methods handle setting the
    // cache metadata.
    //
    // @todo Can this be exposed as a general option on all blocks so that we
    //   don't have to hard code it here?
    if (!$this->mainPageRoute->isCurrent()) {
      return [];
    }

    /** @var array */
    $config = $this->getConfiguration();

    /** @var array */
    $renderArray = [
      '#theme'  => 'omnipedia_founder_message',
      '#body'   => [
        '#type'   => 'processed_text',
        '#text'   => $config['body']['value'],
        // @todo Since we're using the 'text_format' form element in
        //   blockForm(), do we need to check that the current user has access
        //   to the format, or is that handled for us?
        '#format' => $config['body']['format'],
      ],
    ];

    return $renderArray;

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
      // Vary by whether the current route is a main page.
      'omnipedia_is_wiki_main_page',
      // Vary by user permissions.
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

    return Cache::mergeTags(
      parent::getCacheTags(),
      // Add all main page cache tags. If there are any added or removed main
      // pages, this block may need to be rebuilt.
      $this->mainPageCache->getAllCacheTags(),
    );

  }

}
