/**
 * Copyright © MercadoPago. All rights reserved.
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */
let config = {
    paths: {
        'mercadoPagoSkdJs':'https://sdk.mercadopago.com/js/v2?source=Magento',
        'initMPCheckout': 'MercadoPago_AdbPayment/js/view/payment/initMPCheckout',
        'observerCheckoutPro': 'MercadoPago_AdbPayment/js/view/payment/observerCheckoutPro',
        'three_ds': 'MercadoPago_AdbPayment/js/view/payment/method-renderer/three_ds',
        'mpErrorObserver': 'MercadoPago_AdbPayment/js/view/payment/mp-error-observer'
    },
    shim: {
        'mercadoPagoSkdJs': {
            'exports': 'MercadoPago',
            'deps': ['jquery']
        }
    },
    deps: [
        'initMPCheckout'
    ],
    config: {
        mixins: {
            'mage/validation': {
                'MercadoPago_AdbPayment/js/validation/custom-validation-mixin': true
            },
            'MercadoPago_AdbPayment/js/view/payment/default': {
                'MercadoPago_AdbPayment/js/view/melidata/melidata_client': true
            },
            'MercadoPago_AdbPayment/js/view/payment/method-renderer/cc': {
                'MercadoPago_AdbPayment/js/mixin/field-event-tracker': true
            },
            'MercadoPago_AdbPayment/js/view/payment/method-renderer/twocc': {
                'MercadoPago_AdbPayment/js/mixin/field-event-tracker': true
            },
            'MercadoPago_AdbPayment/js/view/payment/method-renderer/pix': {
                'MercadoPago_AdbPayment/js/mixin/field-event-tracker': true
            },
            'MercadoPago_AdbPayment/js/view/payment/method-renderer/pse': {
                'MercadoPago_AdbPayment/js/mixin/field-event-tracker': true
            },
            'MercadoPago_AdbPayment/js/view/payment/method-renderer/webpay': {
                'MercadoPago_AdbPayment/js/mixin/field-event-tracker': true
            },
            'MercadoPago_AdbPayment/js/view/payment/method-renderer/yape': {
                'MercadoPago_AdbPayment/js/mixin/field-event-tracker': true
            },
            'MercadoPago_AdbPayment/js/view/payment/method-renderer/payment_methods_off': {
                'MercadoPago_AdbPayment/js/mixin/field-event-tracker': true
            },
            'MercadoPago_AdbPayment/js/view/payment/method-renderer/checkout_pro': {
                'MercadoPago_AdbPayment/js/mixin/field-event-tracker': true
            }
        }
    }
};
