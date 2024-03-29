<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\omnipedia_block\Plugin\Block\FounderMessage;
use Drupal\omnipedia_main_page\Service\MainPageCacheInterface;
use Drupal\omnipedia_main_page\Service\MainPageRouteInterface;
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
   * {@inheritdoc}
   *
   * @param \Drupal\path_alias\AliasManagerInterface $pathAliasManager
   *   The Drupal path alias manager.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    MainPageCacheInterface $mainPageCache,
    MainPageRouteInterface $mainPageRoute,
    protected readonly AliasManagerInterface $pathAliasManager,
  ) {

    parent::__construct(
      $configuration, $pluginId, $pluginDefinition,
      $mainPageCache, $mainPageRoute,
    );

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
      $container->get('path_alias.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'founder_message_join';
  }

  /**
   * Build a Url object given an internal path or external URL.
   *
   * @param string $pathOrUrl
   *   An internal path or external URL.
   *
   * @return \Drupal\Core\Url
   */
  protected function buildUrlObject(string $pathOrUrl): Url {

    try {

      /** @var \Drupal\Core\Url */
      $url = Url::fromUserInput($pathOrUrl);

    } catch (\Exception $exception) {

      /** @var \Drupal\Core\Url */
      $url = Url::fromUri($pathOrUrl);

    }

    return $url;

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
      '#title'          => $this->t('Join link URL'),
      '#default_value'  => '',
      '#required'       => true,
      '#description'    => $this->t('The URL to use as the join link. This can be a relative path to an internal page or an external URL.'),
    ];

    if (!empty($config['join_url'])) {

      /** @var \Drupal\Core\Url */
      $url = $this->buildUrlObject($config['join_url']);

      if (!$url->isExternal()) {

        $form['join_url']['#default_value'] = $this->pathAliasManager
          ->getAliasByPath($config['join_url']);

      } else {

        $form['join_url']['#default_value'] = $config['join_url'];

      }

    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $formState) {

    if (!empty($formState->getValue('join_url'))) {

      try {

        $url = $this->buildUrlObject($formState->getValue('join_url'));

      } catch (\Exception $exception) {

        $formState->setErrorByName('join_url', $this->t(
          "The value '%url' does not appear to be a valid interal path or external URL, or you do not have access to it.",
          ['%url' => $formState->getValue('join_url')]
        ));

      }

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

    // Converting an alias to internal path would preferably be done in the
    // validation method but that doesn't seem to work for blockValidate() as
    // updating the form state doesn't register for some reason.
    //
    // @see \Drupal\system\Form\SiteInformationForm::validateForm()
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

    /** @var array */
    $config = $this->getConfiguration();

    // Don't render anything if the join URL has not been set and the base
    // product has not been configured.
    if (empty($config['join_url']) && !\is_object($product)) {
      return [];
    }

    /** @var array */
    $renderArray = parent::build();

    if (empty($renderArray)) {
      return $renderArray;
    }

    $renderArray['#theme'] = 'omnipedia_founder_message_join';

    /** @var \Drupal\Core\Url */
    $renderArray['#join_url'] = $this->buildUrlObject($config['join_url']);

    $renderArray['#join_label'] = $config['join_label'];

    return $renderArray;

  }

}
