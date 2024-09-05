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
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\PP\Sdk\Common\Constants;

/**
 * Communication with the Gateway to create a payment by Checkout Pro.
 */
class CreateOrderPaymentCheckoutProClient implements ClientInterface
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
            $preferenceInstance = $sdk->getPreferenceInstance();
            unset($request[self::STORE_ID]);
            $preferenceInstance->setEntity($request);

            $data = $preferenceInstance->save();
            $response = array_merge(
                [
                    self::RESULT_CODE  => isset($data['id']) ? 1 : 0,
                    self::EXT_ORD_ID   => isset($data['id']) ? $data['id'] : null,
                ],
                $data
            );
        } catch (InvalidArgumentException $exc) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            $this->logger->debug(
                [
                    'request'   => $this->json->serialize($request),
                    'error'     => $exc->getMessage(),
                ]
            );
            throw new Exception('Invalid JSON was returned by the gateway');
        } catch (\Throwable $e) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            $this->logger->debug(
                [
                    'request'   => $this->json->serialize($request),
                    'error'     => $e->getMessage(),
                ]
            );
            throw new Exception($e->getMessage());
        }

        $clientHeaders = $preferenceInstance->getLastHeaders();
        $serializeRequest = $this->json->serialize($request);
        $uri = $preferenceInstance->getUris()['post'];
        $baseUrl = Constants::BASEURL_MP;
        $this->logger->debug(
            [
                'url'      => $baseUrl . $uri,
                'header'   => $this->json->serialize($clientHeaders),
                'request'  => $serializeRequest,
                'response' => $this->json->serialize($data),
            ]
        );

        return $response;
    }
}
