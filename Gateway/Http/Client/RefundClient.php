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
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;

/**
 * Communication with Gateway to refund payment.
 */
class RefundClient implements ClientInterface
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
     * Response Refund Id - Block name.
     */
    public const RESPONSE_REFUND_ID = 'id';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_DENIED = 'DENIED';

    /**
     * Idempotency Key block name.
     */
    public const X_IDEMPOTENCY_KEY = 'x-idempotency-key';

    /**
     * Notification Origin - Magento
     */
    public const NOTIFICATION_ORIGIN = 'magento';

    /**
     * Response Payment Details - Value.
     */
    public const PAYMENT_DETAILS = 'payments_details';

    /**
     * Response Total Amount - Value.
     */
    public const TOTAL_AMOUNT = 'total_amount';

    /**
     * Payment Id - Payment Addtional Information.
     */
    public const PAYMENT_ID = 'payment_%_id';

    /**
     * Card Total Amount - Payment Addtional Information.
     */
    public const PAYMENT_TOTAL_AMOUNT = 'payment_%_total_amount';

    /**
     * Card Total Amount - Payment Addtional Information.
     */
    public const PAYMENT_REFUNDED_AMOUNT = 'payment_%_refunded_amount';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

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
     * @param ZendClientFactory $httpClientFactory
     * @param Config            $config
     * @param Json              $json
     */
    public function __construct(
        Logger $logger,
        ZendClientFactory $httpClientFactory,
        Config $config,
        Json $json
    ) {
        $this->config = $config;
        $this->httpClientFactory = $httpClientFactory;
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
        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();
        $request = $transferObject->getBody();
        $storeId = $request[self::STORE_ID];
        $url = $this->config->getApiUrl();
        $clientConfigs = $this->config->getClientConfigs();
        $clientHeaders = $this->config->getClientHeaders($storeId);
        $clientConfigs = array_merge_recursive($clientConfigs, [
            self::X_IDEMPOTENCY_KEY => $request[self::X_IDEMPOTENCY_KEY],
        ]);

        $paymentId = $request['payment_id'];
        $order = $request['order'];
        unset($request['payment_id']);
        unset($request['order']);
        unset($request[self::STORE_ID]);
        unset($request[self::X_IDEMPOTENCY_KEY]);
        $metadata = ['origem' => self::NOTIFICATION_ORIGIN];

        $paymentIndexList = $order['payment']['additional_information']['payment_index_list'] ?? null;
        if (isset($paymentIndexList) && sizeof($paymentIndexList) > 1) {
            return $this->placeMultipleRefunds($order, $client, $url, $request, $clientConfigs, $clientHeaders);
        }

        try {
            $client->setUri($url.'/v1/payments/'.$paymentId.'/refunds');
            $client->setConfig($clientConfigs);
            $client->setHeaders($clientHeaders);

            $request = (object) array_merge( (array)$request, array( 'metadata' => $metadata ) );
            $client->setRawData($this->json->serialize($request), 'application/json');

            $client->setMethod(ZendClient::POST);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);

            $response = array_merge(
                [
                    self::RESULT_CODE  => 0,
                ],
                $data
            );

            if (isset($data[self::RESPONSE_REFUND_ID])) {
                $status = $data[self::RESPONSE_STATUS];
                $response = array_merge(
                    [
                        self::RESULT_CODE         => ($status !== self::RESPONSE_STATUS_DENIED) ? 1 : 0,
                        self::RESPONSE_REFUND_ID  => $data[self::RESPONSE_REFUND_ID],
                    ],
                    $data
                );
            }

            $this->logger->debug(
                [
                    'url'      => $url.'/v1/payments/'.$paymentId.'/refunds',
                    'request'  => $this->json->serialize($request),
                    'response' => $this->json->serialize($response),
                ]
            );
        } catch (InvalidArgumentException $exc) {
            $this->logger->debug(
                [
                    'url'       => $url.'/v1/payments/'.$paymentId.'/refunds',
                    'request'   => $this->json->serialize($request),
                    'error'     => $exc->getMessage(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }

    /**
     * Refund for multiple payments.
     *
     * @return array
     */
    public function placeMultipleRefunds (
        object $order,
        ZendClient $client,
        string $url,
        array $request,
        array $clientConfigs,
        array $clientHeaders
    ) {
        $paymentIndexList = $order['payment']['additional_information']['payment_index_list'];
        $payment = $order->getPayment();
        $remainingAmount = $payment->getAmountPaid() - $payment->getAmountRefunded();
        $amountToRefund = isset($request['amount']) ? $request['amount'] : $remainingAmount;
        $metadata = ['origem' => self::NOTIFICATION_ORIGIN];
        $response = [];

        foreach($paymentIndexList as $paymentIndex) {
            $cardRefundedAmount = str_replace('%', $paymentIndex, self::PAYMENT_REFUNDED_AMOUNT);
            $paymentAddInfo = $payment['additional_information'];
            $paymentId = $paymentAddInfo[str_replace('%', $paymentIndex, self::PAYMENT_ID)];

            $paymentAmount = $paymentAddInfo[str_replace('%', $paymentIndex, self::PAYMENT_TOTAL_AMOUNT)];
            $paymentRefundedAmount = $paymentAddInfo[$cardRefundedAmount];
            if ($amountToRefund > $paymentAmount - $paymentRefundedAmount) {
                $request['amount'] = $paymentAmount - $paymentRefundedAmount;
            } else {
                $request['amount'] = $amountToRefund;
            }
            $amountToRefund -= $request['amount'];

            if($request['amount'] > 0)  {
                try {
                    $client->setUri($url.'/v1/payments/'.$paymentId.'/refunds');
                    $client->setConfig($clientConfigs);
                    $client->setHeaders($clientHeaders);

                    $requestData = (object) array_merge( (array)$request, array( 'metadata' => $metadata ) );
                    $client->setRawData($this->json->serialize($requestData), 'application/json');

                    $client->setMethod(ZendClient::POST);

                    $responseBody = $client->request()->getBody();
                    $data = $this->json->unserialize($responseBody);

                    $refundResponse = array_merge(
                        [
                            self::RESULT_CODE  => 0,
                        ],
                        $data
                    );

                    if (isset($data[self::RESPONSE_REFUND_ID])) {
                        $status = $data[self::RESPONSE_STATUS];
                        $refundResponse = array_merge(
                            [
                                self::RESULT_CODE         => ($status !== self::RESPONSE_STATUS_DENIED) ? 1 : 0,
                                self::RESPONSE_REFUND_ID  => $data[self::RESPONSE_REFUND_ID],
                            ],
                            $data
                        );
                        $payment->setAdditionalInformation($cardRefundedAmount, $paymentRefundedAmount + $request['amount']);
                        $payment->save();
                    }

                    $this->logger->debug(
                        [
                            'url'      => $url.'/v1/payments/'.$paymentId.'/refunds',
                            'request'  => $this->json->serialize($request),
                            'response' => $this->json->serialize($refundResponse),
                        ]
                    );

                    $response = array_merge($response, $refundResponse);
                } catch (InvalidArgumentException $exc) {
                    $this->logger->debug(
                        [
                            'url'       => $url.'/v1/payments/'.$paymentId.'/refunds',
                            'request'   => $this->json->serialize($request),
                            'error'     => $exc->getMessage(),
                        ]
                    );
                    // phpcs:ignore Magento2.Exceptions.DirectThrow
                    throw new Exception('Invalid JSON was returned by the gateway');
                }
            }
        }
        $order->save();
        return $response;
    }
}
