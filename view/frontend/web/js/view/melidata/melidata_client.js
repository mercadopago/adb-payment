define([], function () {
    'use strict';

    return function (OriginalComponent) {
        return OriginalComponent.extend({

            /**
             * Initialize the Melidata client component
             */
            initialize: function () {
                this._super();

                if (!this.isMelidataAlreadyLoaded()) {
                    this.initializeMelidataClient();
                }

                setTimeout(() => {
                    this.initFieldEventTracking();
                }, 100);

                return this;
            },

            /**
             * Check if MelidataClient is already loaded
             * @returns {boolean}
             */
            isMelidataAlreadyLoaded: function () {
                const existingScript = document.getElementById('adbpayment');
                if (existingScript) {
                    return true;
                }

                return false;
            },

            /**
             * Initialize Melidata client
             */
            initializeMelidataClient: function () {
                try {
                    const config = this.getMelidataConfig();
                    const script = this.createMelidataScript(config);

                    this.appendScriptToDocument(script);
                } catch (error) {
                    console.warn('Failed to initialize Melidata client:', error);
                }
            },

            /**
             * Get Melidata configuration from window.checkoutConfig
             * @returns {Object} Configuration object
             */
            getMelidataConfig: function () {
                const mpData = window.checkoutConfig.payment.mercadopago_adbpayment;

                return {
                    type: 'buyer',
                    siteID: mpData.mp_site_id,
                    pluginVersion: mpData.plugin_version,
                    platformVersion: mpData.platform_version,
                    pageLocation: window.location.pathname.replace(/\/$/, '')
                };
            },

            /**
             * Create and configure Melidata script element
             * @param {Object} config - Melidata configuration
             * @returns {HTMLElement} Script element
             */
            createMelidataScript: function (config) {
                const script = document.createElement('script');

                script.setAttribute('id', 'adbpayment');
                script.src = 'https://http2.mlstatic.com/storage/v1/plugins/melidata/adbpayment.min.js';
                script.async = true;
                script.defer = true;

                script.onerror = () => this.handleScriptError(config);

                script.onload = () => this.handleScriptLoad(config);

                return script;
            },

            /**
             * Handle script loading error
             * @param {Object} config - Melidata configuration
             */
            handleScriptError: function (config) {
                const errorPayload = {
                    name: 'ERR_CONNECTION_REFUSED',
                    message: 'Unable to load melidata script on page',
                    target: 'melidata_adbpayment_client',
                    plugin: {
                        version: config.pluginVersion,
                    },
                    platform: {
                        name: 'adbpayment',
                        url: `${window.location.pathname}${window.location.search}`,
                        version: config.platformVersion,
                        location: config.pageLocation,
                    },
                };

                const errorUrl = 'https://api.mercadopago.com/v1/plugins/melidata/errors';
                navigator.sendBeacon(errorUrl, JSON.stringify(errorPayload));
            },

            /**
             * Handle successful script loading
             * @param {Object} config - Melidata configuration
             */
            handleScriptLoad: function (config) {
                window.melidata = new MelidataClient({
                    type: config.type,
                    siteID: config.siteID,
                    pluginVersion: config.pluginVersion,
                    platformVersion: config.platformVersion,
                    pageLocation: config.pageLocation
                });
            },

            /**
             * Append script to document body
             * @param {HTMLElement} script - Script element to append
             */
            appendScriptToDocument: function (script) {
                document.body.appendChild(script);
            }
        });
    };
});
