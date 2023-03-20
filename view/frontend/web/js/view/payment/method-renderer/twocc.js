/* eslint-disable max-len */
/**
 * Copyright © MercadoPago. All rights reserved.
 *
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
    'mage/url',
    'MercadoPago_PaymentMagento/js/action/checkout/set-finance-cost',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'Magento_Catalog/js/price-utils'
], function (
    _,
    $,
    fullScreenLoader,
    quote,
    totals,
    urlBuilder,
    Component,
    urlFormatter,
    setFinanceCost,
    messageList,
    $t,
    priceUtils
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
            selectedCard: 'card-one',
            inputValueProgress:'',
            placeholderInputProgress: priceUtils.formatPrice(),
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
                'selectedCard',
                'inputValueProgress',
                'placeholderInputProgress'
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

            self.selectedCard.subscribe((value) => {
                if (value === 'card-one') {
                    self.selectFirstCard();
                }

                if (value === 'card-two') {
                    self.selectSecondCard();
                }
            });

            self.inputValueProgress.subscribe((value) => {
                self.updateProgress(value);
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
        },

        selectFirstCard: function (){
            console.log("it was called")
            var mpFirstCard = document.getElementById('mp-first-card');
            var mpSecondCard = document.getElementById('mp-second-card');

            console.log(mpFirstCard)
            console.log(mpSecondCard)

            if(mpFirstCard.classList.contains('mp-display-form')) {
                this.formShown('mp-twocc-first-radio')
                this.formHidden('mp-twocc-second-radio')
                mpFirstCard.classList.remove('mp-display-form')
                mpSecondCard.classList.add('mp-display-form')
            }
        },

        selectSecondCard: function (){
            var mpFirstCard = document.getElementById('mp-first-card')
            var mpSecondCard = document.getElementById('mp-second-card')
            var mpFirstHeader = document.getElementById('mp-twocc-first-radio')

            if(mpSecondCard.classList.contains('mp-display-form')) {
                this.formShown('mp-twocc-second-radio')
                this.formHidden('mp-twocc-first-radio')
                mpSecondCard.classList.remove('mp-display-form')
                mpFirstCard.classList.add('mp-display-form')
            }
        },

        formShown: function (id){
            var mpRadio = document.getElementById(id);
            mpRadio.style.borderBottom = '0'
            mpRadio.style.borderRadius = '4px 4px 0 0'
        },

        formHidden: function (id){

            console.log('hidden')
            var mpRadio = document.getElementById(id);
            mpRadio.style.borderBottom = '1px solid #BFBFBF'
            mpRadio.style.borderRadius = '4px'
        },
        /**
         * Progress bar update
         */
        updateProgress(valueInput){
            var progress = document.querySelector(".mp-progress-bar div");
            var total = this.amount();
            var porcent = (valueInput/total) * 100;

            progress.style.width = porcent + "%";
            document.getElementById("mp-message-error").style.display = "none";

            if(valueInput >= total){
                porcent = 0;
                progress.style.width = porcent + "%";
                document.getElementById("mp-message-error").style.display = "block";
            }

            if(valueInput < 0){
                porcent = 0;
                progress.style.width = porcent + "%";
            }
        },

        /**
         * Remaining value label update
         */
        updateRemainingAmount(){
            var amount = this.amount();
            var inputValueProgress = this.inputValueProgress();

            if(inputValueProgress < amount){
                amount = amount - inputValueProgress;
            }

            return priceUtils.formatPrice(amount);
        }

    });
});
