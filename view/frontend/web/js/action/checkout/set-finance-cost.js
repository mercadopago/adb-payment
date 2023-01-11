/* eslint-disable max-len */
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

 define([
    'underscore',
    'jquery',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'mage/url'
], function (
    _,
    $,
    getTotalsAction,
    errorProcessor,
    quote,
    totals,
    urlBuilder,
    customer,
    urlFormatter
) {
        'use strict';

        return {

            /**
             * Add Finance Cost in totals
             * @param {String} selectInstallment
             * @param {Object} rulesForFinanceCost
             * @returns {void}
             */
            financeCost(
                selectInstallment,
                rulesForFinanceCost
            ) {
                var serviceUrl,
                    payload,
                    financeCostAmount = 0,
                    quoteId = quote.getQuoteId(),
                    sendRulesForFinanceCost = {
                        installments: 0,
                        installment_rate: 0,
                        discount_rate: 0,
                        reimbursement_rate: false,
                        total_amount: 0
                    };

                if (totals && totals.getSegment('finance_cost_amount')) {
                    financeCostAmount = totals.getSegment('finance_cost_amount').value;
                }

                if (!financeCostAmount && !selectInstallment) {
                    return;
                }

                _.map(rulesForFinanceCost, (keys) => {
                    if (keys.installments === selectInstallment) {
                        sendRulesForFinanceCost = {
                            installments: keys.installments,
                            installment_rate: keys.installment_rate,
                            discount_rate: keys.discount_rate,
                            reimbursement_rate: keys.reimbursement_rate,
                            total_amount: keys.total_amount
                        };
                    }
                });

                if (!customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/mp-set-finance-cost', {
                        cartId: quoteId
                    });
                } else {
                    serviceUrl = urlBuilder.createUrl('/carts/mine/mp-set-finance-cost', {});
                }

                payload = {
                    cartId: quoteId,
                    userSelect: {
                        selected_installment: selectInstallment
                    },
                    rules: sendRulesForFinanceCost
                };

                $.ajax({
                    url: urlFormatter.build(serviceUrl),
                    data: JSON.stringify(payload),
                    global: false,
                    contentType: 'application/json',
                    type: 'POST',
                    async: true
                }).done(
                    () => {
                        var deferred = $.Deferred();

                        getTotalsAction([], deferred);
                    }
                ).fail(
                    (response) => {
                        errorProcessor.process(response);
                    }
                );
            }
        };
});
