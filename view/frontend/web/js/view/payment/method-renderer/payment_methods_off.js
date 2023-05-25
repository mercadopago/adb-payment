
define([
    'underscore',
    'jquery',
    'MercadoPago_AdbPayment/js/view/payment/default'
], function (
    _,
    $,
    Component
) {
    'use strict';

    return Component.extend({
        defaults: {
            active: false,
            template: 'MercadoPago_AdbPayment/payment/payment-methods-off',
            paymentMethodsOffForm: 'MercadoPago_AdbPayment/payment/payment-methods-off-form',
            payerFirstName: '',
            payerLastName: '',
            paymentMethodsOff: []
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'active',
                'payerFirstName',
                'payerLastName'
            ]);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'mercadopago_adbpayment_payment_methods_off';
        },

        /**
         * Init component
         */
        initialize() {
            this._super();

            this.loadPaymentMethodsOffActive();
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

        getPaymentSelected: function () {
            if ( this.getCountPaymentMethodsOffActive() === 1) {
                var input = document.getElementsByName("payment[payment_methods_off]")[0];
                return {
                    "payment_method_id": input.getAttribute("payment_method_id"),
                    "payment_type_id": input.getAttribute("payment_type_id"),
                    "payment_option_id": input.getAttribute("payment_option_id")
                };
            }

            var element = document.querySelector('input[name="payment[payment_methods_off]"]:checked');

            if (this.getCountPaymentMethodsOffActive() > 1 && element) {
                return {
                    "payment_method_id": element.getAttribute("payment_method_id"),
                    "payment_type_id": element.getAttribute("payment_type_id"),
                    "payment_option_id": element.getAttribute("payment_option_id")
                };
            } else {
                return false;
            }
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData() {
            let self = this;

            var payment_method_data = this.getPaymentSelected();

            return {
                method: self.getCode(),
                'additional_data': {
                    'payment_method_id': payment_method_data.payment_method_id,
                    'payment_type_id': payment_method_data.payment_type_id,
                    'payment_option_id': payment_method_data.payment_option_id,
                    'payer_first_name': self.payerFirstName(),
                    'payer_last_name': self.payerLastName(),
                    'payer_document_type': self.mpPayerType(),
                    'payer_document_identification': self.mpPayerDocument()
                }
            };
        },

        /**
         * Is name capture
         * @returns {boolean}
         */
        NameCapture() {
            return window.checkoutConfig.payment[this.getCode()].name_capture;
        },

        /**
         * Load Payment Methods Off Active
         */
        loadPaymentMethodsOffActive() {
            this.paymentMethodsOff = window.checkoutConfig.payment[this.getCode()].payment_methods_off_active;
        },

        getCountPaymentMethodsOffActive() {
            return this.paymentMethodsOff.length;
        },

        getPaymentMethodsOff() {
            return this.paymentMethodsOff;
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
