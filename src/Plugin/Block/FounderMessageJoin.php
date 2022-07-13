<?php

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Url;
use Drupal\omnipedia_block\Plugin\Block\FounderMessage;
use Drupal\omnipedia_commerce\Service\ContentAccessProductInterface;
use Drupal\omnipedia_core\Service\WikiNodeMainPageInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Founder message block - join variant.
 *
 * @Block(
 *   id           = "omnipedia_founder_message_join",
 *   admin_label  = @Translation("Founder message (join)"),
 *   category     = @Translation("Omnipedia"),
 * )
 */
class FounderMessageJoin extends FounderMessage {

  /**
   * The Omnipedia content access product service.
   *
   * @var \Drupal\omnipedia_commerce\Service\ContentAccessProductInterface
   */
  protected $contentAccessProduct;

  /**
   * The Drupal path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected AliasManagerInterface $pathAliasManager;

  /**
   * The Drupal path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected PathValidatorInterface $pathValidator;

  /**
   * The Drupal request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected RequestContext $requestContext;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\omnipedia_commerce\Service\ContentAccessProductInterface $contentAccessProduct
   *   The Omnipedia content access product service.
   *
   * @param \Drupal\path_alias\AliasManagerInterface $pathAliasManager
   *   The Drupal path alias manager.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The Drupal path validator.
   *
   * @param \Drupal\Core\Routing\RequestContext $requestContext
   *   The Drupal request context.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    WikiNodeMainPageInterface     $wikiNodeMainPage,
    ContentAccessProductInterface $contentAccessProduct,
    AliasManagerInterface         $pathAliasManager,
    PathValidatorInterface        $pathValidator,
    RequestContext                $requestContext
  ) {

    parent::__construct(
      $configuration, $pluginID, $pluginDefinition, $wikiNodeMainPage
    );

    $this->contentAccessProduct = $contentAccessProduct;
    $this->pathAliasManager     = $pathAliasManager;
    $this->pathValidator        = $pathValidator;
    $this->requestContext       = $requestContext;

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
      $container->get('omnipedia.wiki_node_main_page'),
      $container->get('omnipedia_commerce.content_access_product'),
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'founder_message_join';
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
    $form['join_label'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Join link text'),
      '#default_value'  => '',
      '#required'       => true,
    ];

    if (isset($config['join_label'])) {
      $form['join_label']['#default_value'] = $config['join_label'];
    }

    /** @var array */
    $form['join_url'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Join link path'),
      '#default_value'  => '',
      '#description'    => $this->t('A relative path to use as the join link path.'),
      '#field_prefix'   => $this->requestContext->getCompleteBaseUrl(),
    ];

    if (!empty($config['join_url'])) {
      $form['join_url']['#default_value'] = $this->pathAliasManager
        ->getAliasByPath($config['join_url']);
    }

    return $form;

  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\system\Form\SiteInformationForm::validateForm()
   *   Join URL path validation adapted from this.
   */
  public function blockValidate($form, FormStateInterface $formState) {

    // Convert an aliased path to its unaliased form if available.
    //
    // @todo Figure out why this doesn't seem to work here in blockValidate()
    //   but does in Drupal\Core\Form\FormInterface::validateForm()
    //
    // @see \Drupal\system\Form\SiteInformationForm::validateForm()
    // $formState->setValueForElement(
    //   $form['join_url'],
    //   $this->pathAliasManager->getPathByAlias($formState->getValue('join_url'))
    // );

    $joinUrlValue = $formState->getValue('join_url');

    if (!empty($joinUrlValue) && $joinUrlValue[0] !== '/') {

      $formState->setErrorByName('join_url', $this->t(
        "The path '%path' has to start with a slash.",
        ['%path' => $formState->getValue('join_url')]
      ));

    }

    if (!$this->pathValidator->isValid($formState->getValue('join_url'))) {
      $formState->setErrorByName('join_url', $this->t(
        "Either the path '%path' is invalid or you do not have access to it.",
        ['%path' => $formState->getValue('join_url')]
      ));
    }

    parent::blockValidate($form, $formState);

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {

    parent::blockSubmit($form, $formState);

    $this->setConfigurationValue(
      'join_label', $formState->getValue('join_label')
    );

    // @see self::blockValidate()
    $this->setConfigurationValue(
      'join_url', $this->pathAliasManager->getPathByAlias(
        $formState->getValue('join_url')
      )
    );

  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    /** @var \Drupal\commerce_product\Entity\ProductInterface|null */
    $product = $this->contentAccessProduct->getBaseProduct();

    // Don't render anything if the base product has not been configured.
    if (!\is_object($product)) {
      return [];
    }

    /** @var array */
    $renderArray = parent::build();

    if (empty($renderArray)) {
      return $renderArray;
    }

    /** @var array */
    $config = $this->getConfiguration();

    $renderArray['#theme'] = 'omnipedia_founder_message_join';

    if (!empty($config['join_url'])) {

      /** @var \Drupal\Core\Url */
      $renderArray['#join_url'] = Url::fromUserInput($config['join_url']);

    } else {

      /** @var \Drupal\Core\Url */
      $renderArray['#join_url'] = $product->toUrl();

    }

    $renderArray['#join_label'] = $config['join_label'];

    return $renderArray;

  }

}
