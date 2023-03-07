/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
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
            methodCheckoutPro = 'mercadopago_paymentmagento_checkout_pro',
            methodCc = 'mercadopago_paymentmagento_cc',
            methodPec = 'mercadopago_paymentmagento_pec',
            methodPix = 'mercadopago_paymentmagento_pix',
            methodPse = 'mercadopago_paymentmagento_pse',
            methodWebpay = 'mercadopago_paymentmagento_webpay',
            methodsOff = 'mercadopago_paymentmagento_payment_methods_off',
            methodTwoCc = 'mercadopago_paymentmagento_twocc';


        if (methodsOff in config) {
            rendererList.push(
                {
                    type: methodsOff,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/payment_methods_off'
                }
            );
        }

        if (methodCheckoutPro in config) {
            rendererList.push(
                {
                    type: methodCheckoutPro,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/checkout_pro'
                }
            );
        }

        if (methodCc in config) {
            rendererList.push(
                {
                    type: methodCc,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/cc'
                }
            );
        }

        if (methodPec in config) {
            rendererList.push(
                {
                    type: methodPec,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pec'
                }
            );
        }

        if (methodPix in config) {
            rendererList.push(
                {
                    type: methodPix,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pix'
                }
            );
        }

        if (methodPse in config) {
            rendererList.push(
                {
                    type: methodPse,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pse'
                }
            );
        }

        if (methodWebpay in config) {
            rendererList.push(
                {
                    type: methodWebpay,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/webpay'
                }
            );
        }

        if (methodTwoCc in config) {
            rendererList.push(
                {
                    type: methodTwoCc,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/twocc'
                }
            );
        }

        return Component.extend({});
    }
);
