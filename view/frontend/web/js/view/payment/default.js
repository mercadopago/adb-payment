/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'underscore',
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
], function (
    _,
    $,
    Component,
    quote,
) {
    'use strict';

    return Component.extend({
        /**
         * Get logo
         * @returns {String}
         */
        getLogo() {
            return window.checkoutConfig.payment[this.getCode()].logo;
        },

        /**
         * Get title
         * @returns {String}
         */
        getTitle() {
            return window.checkoutConfig.payment[this.getCode()].title;
        },

        /**
         * Get Payment Id Method
         * @returns {String}
         */
        getPaymentIdMethod() {
            return window.checkoutConfig.payment[this.getCode()].payment_method_id;
        },

        /**
         * Get Expiration
         * @returns {String}
         */
        getExpiration() {
            return window.checkoutConfig.payment[this.getCode()].expiration;
        },

        /**
         * Get Mp Site Id
         * @returns {String}
         */
        getMpSiteId() {
            return window.checkoutConfig.payment['mercadopago_paymentmagento'].mp_site_id;
        },

        /**
         * Is document identification capture
         * @returns {Boolean}
         */
        DocumentIdentificationCapture() {

            if (this.getMpSiteId() === 'MLM') {
                return false;
            }

            if (this.getMpSiteId() !== 'MLB') {
                return true;
            }

            if (!quote.billingAddress()) {
                return window.checkoutConfig.payment[this.getCode()].document_identification_capture;
            }

            if (!quote.billingAddress().vatId) {
                return true;
            }

            return window.checkoutConfig.payment[this.getCode()].document_identification_capture;
        },
    });
});