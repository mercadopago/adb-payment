/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

 define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals',
        'mage/translate'
    ],
    function (Component, quote, priceUtils, totals, $t) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'MercadoPago_AdbPayment/cart/summary/finance_cost',
                active: false
            },
            totals: quote.getTotals(),

            /**
             * Init observable variables
             *
             * @return {Object}
             */
            initObservable() {
                this._super().observe(['active']);
                return this;
            },

            /**
             * Is Active
             * @return {*|Boolean}
             */
            isActive() {
                return this.getPureValue() !== 0;
            },

            /**
             * Get Pure Value
             * @return {*}
             */
            getPureValue() {
                var financeCost = 0;

                if (this.totals() && totals.getSegment('finance_cost_amount')) {
                    financeCost = totals.getSegment('finance_cost_amount').value;
                    return financeCost;
                }

                return financeCost;
            },

            /**
             * Custon Title
             * @return {*|String}
             */
            customTitle() {
                if (this.getPureValue() > 0) {
                    return $t('Finance Cost');
                }
                return $t('Discount for payment at sight');
            },

            /**
             * Get Value
             * @return {*|String}
             */
            getValue() {
                return this.getFormattedPrice(this.getPureValue());
            }
        });
    }
);
