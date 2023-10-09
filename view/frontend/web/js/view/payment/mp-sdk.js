// noinspection DuplicatedCode

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'underscore',
    'jquery',
    'MercadoPago_AdbPayment/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'MercadoPago_AdbPayment/js/view/payment/method-renderer/validate-form-security',
    'mage/url',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/url-builder',
    'MercadoPago_AdbPayment/js/action/checkout/set-finance-cost',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/totals',
], function (
    _,
    $,
    Component,
    quote,
    validateFormSF,
    urlFormatter,
    fullScreenLoader,
    urlBuilder,
    setFinanceCost,
    messageList,
    $t,
    priceUtils,
    totals,
) {
    'use strict';

    return Component.extend({

        defaults: {
            mpCardForm: {},
            fields: {},
            installmentWasCalculated: false,
            generatedCards: [],
            // html fields
            mpCardHolderName: '',
            mpCardListInstallments: '',
            mpCardInstallment: '',
            mpCardFinanceCost: '',
            mpSelectedCardType: '',
            mpCardType: '',
            mpCardBin: '',
            mpCardPublicId: '',
            mpUserId: '',
            cardIndex: 0,
            installmentsAmount: 0,
            amount: 0,
            installmentsResponse: {},
            minAllowedAmount: 0,
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe([
                    'mpCardHolderName',
                    'mpCardListInstallments',
                    'mpCardInstallment',
                    'mpCardFinanceCost',
                    'mpSelectedCardType',
                    'mpCardType',
                    'mpCardBin',
                    'installmentWasCalculated',
                    'mpCardPublicId',
                    'mpUserId',
                    'cardIndex',
                    'amount',
                    'installmentsAmount',
                    'installmentsResponse',
                ]);
            return this;
        },

        /**
         * Init component
         */
        initialize: function () {

            let self = this;

            this._super();

            self.amount(self.FormattedCurrencyToInstallments(quote.totals().base_grand_total));

            self.installmentsAmount(self.FormattedCurrencyToInstallments(quote.totals().base_grand_total));

            self.mpCardInstallment.subscribe((value) => {
                self.addFinanceCost();
            });

            self.iniTranslateErrorsFromSDK();

            quote.paymentMethod.subscribe((method) => {
                self.resetCardAmount();
            }, null, 'change');
        },

        /**
         * Un Mount Cart Form
         * @return {void}
         */
        resetCardForm() {
            try {
                window.mpCardForm?.cardNumber?.unmount();
            } catch (e) {
                //
            }

            try {
                window.mpCardForm?.securityCode?.unmount();
            } catch (e) {
                //
            }

            window.mpCardForm?.expirationMonth?.unmount();
            window.mpCardForm?.expirationYear?.unmount();
            window.mpCardForm = {};
            this.fields = {};
            this.installmentWasCalculated(false);
            this.mpSelectedCardType('');
            this.mpCardBin('');
            this.mpCardHolderName('');
            this.mpCardInstallment(null);
            this.mpCardFinanceCost(null);
        },

        /**
         * Mount Cart Form
         * @return {void}
         */
        mountCardForm({fieldCcNumber, fieldSecurityCode, fieldExpMonth, fieldExpYear}) {
            let self = this,
                styleField = {
                    height: '100%',
                    padding: '30px 15px'
                },
                codeCardtype;

            self.resetCardForm();

            self.fields = {fieldCcNumber, fieldSecurityCode, fieldExpMonth, fieldExpYear};

            if (fieldCcNumber) {
                window.mpCardForm.cardNumber = window.mp.fields.create('cardNumber', {style: styleField});
                window.mpCardForm.cardNumber
                    .mount(fieldCcNumber)
                    .on('error', () => {
                        self.mountCardForm({fieldCcNumber, fieldSecurityCode, fieldExpMonth, fieldExpYear});
                        this.installmentWasCalculated(false);
                    })
                    .on('binChange', (event) => {
                        this.mpSelectedCardType('');
                        this.installmentWasCalculated(false);
                        this.clearMinValueError();
                        if (event.bin) {
                            if (event.bin.length === 8) {
                                self.mpCardBin(event.bin);
                                self.getInstallments();
                                window.mp.getPaymentMethods({bin: event.bin}).then((binDetails) => {
                                    codeCardtype = self.getCodeCardType(binDetails.results[0].id);
                                    self.minAllowedAmount = binDetails.results[0].payer_costs[0].min_allowed_amount;
                                    self.mpSelectedCardType(codeCardtype);
                                    self.mpCardType(codeCardtype);
                                    self.validateMinValue(self.installmentsAmount());
                                });
                            }
                        }
                    })
                    .on('blur', () => {
                        validateFormSF.removeClassesIfEmpyt(fieldCcNumber);
                    })
                    .on('focus', () => {
                        validateFormSF.toogleFocusStyle(fieldCcNumber);
                    })
                    .on('validityChange', (event) => {
                        validateFormSF.toogleValidityState(fieldCcNumber, event.errorMessages);
                    });
            }

            if (fieldSecurityCode) {
                window.mpCardForm.securityCode = window.mp.fields.create('securityCode', {style: styleField});
                window.mpCardForm.securityCode
                    .mount(fieldSecurityCode)
                    .on('error', () => {
                        self.mountCardForm({fieldCcNumber, fieldSecurityCode, fieldExpMonth, fieldExpYear});
                    })
                    .on('blur', () => {
                        validateFormSF.removeClassesIfEmpyt(fieldSecurityCode);
                    })
                    .on('focus', () => {
                        validateFormSF.toogleFocusStyle(fieldSecurityCode);
                    })
                    .on('validityChange', (event) => {
                        validateFormSF.toogleValidityState(fieldSecurityCode, event.errorMessages);
                    });
            }

            if (fieldExpMonth) {
                window.mpCardForm.expirationMonth = window.mp.fields.create('expirationMonth', {style: styleField});
                window.mpCardForm.expirationMonth
                    .mount(fieldExpMonth)
                    .on('error', () => {
                        self.mountCardForm({fieldCcNumber, fieldSecurityCode, fieldExpMonth, fieldExpYear});
                    })
                    .on('blur', () => {
                        validateFormSF.removeClassesIfEmpyt(fieldExpMonth);
                    })
                    .on('focus', () => {
                        validateFormSF.toogleFocusStyle(fieldExpMonth);
                    })
                    .on('validityChange', (event) => {
                        if (event.errorMessages.length)
                        {
                            _.map(event.errorMessages, (error) => {
                                error.message = this.getMessageError(error.message);
                            });
                        }
                        validateFormSF.toogleValidityState(fieldExpMonth, event.errorMessages);
                    });
            }

            if (fieldExpYear) {
                window.mpCardForm.expirationYear = window.mp.fields.create('expirationYear', {style: styleField});
                window.mpCardForm.expirationYear
                    .mount(fieldExpYear)
                    .on('error', () => {
                        self.mountCardForm({fieldCcNumber, fieldSecurityCode, fieldExpMonth, fieldExpYear});
                    })
                    .on('blur', () => {
                        validateFormSF.removeClassesIfEmpyt(fieldExpYear);
                    })
                    .on('focus', () => {
                        validateFormSF.toogleFocusStyle(fieldExpYear);
                    })
                    .on('validityChange', (event) => {
                        if (event.errorMessages.length)
                        {
                            _.map(event.errorMessages, (error) => {
                                error.message = this.getMessageError(error.message);
                            });
                        }
                        validateFormSF.toogleValidityState(fieldExpYear, event.errorMessages);
                    })
                    .on('ready', () => {
                        self.isLoading(false);
                    });
            }
        },

        async generateToken() {
            var self = this,
                isVaultEnabled = this.vaultEnabler?.isVaultEnabled() ?? false,
                saveCard = this.vaultEnabler?.isActivePaymentTokenEnabler() ?? false,
                quoteId = quote.getQuoteId(),
                unsupportedPreAuth = self.getUnsupportedPreAuth(),
                mpSiteId = self.getMpSiteId();

            if (unsupportedPreAuth[mpSiteId].includes(self.mpCardType())) {
                isVaultEnabled = false;
                saveCard = false;
            }

            if (self.mpPayerDocument()) {
                self.mpPayerDocument(self.mpPayerDocument().replace(/\D/g, ''));
            }

            fullScreenLoader.startLoader();

            const payload = {
                cardholderName: self.mpCardHolderName(),
                identificationType: self.mpPayerType(),
                identificationNumber: self.mpPayerDocument(),
            };

            try {
                const token = await window.mp.fields.createCardToken(payload);

                fullScreenLoader.stopLoader();

                if (saveCard && isVaultEnabled) {
                    fullScreenLoader.startLoader();

                    const serviceUrl = urlBuilder.createUrl('/carts/mine/mp-create-vault', {});

                    const payloadCreateVault = {
                        cartId: quoteId,
                        vaultData: {
                            token: token.id,
                            identificationNumber: self.mpPayerDocument(),
                            identificationType: self.mpPayerType(),
                        }
                    };

                    try {
                        const response = await $.ajax({
                            url: urlFormatter.build(serviceUrl),
                            data: JSON.stringify(payloadCreateVault),
                            global: true,
                            contentType: 'application/json',
                            type: 'POST',
                            async: false
                        });

                        self.mpCardPublicId(response[0].card_id);
                        self.mpUserId(response[0].mp_user_id);

                        fullScreenLoader.stopLoader();

                    } catch (e) {
                        fullScreenLoader.stopLoader();
                        return false;
                    }
                }

                const selectedPayerCost = self.mpCardListInstallments().filter(obj => obj.installments === self.mpCardInstallment())[0];

                if (!selectedPayerCost) {
                    return false;
                }

                self.generatedCards[self.cardIndex()] = {
                    token,
                    cardNumber: token.first_six_digits + 'xxxxxx' + token.last_four_digits,
                    cardExpirationYear: token.expiration_year,
                    cardExpirationMonth: token.expiration_month,
                    cardPublicId: self.mpCardPublicId(),
                    cardType: self.mpCardType(),
                    documentType: self.mpPayerType(),
                    documentValue: self.mpPayerDocument(),
                    mpUserId: self.mpUserId(),
                    holderName: self.mpCardHolderName(),
                    cardInstallment: self.mpCardInstallment(),
                    cardFinanceCost: self.mpCardFinanceCost(),
                    amount: self.installmentsAmount(),
                    sdkInformation: {
                        installmentLabel: selectedPayerCost.recommended_message,
                        installmentSelected: selectedPayerCost,
                        issuerLogo: self.installmentsResponse().issuer.secure_thumbnail
                    },
                };

                return true;
            } catch(e) {

                validateFormSF.clearSecureFieldsErrors();

                self.displayErrorInField(e);

                messageList.addErrorMessage({
                    message: $t('Unable to make payment, check card details.')
                });
                fullScreenLoader.stopLoader();
                return false;
            }
        },

        /**
         * Display Error in Field
         * @param {Array} error
         * @return {void}
         */
        displayErrorInField(error) {

            var previousField = undefined;

            let msg = error.message || error[0].message;

            let field = error.field || error[0]?.field;

            if (error.length >= 1) {

                error.forEach((error) => {
                    if (error.field && previousField !== error.field) {

                        field = error.field;

                        msg = this.getMessageError(error.message);
                        let fieldsMage = {
                        cardNumber: this.fields.fieldCcNumber,
                        securityCode: this.fields.fieldSecurityCode,
                        expirationMonth: this.fields.fieldExpMonth,
                        expirationYear: this.fields.fieldExpYear,
                    };

                    validateFormSF.singleToogleValidityState(fieldsMage[field], msg);

                    }
                    previousField = error.field;
                });
            }
        },

        /**
         * Returns error message and handles month and year validation
         * @param {String} message
         * @returns {String}
         */
        getMessageError(message) {
            let currentDate = new Date();
            currentDate.setDate(- 1);
            if(message.toLowerCase() === "expirationYear value should be greater or equal than %1.".replace('%1', currentDate.getFullYear()).toLowerCase()) {
                message = "expirationYear value should be greater or equal than %1.";
            } else if (message.toLowerCase() === "expirationMonth value should be greater than '%1' or expirationYear value should be greater than '%2'."
                        .replace('%1', currentDate.toLocaleString('default', { month: '2-digit' }))
                        .replace('%2', currentDate.getFullYear())
                        .toLowerCase()) {
                message = "expirationMonth value should be greater than '%1' or expirationYear value should be greater than '%2'.";
            }
            return message;
        },

        /**
         * Get List Options to Instalments
         * @returns {Array}
         */
        async getInstallments() {
            var self = this,
                installments = {},
                ccNumber = self.mpCardBin(),
                bin = ccNumber;

            self.installmentWasCalculated(false);
            self.installmentSelected = null;
            self.mpCardInstallment(null);
            self.addFinanceCost();

            if (bin.length === 8) {
                const result = await window.mp.getInstallments({
                    amount: String(self.FormattedCurrencyToInstallments(self.installmentsAmount())),
                    bin: bin
                });

                if (result[0] && result[0].payer_costs) {
                    self.installmentWasCalculated(true);
                    self.installmentsResponse(result[0]);
                    var listInstallments = result[0].payer_costs;

                    if (self.getMpSiteId() === 'MCO' || self.getMpSiteId() === 'MPE' || self.getMpSiteId() === 'MLC') {
                        self.addTextInterestForInstallment(listInstallments);
                    }

                    self.mpCardListInstallments(listInstallments);
                }
            }

            return installments;
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

        formatedAmountWithSymbol(amount) {
            return this.currencySymbol() + ' ' + amount;
        },

        currencySymbol() {
            return priceUtils.formatPrice().replaceAll(/[0-9\s\.\,]/g, '');
        },

        /**
         * Get Code Card Type.
         * @param {String} cardTypeName
         * @returns {String}
         */
        getCodeCardType(cardTypeName) {
            return cardTypeName;
        },

        /**
         * Get list of available credit card types
         * @returns {Object}
         */
        getCcAvailableTypes: function () {
            return window.checkoutConfig.payment.ccform.availableTypes[this.getCode()];
        },

        /**
         * Get payment icons
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons: function (type) {

            return window.checkoutConfig.payment[this.getCode()].icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment[this.getCode()].icons[type]
                : false;
        },

        /**
         * Get terms and conditions
         * @returns {String}
         */
        getFingerprint: function () {
            return window.checkoutConfig.payment[this.getCode()].fingerprint;
        },

        /**
         * Get list of months
         * @returns {Object}
         */
        getCcMonths: function () {
            return window.checkoutConfig.payment.ccform.months[this.getCode()];
        },

        /**
         * Get list of years
         * @returns {Object}
         */
        getCcYears: function () {
            return window.checkoutConfig.payment.ccform.years[this.getCode()];
        },

        /**
         * Get list of available credit card types values
         * @returns {Object}
         */
        getCcAvailableTypesValues: function () {
            return _.map(this.getCcAvailableTypes(), function (value, key) {
                return {
                    'value': key,
                    'type': value
                };
            });
        },

        /**
         * Get list of available month values
         * @returns {Object}
         */
        getCcMonthsValues: function () {
            return _.map(this.getCcMonths(), function (value, key) {
                return {
                    'value': key,
                    'month': value
                };
            });
        },

        /**
         * Get list of available year values
         * @returns {Object}
         */
        getCcYearsValues: function () {
            return _.map(this.getCcYears(), function (value, key) {
                return {
                    'value': key,
                    'year': value
                };
            });
        },

        /**
         * Get available credit card type by code
         * @param {String} code
         * @returns {String}
         */
        getCcTypeTitleByCode: function (code) {
            var title = '',
                keyValue = 'value',
                keyType = 'type';

            _.each(this.getCcAvailableTypesValues(), function (value) {
                if (value[keyValue] === code) {
                    title = value[keyType];
                }
            });

            return title;
        },

        /**
         * Get credit card details
         * @returns {Array}
         */
        getInfo: function () {
            return [
                {
                    'name': 'Credit Card Type', value: this.getCcTypeTitleByCode(this.mpCardType())
                },
                {
                    'name': 'Credit Card Number', value: this.mpCardNumber()
                }
            ];
        },

        addFinanceCost() {
            var self = this,
                selectInstallment = self.mpCardInstallment(),
                rulesForFinanceCost = self.mpCardListInstallments();

            if (self.getMpSiteId() === 'MLA') {
                _.map(rulesForFinanceCost, (keys) => {
                    if (keys.installments === selectInstallment) {
                        self.addTextForInstallment(keys.labels);
                    }
                });
            }

            self.mpCardFinanceCost(null);
            setFinanceCost.financeCost(selectInstallment, rulesForFinanceCost, self.cardIndex(), self.item.method, (financeCostAmount) => {
                self.mpCardFinanceCost(financeCostAmount);
            });
        },

        iniTranslateErrorsFromSDK() {
            $t("cardNumber should be a number.");
            $t("cardNumber is empty.");
            $t("cardNumber should be of length between '8' and '19'.");
            $t("securityCode should be a number.");
            $t("securityCode should be of length '3' or '4'.");
            $t("securityCode is empty.");
            $t("expirationMonth should be a number.");
            $t("expirationMonth is empty.");
            $t("expirationYear should be of length '2' or '4'.");
            $t("expirationYear should be a number.");
            $t("expirationYear is empty.");
            $t("expirationMonth should be a value from 1 to 12.");
            $t("expirationYear value should be greater or equal than %1.");
            $t("expirationMonth value should be greater than '%1' or expirationYear value should be greater than '%2'.");
            $t("cardNumber should be of length '16'.");
        },

        /**
         * Formatted Currency to Installments
         * @param {Float} amount
         * @return {Float}
         */
        FormattedCurrencyToInstallments(amount) {
            if (this.getMpSiteId() === 'MCO' || this.getMpSiteId() === 'MLC') {
                return parseFloat(amount ? amount : 0).toFixed(0);
            }
            return parseFloat(amount ? amount : 0).toFixed(2);
        },

        /**
         * Add interest text for installments
         * @param {Array}
         * @return {Array}
         */
        addTextInterestForInstallment(listInstallments) {
            _.map(listInstallments, (installment) => {
                var installmentRate = installment.installment_rate;
                var installmentRateCollector = installment.installment_rate_collector;

                if (installmentRate === 0 && installmentRateCollector[0] === 'MERCADOPAGO') {
                    installment.recommended_message = installment.recommended_message + ' ' + $t("Interest-free");
                }

                if (installmentRate === 0 && installmentRateCollector[0] === 'THIRD_PARTY') {
                    installment.recommended_message = installment.recommended_message + ' ' + $t("Your Bank will apply Interest");
                }

                return installment;
            });
        },

        resetCardAmount() {
            this.installmentSelected = null;
            this.mpCardInstallment(null);
            this.addFinanceCost();
        },

        /**
         * Minimum value validate
         * @param {String} amount
         * @returns {Jquery}
         */
        validateMinValue(amount) {
            var message = $t('Minimum transaction amount not allowed for the chosen brand. Please choose another flag or make a purchase over %1.').replace('%1', this.formatedAmountWithSymbol(this.minAllowedAmount));

            $('.mp-message-error').remove();

            if (amount < this.minAllowedAmount) {

                return $('.mp-iframe-card').append('<div class="mp-message-error" id="mp-minvalue-error">' + message + '</div>');
            }
        },

         /**
         * Clear Error Min Value
         * @return {Jquery}
         */
         clearMinValueError(){
            return $('.mp-message-error').remove();
        }
    });
});
