define([
    'underscore',
    'jquery'
], function (
    _,
    $
 ) {
    'use strict';

    return {
        sendMetric(name, message, team, metric) {
            const url = 'https://api.mercadopago.com/ppcore/prod/monitor/v1/event/datadog/' + team +  '/' + metric;
            const payload = {
              value: name,
              message: message,
              target: metric,
              plugin_version: window.checkoutConfig.payment['mercadopago_adbpayment'].plugin_version,
              platform: {
                name: 'magento',
                uri: window.location.href,
                version: window.checkoutConfig.payment['mercadopago_adbpayment'].platform_version,
                location: window.location.href,
              }
            };

            $.post({
                url: url,
                contentType: 'application/json',
                data: JSON.stringify(payload)
            }).fail(
                (error) => {
                    console.log('Error sending metric', error);
                }
            );
        },

        sendError(name, message, target) {
            const url = 'https://api.mercadopago.com/ppcore/prod/monitor/v1/errors';
            const payload = {
              name,
              message,
              target: target,
              plugin: {
                version: window.checkoutConfig.payment['mercadopago_adbpayment'].plugin_version,
              },
              platform: {
                name: 'magento',
                uri: window.location.href,
                version: window.checkoutConfig.payment['mercadopago_adbpayment'].platform_version,
                location: window.location.href,
              },
            };
          
            navigator.sendBeacon(url, JSON.stringify(payload));
        }
    }
 });
