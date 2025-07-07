// -----------------------------------------------------------------------------
//   Omnipedia - Block - Privacy settings component
// -----------------------------------------------------------------------------

// This progressively enhances the privacy settings placeholder link into a
// button that opens the EU Cookie Compliance pop-up.

AmbientImpact.onGlobals([
  'Drupal.eu_cookie_compliance.toggleWithdrawBanner',
], function() {
AmbientImpact.addComponent('OmnipediaPrivacySettings', function(
  OmnipediaPrivacySettings, $
) {

  'use strict';

  /**
   * The privacy settings toggle, if any, wrapped in a jQuery collection.
   *
   * @type {jQuery}
   */
  var $toggle = $();

  /**
   * Get the privacy settings toggle jQuery collection.
   *
   * @return {jQuery}
   */
  this.getToggle = function() {
    return $toggle;
  };

  // We want this to be detached on leaving a page and before rendering a cached
  // snapshot, but critically we should not detach on
  // 'refreshless:before-cache' because that will cause a flash of the link, and
  // break the privacy pop-up's reference to it.
  //
  // @todo Refactor this component so it doesn't store a persistent reference
  //   to the toggle because that can easily get out of sync with the page as
  //   demonstrated by RefreshLess.
  //
  // @todo Fix delaying caching not working in RefreshLess and remove this?
  const triggers = AmbientImpact.defaults.detachTriggers.filter(
    (trigger) => trigger !== 'refreshless:before-cache',
  );

  triggers.push('refreshless:cached-snapshot');

  this.addBehaviour(
    'OmnipediaPrivacySettings',
    'omnipedia-privacy-settings',
    '.block-omnipedia-privacy-settings',
    triggers,
    function(context, settings) {

      // Remove any existing toggle if it exists so that we don't end up with
      // duplicate elements.
      if ($toggle.length > 0) {

        console.warn(
          'Found existing privacy toggle while attaching behaviour:', $toggle
        );

        $toggle.remove();

      }

      /**
       * The privacy settings placeholder link wrapped in a jQuery collection.
       *
       * @type {jQuery}
       */
      var $placeholderLink = $(
        '.omnipedia-privacy-settings-placeholder', context
      );

      // Bail if we can't find the placeholder link.
      if ($placeholderLink.length === 0) {
        return;
      }

      /**
       * The privacy settings button element wrapped in a jQuery collection.
       *
       * @type {jQuery}
       */
      var $button = $('<button></button>');

      $button
        // Use the text provided by the EU Cookie Compliance settings in the
        // backend.
        .text($placeholderLink.data('privacySettingsTitle'))
        .addClass([
          'omnipedia-privacy-settings-toggle',
          'material-button',
          'button--primary',
        ])
        .on(
          'click.OmnipediaPrivacySettings',
          Drupal.eu_cookie_compliance.toggleWithdrawBanner
        )
        .insertAfter($placeholderLink);

      $placeholderLink.attr('hidden', true);

      $toggle = $button;

    },
    function(context, settings, trigger) {

      /**
       * The privacy settings button element wrapped in a jQuery collection.
       *
       * @type {jQuery}
       */
      var $button = $('.omnipedia-privacy-settings-toggle', context);

      /**
       * The privacy settings placeholder link wrapped in a jQuery collection.
       *
       * @type {jQuery}
       */
      var $placeholderLink = $(
        '.omnipedia-privacy-settings-placeholder', context
      );

      $button.remove();

      $placeholderLink.removeAttr('hidden');

      $toggle = $();

    }
  );

});
});
