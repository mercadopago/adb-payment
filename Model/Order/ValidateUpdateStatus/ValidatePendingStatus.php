<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

use Magento\Sales\Model\Order;

class ValidatePendingStatus extends ValidateOrderStatus {

    public const STATE_PENDING = "pending";

    /**
    * MP Status list
    */
    private array $pendingCanUpdateStatus = [self::MP_STATUS_APPROVED, self::MP_STATUS_PENDING, self::MP_STATUS_REFUNDED, self::MP_STATUS_CANCELLED, self::MP_STATUS_REJECTED];
   
    public function getMpListStatus(){
        return $this->pendingCanUpdateStatus;
    }

    public function getAdobeOrderStatus(){
        return self::STATE_PENDING;
    }

}
