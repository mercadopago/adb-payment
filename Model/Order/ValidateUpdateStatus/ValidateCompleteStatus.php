<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

use Magento\Sales\Model\Order;

class ValidateCompleteStatus extends ValidateOrderStatus {

    /**
    * MP Status list
    */
    private array $completeCanUpdateStatus = [self::MP_STATUS_REFUNDED];
   
    public function getMpListStatus(){
        return $this->completeCanUpdateStatus;
    }

    public function getAdobeOrderStatus(){
        return Order::STATE_COMPLETE;
    }

}
