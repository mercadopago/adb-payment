<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

use Magento\Sales\Model\Order;

class ValidatePaymentReviewStatus extends ValidateOrderStatus {

    /**
    * MP Status list
    */
    private array $paymentReviewCanUpdateStatus = [self::MP_STATUS_APPROVED, self::MP_STATUS_PENDING, self::MP_STATUS_REFUNDED, self::MP_STATUS_CANCELLED, self::MP_STATUS_REJECTED];

    public function getMpListStatus(){
        return $this->paymentReviewCanUpdateStatus;
    }

    public function getAdobeOrderStatus(){
        return Order::STATE_PAYMENT_REVIEW;
    }

}
