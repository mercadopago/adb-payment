/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'underscore',
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'MercadoPago_PaymentMagento/js/model/mp-card-data',
    'MercadoPago_PaymentMagento/js/view/payment/method-renderer/validate-form-security'
], function (
    _,
    $,
    Component,
    quote,
    mpCardData,
    validateFormSF
) {
    'use strict';

    return Component.extend({

        defaults: {
            mpCardNumberToken: '',
            mpCardNumber: '',
            mpCardBin: '',
            mpCardExpYear: '',
            mpCardExpMonth: '',
            mpCardType: '',
            mpSelectedCardType: '',
            mpCardHolderName: '',
            mpCardListInstallments: '',
            mpCardInstallment: '',
            mpCardPublicId: '',
            mpUserId: '',
            mpPayerType: '',
            mpPayerOptionsTypes: '',
            mpPayerDocument: '',
            installmentWasCalculated: false
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe([
                    'mpCardNumberToken',
                    'mpCardNumber',
                    'mpCardBin',
                    'mpCardExpYear',
                    'mpCardExpMonth',
                    'mpCardType',
                    'mpSelectedCardType',
                    'mpCardHolderName',
                    'mpCardListInstallments',
                    'mpCardInstallment',
                    'mpCardPublicId',
                    'mpUserId',
                    'mpPayerType',
                    'mpPayerOptionsTypes',
                    'mpPayerDocument',
                    'installmentWasCalculated'
                ]);

            return this;
        },

        /**
         * Init component
         */
        initialize: function () {
            var self = this,
                defaultTypeDocument;

            this._super();

            self.active.subscribe((value) => {
                if (value === true) {
                    self.getSelectDocumentTypes();
                }
            });

            self.mpCardNumberToken.subscribe((value) => {
                mpCardData.mpCardNumberToken = value;
            });

            self.mpCardNumber.subscribe((value) => {
                mpCardData.mpCardNumber = value;
            });

            self.mpCardBin.subscribe((value) => {
                mpCardData.mpCardBin = value;
                self.getListOptionsToInstallments();
            });

            self.mpCardExpYear.subscribe(function (value) {
                mpCardData.expirationYear = value;
            });

            self.mpCardExpMonth.subscribe(function (value) {
                mpCardData.expirationMonth = value;
            });

            self.mpCardType.subscribe((value) => {
                mpCardData.mpCardType = value;
            });

            self.mpSelectedCardType.subscribe((value) => {
                mpCardData.mpSelectedCardType = value;
            });

            self.mpCardHolderName.subscribe((value) => {
                mpCardData.mpCardHolderName = value;
            });

            self.mpCardListInstallments.subscribe((value) => {
                mpCardData.mpCardListInstallments = value;
            });

            self.mpCardInstallment.subscribe((value) => {
                self.addFinanceCost();
                mpCardData.mpCardInstallment = value;
            });

            self.mpCardPublicId.subscribe((value) => {
                mpCardData.mpCardPublicId = value;
            });

            self.mpUserId.subscribe((value) => {
                mpCardData.mpUserId = value;
            });

            self.mpPayerType.subscribe((value) => {
                mpCardData.mpPayerType = value;
            });

            self.mpPayerOptionsTypes.subscribe((value) => {
                mpCardData.mpPayerOptionsTypes = value;
            });

            self.mpPayerDocument.subscribe((value) => {

                if (self.getMpSiteId() === 'MLB' && value) {
                    defaultTypeDocument = value.replace(/\D/g, '').length <= 11 ? 'CPF' : 'CNPJ';
                    self.mpPayerType(defaultTypeDocument);
                }

                mpCardData.mpPayerDocument = value;
            });
        },


        /**
         * Un Mount Cart Form
         * @return {void}
         */
        unMountCardForm() {
            window.cardNumber.unmount();
            window.securityCode.unmount();
            window.expirationMonth.unmount();
            window.expirationYear.unmount();
        },

        /**
         * Mount Cart Form
         * @return {Void}
         */
        mountCardForm() {
            let self = this,
                fieldCcNumber = 'mercadopago_paymentmagento_cc_number',
                fieldSecurityCode = 'mercadopago_paymentmagento_cc_cid',
                fieldExpMonth = 'mercadopago_paymentmagento_cc_expiration_month',
                fieldExpYear = 'mercadopago_paymentmagento_cc_expiration_yr',
                styleField = {
                    height: '100%',
                    padding: '30px 15px'
                },
                codeCardtype;

            window.cardNumber = window.mp.fields.create('cardNumber', { style: styleField }),
            window.securityCode =  window.mp.fields.create('securityCode', { style: styleField }),
            window.expirationMonth = window.mp.fields.create('expirationMonth', { style: styleField }),
            window.expirationYear = window.mp.fields.create('expirationYear', { style: styleField });

            window.cardNumber
                .mount(fieldCcNumber)
                .on('error', () => { self.mountCardForm(); })
                .on('binChange', (event) => {
                    if (event.bin) {
                        if (event.bin.length === 8) {
                            self.mpCardBin(event.bin);
                            window.mp.getPaymentMethods({bin: event.bin}).then((binDetails) => {
                                codeCardtype = self.getCodeCardType(binDetails.results[0].id);
                                self.mpSelectedCardType(codeCardtype);
                                self.mpCardType(codeCardtype);
                            });
                        }
                    }
                })
                .on('blur', () => { validateFormSF.removeClassesIfEmpyt(fieldCcNumber); })
                .on('focus', () => { validateFormSF.toogleFocusStyle(fieldCcNumber); })
                .on('validityChange', (event) => {
                    validateFormSF.toogleValidityState(fieldCcNumber, event.errorMessages);
                });

            window.securityCode
                .mount(fieldSecurityCode)
                .on('error', () => { self.mountCardForm(); })
                .on('blur', () => { validateFormSF.removeClassesIfEmpyt(fieldSecurityCode); })
                .on('focus', () => { validateFormSF.toogleFocusStyle(fieldSecurityCode); })
                .on('validityChange', (event) => {
                    validateFormSF.toogleValidityState(fieldSecurityCode, event.errorMessages);
                });

            window.expirationMonth
                .mount(fieldExpMonth)
                .on('error', () => { self.mountCardForm(); })
                .on('blur', () => { validateFormSF.removeClassesIfEmpyt(fieldExpMonth); })
                .on('focus', () => { validateFormSF.toogleFocusStyle(fieldExpMonth); })
                .on('validityChange', (event) => {
                    validateFormSF.toogleValidityState(fieldExpMonth, event.errorMessages);
                });

            window.expirationYear
                .mount(fieldExpYear)
                .on('error', () => { self.mountCardForm(); })
                .on('blur', () => { validateFormSF.removeClassesIfEmpyt(fieldExpYear); })
                .on('focus', () => { validateFormSF.toogleFocusStyle(fieldExpYear); })
                .on('validityChange', (event) => {
                    validateFormSF.toogleValidityState(fieldExpYear, event.errorMessages);
                })
                .on('ready', () => { self.isLoading(false); });
        },

        /**
         * Display Error in Field
         * @param {Array} error
         * @return {void}
         */
        displayErrorInField(error) {
            let field = error.field,
                msg = error.message,
                fieldsMage = {
                    cardNumber: 'mercadopago_paymentmagento_cc_number',
                    securityCode: 'mercadopago_paymentmagento_cc_cid',
                    expirationMonth: 'mercadopago_paymentmagento_cc_expiration_month',
                    expirationYear: 'mercadopago_paymentmagento_cc_expiration_yr'
                };

            validateFormSF.singleToogleValidityState(fieldsMage[field], msg);
        },

        /**
         * Get Select Document Types
         * @returns {Void}
         */
        getSelectDocumentTypes() {
            let self = this,
                vatId,
                defaultTypeDocument;

            window.mp.getIdentificationTypes().then((result) => {
                self.mpPayerOptionsTypes(result);

                if (quote.billingAddress()) {
                    vatId = quote.billingAddress().vatId;
                    if (vatId) {
                        self.mpPayerDocument(vatId);
                    }
                }

                if ( vatId && self.getMpSiteId() === 'MLB') {
                    defaultTypeDocument = vatId.replace(/\D/g, '').length <= 11 ? 'CPF' : 'CNPJ';
                    self.mpPayerType(defaultTypeDocument);
                }
            });
        },

        /**
         * Get List Options to Instalments
         * @returns {Array}
         */
        getListOptionsToInstallments() {
            var self = this,
                installments = {},
                ccNumber = self.mpCardBin(),
                bin = ccNumber,
                amount = self.FormattedCurrencyToInstallments(self.amount());

            self.installmentWasCalculated(false);

            if (bin.length === 8) {
                window.mp.getInstallments({
                    amount: String(amount),
                    bin: bin
                }).then((result) => {
                    self.installmentWasCalculated(true);
                    self.mpCardListInstallments(result[0].payer_costs);
                });
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

        /**
         * Get Validation For Document.
         * @returns {Array}
         */
        getValidationForDocument() {
            let self = this,
                mpSiteId = self.getMpSiteId();

            if (mpSiteId === 'MLB') {
                return {
                    'required':true,
                    'mp-validate-document-identification': '#' + self.getCode() + '_document_identification'
                };
            }
            return {'required':true};
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
         * Get auxiliary code
         * @returns {String}
         */
        getAuxiliaryCode() {
            return 'mercadopago_paymentmagento_cc';
        },

        /**
         * Get list of available credit card types
         * @returns {Object}
         */
        getCcAvailableTypes: function () {
            return window.checkoutConfig.payment.ccform.availableTypes[this.getAuxiliaryCode()];
        },

        /**
         * Get payment icons
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons: function (type) {
            return window.checkoutConfig.payment.mercadopago_paymentmagento_cc.icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment.mercadopago_paymentmagento_cc.icons[type]
                : false;
        },

        /**
         * Get list of months
         * @returns {Object}
         */
        getCcMonths: function () {
            return window.checkoutConfig.payment.ccform.months['cc'];
        },

        /**
         * Get list of years
         * @returns {Object}
         */
        getCcYears: function () {
            return window.checkoutConfig.payment.ccform.years['cc'];
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

        /**
         * Is document identification capture
         * @returns {Boolean}
         */
        DocumentIdentificationCapture() {

            if (this.getMpSiteId() === 'MLM') {
                return false;
            }

            if (this.getMpSiteId() !== 'MLB') {
                return true;
            }

            if (!quote.billingAddress()) {
                return window.checkoutConfig.payment[this.getCode()].document_identification_capture;
            }

            if (!quote.billingAddress().vatId) {
                return true;
            }

            return window.checkoutConfig.payment[this.getCode()].document_identification_capture;
        },

        /**
         * Get logo
         * @returns {String}
         */
        getLogo() {
            return window.checkoutConfig.payment[this.getCode()].logo;
        },

        /**
         * Get title
         * @returns {String}
         */
        getTitle() {
            return window.checkoutConfig.payment[this.getCode()].title;
        },

        /**
         * Get Payment Id Method
         * @returns {String}
         */
        getPaymentIdMethod() {
            return window.checkoutConfig.payment[this.getCode()].payment_method_id;
        },

        /**
         * Get Expiration
         * @returns {String}
         */
        getExpiration() {
            return window.checkoutConfig.payment[this.getCode()].expiration;
        },

        /**
         * Get Mp Site Id
         * @returns {String}
         */
        getMpSiteId() {
            return window.checkoutConfig.payment['mercadopago_paymentmagento'].mp_site_id;
        }
    });
});
