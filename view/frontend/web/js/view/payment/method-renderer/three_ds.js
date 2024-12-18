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
