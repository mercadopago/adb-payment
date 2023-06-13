/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'underscore',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
], function (
    _,
    Component,
    quote,
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
    });
});
