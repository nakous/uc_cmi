/**
 * @file
 * For create cmi token when checkout page appear.
 */

(function ($, Drupal) {
  'use strict';

  $('input#edit-panes-payment-details-cc-number').removeAttr('name');
  $('input#edit-panes-payment-details-cc-cvv').removeAttr('name');
  $('input#cc-date-year').removeAttr('name');
  var checkedInput = $('#uc-cart-checkout-form .form-radios .js-form-type-radio input:checked').val();
  var embeddedInput = $('#uc-cart-checkout-form .form-radios .js-form-type-radio .uc-cmi-embedded').parent('.option').siblings('input').val();
  // checking condition.
  if (checkedInput === embeddedInput) {
    var merchantId = drupalSettings.uc_cmi.merchant_id;
    var agreementId = drupalSettings.uc_cmi.agreement_id;
    cmi.Embedded.Form($('.uc-cart-checkout-form'), {
      merchant_id: merchantId,
      agreement_id: agreementId,
      brandChanged: function (brand) {
        $('.form-item-panes-payment-details-cc-number input').after('<span class="card-brand">' + brand + '</span>');
      },
      beforeCreateToken: function (form) {
        $('input.error').removeClass('error');
      },
      failure: function (form, source, message) {
        if (source === 'validation') {
          for (var i = 0; i < message.length; i++) {
            $('input[data-cmi=' + message[i] + ']').addClass('error');
          }
        }
        else {
          alert(source + ': ' + message);
        }
      },
      success: function (form, token) {
        $('input#edit-panes-payment-details-cc-number').attr('name', 'panes[payment][details][cc_number]');
        $('input#cc-date-year').attr('name', 'panes[payment][details][date_year]');
        $('.uc-cart-checkout-form #edit-continue').attr('name', 'op');
      }
    });
  }
  // Ajaxsucess on payment change.
  $(document).ajaxSuccess(function (event, xhr, settings) {
    $('input#edit-panes-payment-details-cc-number').removeAttr('name');
    $('input#edit-panes-payment-details-cc-cvv').removeAttr('name');
    $('input#cc-date-year').removeAttr('name');
    if (event.target.activeElement.nextElementSibling) {
      var clickElement = event.target.activeElement.nextElementSibling.firstElementChild.className;
      if (clickElement === 'uc-cmi uc-cmi-embedded') {
        location.reload();
      }
    }
  });
})(jQuery, Drupal);
