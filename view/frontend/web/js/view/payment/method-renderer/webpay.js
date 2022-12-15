/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

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
            template: 'MercadoPago_PaymentMagento/payment/webpay',
            webpayForm: 'MercadoPago_PaymentMagento/payment/webpay-form',
            financialInstitution: ''
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'active',
                'financialInstitution'
            ]);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'mercadopago_paymentmagento_webpay';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this;

            this._super();

            self.getSelectDocumentTypes();

            self.financialInstitution.subscribe((value) => {
                mpData.financialInstitution = value;
            });
        },


        /**
         * Get Select Financial Institutions
         * @returns {Array}
         */
        getSelectFinancialInstitutions() {
            return window.checkoutConfig.payment[this.getCode()].finance_inst_options;
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
         * Get data
         * @returns {Object}
         */
        getData() {
            let self = this;

            return {
                method: self.getCode(),
                'additional_data': {
                    'payment_method_id': self.getPaymentIdMethod(),
                    'payer_document_type': self.mpPayerType(),
                    'payer_document_identification': self.mpPayerDocument(),
                    'financial_institution': self.financialInstitution()
                }
            };
        },

        /**
         * Get instruction checkout for Webpay
         * @returns {String}
         */
        getInstructionCheckoutWebpay() {
            return window.checkoutConfig.payment[this.getCode()].instruction_checkout_webpay;
        }
    });
});
