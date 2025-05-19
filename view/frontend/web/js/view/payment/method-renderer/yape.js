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
    'MercadoPago_AdbPayment/js/view/payment/mp-sdk',
    'mage/translate',
], function (
    _,
    $,
    Component,
    $t,
) {
    'use strict';
    return Component.extend({
        redirectAfterPlaceOrder: true,
        defaults: {
            active: false,
            template: 'MercadoPago_AdbPayment/payment/yape',
            yapeForm: 'MercadoPago_AdbPayment/payment/yape-form',
            isLoading: true,
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'mercadopago_adbpayment_yape';
        },

        /**
         * Initializes model instance observable.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'active',
                'isLoading',
            ]);
            return this;
        },

        /**
         * Init component
         */
        initialize() {
            let self = this;

            this._super();

            self.active.subscribe((value) => {
                self.isLoading(!value);
                if (value === true) {
                    setTimeout(() => {
                        self.addOTPInputListener();
                        self.validatePhoneChars();
                    }, 3000);
                }
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
            let isError = this.checkForErrors()

            if (!isError && await this.generateTokenYape()) {
                this.placeOrder();
            }
        },

        onPlaceOrderFail() {
            this.clearInputs();
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData() {
            var self = this;
            return {
                'method': this.getCode(),
                'additional_data': {
                    'mp_device_session_id': window.MP_DEVICE_SESSION_ID,
                    'yape_token_id': self.mpYapeTokenId(),
                }
            };
        },

        /**
         * Get Unsupported Pre Auth
         * @returns {Object}
         */
        getUnsupportedPreAuth() {
            return window.checkoutConfig.payment[this.getCode()].unsupported_pre_auth;
        },

        /**
         * Adds terms and conditions link to checkout
         * @returns {string}
         */
        getFingerprint() {
            return window.checkoutConfig.payment[this.getCode()].fingerprint;
        },

        /**
         * Get list of OTP inputs
         * @returns {NodeListOf<Element>}
         */
        getOTPInputs() {
            return document.querySelectorAll("#yape-otp-inputs input");
        },


        /**
         * Add OTP input listener
         * @returns {void}
         */
        addOTPInputListener() {
            let self = this;
            let otpInputs = self.getOTPInputs();
            otpInputs.forEach((input, index) => {
                input.addEventListener("input", (event) => {
                    // Se o valor colado pelo usuário for maior que 1, preenche os inputs com os valores
                    if (input.value.length > 1) {
                        input.value.split("").forEach((char, i) => {
                            otpInputs[index + i].value = char;
                        });
                    }

                    // Se o valor do input for 1, foca no próximo input
                    if (input.value.length === 1) {
                        this.clearYapeCodeErrors()
                        if (index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                    }
                });

                // Se o usuário apertar backspace ou delete, apaga o valor do input atual e foca no input anterior
                input.addEventListener("keydown", (event) => {
                    if (event.key === "Backspace" && index > 0) {
                        otpInputs[index].value = "";
                        otpInputs[index - 1].focus();
                        this.clearYapeCodeErrors()
                    }

                    if (event.key === "Delete") {
                        otpInputs[index].value = "";
                        otpInputs[index - 1].focus();
                        this.clearYapeCodeErrors()
                    }
                });

                // Quando o input mudar, atualiza o valor do observable
                input.addEventListener("change", (event) => {
                    self.updateMPYapeOtp();
                });
            });
        },

        /**
         * Update OTPValue
         * @returns {void}
         */
        updateMPYapeOtp() {
            let self = this;
            let otpInputs = self.getOTPInputs();
            let otp = "";
            otpInputs.forEach((input) => {
                otp += input.value;
            });

            self.mpYapeOtp(otp);
        },

        /**
         * Clear inputs
         * @returns {void}
         */
        clearInputs() {
            let self = this;
            self.mpYapeOtp("");
            self.mpYapePhone("");
            let otpInputs = self.getOTPInputs();
            otpInputs.forEach((input) => {
                input.value = "";
            });
        },

        /**
         * Return form elements
         * @returns {Object}
         */
        returnYapeFormElements() {
            const yapeCode = document.getElementById("yape-otp-inputs")
            const phoneNumber = document.getElementById('yape-phone')
            const phoneNumberArea = document.getElementsByClassName('phone-area')
            const yapeOtpArea = document.getElementsByClassName("otp-area")

            return {
                yapeCode,
                phoneNumber,
                phoneNumberArea,
                yapeOtpArea
            }
        },

        /**
         * Return error elements
         * @returns {Object}
         */
        returnYapeErrorElements() {
            const emptyPhoneField = document.getElementById("yape-phone-empty")
            const incompletePhoneField = document.getElementById("yape-phone-incomplete")
            const incorrectPhoneField = document.getElementById("yape-phone-incorrect")
            const emptyYapeField = document.getElementById("yape-code-empty")
            const incompleteYapeField = document.getElementById("yape-code-incomplete")
            const incorrectYapeField = document.getElementById("yape-code-incorrect")

            return {
                emptyPhoneField,
                incompletePhoneField,
                incorrectPhoneField,
                emptyYapeField,
                incompleteYapeField,
                incorrectYapeField
            }
        },

        /**
         * Change phone field border to red
         * @returns {void}
         */
        emphasizeYapePhoneError() {
            const { phoneNumberArea, phoneNumber } = this.returnYapeFormElements()

            phoneNumberArea[0].classList.add('yape-error-text')
            phoneNumber.classList.add('yape-incomplete-numbers')
        },

        /**
         * Change yape field border to red
         * @returns {void}
         */
        emphasizeYapeCodeError() {
            const { yapeOtpArea, yapeCode } = this.returnYapeFormElements()

            Array.prototype.forEach.call(yapeCode.children, child => {
                child.classList.add('yape-incomplete-numbers')
            })

            yapeOtpArea[0].classList.add('yape-error-text')
        },

        /**
         * Remove phone field red border
         * @returns {void}
         */
        deEmphasizeYapePhoneError() {
            const { phoneNumberArea, phoneNumber } = this.returnYapeFormElements()

            phoneNumberArea[0].classList.contains('yape-error-text') &&  phoneNumberArea[0].classList.remove('yape-error-text')
            phoneNumber.classList.contains('yape-incomplete-numbers') && phoneNumber.classList.remove('yape-incomplete-numbers')

        },

        /**
         * Remove yape field red border
         * @returns {void}
         */
        deEmphasizeYapeCodeError() {
            const { yapeOtpArea, yapeCode } = this.returnYapeFormElements()

            Array.prototype.forEach.call(yapeCode.children, child => {
                child.classList.contains('yape-incomplete-numbers') && child.classList.remove('yape-incomplete-numbers')
            })
            yapeOtpArea[0].classList.contains('yape-error-text') && yapeOtpArea[0].classList.remove('yape-error-text')
        },

        /**
         * Show incomplete fields errors
         * @returns {void}
         */
        showIncompleteFormFieldsErrors() {
            const { yapeCode, phoneNumber } = this.returnYapeFormElements()
            const { incompletePhoneField, incompleteYapeField } = this.returnYapeErrorElements()

            const isYapeLength = this.validateYapeCodeLength(yapeCode)
            const isPhoneLength = this.validatePhoneNumberLength(phoneNumber)


            if (!isYapeLength) {
                this.emphasizeYapeCodeError()
                incompleteYapeField.style.display = 'flex';
            }

            if (!isPhoneLength) {
                this.emphasizeYapePhoneError()
                incompletePhoneField.style.display = 'flex';
            }
        },

        /**
        * Validate yape code length
        * @returns {void}
        */
        validateYapeCodeLength(yapeCode) {
            const YAPE_CODE_LENGTH = 6
            let count = 0;

            Array.prototype.forEach.call(yapeCode.children, child => {
                if (child.value === '') {
                    count++
                }
            })

            if(count > 0 && count < YAPE_CODE_LENGTH){
                return false;
            }


            return true;
        },

        /**
         * Validate phone number length
         * @returns {void}
         */
        validatePhoneNumberLength(phoneNumber) {
            const PHONE_NUMBER_LENGTH = 9
            const EMPTY_PHONE_NUMBER = 0

            if (phoneNumber.value.length < PHONE_NUMBER_LENGTH && phoneNumber.value.length > EMPTY_PHONE_NUMBER) {
                return false
            }
            return true
        },

        /**
         * Show empty fields errors
         * @returns {void}
         */
        showEmptyFormFieldsErrors() {

            const { yapeCode, phoneNumber } = this.returnYapeFormElements()
            const { emptyPhoneField, emptyYapeField } = this.returnYapeErrorElements()

            const isEmptyYapeCode = this.validateEmptyYapeNumber(yapeCode)

            const isEmptyPhoneNumber = this.validateEmptyPhoneField(phoneNumber)


            if (isEmptyYapeCode) {
                this.emphasizeYapeCodeError()
                emptyYapeField.style.display = 'flex'
            }

            if (isEmptyPhoneNumber) {
                this.emphasizeYapePhoneError()
                emptyPhoneField.style.display = 'flex'
            }
        },

        /**
         * Validate if phone field is empty
         * @returns {Boolean}
         */
        validateEmptyPhoneField(phoneNumber) {
            const EMPTY_PHONE_NUMBER = 0

            if (phoneNumber.value.length > EMPTY_PHONE_NUMBER) {
                return false
            }

            return true
        },

        /**
         * Validate if yape code field is empty
         * @returns {Boolean}
         */
        validateEmptyYapeNumber(yapeCode) {
            let yapeLength = 0;

            Array.prototype.forEach.call(yapeCode.children, child => {

                if (child.value !== '') {
                    yapeLength++
                }

            })

            return yapeLength === 0
        },

        /**
         * Clear yape code errors
         * @returns {void}
         */
        clearYapeCodeErrors() {
            const { emptyYapeField, incompleteYapeField, incorrectYapeField } = this.returnYapeErrorElements()

            incompleteYapeField.style.display = 'none'
            emptyYapeField.style.display = 'none'
            incorrectYapeField.style.display = 'none'

            this.deEmphasizeYapeCodeError()
        },

        /**
         * Clear phone number errors
         * @returns {void}
         */
        clearYapePhoneErrors() {
            const { emptyPhoneField, incompletePhoneField, incorrectPhoneField } = this.returnYapeErrorElements()

            emptyPhoneField.style.display = 'none'
            incompletePhoneField.style.display = 'none'
            incorrectPhoneField.style.display = 'none'

            this.deEmphasizeYapePhoneError()
        },

        /**
         * Check for errors
         * @returns {void}
         */
        checkForErrors() {
            this.clearYapeCodeErrors()
            this.clearYapePhoneErrors()
            this.showIncompleteFormFieldsErrors()
            this.showEmptyFormFieldsErrors()

            if(document.getElementsByClassName('yape-error-text').length > 0){
                return true
            }

            return false
        },

        /**
         * Get icons
         * @returns {String}
         */
        getErrorIcon: function () {
            return window.checkoutConfig.payment[this.getCode()].yapeIcons.attention
        },

        validatePhoneChars: function() {

            const yapePhone = document.getElementById("yape-phone")
            const regex = /[^0-9]/g;
            const erroDiv = document.getElementById('yape-phone-incorrect');

            yapePhone.addEventListener('keyup', (event) =>{
                this.clearYapePhoneErrors()
                let inputValue = event.target.value;

                if (regex.test(inputValue)) {

                  event.target.value = inputValue.replace(regex, '');
                  this.emphasizeYapePhoneError()

                  erroDiv.style.display = 'block';
                } else {

                  erroDiv.style.display = 'none';
                  this.deEmphasizeYapePhoneError()

                }
            })
        },
    });
});
