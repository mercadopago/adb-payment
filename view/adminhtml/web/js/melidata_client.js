/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

define([], function () {
  'use strict';

  return function (config) {

    window.melidataConfig = config;

    window.melidatalog = function ($data) {
      console.log("Data: ", $data);
    };

    try {
      const scriptMelidata = document.createElement('script');
      scriptMelidata.setAttribute('id', 'adbpayment');
      scriptMelidata.src = 'https://http2.mlstatic.com/storage/v1/plugins/melidata/adbpayment.min.js';
      scriptMelidata.async = true;
      scriptMelidata.defer = true;
      scriptMelidata.onerror = function () {

        const url = 'https://api.mercadopago.com/v1/plugins/melidata/errors';

        const payload = {
          name: 'ERR_CONNECTION_REFUSED',
          message: 'Unable to load melidata script on page',
          target: 'melidata_adbpayment_client',
          plugin: {
            version: window.melidataConfig.moduleVersion,
          },
          platform: {
            name: 'adbpayment',
            url: `${window.location.pathname}${window.location.search}`,
            version: window.melidataConfig.platformVersion,
            location: '/settings',
          },
        };

        navigator.sendBeacon(url, JSON.stringify(payload));

      };

      scriptMelidata.onload = function () {
        window.melidata = new MelidataClient({
          type: 'seller',
          siteID: melidataConfig.siteId,
          pluginVersion: melidataConfig.moduleVersion,
          platformVersion: melidataConfig.platformVersion,
          pageLocation: '/settings'
        });
      };

      document.body.appendChild(scriptMelidata);
    } catch (error) {
      console.warn(error);
    }
  }
});
