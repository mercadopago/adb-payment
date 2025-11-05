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
        'three_ds': 'MercadoPago_AdbPayment/js/view/payment/method-renderer/three_ds'
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
            }
        }
    }
};
