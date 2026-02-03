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
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

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
        $requester = new CurlRequester();
        $baseUrl = $this->config->getApiUrl();
        $client  = new HttpClient($baseUrl, $requester);
        $request = $transferObject->getBody();
        $storeId = $request[self::STORE_ID];
        $clientHeaders = $this->config->getClientHeadersMpPluginsPhpSdk($storeId);

        $paymentId = $request['payment_id'];
        $order = $request['order'] ?? null;
        $idempotencyKey = $request[self::X_IDEMPOTENCY_KEY];
        unset($request['payment_id']);
        unset($request['order']);
        unset($request[self::STORE_ID]);
        unset($request[self::X_IDEMPOTENCY_KEY]);
        $metadata = ['origem' => self::NOTIFICATION_ORIGIN];
        $uri = '/v1/payments/'.$paymentId.'/refunds';

        // Check if order exists and has multiple payments
        $paymentIndexList = null;
        if ($order) {
            $paymentIndexList = $order['payment']['additional_information']['payment_index_list'] ?? null;
        }

        if ($order && isset($paymentIndexList) && sizeof($paymentIndexList) > 1) {
            return $this->placeMultipleRefunds($order, $client, $baseUrl, $request, $clientHeaders);
        }

        $clientHeaders = array_merge_recursive($clientHeaders, [
            self::X_IDEMPOTENCY_KEY . ': ' . $idempotencyKey,
        ]);

        try {
            $request = (object) array_merge( (array)$request, array( 'metadata' => $metadata ) );
            $result = $client->post($uri, $clientHeaders, $this->json->serialize($request));
            $data = $result->getData();

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
                    'url'      => $baseUrl . $uri,
                    'request'  => $this->json->serialize($request),
                    'response' => $this->json->serialize($response),
                ]
            );
        } catch (InvalidArgumentException $exc) {
            $this->logger->debug(
                [
                    'url'       => $baseUrl . $uri,
                    'request'   => $this->json->serialize($request),
                    'error'     => $exc->getMessage(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        } catch (\Throwable $e) {
            $this->logger->debug(
                [
                    'url'       => $baseUrl . $uri,
                    'request'   => $this->json->serialize($request),
                    'error'     => $e->getMessage(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($e->getMessage());
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
        HttpClient $client,
        string $baseUrl,
        array $request,
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

            $uri = '/v1/payments/'.$paymentId.'/refunds';

            if($request['amount'] > 0)  {
                try {
                    $newIdempotencyKey = $payment->getTransactionId() . '-' .  uniqid();
                    $clientHeadersWithNewIdempotencyKey = array_merge_recursive($clientHeaders, [
                        self::X_IDEMPOTENCY_KEY . ': ' . $newIdempotencyKey,
                    ]);

                    $requestData = (object) array_merge( (array)$request, array( 'metadata' => $metadata ) );
                    $result = $client->post($uri, $clientHeadersWithNewIdempotencyKey, $this->json->serialize($requestData));
                    $data = $result->getData();

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
                            'url'      => $baseUrl . $uri,
                            'request'  => $this->json->serialize($request),
                            'response' => $this->json->serialize($refundResponse),
                        ]
                    );

                    $response = array_merge($response, $refundResponse);
                } catch (InvalidArgumentException $exc) {
                    $this->logger->debug(
                        [
                            'url'       => $baseUrl . $uri,
                            'request'   => $this->json->serialize($request),
                            'error'     => $exc->getMessage(),
                        ]
                    );
                    // phpcs:ignore Magento2.Exceptions.DirectThrow
                    throw new Exception('Invalid JSON was returned by the gateway');
                } catch (\Throwable $e) {
                    $this->logger->debug(
                        [
                            'url'       => $baseUrl . $uri,
                            'request'   => $this->json->serialize($request),
                            'error'     => $e->getMessage(),
                        ]
                    );
                    // phpcs:ignore Magento2.Exceptions.DirectThrow
                    throw new Exception($e->getMessage());
                }
            }
        }
        $order->save();
        return $response;
    }
}
