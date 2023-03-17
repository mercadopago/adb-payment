/* eslint-disable max-len */
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

define([
    'underscore',
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/url-builder',
    'MercadoPago_PaymentMagento/js/view/payment/mp-sdk',
], function (
    _,
    $,
    fullScreenLoader,
    quote,
    totals,
    urlBuilder,
    Component,
) {
    'use strict';
    return Component.extend({

        totals: quote.getTotals(),

        defaults: {
            active: false,
            template: 'MercadoPago_PaymentMagento/payment/twocc',
            twoCcForm: 'MercadoPago_PaymentMagento/payment/twocc-form',
            securityField: 'MercadoPago_PaymentMagento/payment/security-field',
            amount: '',
            installmentTextInfo: false,
            installmentTextTEA: null,
            installmentTextCFT: null,
            isLoading: true,
            fieldCcNumber: 'mercadopago_paymentmagento_twocc_number',
            fieldSecurityCode: 'mercadopago_paymentmagento_twocc_cid',
            fieldExpMonth: 'mercadopago_paymentmagento_twocc_expiration_month',
            fieldExpYear: 'mercadopago_paymentmagento_twocc_expiration_yr',
            cardIndex: 0,
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'mercadopago_paymentmagento_twocc';
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
                'installmentTextCFT',
                'cardIndex',
            ]);
            return this;
        },

        /**
         * Init component
         */
        initialize() {
            let self = this;

            this._super();

            if (quote.billingAddress()) {
                self.mpPayerDocument(quote.billingAddress().vatId);
            }

            self.amount(quote.totals().base_grand_total);

            self.active.subscribe((value) => {
                if (value === true) {
                    self.getSelectDocumentTypes();
                    self.getListOptionsToInstallments();

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

            quote.totals.subscribe((value) => {
                var financeCostAmount = 0;

                if (this.totals() && totals.getSegment('finance_cost_amount')) {
                    financeCostAmount = totals.getSegment('finance_cost_amount').value;
                }

                self.amount(value.base_grand_total - financeCostAmount);
            });

            self.amount.subscribe((value) => {
                self.getListOptionsToInstallments(value);
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

            const tokenResponse = await this.generateToken(this.cardIndex());

            if (tokenResponse === false) {
                return;
            }

            if (this.cardIndex() > 0) {
                this.placeOrder();
                return;
            }

            this.cardIndex(this.cardIndex() + 1);
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
                'additional_data': {}
            };

            for (let i = 0; i < self.generatedCards.length; i ++) {
                data.additional_data[`payer_${i}_document_type`] = self.generatedCards[i]?.documentType;
                data.additional_data[`payer_${i}_document_identification`] = self.generatedCards[i]?.documentValue;
                data.additional_data[`card_${i}_number_token`] = self.generatedCards[i]?.token.id;
                data.additional_data[`card_${i}_holder_name`] = self.generatedCards[i]?.holderName;
                data.additional_data[`card_${i}_number`] = self.generatedCards[i]?.cardNumber;
                data.additional_data[`card_${i}_exp_month`] = self.generatedCards[i]?.cardExpirationMonth;
                data.additional_data[`card_${i}_exp_year`] = self.generatedCards[i]?.cardExpirationYear;
                data.additional_data[`card_${i}_type`] = self.generatedCards[i]?.cardType;
                data.additional_data[`card_${i}_installments`] = self.generatedCards[i]?.cardInstallment;
                data.additional_data[`card_${i}_public_id`] = self.generatedCards[i]?.cardPublicId;
                data.additional_data[`mp_${i}_user_id`] = self.generatedCards[i]?.mpUserId;
            }

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

            console.log(data);

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

        isVaultEnabled: function () {
            return false;
        }
    });
});
