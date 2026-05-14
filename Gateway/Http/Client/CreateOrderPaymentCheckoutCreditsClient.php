<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Http\Client;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use MercadoPago\AdbPayment\Gateway\Http\Client\CreateOrderPaymentCheckoutProClient;

/**
 * Communication with the Gateway to create a payment by Checkout Credits.
 */
class CreateOrderPaymentCheckoutCreditsClient extends CreateOrderPaymentCheckoutProClient implements ClientInterface
{
    /**
     * API error identifier for excluded default payment method.
     */
    private const EXCLUDED_DEFAULT_METHOD_ERROR = 'default_payment_method_id';

    /**
     * Keyword that qualifies the error as a minimum-amount exclusion, not other rejections.
     */
    private const EXCLUDED_KEYWORD = 'excluded';

    /**
     * MercadoPago site identifier for Chile, where the installments-without-card minimum applies.
     */
    private const SITE_ID_MLC = 'MLC';

    /**
     * Places request to gateway, translating minimum-amount API errors into a user-friendly message.
     *
     * The translation is only applied for the MLC (Chile) site, since the
     * "consumer_credits below minimum amount" rule that triggers this error is specific to it.
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $request = $transferObject->getBody();
        $storeId = isset($request[self::STORE_ID]) ? (int) $request[self::STORE_ID] : null;

        try {
            return parent::placeRequest($transferObject);
        } catch (Exception $e) {
            if ($this->isMlcMinimumAmountError($e->getMessage(), $storeId)) {
                throw new LocalizedException(
                    __('The order amount does not meet the minimum required to use installments without card. Please add more items to your cart or choose another payment method.')
                );
            }
            throw $e;
        }
    }

    /**
     * Whether the gateway error represents the MLC minimum-amount rule for installments without card.
     *
     * @param string   $errorMessage
     * @param int|null $storeId
     *
     * @return bool
     */
    private function isMlcMinimumAmountError(string $errorMessage, ?int $storeId): bool
    {
        if (strpos($errorMessage, self::EXCLUDED_DEFAULT_METHOD_ERROR) === false
            || strpos($errorMessage, self::EXCLUDED_KEYWORD) === false
        ) {
            return false;
        }

        return $this->config->getMpSiteId($storeId) === self::SITE_ID_MLC;
    }
}
