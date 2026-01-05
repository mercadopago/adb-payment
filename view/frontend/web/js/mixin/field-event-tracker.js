define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    /**
     * Field Event Tracker Mixin
     */
    return function (targetModule) {
        return targetModule.extend({

            /**
             * Field event configuration
             */
            fieldEventConfig: {
                trackFocus: true,
                trackInput: true,
                trackBlur: true,
                debounceDelay: 500,
                enabledEvents: ['blur']
            },

            /**
             * Initialize field event tracking
             */
            initFieldEventTracking: function () {
                this.setupNativeFieldTracking();
            },

            /**
             * Setup tracking for native HTML fields
             */
            setupNativeFieldTracking: function () {
                const self = this;
                const paymentCode = this.getCode();

                // Track document number input
                this.trackFieldEvents(`#${paymentCode}_document_identification`, 'document_number');

                // Track card holder name (if exists)
                this.trackFieldEvents(`#${paymentCode}_cardholder_name`, 'cardholder_name');

                // Track entity type (PSE)
                this.trackFieldEvents(`#${paymentCode}_payer_entity_type`, 'entity_type');

                // Track financial institution (PSE)
                this.trackFieldEvents(`#${paymentCode}_financial_institutions`, 'financial_institution');

                // Track Yape phone number (Yape)
                this.trackFieldEvents('#yape-phone', 'yape-phone');

            },

            /**
             * Track events for native HTML fields
             */
            trackFieldEvents: function (selector, fieldName) {
                const self = this;

                $(document).off('blur.mp-tracking', selector);

                $(document).on('blur.mp-tracking', selector, _.debounce(function (event) {
                    self.onFieldBlur(fieldName, event.target, event);
                }, this.fieldEventConfig.debounceDelay));
            },

            /**
             * Handle field blur event
             */
            onFieldBlur: function (fieldName, element, event) {
                const value = element ? element.value : (event.target ? event.target.value : '');
                this.triggerFieldEvent('blur', {
                    fieldName: fieldName,
                    paymentMethod: this.getCode(),
                    isEmpty: this.isFieldEmpty(value),
                }, 'mp_field_interaction');
            },

            /**
             * Trigger custom field event
             */
            triggerFieldEvent: function (eventType, data, customEventType) {
                $(document).trigger(`mp:field:${eventType}`, [data]);
                if (data.fieldName && !data.isEmpty) {
                    document.dispatchEvent(
                        new CustomEvent('mp_field_interaction', {
                            detail: {
                                fieldName: data.fieldName,
                                paymentMethod: data.paymentMethod,
                                eventType: customEventType
                            }
                        })
                    );
                }
            },

            /**
             * Check if field is empty
             */
            isFieldEmpty: function (value) {
                return !value || value.trim() === '';
            },

            /**
             * Cleanup event listeners when component is destroyed
             */
            destroy: function () {
                $(document).off('.mp-tracking');
                this._super();
            }
        });
    };
});