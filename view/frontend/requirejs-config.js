/**
 * Copyright © MercadoPago. All rights reserved.
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */
let config = {
    paths: {
        'mercadoPagoSkdJs':'https://sdk.mercadopago.com/js/v2?source=Magento',
        'initMPCheckout': 'MercadoPago_PaymentMagento/js/view/payment/initMPCheckout',
        'observerCheckoutPro': 'MercadoPago_PaymentMagento/js/view/payment/observerCheckoutPro'
    },
    shim: {
        'mercadoPagoSkdJs': {
            'deps': ['jquery']
        }
    },
    config: {
        mixins: {
            'mage/validation': {
                'MercadoPago_PaymentMagento/js/validation/custom-validation-mixin': true
            }
        }
    }
};
