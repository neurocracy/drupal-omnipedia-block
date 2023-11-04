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

  this.addBehaviour(
    'OmnipediaPrivacySettings',
    'omnipedia-privacy-settings',
    '.block-omnipedia-privacy-settings',
    function(context, settings) {

      // Bail if we've already built the toggle so that we don't end up with
      // duplicate elements.
      if ($toggle.length > 0) {
        return;
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
