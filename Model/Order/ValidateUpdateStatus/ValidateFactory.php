<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

use Magento\Sales\Model\Order;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidatePendingStatus;

abstract class ValidateFactory {

    public static function createValidate($orderStatus) {
        
        switch ($orderStatus) {
            case ValidatePendingStatus::STATE_PENDING:
                return new ValidatePendingStatus();
                break;
            case Order::STATE_PROCESSING:
                return new ValidateProcessingStatus();
                break;
            case Order::STATE_COMPLETE:
                return new ValidateCompleteStatus();
                break;
            case Order::STATE_CANCELED:
                return new ValidateCancelledStatus();
                break;
            case Order::STATE_CLOSED:
                return new ValidateClosedStatus();
                break;
            case Order::STATE_PAYMENT_REVIEW:
                return new ValidatePaymentReviewStatus();
                break;
            case Order::STATE_PENDING_PAYMENT:
                return new ValidatePendingPaymentStatus();
                break;
            
            default:
                return new ValidateNotValidOrderStatus($orderStatus);
                break;
        }
    }
}