/* eslint-disable max-len */
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

define([
    'underscore',
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/url-builder',
    'MercadoPago_AdbPayment/js/view/payment/mp-sdk',
    'Magento_Vault/js/view/payment/vault-enabler',
], function (
    _,
    $,
    fullScreenLoader,
    quote,
    totals,
    urlBuilder,
    Component,
    VaultEnabler,
 ) {
    'use strict';
    return Component.extend({

        totals: quote.getTotals(),

        defaults: {
            active: false,
            template: 'MercadoPago_AdbPayment/payment/cc',
            ccForm: 'MercadoPago_AdbPayment/payment/cc-form',
            securityField: 'MercadoPago_AdbPayment/payment/security-field',
            amount: '',
            installmentTextInfo: false,
            installmentTextTEA: null,
            installmentTextCFT: null,
            isLoading: true,
            fieldCcNumber: 'mercadopago_adbpayment_cc_number',
            fieldSecurityCode: 'mercadopago_adbpayment_cc_cid',
            fieldExpMonth: 'mercadopago_adbpayment_cc_expiration_month',
            fieldExpYear: 'mercadopago_adbpayment_cc_expiration_yr',
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'mercadopago_adbpayment_cc';
        },

        /**
         * Initializes model instance observable.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'active',
                'amount',
                'isLoading',
                'installmentTextInfo',
                'installmentTextTEA',
                'installmentTextCFT'
            ]);
            return this;
        },

        /**
         * Init component
         */
        initialize() {
            let self = this;

            this._super();

            this.vaultEnabler = new VaultEnabler();

            this.vaultEnabler.setPaymentCode(this.getVaultCode());

            if (quote.billingAddress()) {
                self.mpPayerDocument(quote.billingAddress().vatId);
            }

            self.active.subscribe((value) => {
                if (value === true) {
                    self.getSelectDocumentTypes();
                    self.getInstallments();

                    setTimeout(() => {
                        self.mountCardForm({
                            fieldCcNumber: self.fieldCcNumber,
                            fieldSecurityCode: self.fieldSecurityCode,
                            fieldExpMonth: self.fieldExpMonth,
                            fieldExpYear: self.fieldExpYear,
                        });
                        self.isLoading(false);
                    }, 3000);
                }

                if (value === false) {
                    self.isLoading(true);
                }
            });

            self.installmentsAmount.subscribe((value) => {
                self.getInstallments();
            });

            quote.totals.subscribe((value) => {
                const fcObject = value.total_segments.filter(segment => segment.code === 'finance_cost_amount')[0] ?? null
                const financeCostAmount = fcObject && fcObject.value ? fcObject.value : 0;
                const amount = self.FormattedCurrencyToInstallments(value.base_grand_total - financeCostAmount);

                self.amount(amount);
                self.installmentsAmount(amount);
            });
        },

        /**
         * Is Active
         * @returns {Boolean}
         */
        isActive() {
            var self = this,
                active = self.getCode() === self.isChecked();

            self.active(active);

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
        async beforePlaceOrder() {
            if (!$(this.formElement).valid()) {
                return;
            }
            if (await this.generateToken(0)) {
                this.placeOrder();
            }
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData() {
            var self = this,
                data;

            data = {
                'method': this.getCode(),
                'additional_data': {
                    'payer_document_type': self.generatedCards[0]?.documentType,
                    'payer_document_identification': self.generatedCards[0]?.documentValue,
                    'card_number_token': self.generatedCards[0]?.token.id,
                    'card_holder_name': self.generatedCards[0]?.holderName,
                    'card_number': self.generatedCards[0]?.cardNumber,
                    'card_exp_month': self.generatedCards[0]?.cardExpirationMonth,
                    'card_exp_year': self.generatedCards[0]?.cardExpirationYear,
                    'card_type': self.generatedCards[0]?.cardType,
                    'card_installments': self.generatedCards[0]?.cardInstallment,
                    'card_finance_cost': self.generatedCards[0]?.cardFinanceCost,
                    'card_public_id': self.generatedCards[0]?.cardPublicId,
                    'mp_user_id': self.generatedCards[0]?.mpUserId,
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
            this.vaultEnabler.visitAdditionalData(data);

            return data;
        },

        /**
         * Is show legend
         * @returns {Boolean}
         */
        isShowLegend() {
            return true;
        },

        /**
         * Get Cc Type
         * @returns {Object}
         */
        getCcType() {
            return window.checkoutConfig.payment[this.getCode()].ccTypesMapper;
        },

        /**
         * Get Unsupported Pre Auth
         * @returns {Object}
         */
        getUnsupportedPreAuth() {
            return window.checkoutConfig.payment[this.getCode()].unsupported_pre_auth;
        },

        /**
         * Get Vault Code
         * @returns {Boolean}
         */
        getVaultCode() {
            return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
        },

        /**
         * Is Vault enabled
         * @returns {Boolean}
         */
        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        }
    });
});
