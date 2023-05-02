/**
 * Copyright © MercadoPago. All rights reserved.
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */
let config = {
    paths: {
        'mercadoPagoSkdJs':'https://sdk.mercadopago.com/js/v2?source=Magento',
        'initMPCheckout': 'MercadoPago_AdbPayment/js/view/payment/initMPCheckout',
        'observerCheckoutPro': 'MercadoPago_AdbPayment/js/view/payment/observerCheckoutPro'
    },
    shim: {
        'mercadoPagoSkdJs': {
            'deps': ['jquery']
        }
    },
    config: {
        mixins: {
            'mage/validation': {
                'MercadoPago_AdbPayment/js/validation/custom-validation-mixin': true
            }
        }
    }
};
