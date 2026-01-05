/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'underscore',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/redirect-on-success',
    'mpErrorObserver',
], function (
    _,
    Component,
    quote,
    additionalValidators,
    redirectOnSuccessAction,
    mpErrorObserver,
) {
    'use strict';

    return Component.extend({
        defaults: {
            mpPayerOptionsTypes: '',
            mpPayerDocument: '',
            mpPayerType: '',
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe([
                    'mpPayerOptionsTypes',
                    'mpPayerDocument',
                    'mpPayerType',
                ]);
            return this;
        },

        initialize: function () {
            const self = this;

            this._super();

            self.active.subscribe((value) => {
                if (value === true) {
                    self.getSelectDocumentTypes();
                }
            });

            self.mpPayerDocument.subscribe((value) => {
                if (self.getMpSiteId() === 'MLB' && value) {
                    self.mpPayerType(value.replace(/\D/g, '').length <= 11 ? 'CPF' : 'CNPJ');
                }
            });

            self.generateMpFlowId();
        },

        

        generateUUIDV4() {
            if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
                return crypto.randomUUID();
            }
    
            if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
                return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c =>
                    (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
                )
            }
    
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(substring) {
                const randomInteger = Math.random() * 16 | 0;
                const uuidV4Digit = substring === 'x' ? randomInteger : (randomInteger & 0x3 | 0x8);
                return uuidV4Digit.toString(16);
            });
        },

        /**
         * Get or create MercadoPago Flow ID
         * Store value in sessionStorage
         * @returns {String} Flow ID
         */
        generateMpFlowId() {
            const sessionKey = '_mp_flow_id';
            let flowId = null;

            if (typeof window.mp !== 'undefined' && 
                typeof window.mp.getSDKInstanceId === 'function') {
                flowId = window.mp.getSDKInstanceId();
            }

            if (!flowId) {
                flowId = sessionStorage.getItem(sessionKey);
            }

            if (!flowId) {
                flowId = this.generateUUIDV4();
            }

            sessionStorage.setItem(sessionKey, flowId);

            return flowId;
        },

        /**
         * Get Select Document Types
         * @returns {void}
         */
        async getSelectDocumentTypes() {
            const self = this;

            self.mpPayerOptionsTypes(await window.mp.getIdentificationTypes());

            if (quote.billingAddress()) {
                const vatId = quote.billingAddress().vatId;
                if (vatId) {
                    self.mpPayerDocument(vatId);
                }
            }
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
            return window.checkoutConfig.payment['mercadopago_adbpayment'].mp_site_id;
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
         * Get Validation For Document.
         * @returns {Array}
         */
        getValidationForDocument() {
            let self = this,
                mpSiteId = self.getMpSiteId();

            if (mpSiteId === 'MLB') {
                return {
                    'required': true,
                    'mp-validate-document-identification': '#' + self.getCode() + '_document_identification'
                };
            }
            return {'required': true};
        },

        formatPrice(amount) {
            return Number(Math.round(Math.abs(+amount || 0) + 'e+' + 2) + ('e-' + 2));
        },

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
    });
});
