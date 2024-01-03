<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order;

use Magento\Sales\Model\Order;

class UpdatePayment {

    public function updateInformation(Order $order, array $mpNotification) {
        $payment = $order->getPayment();

        $additionalInfo = $payment->getAdditionalInformation();
        foreach($mpNotification['payments_details'] as $paymentNotification) {
            $nid = $paymentNotification['id'];
            $payIndex = "";
            $payKey = array_search($nid, $additionalInfo);
            if(
                isset($mpNotification["multiple_payment_transaction_id"]) &&
                !empty($mpNotification["multiple_payment_transaction_id"]) ||
                $additionalInfo["method_title"] == "Checkout Pro"
            ) {
                $payIndex = filter_var($payKey, FILTER_SANITIZE_NUMBER_INT) . "_";
            } else if (isset($additionalInfo["mp_0_status"])) {
                $additionalInfo['mp_status'] = $paymentNotification['status'];
                $additionalInfo['mp_status_detail'] = $paymentNotification['status_detail'];
                $payIndex = "0_";
            }
            $additionalInfo['mp_' . $payIndex . 'status'] = $paymentNotification['status'];
            $additionalInfo['mp_' . $payIndex . 'status_detail'] = $paymentNotification['status_detail'];
        };
        $payment->setAdditionalInformation($additionalInfo);
        $order->setPayment($payment);
        $order->save();
    }
}
