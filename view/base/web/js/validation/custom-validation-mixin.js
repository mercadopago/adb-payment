/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

 define(['jquery'], function ($) {
    'use strict';

    return function () {

        /**
         * Invalidate Common CNPJ
         * @param {String} value
         * @return {boolean}
         */
        function getInvalidateCommonCNPJ(value) {
            if (
                value === '00000000000000' ||
                value === '11111111111111' ||
                value === '22222222222222' ||
                value === '33333333333333' ||
                value === '44444444444444' ||
                value === '55555555555555' ||
                value === '66666666666666' ||
                value === '77777777777777' ||
                value === '88888888888888' ||
                value === '99999999999999'
            ) {
                return true;
            }
            return false;
        }

        /**
         * Invalidate Common CPF
         * @param {String} value
         * @return {boolean}
         */
        function getInvalidateCommonCPF(value) {
            if (
                value === '00000000000' ||
                value === '11111111111' ||
                value === '22222222222' ||
                value === '33333333333' ||
                value === '44444444444' ||
                value === '55555555555' ||
                value === '66666666666' ||
                value === '77777777777' ||
                value === '88888888888' ||
                value === '99999999999'
            ) {
                return true;
            }
            return false;
        }

        /**
         * Validate CPF
         *
         * @param {String} cpf - CPF number
         * @return {Boolean}
         */
        function validateCPF(cpf) {

            if (cpf.length !== 11) {
                return false;
            }

            if (getInvalidateCommonCPF(cpf)) {
                return false;
            }

            let add = 0,
                i,
                j,
                rev;

            for (i = 0; i < 9; i++) {
                add += parseInt(cpf.charAt(i), 10) * (10 - i);
            }

            rev = 11 - add % 11;
            if (rev === 10 || rev === 11) {
                rev = 0;
            }
            if (rev !== parseInt(cpf.charAt(9), 10)) {
                return false;
            }

            add = 0;
            for (j = 0; j < 10; j++) {
                add += parseInt(cpf.charAt(j), 10) * (11 - j);
            }

            rev = 11 - add % 11;

            if (rev === 10 || rev === 11) {
                rev = 0;
            }

            if (rev !== parseInt(cpf.charAt(10), 10)) {
                return false;
            }

            return true;
        }

        /**
         * Validate CNPJ
         *
         * @param {String} cnpj - CNPJ number
         * @return {Boolean}
         */
        function validateCNPJ(cnpj) {
            var tamanho = cnpj.length - 2,
                numeros = cnpj.substring(0, tamanho),
                digitos = cnpj.substring(tamanho),
                soma = 0,
                pos = tamanho - 7;

            if (cnpj.length !== 14) {
                return false;
            }

            if (getInvalidateCommonCNPJ(cnpj)) {
                return false;
            }

            let i,
                j,
                resultado;

            for (i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) {
                    pos = 9;
                }
            }
            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;

            if (resultado !== parseInt(digitos.charAt(0), 10)) {
                return false;
            }

            tamanho += 1;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;
            for (j = tamanho; j >= 1; j--) {
                soma += numeros.charAt(tamanho - j) * pos--;
                if (pos < 2) {
                    pos = 9;
                }
            }
            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;

            if (resultado !== parseInt(digitos.charAt(1), 10)) {
                return false;
            }

            return true;
        }

        /**
         * Add Validation Document Identification
         */
        $.validator.addMethod(
            'mp-validate-document-identification',

                /**
                 * Validate document idenfitication.
                 *
                 * @param {String} value - document idenfitication number
                 * @return {Boolean}
                 */
                function (value) {
                    var documment = value.replace(/[^\d]+/g, '');

                    if (documment.length === 14) {
                        return validateCNPJ(documment);
                    }

                    if (documment.length === 11) {
                        return validateCPF(documment);
                    }

                    return false;
                },
            $.mage.__('Please provide a valid document identification.')
        );
    };
});
