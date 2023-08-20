<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\omnipedia_core\Service\WikiNodeAccessInterface;
use Drupal\omnipedia_date\Service\TimelineInterface;
use Drupal\omnipedia_search\Service\WikiSearchInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Site header block.
 *
 * This displays the current Omnipedia date and a search field.
 *
 * @Block(
 *   id           = "omnipedia_header",
 *   admin_label  = @Translation("Header"),
 *   category     = @Translation("Omnipedia"),
 * )
 */
class Header extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\views\ViewExecutableFactory $viewsExecutableFactory
   *   The Views executable factory.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $viewsEntityStorage
   *   The Views entity storage.
   *
   * @param \Drupal\omnipedia_date\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeAccessInterface $wikiNodeAccess
   *   The Omnipedia wiki node access service.
   *
   * @param \Drupal\omnipedia_search\Service\WikiSearchInterface $wikiSearch
   *   The Omnipedia wiki search service.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TimelineInterface          $timeline,
    protected readonly ViewExecutableFactory      $viewsExecutableFactory,
    protected readonly WikiNodeAccessInterface    $wikiNodeAccess,
    protected readonly WikiSearchInterface        $wikiSearch,
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
      $container->get('entity_type.manager'),
      $container->get('omnipedia.timeline'),
      $container->get('views.executable'),
      $container->get('omnipedia.wiki_node_access'),
      $container->get('omnipedia.wiki_search'),
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
          // Must be an array and not an Attribute object.
          //
          // @see omnipedia-header.html.twig
          '#attributes' => [],
        ],
      ],
    ];

    /** @var array */
    $searchForm = $this->getSearchForm();

    if (!empty($searchForm)) {
      if (isset($searchForm['#attributes'])) {
        $searchForm['#attributes'] = new Attribute($searchForm['#attributes']);

      } else {
        $searchForm['#attributes'] = new Attribute();
      }

      $renderArray['header']['#search_form'] = $searchForm;
    }

    return $renderArray;

  }

  /**
   * Get the wiki search form.
   *
   * @return array
   *   The form render array or an empty array on error.
   *
   * @see \Drupal\views\Views::getView()
   *   We load the view like in this static method except with dependency
   *   injection.
   */
  protected function getSearchForm(): array {

    // Don't display the search form on the wiki search page as it's redundant.
    if ($this->wikiSearch->isCurrentRouteSearchPage()) {
      return [];
    }

    /** @var \Drupal\views\ViewExecutable|null */
    $viewEntity = $this->entityTypeManager->getStorage('view')->load(
      'wiki_search'
    );

    if (!\is_object($viewEntity)) {
      return [];
    }

    /** @var \Drupal\views\ViewExecutable */
    $viewExecutable = $this->viewsExecutableFactory->get($viewEntity);

    // We have to build the display to ensure that various handlers are
    // initialized so that we don't cause any errors when building the form.
    $viewExecutable->build('page');

    return $viewExecutable->getDisplay()->getPlugin(
      'exposed_form'
    )->renderExposedForm(true);

  }

 /**
   * {@inheritdoc}
   *
   * @todo Can/should we vary this per wiki date?
   */
  public function access(AccountInterface $account, $returnAsObject = false) {
    return AccessResult::allowedIf(
      $this->wikiNodeAccess->canUserAccessAnyWikiNode($account)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {

    return Cache::mergeContexts(parent::getCacheContexts(), [
      'omnipedia_dates',
      'omnipedia_is_wiki_search_page',
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
        'current', 'storage'
      ),
    ]);

  }

}
