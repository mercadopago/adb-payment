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
            methodWebpay = 'mercadopago_paymentmagento_webpay';

        if (config[methodCheckoutPro].isActive) {
            rendererList.push(
                {
                    type: methodCheckoutPro,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/checkout_pro'
                }
            );
        }

        if (config[methodCc].isActive) {
            rendererList.push(
                {
                    type: methodCc,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/cc'
                }
            );
        }

        if (config[methodBoleto].isActive) {
            rendererList.push(
                {
                    type: methodBoleto,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/boleto'
                }
            );
        }

        if (config[methodPec].isActive) {
            rendererList.push(
                {
                    type: methodPec,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pec'
                }
            );
        }

        if (config[methodPix].isActive) {
            rendererList.push(
                {
                    type: methodPix,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pix'
                }
            );
        }

        if (config[methodPagoFacil].isActive) {
            rendererList.push(
                {
                    type: methodPagoFacil,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pago_facil'
                }
            );
        }

        if (config[methodRapiPago].isActive) {
            rendererList.push(
                {
                    type: methodRapiPago,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/rapi_pago'
                }
            );
        }

        if (config[methodPayCash].isActive) {
            rendererList.push(
                {
                    type: methodPayCash,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pay_cash'
                }
            );
        }

        if (config[methodOxxo].isActive) {
            rendererList.push(
                {
                    type: methodOxxo,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/oxxo'
                }
            );
        }

        if (config[methodEfecty].isActive) {
            rendererList.push(
                {
                    type: methodEfecty,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/efecty'
                }
            );
        }

        if (config[methodAbitab].isActive) {
            rendererList.push(
                {
                    type: methodAbitab,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/abitab'
                }
            );
        }

        if (config[methodRedpagos].isActive) {
            rendererList.push(
                {
                    type: methodRedpagos,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/redpagos'
                }
            );
        }

        if (config[methodPse].isActive) {
            rendererList.push(
                {
                    type: methodPse,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pse'
                }
            );
        }

        if (config[methodBanamex].isActive) {
            rendererList.push(
                {
                    type: methodBanamex,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/banamex'
                }
            );
        }

        if (config[methodBancomer].isActive) {
            rendererList.push(
                {
                    type: methodBancomer,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/bancomer'
                }
            );
        }

        if (config[methodSerfin].isActive) {
            rendererList.push(
                {
                    type: methodSerfin,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/serfin'
                }
            );
        }

        if (config[methodPagoEfectivo].isActive) {
            rendererList.push(
                {
                    type: methodPagoEfectivo,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/pago_efectivo'
                }
            );
        }

        if (config[methodWebpay].isActive) {
            rendererList.push(
                {
                    type: methodWebpay,
                    component: 'MercadoPago_PaymentMagento/js/view/payment/method-renderer/webpay'
                }
            );
        }

        return Component.extend({});
    }
);
