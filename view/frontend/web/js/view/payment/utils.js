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
    }
 });
