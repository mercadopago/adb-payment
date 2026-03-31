/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

define([
    'underscore',
    'jquery',
    'mage/translate',
], function (
    _,
    $,
    $t
) {
    'use strict';

    return {

        customLoader() {

            let loaderMessage = $('<div id="loading-area" class="loading-area">'
                +'<div class="loader-area"><div id="custom-loader" class="custom-loader"></div></div>'
                +'<div id="loading-challenge">'
                +'<strong class="loading-message">'+ $t('We are receiving the reply from your bank') + '</strong>'
                +'</div>'
                +'</div>');

            $('#modal-3ds-challenge').empty();
            $('#modal-3ds-challenge').append(loaderMessage);
        },

        formatCreditCard(cardNumber, cardType) {

            var lastDigits = cardNumber;
            var brand = cardType;

            var maskedCardNumber = '*'.repeat(4) + lastDigits;

            var formattedBrand = brand.toLowerCase();
            formattedBrand = formattedBrand.charAt(0).toUpperCase() + formattedBrand.slice(1);

            return `${formattedBrand} ${maskedCardNumber}`;
        },

        createModalChallenge(cardNumber, cardType) {
            let div3DS = $('<div id="modal-3ds-challenge">'
            +'<div id="loading-area" class="loading-area">'
            +'<div class="loader-area"><div id="custom-loader" class="custom-loader"></div></div>'
            +'<div id="loading-challenge">'
            +'<strong class="loading-message">'+ $t('We are taking you to validate the card ') + this.formatCreditCard(cardNumber, cardType) + $t(' with your bank') +'</strong>'
            +'<p>'+ $t('We need to confirm that you are the cardholder.') +'</p></div>'
            +'</div>'
            +'</div>');

            return div3DS;
        },

        validateThreeDSResponse(response) {
            var errorMessage = $t('It was not possible to complete your payment due to a processing error. Please try again later or use another payment method.'),
                data = Array.isArray(response) ? response[0] : null,
                requiredFields = ['three_ds_external_resource_url', 'three_ds_creq', 'payment_id', 'quote_id'],
                missingFields = data ? requiredFields.filter(f => !data[f]) : requiredFields,
                valid = data && missingFields.length === 0;

            return { valid, missingFields, errorMessage: valid ? null : errorMessage };
        },

        createErrorMessage(message) {
            var $errorMessage = $('<div class="messages"><div class="message message-error error"><span></span><div data-ui-id="messages-message-error"></div></div></div>');
            $errorMessage.find('span').text(message);
            return $errorMessage;
        },

        showModalError($modal, errorMessage) {
            $modal.empty();
            $modal.append(this.createErrorMessage(errorMessage));
        },

        appendIframeContent() {
            let iframeDiv = $('<div class="messages"><div class="message message-info info">'
                    + $t('Please keep this page open. If you close it, you will not be able to resume the validation.')
                    +'<div data-ui-id="messages-message-info"></div></div></div>'
                    +'<div class="iframe-div" id="iframe-challenge"></div>'
                    );

            return iframeDiv;
        },
    };
});
