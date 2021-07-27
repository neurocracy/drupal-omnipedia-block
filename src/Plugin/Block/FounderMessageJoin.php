<?php

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\omnipedia_block\Plugin\Block\FounderMessage;
use Drupal\omnipedia_commerce\Service\ContentAccessProductInterface;
use Drupal\omnipedia_core\Service\WikiNodeMainPageInterface;
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
   * {@inheritdoc}
   *
   * @param \Drupal\omnipedia_commerce\Service\ContentAccessProductInterface $contentAccessProduct
   *   The Omnipedia content access product service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    WikiNodeMainPageInterface     $wikiNodeMainPage,
    ContentAccessProductInterface $contentAccessProduct
  ) {

    parent::__construct(
      $configuration, $pluginID, $pluginDefinition, $wikiNodeMainPage
    );

    $this->contentAccessProduct = $contentAccessProduct;

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
      $container->get('omnipedia_commerce.content_access_product')
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

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {

    parent::blockSubmit($form, $formState);

    $this->setConfigurationValue(
      'join_label', $formState->getValue('join_label')
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

    /** @var \Drupal\Core\Url */
    $renderArray['#join_url'] = $product->toUrl();

    $renderArray['#join_label'] = $config['join_label'];

    return $renderArray;

  }

}
