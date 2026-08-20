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
    'MercadoPago_AdbPayment/js/view/payment/utils',
    'MercadoPago_AdbPayment/js/view/payment/metrics'
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
    utils,
    metrics
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
            cvvTooltip: '',
            // Mirrors security_code.mode from getPaymentMethods; drives the CVV field's
            // "required" (*) label (PSW-4359). Defaults to true (mandatory) so an
            // unrecognized/not-yet-looked-up BIN still shows the CVV as required.
            cvvRequired: true,
            mpCardPublicId: '',
            mpUserId: '',
            cardIndex: 0,
            installmentsAmount: 0,
            amount: 0,
            installmentsResponse: {},
            minAllowedAmount: 0,
            mpYapeTokenId: '',
            mpYapeOtp: '',
            mpYapePhone: '',
            cardNumberIsValid: true,
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
                    'cvvTooltip',
                    'cvvRequired',
                    'installmentWasCalculated',
                    'mpCardPublicId',
                    'mpUserId',
                    'cardIndex',
                    'amount',
                    'installmentsAmount',
                    'installmentsResponse',
                    'mpYapeTokenId',
                    'mpYapeOtp',
                    'mpYapePhone',
                    'cardNumberIsValid',
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

            self.cvvTooltip($t('A 3-digit number in italics on the back of your card.'));

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

            // Reset the CVV tooltip and its placeholder/label state to the default, so
            // the next card (e.g. the 2nd card in twocc, or the cc after clearing the
            // number) does not inherit the previous brand's tooltip/floated label.
            if (this.fields?.fieldSecurityCode) {
                validateFormSF.unmarkPlaceholder(this.fields.fieldSecurityCode);
            }
            this.cvvTooltip($t('A 3-digit number in italics on the back of your card.'));

            window.mpCardForm = {};
            this.fields = {};
            this.cardNumberIsValid(true);
            // Next card (e.g. 2nd card in twocc) must not inherit an optional CVV from the
            // previous brand's mode (PSW-4359).
            this.cvvRequired(true);
            this.binUnrecognized = false;
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
                codeCardtype,
                notRecognizedMsg = $t('The card number entered was not recognized. Please check and try again.'),
                invalidCardMsg = $t('The card number entered is invalid. Please check the digits and try again.');

            self.resetCardForm();

            self.fields = {fieldCcNumber, fieldSecurityCode, fieldExpMonth, fieldExpYear};

            if (fieldCcNumber) {
                window.mpCardForm.cardNumber = window.mp.fields.create('cardNumber', {style: styleField, enableLuhnValidation: true});
                window.mpCardForm.cardNumber
                    .mount(fieldCcNumber)
                    .on('error', () => {
                        self.mountCardForm({fieldCcNumber, fieldSecurityCode, fieldExpMonth, fieldExpYear});
                        this.installmentWasCalculated(false);
                    })
                    .on('binChange', (event) => {
                        // Capture whether a brand was applied before clearing it, so the CVV
                        // reset in the else branch runs only when there was a placeholder to
                        // drop (avoids rebuilding the field on every keystroke of a fresh card).
                        const hadBrand = !!this.mpSelectedCardType();
                        this.mpSelectedCardType('');
                        this.mpCardType('');
                        // Reset BIN recognition up front: a changed BIN is "unknown" until its
                        // getPaymentMethods lookup resolves. This also prevents a failed lookup
                        // (catch) or a cleared/partial BIN from inheriting the previous BIN's
                        // recognition state and rendering a stale "not recognized" message.
                        this.binUnrecognized = false;
                        this.installmentWasCalculated(false);
                        this.clearMinValueError();
                        if (event.bin && event.bin.length === 8) {
                            self.mpCardBin(event.bin);
                            self.getInstallments();
                            window.mp.getPaymentMethods({bin: event.bin}).then((binDetails) => {
                                // Drop a stale/out-of-order response: if the bin changed
                                // while this request was in flight, a newer binChange now
                                // owns the field, tooltip and placeholder.
                                if (self.mpCardBin() !== event.bin) {
                                    return;
                                }

                                if (!binDetails.results || !binDetails.results[0]) {
                                    // BIN not recognized (PSW-3964). Persist the flag so the
                                    // message is re-rendered by validityChange even when only the
                                    // number length changes later — binChange does not re-fire
                                    // while the first 8 digits stay the same (e.g. deleting an
                                    // extra digit). Show it now only when the whole number is
                                    // valid; otherwise the SDK format (length) or Luhn error is the
                                    // relevant one. Mirrors the recognized-BIN branch below so a
                                    // Luhn-invalid number is not relabelled "not recognized" when
                                    // this async response lands after validityChange.
                                    self.binUnrecognized = true;
                                    if (self.cardNumberIsValid()) {
                                        validateFormSF.toogleValidityState(fieldCcNumber, [{
                                            message: notRecognizedMsg
                                        }]);
                                    }
                                    // No brand to apply: drop the previous brand's CVV
                                    // placeholder/floated label (PSW-3965).
                                    self.resetSecurityCodeField();
                                    return;
                                }

                                self.binUnrecognized = false;
                                // Clear only when the whole number is valid; a length-only check
                                // would wipe a Luhn error (Luhn keeps the length valid) when this
                                // recognized-BIN response lands after the validityChange.
                                if (self.cardNumberIsValid()) {
                                    validateFormSF.toogleValidityState(fieldCcNumber, []);
                                }

                                let result = binDetails.results[0];

                                codeCardtype = self.getCodeCardType(result.id);
                                self.minAllowedAmount = result.payer_costs?.[0]?.min_allowed_amount ?? 0;
                                self.mpSelectedCardType(codeCardtype);
                                self.mpCardType(codeCardtype);
                                self.updateSecurityCodeField(result);
                                self.validateMinValue(self.installmentsAmount());

                                const cardNumberSettings = result.settings?.[0]?.card_number;
                                if (cardNumberSettings && window.mpCardForm.cardNumber) {
                                    try {
                                        window.mpCardForm.cardNumber.update({ settings: cardNumberSettings });
                                    } catch (updateError) {
                                        metrics.sendError('mp_card_number_update_error',
                                            updateError.message || updateError,
                                            self.getCode()
                                        );
                                    }
                                }
                            }).catch((binError) => {
                                // Lookup failed (e.g. network). Rebuild the CVV field back to the
                                // mandatory default (resets cvvRequired to true) so a previous brand's
                                // mode:'optional' (PSW-4359) does not bleed onto this unresolved BIN and
                                // let it tokenize without a CVV. Guarded by the stale-BIN check so a late
                                // failure for an older BIN does not clobber a newer one. binUnrecognized
                                // stays false (reset at the top of binChange) — the BIN wasn't evaluated.
                                if (self.mpCardBin() === event.bin) {
                                    self.resetSecurityCodeField();
                                }
                                metrics.sendError('mp_get_payment_methods_error',
                                    binError.message || binError,
                                    self.getCode()
                                );
                            });
                        } else {
                            // Card number cleared or BIN still partial. Reset mpCardBin so an
                            // in-flight getPaymentMethods response for the previous BIN is
                            // dropped by the stale guard above — otherwise it could re-apply a
                            // brand to the now-cleared field, overwriting the CVV reset. Not
                            // gated by hadBrand: the in-flight case happens before a brand is set.
                            self.mpCardBin('');
                            // Rebuild the CVV field only when a brand was applied before, to drop
                            // its placeholder/floated label; skip on fresh typing (no brand yet).
                            if (hadBrand) {
                                self.resetSecurityCodeField();
                            }
                        }
                    })
                    .on('blur', () => {
                        validateFormSF.removeClassesIfEmpyt(fieldCcNumber);
                    })
                    .on('focus', () => {
                        validateFormSF.toogleFocusStyle(fieldCcNumber);
                        self.triggerFieldEvent('focus', {
                            fieldName: 'card_number',
                            paymentMethod: this.getCode(),
                            isEmpty: false,
                            isSDKField: true
                        }, 'mp_field_focus');
                    })
                    .on('validityChange', (event) => {
                        // Whole-number validity: no SDK error at all — length, type and, since the
                        // field is created with enableLuhnValidation:true, the Luhn checksum. The
                        // submit gate (generateToken) consumes this.
                        self.cardNumberIsValid(event.errorMessages.length === 0);
                        // Render precedence: a format error (length/type/empty) wins over the
                        // Luhn checksum — Luhn on an incomplete number is meaningless, and the SDK
                        // emits both together (length then luhn) while typing; toogleValidityState
                        // renders the LAST message, so passing the raw array would keep showing
                        // Luhn even after a digit is removed. Order: format error → Luhn → the
                        // not-recognized BIN message (PSW-3964, restored here because binChange
                        // does not re-fire on length-only edits) → valid. Luhn arrives as cause
                        // 'invalid_value' + details.reason 'luhn'; its raw English message is
                        // swapped for the translatable invalidCardMsg (details.reason also keeps it
                        // distinct from the CVV's own invalid_value).
                        // Any other invalid_value (a reason the SDK may add later) is shown raw as
                        // a defensive fallback, so the field never looks valid while the submit
                        // gate (cardNumberIsValid) blocks it.
                        const formatErrors = event.errorMessages.filter((e) => e.cause !== 'invalid_value'),
                            hasLuhn = event.errorMessages.some((e) => e.cause === 'invalid_value' && e.details?.reason === 'luhn'),
                            otherErrors = event.errorMessages.filter((e) => e.cause === 'invalid_value' && e.details?.reason !== 'luhn');

                        if (formatErrors.length) {
                            validateFormSF.toogleValidityState(fieldCcNumber, formatErrors);
                        } else if (hasLuhn) {
                            validateFormSF.toogleValidityState(fieldCcNumber, [{ message: invalidCardMsg }]);
                        } else if (otherErrors.length) {
                            validateFormSF.toogleValidityState(fieldCcNumber, otherErrors);
                        } else if (self.binUnrecognized) {
                            validateFormSF.toogleValidityState(fieldCcNumber, [{ message: notRecognizedMsg }]);
                        } else {
                            validateFormSF.toogleValidityState(fieldCcNumber, []);
                        }
                        this.triggerFieldEvent('validityChange', {
                            fieldName: 'card_number',
                            paymentMethod: this.getCode(),
                            isEmpty: false,
                            isSDKField: true
                        }, 'mp_field_interaction');
                    });
            }

            if (fieldSecurityCode) {
                self.mountSecurityCodeField(fieldSecurityCode);
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

            // Field hidden: refresh the document from the latest billing vatId at submit time
            // (shared with pix/ticket — see default.js). No-op when visible, so a value typed in
            // the payment form is not lost.
            self.syncHiddenDocumentFromAddress();

            if (self.mpPayerDocument()) {
                // Normalize the stored value (not just the validation copy) before tokenization:
                // alphanumeric CNPJ must be uppercase for the MP SDK/services. Scoped to CNPJ so
                // documents from other countries are left unchanged.
                self.mpPayerDocument(
                    self.mpPayerType() === 'CNPJ'
                        ? self.mpPayerDocument().replace(/[^A-Z0-9]/gi, '').toUpperCase()
                        : self.mpPayerDocument().replace(/\W/g, '')
                );
            }

            if (!self.cardNumberIsValid()) {
                self.showError('invalid_card_number');
                return false;
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
                    self.showError('invalid_installments');
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
         * Run the shared pre-submit validation gate for card renderers.
         *
         * Executes three checks in order, short-circuiting on the first failure.
         * Callers should return immediately when this returns false.
         *
         * @returns {Boolean}
         */
        _runPreSubmitGate() {
            const checks = [
                { check: () => $(this.formElement).valid(), error: 'invalid_form' },
                { check: () => this.installmentsAreValid(), error: 'invalid_installments' },
                { check: () => this.cardNumberIsValid(), error: 'invalid_card_number' }
            ];

            for (const { check, error } of checks) {
                if (!check()) {
                    this.showError(error);
                    return false;
                }
            }

            return true;
        },

        /**
         * Whether the installments selection is valid for submit.
         *
         * Installments are only required once they have been calculated for the
         * current BIN (the select is rendered). Payment methods without an
         * installments step never calculate them, so this returns true for them.
         *
         * @returns {Boolean}
         */
        installmentsAreValid() {
            if (!this.installmentWasCalculated()) {
                return true;
            }

            const selected = this.mpCardInstallment();

            return selected !== null && selected !== undefined && selected !== '';
        },

        /**
         * Show a pre-submit validation error for the given check code.
         *
         * `invalid_form` is intentionally silent here: jQuery validation already
         * renders inline field errors, so no global banner is added for it.
         * It still clears any stale banner from a previous submission.
         *
         * @param {String} errorCode
         * @return {void}
         */
        showError(errorCode) {
            const messages = {
                invalid_installments: $t('Please select the number of installments.'),
                invalid_card_number: $t('Unable to make payment, check card details.')
            };

            messageList.clear();

            const message = messages[errorCode];

            if (!message) {
                return;
            }

            messageList.addErrorMessage({ message });
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
                        utils.addTextInterestForInstallment(listInstallments);
                    }

                    self.mpCardListInstallments(listInstallments);
                }
            }

            return installments;
        },

        /**
         * Add Text for Installments
         * @param {Array} labels
         * @param {Number} selectedInstallment
         * @return {void}
         */
        addTextForInstallment(labels, selectedInstallment) {
            var self = this;

            const formatedFees = utils.formatInstallmentFees(labels, selectedInstallment);

            if (!formatedFees) {
                self.installmentTextInfo(false);
                self.installmentTextCFT(null);
                self.installmentTextTNA(null);
                self.installmentTextTEA(null);
                return;
            }

            self.installmentTextInfo(true);

            Object.entries(formatedFees).forEach(([key, value]) => {
                switch (key) {
                    case 'TNA':
                        self.installmentTextTNA(value);
                        break;
                    case 'TEA':
                        self.installmentTextTEA(value);
                        break;
                    case 'CFT':
                        self.installmentTextCFT(value);
                        break;
                }
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
         * Create, mount and wire the secure securityCode field.
         *
         * Extracted so the field can be rebuilt on brand change (see
         * updateSecurityCodeField). When brand settings are given, the per-brand
         * length is applied on the field's `ready` event — settings is an update-only
         * property in the SDK (not a create option) and must be applied after mount.
         *
         * @param {String} fieldSecurityCode DOM selector/id of the CVV container
         * @param {Object} [settings] security_code settings ({mode, length}) from the API
         * @return {void}
         */
        mountSecurityCodeField(fieldSecurityCode, settings) {
            let self = this,
                styleField = {
                    height: '100%',
                    padding: '30px 15px'
                };

            if (!fieldSecurityCode) {
                return;
            }

            const field = window.mp.fields.create('securityCode', {style: styleField});

            window.mpCardForm.securityCode = field;

            field
                .mount(fieldSecurityCode)
                .on('ready', () => {
                    // Apply to the captured instance and confirm it is still the mounted
                    // one — a fast double binChange can replace window.mpCardForm.securityCode
                    // before this ready fires, which would otherwise apply the previous
                    // brand's settings to the newer field.
                    // Accept mode-only settings too (PSW-4359): a CVV-less brand (e.g. MLC)
                    // returns {mode: 'optional', length: 0}, so length alone can't gate this.
                    if (settings && (settings.length || settings.mode) && window.mpCardForm.securityCode === field) {
                        const update = {
                            settings: {
                                mode: settings.mode || 'mandatory',
                                length: settings.length
                            }
                        };

                        // length 0 (a CVV-less brand, e.g. MLC): no placeholder and no
                        // markPlaceholder — there is nothing visible to float the label over (PSW-4359).
                        if (settings.length) {
                            update.placeholder = '0'.repeat(settings.length);
                        }

                        // Wrapped like the cardNumber.update above: a future SDK could throw on
                        // an unexpected settings shape, and an unhandled throw here would break
                        // the ready callback silently (PSW-4359).
                        try {
                            field.update(update);
                        } catch (updateError) {
                            metrics.sendError('mp_security_code_update_error',
                                updateError.message || updateError,
                                self.getCode()
                            );
                        }

                        // Float the label and flag the field as holding a placeholder, so
                        // the label no longer sits over the placeholder and does not drop
                        // back on blur when the field is left empty.
                        if (settings.length) {
                            validateFormSF.markPlaceholder(fieldSecurityCode);
                        }
                    }
                })
                .on('error', () => {
                    self.mountCardForm(self.fields);
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
        },

        /**
         * Reconfigure the secure securityCode field for the detected card brand.
         *
         * The MP SDK creates the field with a generic 3-or-4 length rule, so a
         * wrong-length CVV (e.g. Visa with 4, Amex with 3) only fails later at the
         * payment API with `Invalid security_code_length`. getPaymentMethods returns the
         * brand-specific length in `settings[0].security_code`.
         *
         * The field is fully rebuilt (unmount + recreate) rather than update()d in place
         * because a CVV already typed for the previous card would otherwise survive the
         * brand switch and keep its stale validity — the new brand's length is never
         * re-checked against it. Secure fields expose no clear(), so recreation is the
         * only way to empty one. This is safe on binChange: focus is on the card-number
         * field at that point, not the CVV.
         *
         * @param {Object} result First entry of getPaymentMethods().results
         * @return {void}
         */
        updateSecurityCodeField(result) {
            let settings = result.settings?.[0]?.security_code,
                fieldSecurityCode = this.fields?.fieldSecurityCode;

            // `length` can legitimately be 0 for a CVV-less brand (e.g. MLC returns
            // {mode: 'optional', length: 0}), so `mode` — not `length` — is the proxy for
            // "are there settings to apply". Bail only when neither is present (PSW-4359).
            if (!fieldSecurityCode || !window.mpCardForm?.securityCode || !settings || (!settings.length && !settings.mode)) {
                return;
            }

            // Reflects security_code.mode on the "*" required label (PSW-4359).
            this.cvvRequired(settings.mode !== 'optional');

            // Clear any error/valid state left over from the previous card before the
            // field is rebuilt empty — otherwise a stale CVV error stays on screen.
            validateFormSF.clearValidityState(fieldSecurityCode);

            // Brand-specific CVV help tooltip. Premise: among MP brands, only Amex has a
            // 4-digit CVV, and it is printed on the front; every other brand is 3 digits on
            // the back. If MP ever returns length 4 for a back-CVV brand, revisit this using
            // settings.card_location instead of the length heuristic.
            this.cvvTooltip(settings.length === 4
                ? $t('A 4-digit number on the front of your card.')
                : $t('A 3-digit number in italics on the back of your card.'));

            try {
                window.mpCardForm.securityCode.unmount();
            } catch (e) {
                metrics.sendError('mp_security_code_unmount_error',
                    e.message || e,
                    this.getCode()
                );
            }

            this.mountSecurityCodeField(fieldSecurityCode, settings);
        },

        /**
         * Reset the securityCode field to its default (no-brand) state, used when the BIN is
         * cleared or not recognized. Secure Fields ignores update({placeholder: ''}), so the
         * only way to drop a brand's placeholder/floated label is unmount + remount without
         * settings; also unmarks the label and restores the default CVV tooltip.
         *
         * @return {void}
         */
        resetSecurityCodeField() {
            const fieldSecurityCode = this.fields?.fieldSecurityCode;

            if (!fieldSecurityCode || !window.mpCardForm?.securityCode) {
                return;
            }

            validateFormSF.clearValidityState(fieldSecurityCode);
            validateFormSF.unmarkPlaceholder(fieldSecurityCode);
            this.cvvTooltip($t('A 3-digit number in italics on the back of your card.'));
            // Back to mandatory default: an unrecognized/cleared BIN has no mode of its own
            // to defer to (PSW-4359).
            this.cvvRequired(true);

            try {
                window.mpCardForm.securityCode.unmount();
            } catch (e) {
                metrics.sendError('mp_security_code_unmount_error',
                    e.message || e,
                    this.getCode()
                );
            }

            this.mountSecurityCodeField(fieldSecurityCode);
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
                        self.addTextForInstallment(keys.labels, selectInstallment);
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
            $t("securityCode should be of length '3'.");
            $t("securityCode should be of length '4'.");
            $t("securityCode is empty.");
            $t("expirationMonth should be a number.");
            $t("expirationMonth is empty.");
            $t("expirationYear should be of length '2' or '4'.");
            $t("expirationYear should be a number.");
            $t("expirationYear is empty.");
            $t("expirationMonth should be a value from 1 to 12.");
            $t("expirationYear value should be greater or equal than %1.");
            $t("expirationMonth value should be greater than '%1' or expirationYear value should be greater than '%2'.");
            $t("cardNumber should be of length '15'.");
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
        },

        /**
         * Generated Token Yape
         * @returns {Promise|Boolean}
         */
        async generateTokenYape() {
            var self = this;

            const payload = {
                otp: self.mpYapeOtp(),
                phoneNumber: self.mpYapePhone(),
            };

            try {
                const yape = window.mp.yape(payload);
                const tokenYape = await yape.create();

                self.mpYapeTokenId(tokenYape.id);
                metrics.sendMetric('mp_yape_token_success',
                    'Yape token successfully generated',
                    'big',
                    'mp_checkout_custom_yape'
                );

                return true;
            } catch(e) {
                const message = e.message || e;
                metrics.sendError('mp_yape_token_error',
                    message,
                    'mp_checkout_custom_yape'
                );
                return false;
            }

        }
    });
});
