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
            methodBoleto = 'mercadopago_paymentmagento_boleto',
            methodPec = 'mercadopago_paymentmagento_pec',
            methodPix = 'mercadopago_paymentmagento_pix',
            methodPagoFacil = 'mercadopago_paymentmagento_pagofacil',
            methodRapiPago = 'mercadopago_paymentmagento_rapipago',
            methodPayCash = 'mercadopago_paymentmagento_paycash',
            methodOxxo = 'mercadopago_paymentmagento_oxxo',
            methodEfecty = 'mercadopago_paymentmagento_efecty',
            methodAbitab = 'mercadopago_paymentmagento_abitab',
            methodRedpagos = 'mercadopago_paymentmagento_redpagos',
            methodPse = 'mercadopago_paymentmagento_pse',
            methodBanamex = 'mercadopago_paymentmagento_banamex',
            methodBancomer = 'mercadopago_paymentmagento_bancomer',
            methodSerfin = 'mercadopago_paymentmagento_serfin',
            methodPagoEfectivo = 'mercadopago_paymentmagento_pagoefectivo',
            methodWebpay = 'mercadopago_paymentmagento_webpay',
            methodTwoCc = 'mercadopago_paymentmagento_twocc';

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

        if (methodBoleto in config) {
            rendererList.push(
                {
                    type: methodBoleto,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/boleto'
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

        if (methodPagoFacil in config) {
            rendererList.push(
                {
                    type: methodPagoFacil,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pago_facil'
                }
            );
        }

        if (methodRapiPago in config) {
            rendererList.push(
                {
                    type: methodRapiPago,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/rapi_pago'
                }
            );
        }

        if (methodPayCash in config) {
            rendererList.push(
                {
                    type: methodPayCash,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pay_cash'
                }
            );
        }

        if (methodOxxo in config) {
            rendererList.push(
                {
                    type: methodOxxo,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/oxxo'
                }
            );
        }

        if (methodEfecty in config) {
            rendererList.push(
                {
                    type: methodEfecty,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/efecty'
                }
            );
        }

        if (methodAbitab in config) {
            rendererList.push(
                {
                    type: methodAbitab,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/abitab'
                }
            );
        }

        if (methodRedpagos in config) {
            rendererList.push(
                {
                    type: methodRedpagos,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/redpagos'
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

        if (methodBanamex in config) {
            rendererList.push(
                {
                    type: methodBanamex,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/banamex'
                }
            );
        }

        if (methodBancomer in config) {
            rendererList.push(
                {
                    type: methodBancomer,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/bancomer'
                }
            );
        }

        if (methodSerfin in config) {
            rendererList.push(
                {
                    type: methodSerfin,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/serfin'
                }
            );
        }

        if (methodPagoEfectivo in config) {
            rendererList.push(
                {
                    type: methodPagoEfectivo,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pago_efectivo'
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
