<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Founder message block.
 */
#[Block(
  id: 'omnipedia_founder_message',
  admin_label:  new TranslatableMarkup('Founder message'),
  category:     new TranslatableMarkup('Omnipedia'),
)]
class FounderMessage extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    TranslationInterface $stringTranslation,
  ) {

    parent::__construct(
      $configuration, $pluginId, $pluginDefinition,
    );

    // BlockPluginTrait uses StringTranslationTrait but BlockBase doesn't use
    // real dependency injection for it. This does it for real.
    $this->setStringTranslation($stringTranslation);

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
      $container->get(TranslationInterface::class),
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
  protected function blockAccess(AccountInterface $account) {

    return AccessResult::allowedIfHasPermission($account, 'access content');

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {

    return Cache::mergeContexts(parent::getCacheContexts(), [
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

}
