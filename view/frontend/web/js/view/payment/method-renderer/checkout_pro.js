/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

define([
    'underscore',
    'jquery',
    'Magento_Checkout/js/model/url-builder',
    'MercadoPago_AdbPayment/js/view/payment/default',
    'mage/storage',
    'observerCheckoutPro'
], function (
    _,
    $,
    urlBuilder,
    Component,
    storage
) {
    'use strict';

    return Component.extend({
        redirectAfterPlaceOrder: true,
        defaults: {
            active: false,
            template: 'MercadoPago_AdbPayment/payment/checkout-pro',
            checkoutProForm: 'MercadoPago_AdbPayment/payment/checkout-pro-form'
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe(['active']);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'mercadopago_adbpayment_checkout_pro';
        },

        /**
         * Init component
         */
        initialize() {
            this._super();
        },

        /**
         * Is Active
         * @returns {Boolean}
         */
        isActive() {
            var active = this.getCode() === this.isChecked();

            this.active(active);
            return active;
        },

        /**
         * Init Form Element
         * @returns {void}
         */
        initFormElement(element) {
            this.formElement = element;
            $(this.formElement).validation();
        },

        /**
         * Before Place Order
         * @returns {void}
         */
        beforePlaceOrder() {
            if (!$(this.formElement).valid()) {
                return;
            }
            this.placeOrder();
        },

        /**
         * Place order.
         */
        placeOrder: function (data, event) {
            var self = this,
                serviceUrl;

            if (event) {
                event.preventDefault();
            }

            if (
                this.isPlaceOrderActionAllowed() === true
            ) {
                this.isPlaceOrderActionAllowed(false);

                this.getPlaceOrderDeferredObject()
                    .done(
                        function (res) {
                            serviceUrl = urlBuilder.createUrl('/orders/:orderId/mp-payment-information', {
                                orderId: res
                            });

                            storage.get(
                                serviceUrl, false
                            ).done(function (response) {
                                if (self.getTypeRedirect() === 'modal') {
                                    window.mp.checkout({
                                        'preference': {
                                            'id': response[0].id
                                        },
                                        'theme': self.getTheme(),
                                        'autoOpen': true
                                    });
                                }
                                if (self.getTypeRedirect() === 'redirect') {
                                    window.location.href = response[0].init_point;
                                }
                            });

                            self.afterPlaceOrder();
                        }
                    );

                return true;
            }

            return false;
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData() {
            return {
                method: this.getCode()
            };
        },

        /**
         * Get Type Redirect
         * @returns {string}
         */
        getTypeRedirect() {
            return window.checkoutConfig.payment[this.getCode()].type_redirect;
        },

        /**
         * Get Theme
         * @returns {string}
         */
        getTheme() {
            return window.checkoutConfig.payment[this.getCode()].theme;
        },

        /**
         * Get instruction checkout
         * @returns {string}
         */
        getInstructionCheckout() {
            return window.checkoutConfig.payment[this.getCode()].instruction_checkout;
        },

        /**
         * Adds terms and conditions link to checkout
         * @returns {string}
         */
        getFingerprint() {
            return window.checkoutConfig.payment[this.getCode()].fingerprint;
        },
    });
});
