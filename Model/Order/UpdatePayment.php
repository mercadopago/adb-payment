<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order;

use Magento\Sales\Model\Order;

class UpdatePayment
{
    /**
     * Update payment information from MercadoPago notification.
     * 
     * Supports both Payment API (payments_details) and Order API structures.
     *
     * @param Order $order
     * @param array $mpNotification
     * @return void
     */
    public function updateInformation(Order $order, array $mpNotification)
    {
        $payment = $order->getPayment();
        $additionalInfo = $payment->getAdditionalInformation();

        $paymentsData = $mpNotification['payments_details'] ?? [];

        $isOrderApi = ($mpNotification['transaction_type'] ?? '') === 'pp_order';

        if ($isOrderApi) {
            $additionalInfo['mp_status'] = $mpNotification['status'] ?? null;
            $additionalInfo['mp_status_detail'] = $mpNotification['status_detail'] ?? null;
            
            $payment->setAdditionalInformation($additionalInfo);
            $order->setPayment($payment);
            $order->save();
            return;
        }

        foreach ($paymentsData as $paymentNotification) {
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