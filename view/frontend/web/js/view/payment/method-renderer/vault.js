/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */
define([
    'underscore',
    'jquery',
    'ko',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'mage/translate',
    'MercadoPago_AdbPayment/js/action/checkout/set-finance-cost',
    'Magento_Ui/js/model/messageList',
    'MercadoPago_AdbPayment/js/view/payment/method-renderer/validate-form-security'
], function (
    _,
    $,
    _ko,
    fullScreenLoader,
    quote,
    creditCardData,
    VaultComponent,
    $t,
    setFinanceCost,
    messageList,
    validateFormSF
) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            active: false,
            template: 'MercadoPago_AdbPayment/payment/vault',
            vaultForm: 'MercadoPago_AdbPayment/payment/vault-form',
            amount:  quote.totals().base_grand_total,
            creditCardListInstallments: '',
            creditCardVerificationNumber: '',
            creditCardInstallment: '',
            creditCardFinanceCost: '',
            creditCardNumberToken: '',
            creditCardType: '',
            installmentTextInfo: false,
            installmentTextTEA: null,
            installmentTextCFT: null,
            isLoading: true
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'amount',
                'active',
                'creditCardListInstallments',
                'creditCardVerificationNumber',
                'creditCardInstallment',
                'creditCardFinanceCost',
                'creditCardNumberToken',
                'creditCardType',
                'installmentTextInfo',
                'installmentTextTEA',
                'installmentTextCFT',
                'isLoading'
            ]);
            return this;
        },

        /**
         * Get auxiliary code
         * @returns {String}
         */
        getAuxiliaryCode() {
            return 'mercadopago_adbpayment_cc';
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'mercadopago_adbpayment_cc_vault';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this;

            this._super();

            self.active.subscribe((value) => {
                if (value === true) {
                    this.getListOptionsToInstallmentsVault();
                    creditCardData.creditCardInstallment =  null;

                    setTimeout(() => {
                        self.mountCardForm();
                    }, 3000);
                }

                if (value === false) {
                    self.isLoading(true);
                    self.resetCardForm();
                    creditCardData.creditCardInstallment =  null;
                    self.creditCardInstallment(null);
                }
            });

            self.creditCardInstallment.subscribe((value) => {
                self.addFinanceCost();
                creditCardData.creditCardInstallment = value;
            });

            self.creditCardVerificationNumber.subscribe((value) => {
                self.getCardIdDetails();
                creditCardData.creditCardVerificationNumber = value;
            });

            self.creditCardNumberToken.subscribe((value) => {
                creditCardData.creditCardNumberToken = value;
            });

            self.creditCardType.subscribe((value) => {
                creditCardData.creditCardType = value;
            });

            self.creditCardListInstallments.subscribe((value) => {
                creditCardData.creditCardListInstallments = value;
            });

            quote.totals.subscribe((value) => {
                var financeCostAmount = 0;

                _.map(quote.totals()['total_segments'], (segment) => {
                    if (segment['code'] === 'finance_cost_amount') {
                        financeCostAmount = segment['value'];
                    }
                });
                self.amount(value.base_grand_total - financeCostAmount);
            });

            self.amount.subscribe((value) => {
                creditCardData.amount = value;
                self.getListOptionsToInstallmentsVault();
            });
        },

        /**
         * Un Mount Cart Form
         * @return {void}
         */
        resetCardForm() {
            window.mpCardForm?.cardNumber?.unmount();
            window.mpCardForm?.securityCode?.unmount();
            window.mpCardForm?.expirationMonth?.unmount();
            window.mpCardForm?.expirationYear?.unmount();
            window.mpCardForm = {};
        },

        /**
         * Mount Cart Form
         * @return {void}
         */
        mountCardForm() {
            let self = this,
                vaultId = self.getId(),
                fieldSecurityCode = vaultId + '_cc_id',
                styleField = {
                    height: '100%',
                    padding: '30px 15px'
                };

            self.resetCardForm();

            window.mpCardForm.securityCode = window.mp.fields.create('securityCode', { style: styleField });
            window.mpCardForm.securityCode
                .mount(fieldSecurityCode)
                .on('error', () => { self.mountCardForm(); })
                .on('blur', () => { validateFormSF.removeClassesIfEmpyt(fieldSecurityCode); })
                .on('focus', () => { validateFormSF.toogleFocusStyle(fieldSecurityCode); })
                .on('validityChange', (event) => {
                    validateFormSF.toogleValidityState(fieldSecurityCode, event.errorMessages);
                })
                .on('ready', () => { self.isLoading(false); });
        },

        /**
         * Display Error in Field
         * @param {Array} error
         * @return {void}
         */
        displayErrorInField(error) {
            let self = this,
                field = error.field,
                msg = error.message,
                vaultId = self.getId(),
                fieldSecurityCode = vaultId + '_cc_id',
                fieldsMage = {
                    securityCode: fieldSecurityCode
                };

                validateFormSF.singleToogleValidityState(fieldsMage[field], msg);
        },

        /**
         * Is Active
         * @returns {Boolean}
         */
        isActive() {
            var active = this.getId() === this.isChecked();

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
            this.getCardIdDetails();
        },

        /**
         * Add Text for Installments
         * @param {Array} labels
         * @return {void}
         */
        addTextForInstallment(labels) {
            var self = this,
                texts;

            self.installmentTextInfo(true);

            _.map(labels, (label) => {
                texts = label.split('|');
                _.map(texts, (text) => {
                    if (text.includes('TEA')) {
                        self.installmentTextTEA(text.replace('_', ' '));
                    }
                    if (text.includes('CFT')) {
                        self.installmentTextCFT(text.replace('_', ' '));
                    }
                });
            });
        },

        /**
         * Get card id details
         * @returns {void}
         */
        getCardIdDetails() {
            var self = this,
                payload;

            fullScreenLoader.startLoader();

            payload = {
                cardId: this.getMpPublicId()
            };

            window.mp.fields.createCardToken(payload)
            .then((token) => {
                self.creditCardNumberToken(token.id);
                this.placeOrder();
                fullScreenLoader.stopLoader();
            }).catch((errors) => {

                _.map(errors, (error) => {
                    self.displayErrorInField(error);
                });

                messageList.addErrorMessage({
                    message: $t('Unable to make payment, check card details.')
                });
                fullScreenLoader.stopLoader();
            });
        },

        /**
         * Add Finance Cost in totals
         * @returns {void}
         */
        addFinanceCost() {
            var self = this,
                selectInstallment = self.creditCardInstallment(),
                rulesForFinanceCost = self.creditCardListInstallments();

            if (self.getMpSiteId() === 'MLA') {
                _.map(rulesForFinanceCost, (keys) => {
                    if (keys.installments === selectInstallment) {
                        self.addTextForInstallment(keys.labels);
                    }
                });
            }

            setFinanceCost.financeCost(selectInstallment, rulesForFinanceCost, null, null, (financeCostAmount) => {
                self.creditCardFinanceCost(financeCostAmount);
            });
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData() {
            var self = this,
                data;

            data = {
                'method': self.getCode(),
                'additional_data': {
                    'payer_document_type': self.getPayerDocumentType(),
                    'payer_document_identification': self.getPayerDocumentNumber(),
                    'card_installments': self.creditCardInstallment(),
                    'card_finance_cost': self.creditCardFinanceCost(),
                    'card_number_token': self.creditCardNumberToken(),
                    'card_holder_name': self.getMpHolderName(),
                    'card_number': self.getMaskedCard(),
                    'card_type': self.getCardType(),
                    'public_hash': self.getToken(),
                    'mp_user_id': self.getMpUserId()
                }
            };

            return data;
        },

        /**
         * Get Code Cc Type
         * @returns {String}
         */
        getCodeCcType() {
            return this.creditCardType();
        },

        /**
         * Is show legend
         * @returns {Boolean}
         */
        isShowLegend() {
            return true;
        },

        /**
         * Get Token
         * @returns {String}
         */
        getToken() {
            return this.publicHash;
        },

        /**
         * Get Payer Document Type
         * @returns {String}
         */
        getPayerDocumentType() {
            return this.details['payer_document_type'];
        },

        /**
         * Get Payer Document Type
         * @returns {String}
         */
        getPayerDocumentNumber() {
            return this.details['payer_document_number'];
        },

        /**
         * Get Mp Public Id
         * @returns {String}
         */
        getMpPublicId() {
            return this.details['mp_public_id'];
        },

        /**
         * Get Mp User Id
         * @returns {String}
         */
        getMpUserId() {
            return this.details['mp_user_id'];
        },

        /**
         * Get Mp Holder Name
         * @returns {String}
         */
        getMpHolderName() {
            return this.details['card_holder_name'];
        },

        /**
         * Get masked card
         * @returns {String}
         */
        getMaskedCard() {
            return this.getCardFirstSix() + 'xxxxxx' + this.getCardLastFour();
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType() {
            return this.details['card_type'];
        },

        /**
         * Get Card Last Four
         * @returns {String}
         */
        getCardLastFour() {
            return this.details['card_last4'];
        },

        /**
         * Get Card First Six
         * @returns {String}
         */
        getCardFirstSix() {
            return this.details['card_first6'];
        },

        /**
         * Get Mp Site Id
         * @returns {String}
         */
        getMpSiteId() {
            return window.checkoutConfig.payment['mercadopago_adbpayment'].mp_site_id;
        },

        /**
         * Get payment icons
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons(type) {
            return window.checkoutConfig.payment[this.getCode()].icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment[this.getCode()].icons[type]
                : false;
        },

        /**
         * Get List Options to Instalments
         * @returns {Array}
         */
        getListOptionsToInstallmentsVault() {
            var self = this,
                installments = {},
                bin = this.getCardFirstSix(),
                amount = self.amount();

            window.mp.getInstallments({
                amount: String(amount),
                bin: bin
            }).then((result) => {
                self.creditCardListInstallments(result[0].payer_costs);
            });

            return installments;
        }

    });
});
