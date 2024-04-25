<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

use Magento\Sales\Model\Order;

class ValidateCancelledStatus extends ValidateOrderStatus {
    
    /**
    * MP Status list
    */
    private array $cancelledCanUpdateStatus = [self::MP_STATUS_CANCELLED];
   
    public function getMpListStatus(){
        return $this->cancelledCanUpdateStatus;
    }

    public function getAdobeOrderStatus(){
        return Order::STATE_CANCELED;
    }

}
