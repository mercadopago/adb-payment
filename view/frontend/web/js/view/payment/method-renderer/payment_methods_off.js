
define([
    'underscore',
    'jquery',
    'MercadoPago_PaymentMagento/js/view/payment/mp-security-form',
    'MercadoPago_PaymentMagento/js/model/mp-card-data'
], function (
    _,
    $,
    Component,
    mpData
) {
    'use strict';

    return Component.extend({
        defaults: {
            active: false,
            template: 'MercadoPago_PaymentMagento/payment/payment-methods-off',
            paymentMethodsOffForm: 'MercadoPago_PaymentMagento/payment/payment-methods-off-form',
            payerFirstName: '',
            payerLastName: ''
        },

        paymentMethodsOff: [],

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
            return 'mercadopago_paymentmagento_payment_methods_off';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this;

            this._super();

            self.payerFirstName.subscribe((value) => {
                mpData.payerFirstName = value;
            });

            self.payerLastName.subscribe((value) => {
                mpData.payerLastName = value;
            });

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
              return { "id": input.value, "payment_type_id": input.getAttribute("payment_type_id")};
            }
    
            var element = document.querySelector('input[name="payment[payment_methods_off]"]:checked');
    
            if (this.getCountPaymentMethodsOffActive() > 1 && element) {
              return { "id": element.value, "payment_type_id": element.getAttribute("payment_type_id")};
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

            console.log(this.getPaymentSelected());

            return {
                method: self.getCode(),
                'additional_data': {
                    'payment_method_id': self.getPaymentIdMethod(),
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
        }
    });
});
