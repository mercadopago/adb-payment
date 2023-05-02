/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */
define(
    [
        'initMPCheckout',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        _initMPCheckout,
        Component,
        rendererList
    ) {
        'use strict';
        var config = window.checkoutConfig.payment,
            methodCheckoutPro = 'mercadopago_adbpayment_checkout_pro',
            methodCc = 'mercadopago_adbpayment_cc',
            methodPec = 'mercadopago_adbpayment_pec',
            methodPix = 'mercadopago_adbpayment_pix',
            methodPse = 'mercadopago_adbpayment_pse',
            methodWebpay = 'mercadopago_adbpayment_webpay',
            methodsOff = 'mercadopago_adbpayment_payment_methods_off',
            methodTwoCc = 'mercadopago_adbpayment_twocc';


        if (methodsOff in config) {
            rendererList.push(
                {
                    type: methodsOff,
                    component: 'MercadoPago_AdbPayment/js/view/payment/method-renderer/payment_methods_off'
                }
            );
        }

        if (methodCheckoutPro in config) {
            rendererList.push(
                {
                    type: methodCheckoutPro,
                    component: 'MercadoPago_AdbPayment/js/view/payment/method-renderer/checkout_pro'
                }
            );
        }

        if (methodCc in config) {
            rendererList.push(
                {
                    type: methodCc,
                    component: 'MercadoPago_AdbPayment/js/view/payment/method-renderer/cc'
                }
            );
        }

        if (methodPec in config) {
            rendererList.push(
                {
                    type: methodPec,
                    component: 'MercadoPago_AdbPayment/js/view/payment/method-renderer/pec'
                }
            );
        }

        if (methodPix in config) {
            rendererList.push(
                {
                    type: methodPix,
                    component: 'MercadoPago_AdbPayment/js/view/payment/method-renderer/pix'
                }
            );
        }

        if (methodPse in config) {
            rendererList.push(
                {
                    type: methodPse,
                    component: 'MercadoPago_AdbPayment/js/view/payment/method-renderer/pse'
                }
            );
        }

        if (methodWebpay in config) {
            rendererList.push(
                {
                    type: methodWebpay,
                    component: 'MercadoPago_AdbPayment/js/view/payment/method-renderer/webpay'
                }
            );
        }

        if (methodTwoCc in config) {
            rendererList.push(
                {
                    type: methodTwoCc,
                    component: 'MercadoPago_AdbPayment/js/view/payment/method-renderer/twocc'
                }
            );
        }

        return Component.extend({});
    }
);
