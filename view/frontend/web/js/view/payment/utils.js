define([
    'mage/translate',
], function (
    $t
 ) {
    'use strict';

    return {

        /**
         * Add interest text for installments
         * @param {Array}
         * @return {Array}
         */
        addTextInterestForInstallment(listInstallments) {
            _.map(listInstallments, (installment) => {
                var installmentRate = installment.installment_rate;
                var installmentRateCollector = installment.installment_rate_collector;

                if (installmentRate === 0 && installmentRateCollector[0] === 'MERCADOPAGO') {
                    installment.recommended_message = installment.recommended_message + ' ' + $t("Interest-free");
                }

                if (installmentRate === 0 && installmentRateCollector[0] === 'THIRD_PARTY') {
                    installment.recommended_message = installment.recommended_message + ' ' + $t("Your Bank will apply Interest");
                }

                return installment;
            });
        },

        /**
         * Generate UUID V4
         * @returns {String}
         */
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
         * @returns {String}
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
         * Format Installment Fees
         *
         * Currently used only for Argentina
         *
         * @param {Array} labels
         * @param {Number} selectedInstallment
         * @return {Map|null} {CFT: 'CFTEA: 0,00%', TNA: 'TNA: 0,00%', TEA: 'TEA: 0,00%'}
         */
        formatInstallmentFees(labels, selectedInstallment) {
            const _formatFeeText = (fee, feeName, label = null) => {
                return fee.replace(`${feeName}_`, `${label ?? feeName}: `);
            };

            if (selectedInstallment === 1 || labels.length === 0) {
                return null;
            }

            const formatedFees = {
                CFT: null,
                TNA: null,
                TEA: null,
            };

            labels.forEach((label) => {
                const allFees = label.split('|');
                allFees.forEach((fee) => {
                    switch (true) {
                        case fee.includes('TNA'):
                            formatedFees['TNA'] = _formatFeeText(fee, 'TNA');
                            break;
                        case fee.includes('TEA'):
                            formatedFees['TEA'] = _formatFeeText(fee, 'TEA');
                            break;
                        case fee.includes('CFT'):
                            formatedFees['CFT'] = _formatFeeText(fee, 'CFT', 'CFTEA');
                            break;
                    }
                });
            });

            return formatedFees;
        },
    }
 });
