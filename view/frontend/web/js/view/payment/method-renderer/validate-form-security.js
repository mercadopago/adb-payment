/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'underscore',
    'jquery',
    'mage/translate'
], function (
    _,
    $,
    $t
) {
    'use strict';

    return {

        /**
         * Remove Classes if Empyt
         * @param {String} element
         * @returns {void}
         */
        removeClassesIfEmpyt(element) {
            let hasError = $('#' + element).closest('.control-mp-iframe.has-error').length,
                isValid = $('#' + element).closest('.control-mp-iframe.is-valid').length;

            if (!hasError) {
                if (!isValid) {
                    $('#' + element).closest('.control-mp-iframe').removeClass('in-focus');
                }
            }
        },

        /**
         * Toogle Focus Style
         * @param {String} element
         * @returns {void}
         */
        toogleFocusStyle(element) {
            $('#' + element).closest('.control-mp-iframe').addClass('in-focus');
        },

        /**
         * Single Toogle Validity State
         * @param {String} element
         * @param {String} errorMessages
         * @returns {Jquery}
         */
        singleToogleValidityState(element, errorMessages) {
            var target = $('#' + element).closest('.mercadopago-input-group');

            if (errorMessages.length)
            {
                target.append('<div class="field-error"><span>' + $t(errorMessages) + '</span></div>');
                return $('#' + element).closest('.control-mp-iframe').addClass('has-error').removeClass('is-valid');
            }
            return $('#' + element).closest('.control-mp-iframe').addClass('is-valid').removeClass('has-error');
        },

        /**
         * Toogle Validity State
         * @param {String} element
         * @param {String} errorMessages
         * @returns {Jquery}
         */
        toogleValidityState(element, errorMessages) {
            var target = $('#' + element).closest('.mercadopago-input-group'),
                infoErro = $('#' + element).closest('.mercadopago-input-group').find('.field-error'),
                msg;

            if (infoErro.length) {
                infoErro.remove();
            }

            if (errorMessages.length)
            {
                _.map(errorMessages, (error) => {
                    msg = error.message;
                });

                target.append('<div class="field-error"><span>' + $t(msg) + '</span></div>');
                return $('#' + element).closest('.control-mp-iframe').addClass('has-error').removeClass('is-valid');
            }
            return $('#' + element).closest('.control-mp-iframe').addClass('is-valid').removeClass('has-error');
        },

        /**
         * Clear Errors in Field
         * @return {void}
         */
        clearSecureFieldsErrors(){
            return $('#form-secure-fields div.field-error').remove()  
        },
    };
});
