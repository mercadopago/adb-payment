<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

use Magento\Sales\Model\Order;

class ValidatePendingPaymentStatus extends ValidateOrderStatus {

    /**
    * MP Status list
    */
    private array $pendingCanUpdateStatus = [self::MP_STATUS_APPROVED, self::MP_STATUS_PENDING, self::MP_STATUS_REFUNDED, self::MP_STATUS_CANCELLED, self::MP_STATUS_REJECTED];
   
    public function getMpListStatus(){
        return $this->pendingCanUpdateStatus;
    }

    public function getAdobeOrderStatus(){
        return Order::STATE_PENDING_PAYMENT;
    }

}
