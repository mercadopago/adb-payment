/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

define([
    'MercadoPagoSDKJs'
], function () {
    'use strict';
    // eslint-disable-next-line no-undef
    window.mp = new MercadoPago(window.checkoutConfig.payment['mercadopago_adbpayment'].public_key, {
        locale: window.checkoutConfig.payment['mercadopago_adbpayment'].locale
    });
});
