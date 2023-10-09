/* eslint-disable max-len */
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @license     See LICENSE for license details.
 */

define([
    'underscore',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'MercadoPago_AdbPayment/js/view/payment/mp-sdk',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/payment/additional-validators',
    'MercadoPago_AdbPayment/js/view/payment/method-renderer/validate-form-security',
    'Magento_Checkout/js/action/redirect-on-success'
], function (
    _,
    $,
    quote,
    totals,
    Component,
    priceUtils,
    additionalValidators,
    validateFormSecurity,
    redirectOnSuccessAction,
 ) {
    'use strict';
    return Component.extend({

        totals: quote.getTotals(),

        defaults: {
            active: false,
            template: 'MercadoPago_AdbPayment/payment/twocc',
            twoCcForm: 'MercadoPago_AdbPayment/payment/twocc-form',
            securityField: 'MercadoPago_AdbPayment/payment/security-field',
            installmentTextInfo: false,
            installmentTextTEA: null,
            installmentTextCFT: null,
            isLoading: true,
            inputValueProgress:'',
            fieldCcNumber: 'mercadopago_adbpayment_twocc_number',
            fieldSecurityCode: 'mercadopago_adbpayment_twocc_cid',
            fieldExpMonth: 'mercadopago_adbpayment_twocc_expiration_month',
            fieldExpYear: 'mercadopago_adbpayment_twocc_expiration_yr',
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'mercadopago_adbpayment_twocc';
        },

        /**
         * Initializes model instance observable.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'active',
                'isLoading',
                'installmentTextInfo',
                'installmentTextTEA',
                'installmentTextCFT',
                'inputValueProgress',
            ]);
            return this;
        },

        /**
         * Init component
         */
        initialize() {
            let self = this;
            quote.paymentMethod.subscribe(function(method){
                self.resetFirstCard();
            }, null, 'change');
            this._super();

            if (quote.billingAddress()) {
                self.mpPayerDocument(quote.billingAddress().vatId);
            }

            self.active.subscribe((value) => {
                if (value === true) {
                    self.initForm();
                }

                if (value === false) {
                    self.isLoading(true);
                }
            });

            self.inputValueProgress.subscribe((value) => {
                self.installmentsAmount(value);
                self.validateMinValue(value);
                if (self.isSiteIdMCOorMLC() && value){
                    var amount = parseFloat(value.toString().replace(/\D/g, ''));
                    self.installmentsAmount(amount);
                    self.inputValueProgress(amount);
                }
            });

            const am = Math.floor(self.amount() / 2);
            self.inputValueProgress(am);

            self.installmentsAmount.subscribe((value) => {
                self.getInstallments();
            });

            quote.totals.subscribe((value) => {
                const self = this;

                const fcObject = value.total_segments.filter(segment => segment.code === 'finance_cost_amount')[0] ?? null
                const financeCostAmount = fcObject && fcObject.value ? fcObject.value : 0;

                const newAmount = self.FormattedCurrencyToInstallments(value.base_grand_total - financeCostAmount);
                const amount = self.amount();

                self.amount(newAmount);

                if (amount !== newAmount) {
                    self.resetFirstCard();
                }

                const firstCardAmount = self.FormattedCurrencyToInstallments(self.inputValueProgress());

                const currentCardAmount = self.cardIndex() == 0
                    ? self.FormattedCurrencyToInstallments(firstCardAmount)
                    : self.FormattedCurrencyToInstallments(newAmount - firstCardAmount);

                if (currentCardAmount !== self.installmentsAmount()) {
                    self.installmentsAmount(currentCardAmount);
                }
            });
        },

        initForm() {
            const self = this;

            self.isLoading(true);
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
            const self = this;

            if (!$(this.formElement).valid()) {
                return;
            }

            if(this.progressHasError()){
                $('#input-progress').trigger('focus');
                return;
            }

            const tokenResponse = await this.generateToken(this.cardIndex());

            if (tokenResponse === false) {
                return;
            }

            if (this.cardIndex() === 0) {
                await this.finishFirstCard();
                return;
            }

            this.placeOrder();
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
                data.additional_data[`card_${i}_finance_cost`] = self.generatedCards[i]?.cardFinanceCost;
                data.additional_data[`card_${i}_public_id`] = self.generatedCards[i]?.cardPublicId;
                data.additional_data[`card_${i}_amount`] = self.generatedCards[i]?.amount;
                data.additional_data[`mp_${i}_user_id`] = self.generatedCards[i]?.mpUserId;
            }

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

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

        isVaultEnabled() {
            return false;
        },

        editFirstCard() {
            if (!this.generatedCards[0]) {
                return;
            }

            if (0 === this.cardIndex()) {
                return;
            }

            delete this.generatedCards[0];
            delete this.generatedCards[1];

            this.installmentsAmount(this.amount() - this.inputValueProgress());
            this.cardIndex(0);
            this.mpSelectedCardType('');
            this.mpCardListInstallments('')
            this.resetCardForm();
            this.initForm();
        },

        async finishFirstCard() {
            if (!$(this.formElement).valid()) {
                return;
            }

            if(this.progressHasError()){
                $('#input-progress').trigger('focus');
                return;
            }

            const tokenGenerated = await this.generateToken();

            if (tokenGenerated === false) {
                return;
            }

            this.cardIndex(1);
            this.installmentsAmount(this.amount() - this.installmentsAmount());
            this.mpSelectedCardType('');
            this.mpCardListInstallments('');
            this.installmentWasCalculated(false),
            this.resetCardForm();
            this.initForm();
        },

        getProgressBarWidth() {
            const w = (this.inputValueProgress() / this.amount()) * 100;

            if (w <= 0 || w > 100) {
                return '100%';
            }

            return `${w}%`;
        },

        progressHasError() {
            if (this.inputValueProgress() == '') {
                return true;
            }

            const v = parseFloat(this.inputValueProgress());
            return v > this.amount() - 1 || v < 1;
        },

        isSiteIdMCOorMLC() {
            if (this.getMpSiteId() === 'MCO' || this.getMpSiteId() === 'MLC') {
                return true;
            }

            return false;
        },

        /**
         * Remaining value label update
         */
        updateRemainingAmount() {
            var amount = this.amount();
            var inputValueProgress = this.inputValueProgress();

            if(inputValueProgress < amount){
                amount = amount - inputValueProgress;
            }

            return this.formatedAmountWithSymbol(this.FormattedCurrencyToInstallments(amount));
        },

        formatedInstallmentAmount() {
            return this.formatedAmountWithSymbol(this.FormattedCurrencyToInstallments(this.installmentsAmount()));
        },

        showFirstCardBlock() {
            if (this.cardIndex() === 0) {
                return 'first-card-opened-form';
            }

            return 'first-card-edit-button';
        },

        showSecondCardBlock() {
            if (this.cardIndex() === 0) {
                return 'second-card-radio-selector';
            }

            return 'second-card-opened-form';
        },

        resetFirstCard() {
            this.editFirstCard();
            this.mpPayerDocument('');
            this.mpPayerType('');

            const am = Math.floor(this.amount() / 2);
            this.inputValueProgress(am);
        },

        /**
         * Get payment images
         * @param {String} type
         * @returns {Boolean}
         */
        getImages: function (type) {

            return window.checkoutConfig.payment[this.getCode()].images.hasOwnProperty(type) ?
                window.checkoutConfig.payment[this.getCode()].images[type]
                : false;
        },

        onPlaceOrderFail: function() {
            this.resetFirstCard();
        },
    });
});
