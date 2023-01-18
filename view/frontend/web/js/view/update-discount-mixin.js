define([
    'MercadoPago_PaymentMagento/js/model/mp-card-data',
    'Magento_SalesRule/js/action/set-coupon-code',
    'Magento_SalesRule/js/action/cancel-coupon',
    'Magento_SalesRule/js/model/coupon'
  ], function (mpCardData, setCouponCodeAction, cancelCouponAction, coupon) 
  {
    'use strict';

    var couponCode = coupon.getCouponCode(),
        isApplied = coupon.getIsApplied(),
        mixin = {
            apply: function () {
                if (this.validate()) {
                    setCouponCodeAction(couponCode(), isApplied);
                    mpCardData.mpCardInstallment = null;
                    mpCardData.mpCardListInstallments = null;
                }
            },
            cancel: function () {
                if (this.validate()) {
                    couponCode('');
                    cancelCouponAction(isApplied);
                    mpCardData.mpCardInstallment = null;
                    mpCardData.mpCardListInstallments = null;
                }
            }
        };

    return function (origin) {
        return origin.extend(mixin);
    };
});
