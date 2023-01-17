<?php

declare(strict_types=1);

namespace Drupal\omnipedia_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Privacy settings block.
 *
 * This primarily provides a link to the privacy policy that is intended to be
 * progressively enhanced into a button to open the EU Cookie Compliance (GDPR)
 * pop-up. Other features may be added later, hence the generic name of this
 * block.
 *
 * @Block(
 *   id           = "omnipedia_privacy_settings",
 *   admin_label  = @Translation("Privacy settings"),
 *   category     = @Translation("Omnipedia"),
 * )
 */
class PrivacySettings extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The EU Cookie Compliance settings configuration name.
   */
  protected const EU_COOKIE_COMPLIANCE_CONFIG_NAME = 'eu_cookie_compliance.settings';

  /**
   * The base BEM class applied to the placeholder link.
   */
  protected const PLACEHOLDER_BASE_CLASS = 'omnipedia-privacy-settings-placeholder';

  /**
   * The Drupal configuration object factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user proxy service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Our logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $loggerChannel;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Drupal configuration object factory service.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user proxy service.
   *
   * @param \Psr\Log\LoggerInterface $loggerChannel
   *   Our logger channel.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    ConfigFactoryInterface  $configFactory,
    AccountProxyInterface   $currentUser,
    LoggerInterface         $loggerChannel
  ) {

    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->configFactory  = $configFactory;
    $this->currentUser    = $currentUser;
    $this->loggerChannel  = $loggerChannel;

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
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('logger.channel.omnipedia_block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'privacy_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {

    /** @var array */
    $form = parent::blockForm($form, $formState);

    if (
      $this->currentUser->hasPermission('administer eu cookie compliance popup')
    ) {

      /** @var array */
      $form['privacy_settings'] = [
        '#type'   => 'html_tag',
        '#tag'    => 'p',
        '#value'  => $this->t(
          'The privacy policy link and text can be configured in the <a href=":euCookieComplianceSettingsUrl">EU Cookie Compliance settings</a>.',
          [':euCookieComplianceSettingsUrl' => Url::fromRoute(
            'eu_cookie_compliance.settings'
          )->toString()]
        ),
      ];

    } else {

      $form['privacy_settings'] = [
        '#type'   => 'html_tag',
        '#tag'    => 'p',
        '#value'  => $this->t(
          'The privacy policy link and text are configured via a page that you don\'t have access to. Please contact a site administrator to change these.',
        ),
      ];

    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    /** @var \Drupal\Core\Config\ImmutableConfig */
    $euCookieComplianceSettings = $this->configFactory
      ->get(self::EU_COOKIE_COMPLIANCE_CONFIG_NAME);

    /** @var string|null */
    $privacyPolicyUrl = $euCookieComplianceSettings->get('popup_link');

    /** @var string|null */
    $privacyPolicyTitle = $euCookieComplianceSettings
      ->get('popup_more_info_button_message');

    /** @var string|null */
    $privacySettingsTitle = $euCookieComplianceSettings
      ->get('withdraw_tab_button_label');

    // Bail and log an error if the 'eu_cookie_compliance.settings' config
    // didn't return expected values.
    if (
      empty($privacyPolicyUrl) ||
      empty($privacyPolicyTitle) ||
      empty($privacySettingsTitle)
    ) {

      $this->loggerChannel->error(
        'One or more of the required configuration items from the EU Cookie Compliance module returned an empty value:<pre>$privacyPolicyUrl = @privacyPolicyUrl</pre><pre>$privacyPolicyTitle = @privacyPolicyTitle</pre><pre>$privacySettingsTitle = @privacySettingsTitle</pre>',
        [
          '@privacyPolicyUrl'     => \print_r($privacyPolicyUrl, true),
          '@privacyPolicyTitle'   => \print_r($privacyPolicyTitle, true),
          '@privacySettingsTitle' => \print_r($privacySettingsTitle, true),
        ]
      );

      return [];

    }

    return [
      // This needs to be wrapped in its own key so that #attributes doesn't get
      // removed by the render/block system.
      'privacy_settings_placeholder'  => [
        '#type'         => 'link',
        '#title'        => [
          '#type'         => 'html_tag',
          '#tag'          => 'span',
          '#value'        => $privacyPolicyTitle,
          '#attributes'   => [
            'class'         => [
              self::PLACEHOLDER_BASE_CLASS . '__content',
            ],
          ],
        ],
        '#url'          => Url::fromUserInput($privacyPolicyUrl),
        '#attributes'   => [
          'class'         => [
            self::PLACEHOLDER_BASE_CLASS,
            'button-placeholder',
          ],
          'data-privacy-settings-title' => $privacySettingsTitle,
        ],
        '#attached'   => [
          'library'     => ['omnipedia_block/component.privacy_settings'],
        ],
      ],
    ];

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
      // Add a cache tag for this config so that this block's cache is
      // invalidated whenever the config is updated.
      'config:' . self::EU_COOKIE_COMPLIANCE_CONFIG_NAME
    ]);

  }

}
