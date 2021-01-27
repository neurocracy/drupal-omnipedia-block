<?php

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\omnipedia_core\Service\WikiNodeMainPageInterface;
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
   * The Omnipedia wiki node main page service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeMainPageInterface
   */
  protected $wikiNodeMainPage;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeMainPageInterface $wikiNodeMainPage
   *   The Omnipedia wiki node main page service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    WikiNodeMainPageInterface $wikiNodeMainPage
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->wikiNodeMainPage = $wikiNodeMainPage;
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
      $container->get('omnipedia.wiki_node_main_page')
    );
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
    if (!$this->wikiNodeMainPage->isCurrentRouteMainPage()) {
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
    return Cache::mergeContexts(
      parent::getCacheContexts(),
      [
        // Vary by whether the current route is a main page.
        'omnipedia_is_wiki_main_page',
        // Vary by user permissions.
        'user.permissions'
      ]
    );
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
      $this->wikiNodeMainPage->getMainPagesCacheTags()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'founder_message';
  }

}