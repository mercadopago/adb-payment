/* eslint-disable max-len */
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

define([
    'underscore',
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/url-builder',
    'MercadoPago_PaymentMagento/js/view/payment/mp-security-form',
    'Magento_Vault/js/view/payment/vault-enabler',
    'mage/url',
    'MercadoPago_PaymentMagento/js/model/mp-card-data',
    'MercadoPago_PaymentMagento/js/action/checkout/set-finance-cost',
    'Magento_Ui/js/model/messageList',
    'mage/translate'
], function (
    _,
    $,
    fullScreenLoader,
    quote,
    totals,
    urlBuilder,
    Component,
    VaultEnabler,
    urlFormatter,
    mpCardData,
    setFinanceCost,
    messageList,
    $t
 ) {
    'use strict';
    return Component.extend({

        totals: quote.getTotals(),

        defaults: {
            active: true,
            template: 'MercadoPago_PaymentMagento/payment/twocc',
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'mercadopago_paymentmagento_twocc';
        },

    });
});
