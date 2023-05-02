/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

define([
    'underscore',
    'jquery',
    'MercadoPago_AdbPayment/js/view/payment/default',
], function (
    _,
    $,
    Component,
) {
    'use strict';

    return Component.extend({
        defaults: {
            active: false,
            template: 'MercadoPago_AdbPayment/payment/pse',
            pseForm: 'MercadoPago_AdbPayment/payment/pse-form',
            payerEntityType: '',
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
                'payerEntityType',
                'financialInstitution'
            ]);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'mercadopago_adbpayment_pse';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this;

            this._super();

            self.getSelectDocumentTypes();
        },

        /**
         * Get Select Entity Types
         * @returns {Array}
         */
        getSelectEntityTypes() {
            return window.checkoutConfig.payment[this.getCode()].payer_entity_types;
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
                    'payer_entity_type': self.payerEntityType(),
                    'financial_institution': self.financialInstitution()
                }
            };
        },

        /**
         * Get instruction checkout for Pse
         * @returns {String}
         */
        getInstructionCheckoutPse() {
            return window.checkoutConfig.payment[this.getCode()].instruction_checkout_pse;
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
