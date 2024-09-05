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
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/url-builder',
    'MercadoPago_AdbPayment/js/view/payment/mp-sdk',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'mage/translate',
    'MercadoPago_AdbPayment/js/view/payment/method-renderer/three_ds',
], function (
    _,
    $,
    fullScreenLoader,
    errorProcessor,
    quote,
    totals,
    urlBuilder,
    Component,
    VaultEnabler,
    modal,
    urlFormatter,
    $t,
    threeDs,

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
            threeDSDataResponse: {},
            getPaymentStatusResponse: {}
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
                'installmentTextCFT',
                'threeDSDataResponse',
                'getPaymentStatusResponse'

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
                    'mp_device_session_id': window.MP_DEVICE_SESSION_ID
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
        },

        onPlaceOrderFail: function (response) {
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
                    errorProcessor.process(response);
                }
            );
        },

        openModalChallenge: function (threeDSData) {
            var self = this;

            try {
                $('body').append(threeDs.createModalChallenge(self.generatedCards[0]?.cardNumber.slice(-4), self.generatedCards[0]?.cardType));

                let $modalChallenge = $('#modal-3ds-challenge');
                let popup = modal(this.getModalChallengeOptions(), $modalChallenge);

                $modalChallenge.modal('openModal');
                $(".modal-footer").hide();

                this.loadChallengeInfo(threeDSData);
                this.addListenerResponseChallenge();

                threeDs.sendMetric('mp_3ds_success_modal', '3ds modal challenge opened');
            }catch (error) {
                const message = error.message || error;
                threeDs.sendMetric('mp_3ds_error_modal', message);
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
                    threeDs.sendMetric('mp_3ds_error_load_challenge_info', message);
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
                        threeDs.sendMetric('mp_3ds_success_pooling_time', 'Pooling time: ' + elapsedTime.toString() + ' ms');
                    }
                    elapsedTime += interval;
                }, interval);
            } catch (error) {
                const message = error.message || error;
                threeDs.sendMetric('mp_3ds_error_pooling_time', message);
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
