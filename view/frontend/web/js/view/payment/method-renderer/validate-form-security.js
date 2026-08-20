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
            let control = $('#' + element).closest('.control-mp-iframe'),
                keepFocus = control.hasClass('has-error') ||
                    control.hasClass('is-valid') ||
                    control.hasClass('_has-placeholder');

            if (!keepFocus) {
                control.removeClass('in-focus');
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
         * Mark a secure field as holding a placeholder and float its label. The
         * `_has-placeholder` flag keeps the label floated on blur (removeClassesIfEmpyt)
         * so it does not drop back over the placeholder when the field is left empty.
         * @param {String} element
         * @returns {void}
         */
        markPlaceholder(element) {
            $('#' + element).closest('.control-mp-iframe').addClass('in-focus _has-placeholder');
        },

        /**
         * Clear the placeholder marker and the floated-label state.
         * @param {String} element
         * @returns {void}
         */
        unmarkPlaceholder(element) {
            $('#' + element).closest('.control-mp-iframe').removeClass('in-focus _has-placeholder');
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
         * Clear Validity State
         * Resets a single field to neutral: removes its error message and both the
         * has-error and is-valid styles. Used when a secure field is rebuilt (e.g. the
         * securityCode field on card/brand change) so a stale error is not left behind.
         * Intentionally does NOT touch `in-focus`/`_has-placeholder`: the field is
         * remounted right after and `markPlaceholder` re-floats the label on `ready`, so
         * clearing them here would drop and re-raise the label (visible flicker). Use
         * `unmarkPlaceholder` for a full reset (see resetCardForm).
         * @param {String} element
         * @returns {void}
         */
        clearValidityState(element) {
            $('#' + element).closest('.mercadopago-input-group').find('.field-error').remove();
            $('#' + element).closest('.control-mp-iframe').removeClass('has-error is-valid');
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
