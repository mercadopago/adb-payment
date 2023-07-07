/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

define([
    'MercadoPago_AdbPayment/js/view/payment/method-renderer/checkout_pro',
], function (
    Component,
) {
    'use strict';

    return Component.extend({
        redirectAfterPlaceOrder: true,
        defaults: {
            active: false,
            template: 'MercadoPago_AdbPayment/payment/checkout-credits',
            checkoutCreditsForm: 'MercadoPago_AdbPayment/payment/checkout-credits-form'
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'mercadopago_adbpayment_checkout_credits';
        },

        /**
         * Get banner images
         * @param {String} type
         * @returns {Boolean}
         */
        getImages: function (type) {
            return window.checkoutConfig.payment[this.getCode()].images.hasOwnProperty(type)
            ? window.checkoutConfig.payment[this.getCode()].images[type]
            : false;
        },

        /**
         * Get credits banner text
         * @param {String} type
         * @returns {Boolean}
         */
        getBannerTexts(type) {
            return window.checkoutConfig.payment[this.getCode()].texts.hasOwnProperty(type)
            ? window.checkoutConfig.payment[this.getCode()].texts[type]
            : false;
        },
    });
});
