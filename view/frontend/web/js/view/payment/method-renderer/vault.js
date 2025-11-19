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
    'MercadoPago_AdbPayment/js/view/payment/method-renderer/validate-form-security',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/model/payment/additional-validators',
    'mage/url',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Ui/js/modal/modal',
    'MercadoPago_AdbPayment/js/view/payment/method-renderer/three_ds',
    'MercadoPago_AdbPayment/js/view/payment/utils',
    'MercadoPago_AdbPayment/js/view/payment/metrics'
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
    validateFormSF,
    redirectOnSuccessAction,
    additionalValidators,
    urlFormatter,
    urlBuilder,
    errorProcessor,
    modal,
    threeDs,
    utils,
    metrics
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
            installmentTextTNA: null,
            installmentTextTEA: null,
            installmentTextCFT: null,
            isLoading: true,
            threeDSDataResponse: {},
            getPaymentStatusResponse: {}
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
                'installmentTextTNA',
                'installmentTextTEA',
                'installmentTextCFT',
                'isLoading',
                'threeDSDataResponse',
                'getPaymentStatusResponse'
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

        /**
         * Override PlaceOrder
         */
        placeOrder: function (data, event) {
            var self = this;

            if (event) {
                event.preventDefault();
            }

            if (this.validate() &&
                additionalValidators.validate() &&
                this.isPlaceOrderActionAllowed() === true
            ) {
                this.isPlaceOrderActionAllowed(false);

                this.getPlaceOrderDeferredObject()
                    .done(
                        function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    ).always(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    ).fail(
                        function (response) {
                            self.onPlaceOrderFail(response);
                        }
                )

                return true;
            }

            return false;
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
                self.placeOrder();
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
                        self.addTextForInstallment(keys.labels, selectInstallment);
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
                    'mp_user_id': self.getMpUserId(),
                    'mp_flow_id': self.generateMpFlowId()
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
                if (result[0] && result[0].payer_costs) {
                    var listInstallments = result[0].payer_costs;

                    if (self.getMpSiteId() === 'MCO' || self.getMpSiteId() === 'MPE' || self.getMpSiteId() === 'MLC') {
                        utils.addTextInterestForInstallment(listInstallments);
                    }

                    self.creditCardListInstallments(result[0].payer_costs);
                }
            });

            return installments;
        },

        onPlaceOrderFail: function (response) {
            var self = this;
            if(response.responseJSON.message === '3DS') {
                this.getThreeDSData();
                $('.messages').hide();
            }
        },

        getThreeDSData: function () {
            var serviceUrl = urlBuilder.createUrl('/quote/:quoteId/mp-payment-information', {
                quoteId: quote.getQuoteId()
            });

            $.ajax({
                url: urlFormatter.build(serviceUrl),
                global: false,
                contentType: 'application/json',
                type: 'GET',
                async: true
            }).done(
                (response) => {
                    this.threeDSDataResponse(response[0]);
                    this.openModalChallenge(response[0]);
                }
            ).fail(
                (response) => {
                    this.errorProcessor.process(response);
                }
            );
        },

        openModalChallenge: function (threeDSData) {
            var self = this;

            try {
                $('body').append(threeDs.createModalChallenge(self.details.card_last4, self.details.card_type));

                let $modalChallenge = $('#modal-3ds-challenge');
                let popup = modal(this.getModalChallengeOptions(), $modalChallenge);

                $modalChallenge.modal('openModal');
                $(".modal-footer").hide();

                this.loadChallengeInfo(threeDSData);
                this.addListenerResponseChallenge();

                metrics.sendMetric(
                    'mp_3ds_success_modal',
                    '3ds modal challenge opened',
                    'big',
                    'mp_magento_credit_card_three_ds'
                );
            }catch (error) {
                const message = error.message || error;
                metrics.sendError('mp_3ds_error_modal', message, 'mp_magento_credit_card_three_ds');
            }
        },

        getModalChallengeOptions: function () {
            var self = this;
            return {
                type: 'popup',
                responsive: false,
                innerScroll: false,
                title: $t('Complete the bank validation so your payment can be approved'),
                modalClass: 'modal-challenge',
                closed: function () {
                    self.destroyModal();
                    self.placeOrder();
                }
            };
        },

        loadChallengeInfo(threeDSData) {
            var self = this;
            setTimeout(function() {
                try {
                    $('#loading-area').remove();
                    $('#modal-3ds-challenge').append(threeDs.appendIframeContent());

                    var iframe = document.createElement("iframe");
                    iframe.name = "myframe";
                    iframe.id = "myframe";
                    iframe.height = "500px";
                    iframe.width = "600px";
                    iframe.style = "border:none;";
                    document.getElementById("iframe-challenge").appendChild(iframe);

                    var idocument = iframe.contentWindow.document;

                    var myform = idocument.createElement("form");
                    myform.name = "myform";
                    myform.setAttribute("target", "myframe");
                    myform.setAttribute("method", "post");
                    myform.setAttribute("action", threeDSData.three_ds_external_resource_url);

                    var hiddenField = idocument.createElement("input");
                    hiddenField.setAttribute("type", "hidden");
                    hiddenField.setAttribute("name", "creq");
                    hiddenField.setAttribute("value", threeDSData.three_ds_creq);
                    myform.appendChild(hiddenField);
                    iframe.appendChild(myform);

                    myform.submit();

                } catch (error) {
                    const message = error.message || error;
                    metrics.sendError('mp_3ds_error_load_challenge_info', message, 'mp_custom_checkout_three_ds');
                }
            }, 3000)
        },

        destroyModal() {
            $('#modal-3ds-challenge').remove();
        },

        addListenerResponseChallenge() {
            var self = this;
            window.addEventListener('message', function (e) {
                const statusChallenge = e.data.status;
                if (statusChallenge === 'COMPLETE') {
                    self.loadPulling();
                    threeDs.customLoader();
                }
            });
        },

        loadPulling() {
            try {
                const interval = 2000;
                let elapsedTime = 0;

                const intervalId = setInterval(() => {
                    this.getPaymentStatus();
                    var paymentStatus = this.getPaymentStatusResponse();

                    if (elapsedTime >= 10000 || paymentStatus.status === 'approved' || paymentStatus.status === 'rejected') {
                        $('#modal-3ds-challenge').modal('closeModal');
                        this.destroyModal();
                        clearInterval(intervalId);
                        this.placeOrder();
                        metrics.sendMetric(
                            'mp_3ds_success_pooling_time',
                            'Pooling time: ' + elapsedTime.toString() + ' ms',
                            'big',
                            'mp_3ds_success_pooling_time'
                        );
                    }
                    elapsedTime += interval;
                }, interval);
            } catch (error) {
                const message = error.message || error;
                metrics.sendError('mp_3ds_error_pooling_time', message, 'mp_custom_checkout_three_ds');
            }
        },

        getPaymentStatus(){
            var serviceUrl = urlBuilder.createUrl('/payment/mp-payment-status', {});

            const payloadPaymentStatus = {
                paymentId: this.threeDSDataResponse().payment_id,
                cartId: this.threeDSDataResponse().quote_id,
            }

            $.ajax({
                url: urlFormatter.build(serviceUrl),
                data: payloadPaymentStatus,
                global: false,
                type: 'GET',
                async: true
            }).done(
                (response) => {
                    this.getPaymentStatusResponse(response[0]);
                    return response[0];
                }
            ).fail(
                (response) => {
                    errorProcessor.process(response[0]);
                }
            );
        },
    });
});
