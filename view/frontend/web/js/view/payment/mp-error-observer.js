/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/model/messageList'
], function ($, messageList) {
    'use strict';

    var MPErrorMessageNormalizer = {
        DEFAULT_ERROR_MESSAGE: 'empty error message',
        DEFAULT_ERROR_FIELD: 'Miss or wrong information on required fields',
        
        /*
        * Normalize the error message
        * @param {string} message - The error message
        * @returns {string} The normalized error message
        */
        normalize: function(message) {
            if (!message) return this.DEFAULT_ERROR_MESSAGE;
            return message.replace(/[\t\n]/g, '').trim() || this.DEFAULT_ERROR_MESSAGE;
        }
    };

    if (window.MpCheckoutErrorObserverInstance) {
        return window.MpCheckoutErrorObserverInstance;
    }

    var MpCheckoutErrorObserver = {
        ERROR_EVENT_NAME: 'mp_checkout_error',
        isProcessing: false,
        isInitialized: false,

        /*
        * Check if the payment method is MercadoPago
        * @returns {string} The payment method code
        */
        isMercadoPagoPayment: function() {
            var paymentMethod = $('input[name="payment[method]"]:checked').val();
            
            if (paymentMethod && paymentMethod.includes('mercadopago_adbpayment')) {
                return paymentMethod;
            }
            
            return null;
        },

        init: function () {
            if (this.isInitialized) {
                return;
            }
            this.isInitialized = true;

            this.observeAjaxErrors();
            this.observeMessageListErrors();
            this.observePlaceOrderClicks();
        },


        /*
        * Dispatch an event to the document
        * @param {object} detail - The detail object
        */
        dispatchEvent: function(detail) { 
            document.dispatchEvent(new CustomEvent(this.ERROR_EVENT_NAME, { detail }));
        },

        /*
        * Observe AJAX errors
        */
        observeAjaxErrors: function () {
            var self = this;
            
            $(document).off('ajaxError.mpErrorObserver');
                        
            $(document).on('ajaxError.mpErrorObserver', function (event, jqXHR, jqXHRSettings) {
                if (jqXHR && jqXHR.responseJSON) {
                    var paymentMethod = self.isMercadoPagoPayment();
                    if (!paymentMethod) return;
                    
                    self.dispatchEvent({
                        message: MPErrorMessageNormalizer.normalize(jqXHR.responseJSON.message),
                        status: jqXHR.status,
                        statusText: jqXHR.statusText,
                        type: 'api_error',
                        paymentMethod: paymentMethod,
                    });
                }
            });
        },

        /*
        * Observe message list errors
        */
        observeMessageListErrors: function() {
            var self = this;
            
            if (window._mpMessageListPatched) {
                return;
            }
            
            var originalAddErrorMessage = messageList.addErrorMessage;
            
            messageList.addErrorMessage = function(message) {
                var paymentMethod = self.isMercadoPagoPayment();
                if (paymentMethod) {                    
                    self.dispatchEvent({
                        message: MPErrorMessageNormalizer.normalize(message.message || message),
                        type: 'magento_error',
                        paymentMethod: paymentMethod,
                    });
                }
                
                return originalAddErrorMessage.call(this, message);
            };
            
            window._mpMessageListPatched = true;
        },

        /*
        * Observe place order clicks
        */
        observePlaceOrderClicks: function() {
            var self = this;
            
            $(document).off('click.mpErrorObserver', '.action.primary.checkout');
            
            $(document).on('click.mpErrorObserver', '.action.primary.checkout', function(e) {
                if (self.isProcessing) {
                    return;
                }
                
                self.isProcessing = true;

                setTimeout(function() {
                    var paymentMethod = self.isMercadoPagoPayment();
                    if (!paymentMethod) {
                        self.isProcessing = false;
                        return;
                    }
                    
                    var mpContainer = $('.payment-method._active[id*="mercadopago"]').first();

                    if (mpContainer.length > 0) {
                        var errorFields = [];

                        self.dispatchFieldErrors(paymentMethod, mpContainer, errorFields);

                        if (errorFields.length > 0) {
                            self.dispatchEvent({
                                message: MPErrorMessageNormalizer.DEFAULT_ERROR_FIELD,
                                type: 'form_error',
                                field: errorFields,
                                paymentMethod: paymentMethod,
                            });
                        }
                    }

                    self.isProcessing = false;

                }, 150);
            });
        },

        /*
        * Dispatch field errors
        * @param {string} paymentMethod - The payment method code
        * @param {object} mpContainer - The MercadoPago container
        * @param {array} errorFields - The error fields
        */
        dispatchFieldErrors: function(paymentMethod, mpContainer, errorFields) {
            var errors;
            
            if (paymentMethod.includes('yape')) {
                errors = this.extractYapeFieldErrors(mpContainer);
            } else {
                errors = this.extractStandardFieldErrors(mpContainer);
            }
            
            errors.forEach(function(error) {
                errorFields.push(error.fieldName);
            });
        },

        /*
        * Extract standard field errors
        * @param {object} mpContainer - The MercadoPago container
        * @returns {array} The error fields
        */
        extractStandardFieldErrors: function(mpContainer) {
            var errors = [];
            
            mpContainer.find('.mage-error[id$="-error"]:visible').each(function() {
                var fieldName = this.id.replace('-error', '');
                errors.push({
                    fieldName: fieldName,
                });
            });
            
            return errors;
        },

        /*
        * Extract Yape field errors
        * @param {object} mpContainer - The MercadoPago container
        * @returns {array} The error fields
        */
        extractYapeFieldErrors: function(mpContainer) {
            var errors = [];
            
            mpContainer.find('.yape-error:visible').each(function() {
                var parentElement = this.parentElement;
                var fieldName = parentElement.querySelector('input').id;

                if (!fieldName) {
                    fieldName = parentElement.className;
                }

                errors.push({
                    fieldName: fieldName,
                });
            });
            
            return errors;
        },
    };

    MpCheckoutErrorObserver.init();
    window.MpCheckoutErrorObserverInstance = MpCheckoutErrorObserver;
    
    return MpCheckoutErrorObserver;
});