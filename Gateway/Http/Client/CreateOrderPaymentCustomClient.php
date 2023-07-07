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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Request\CaptureAmountRequest;
use MercadoPago\AdbPayment\Gateway\Request\CcPaymentDataRequest;
use MercadoPago\PP\Sdk\Common\Constants;

/**
 * Communication with the Gateway to create a payment by custom (Card, Pix, Ticket, Pec).
 */
class CreateOrderPaymentCustomClient implements ClientInterface
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
     * External Order Id - Block name.
     */
    public const EXT_ORD_ID = 'EXT_ORD_ID';

    /**
     * External Status - Block name.
     */
    public const STATUS = 'status';

    /**
     * External Status Rejected - Block name.
     */
    public const STATUS_REJECTED = 'rejected';

    /**
     * Payment Method Id - Block name.
     */
    public const PAYMENT_METHOD_ID = 'payment_method_id';

    /**
     * PP Multiple Payments - Block name.
     */
    public const PP_MULTIPLE_PAYMENTS = 'pp_multiple_payments';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param Logger            $logger
     * @param Config            $config
     * @param Json              $json
     */
    public function __construct(
        Logger $logger,
        Config $config,
        Json $json
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->json = $json;
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

        try {
            $sdk = $this->config->getSdkInstance($storeId);

            if($request[self::PAYMENT_METHOD_ID] == self::PP_MULTIPLE_PAYMENTS) {
                $paymentInstance = $sdk->getMultipaymentV2Instance();
            } else {
                $paymentInstance = $sdk->getPaymentV2Instance();
            }

            unset($request[self::STORE_ID]);
            unset($request[CcPaymentDataRequest::MP_PAYMENT_ID]);
            unset($request[CaptureAmountRequest::AMOUNT_PAID]);
            unset($request[CaptureAmountRequest::AMOUNT_TO_CAPTURE]);

            $paymentInstance->setEntity($request);

            $data = $paymentInstance->save();

            $clientHeaders = $paymentInstance->getLastHeaders();
            $serializeRequest = $this->json->serialize($request);
            $uri = $paymentInstance->getUris()['post'];
            $baseUrl = Constants::BASEURL_MP;

            if ($data[self::STATUS] === self::STATUS_REJECTED) {
                $data['id'] = null;
            }

            $response = array_merge(
                [
                    self::RESULT_CODE  => isset($data['id']) ? 1 : 0,
                    self::EXT_ORD_ID   => isset($data['id']) ? $data['id'] : null,
                ],
                $data
            );

            $this->logger->debug(
                [
                    'url'      => $baseUrl.$uri,
                    'header'   => $this->json->serialize($clientHeaders),
                    'request'  => $serializeRequest,
                    'response' => $this->json->serialize($data),
                ]
            );
        } catch (InvalidArgumentException $exc) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        } catch (\Throwable $e) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException(__($e->getMessage()));
        }
        return $response;
    }
}
