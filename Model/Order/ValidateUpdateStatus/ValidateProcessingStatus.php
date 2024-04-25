<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

use Magento\Sales\Model\Order;

class ValidateProcessingStatus extends ValidateOrderStatus {

    /**
    * MP Status list
    */
    private array $processingCanUpdateStatus = [self::MP_STATUS_REFUNDED];
   
    public function getMpListStatus(){
        return $this->processingCanUpdateStatus;
    }

    public function getAdobeOrderStatus(){
        return Order::STATE_PROCESSING;
    }

}
