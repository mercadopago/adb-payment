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
    'MercadoPago_AdbPayment/js/view/payment/utils',
], function (
    _,
    Component,
    quote,
    additionalValidators,
    redirectOnSuccessAction,
    mpErrorObserver,
    utils,
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
                    self.mpPayerType(value.replace(/[^A-Z0-9]/gi, '').length <= 11 ? 'CPF' : 'CNPJ');
                }
            });

            // When the document field is hidden, the only way the buyer can fix an invalid
            // document is by editing the billing address VAT. Nothing else re-syncs it after
            // init, so a corrected vatId was never re-sent on retry and the backend kept
            // rejecting the stale value. Keep mpPayerDocument in step with the address whenever
            // the field is hidden. Scoped to the hidden case so a value the buyer typed directly
            // in the visible field is never overwritten. Kept so destroy() can dispose it and
            // avoid leaking subscriptions in themes that recreate payment components.
            self.billingAddressSubscription = quote.billingAddress.subscribe((address) => {
                if (address && address.vatId && !self.DocumentIdentificationCapture()) {
                    self.mpPayerDocument(address.vatId);
                }
            });

            self.generateMpFlowId();
        },

        /**
         * Dispose the billing address subscription set up in initialize so it does not leak
         * when the component is torn down and recreated (e.g. tabbed / SPA-style checkouts).
         *
         * @returns {void}
         */
        destroy: function () {
            if (this.billingAddressSubscription) {
                this.billingAddressSubscription.dispose();
                this.billingAddressSubscription = null;
            }
            this._super();
        },



        generateMpFlowId() {
            return utils.generateMpFlowId();
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
                // Field hidden: the address VAT is the source of truth — always sync it, so a VAT
                // corrected after an error is picked up on retry. Field visible: the payment input is
                // authoritative — only pre-fill when empty, never clobber what the buyer typed (this
                // method re-runs on active/initForm, e.g. twocc card switch).
                if (vatId && (!self.DocumentIdentificationCapture() || !self.mpPayerDocument())) {
                    self.mpPayerDocument(vatId);
                }
            }
        },

        /**
         * When the document field is hidden, the document comes from the billing address. Re-read
         * the latest vatId at submit time so a value corrected after an error is used on retry,
         * even if the Knockout subscription didn't fire (in-place address edit). No-op when the
         * field is visible, so a value typed in the payment form is never overwritten.
         *
         * @returns {void}
         */
        syncHiddenDocumentFromAddress() {
            if (!this.DocumentIdentificationCapture() && quote.billingAddress() && quote.billingAddress().vatId) {
                this.mpPayerDocument(quote.billingAddress().vatId);
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
