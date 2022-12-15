/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

define([
    'MercadoPagoSDKJs'
], function () {
    'use strict';
    // eslint-disable-next-line no-undef
    window.mp = new MercadoPago(window.checkoutConfig.payment['mercadopago_paymentmagento'].public_key, {
        locale: window.checkoutConfig.payment['mercadopago_paymentmagento'].locale
    });
});
