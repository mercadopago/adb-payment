/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

define(
    [
        'MercadoPago_AdbPayment/checkout/summary/finance_cost'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            /**
             * @override
             *
             * @returns {boolean}
             */
            isDisplayed() {
                return this.getPureValue() !== 0;
            }
        });
    }
);
