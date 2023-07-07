<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Http\Client;

use Exception;
use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Model\MPApi\Notification;

/**
 * Communication with the Gateway to seek Payment information.
 */
class FetchPaymentClient implements ClientInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Store Id - Block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * Store Id - Block name.
     */
    public const NOTIFICATION_ID = 'notificationId';

    /**
     * Mercado Pago Payment Id - Block Name.
     */
    public const MP_PAYMENT_ID = 'mp_payment_id';

    /**
     * Response Payment Id - Block name.
     */
    public const RESPONSE_PAYMENT_ID = 'id';

     /**
     * Response Payment Id - Block name.
     */
    public const RESPONSE_NOTIFICATION_ID = 'notification_id';

     /**
     * Response Payment Id - Block name.
     */
    public const RESPONSE_TRANSACTION_ID = 'transaction_id';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Json
     */
    protected $json;

    protected $mpApiNotification;

    /**
     * @param Logger            $logger
     * @param Json              $json
     */
    public function __construct(
        Logger $logger,
        Json $json,
        Notification $mpApiNotification
    ) {
        $this->logger = $logger;
        $this->json = $json;
        $this->mpApiNotification = $mpApiNotification;
    }

    /**
     * Places request to gateway.
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();
        $storeId = $request[self::STORE_ID];

        $paymentId = $request[self::MP_PAYMENT_ID];

        $notificationId = $request[self::NOTIFICATION_ID];

        try {
            $data = $this->mpApiNotification->get($notificationId, $storeId);

            $response = array_merge(
                [
                    self::RESULT_CODE  => 0,
                ],
                $data
            );
            if (isset($data[self::RESPONSE_TRANSACTION_ID])) {
                $response = array_merge(
                    [
                        self::RESULT_CODE          => 1,
                        self::RESPONSE_PAYMENT_ID  => $data[self::RESPONSE_TRANSACTION_ID],
                    ],
                    $data
                );
            }

        } catch (InvalidArgumentException $exc) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($exc->getMessage());
        }

        return $response;
    }
}
