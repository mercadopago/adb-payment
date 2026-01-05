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