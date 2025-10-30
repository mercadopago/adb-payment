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
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Api\QuoteMpPaymentRepositoryInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Request\CaptureAmountRequest;
use MercadoPago\AdbPayment\Gateway\Request\CcPaymentDataRequest;
use MercadoPago\AdbPayment\Gateway\Request\MpDeviceSessionId;
use MercadoPago\AdbPayment\Model\QuoteMpPaymentFactory;
use MercadoPago\AdbPayment\Model\QuoteMpPaymentRepository;
use MercadoPago\PP\Sdk\Common\Constants;
use MercadoPago\AdbPayment\Model\MPApi\PaymentGet;
use Magento\Quote\Api\CartRepositoryInterface;

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
     * External Status Detail - Block name.
     */
    public const STATUS_DETAIL = 'status_detail';

    /**
     * External Status Rejected - Block name.
     */
    public const STATUS_REJECTED = 'rejected';

    /**
     * External Status Pending - Block name.
     */
    public const STATUS_PENDING = 'pending';

    /**
     * External Status Pending Challenge - Block name.
     */
    public const STATUS_PENDING_CHALLENGE = 'pending_challenge';

    /**
     * External Payment Id - Block name.
     */
    public const PAYMENT_ID = 'id';

    /**
     * External Three DS Info - Block name.
     */
    public const THREE_DS_INFO = 'three_ds_info';

    /**
     * External Resource Url - Block name.
     */
    public const EXTERNAL_RESOURCE_URL = 'external_resource_url';

    /**
     * External Creq - Block name.
     */
    public const CREQ = 'creq';

    /**
     * Payment Method Id - Block name.
     */
    public const PAYMENT_METHOD_ID = 'payment_method_id';

    /**
     * PP Multiple Payments - Block name.
     */
    public const PP_MULTIPLE_PAYMENTS = 'pp_multiple_payments';

    /**
     * Custom Header name to paymant with vault  - Payer Id.
     */
    public const HEADER_CUSTOMER_ID = 'x-customer-id: ';

    public const X_MELI_SESSION_ID = 'X-meli-session-id: ';

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
     * @var QuoteMpPaymentRepositoryInterface
     */
    protected $quoteMpPaymentRepository;

    /**
     * @var QuoteMpPaymentFactory
     */
    protected $quoteMpPaymentFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var PaymentGet
     */
    protected $paymentGet;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @param Logger            $logger
     * @param Config            $config
     * @param Json              $json
     * @param QuoteMpPaymentRepository $quoteMpPaymentRepository
     * @param QuoteMpPaymentFactory    $quoteMpPaymentFactory
     * @param Session           $checkoutSession
     * @param PaymentGet        $paymentGet
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Logger $logger,
        Config $config,
        Json $json,
        QuoteMpPaymentRepository $quoteMpPaymentRepository,
        QuoteMpPaymentFactory $quoteMpPaymentFactory,
        Session $checkoutSession,
        PaymentGet $paymentGet,
        CartRepositoryInterface $cartRepository
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->json = $json;
        $this->quoteMpPaymentRepository = $quoteMpPaymentRepository;
        $this->quoteMpPaymentFactory = $quoteMpPaymentFactory;
        $this->checkoutSession = $checkoutSession;
        $this->paymentGet = $paymentGet;
        $this->cartRepository = $cartRepository;
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

        $data = [];
        $mpPaymentIdQuote = null;

        $mpPaymentQuote = $this->quoteMpPaymentRepository->getByQuoteId($this->checkoutSession->getQuoteId());

        if ($mpPaymentQuote !== null) {
            $mpPaymentIdQuote = $mpPaymentQuote->getPaymentId();

            if ($mpPaymentIdQuote !== null) {
                $data = $this->paymentGet->get($mpPaymentIdQuote, $storeId);

                $this->logger->debug(
                    [
                        'action'    => '3DS',
                        'response' => $this->json->serialize($data),
                    ]
                );
            }
        } else {

            $uri = null;
            $baseUrl = null;
            $serializeRequest = null;

            try {
                $sdk = $this->config->getSdkInstance($storeId);

                if ($request[self::PAYMENT_METHOD_ID] == self::PP_MULTIPLE_PAYMENTS) {
                    $paymentInstance = $sdk->getMultipaymentV21Instance();
                } else {
                    $paymentInstance = $sdk->getPaymentV21Instance();
                }

                $customHeaders = [];

                if (isset($request[MpDeviceSessionId::MP_DEVICE_SESSION_ID])) {
                    $customHeaders[] = self::X_MELI_SESSION_ID . $request[MpDeviceSessionId::MP_DEVICE_SESSION_ID];
                    unset($request[MpDeviceSessionId::MP_DEVICE_SESSION_ID]);
                }

                unset($request[self::STORE_ID]);
                unset($request[CcPaymentDataRequest::MP_PAYMENT_ID]);
                unset($request[CaptureAmountRequest::AMOUNT_PAID]);
                unset($request[CaptureAmountRequest::AMOUNT_TO_CAPTURE]);

                $paymentInstance->setEntity($request);


                if(isset($paymentInstance->payer->id)) {
                    $customHeaders[] = self::HEADER_CUSTOMER_ID . $paymentInstance->payer->id;
                }

                $paymentInstance->setCustomHeaders($customHeaders);

                $clientHeaders = $paymentInstance->getLastHeaders();
                $serializeRequest = $this->json->serialize($paymentInstance);
                $uri = $paymentInstance->getUris()['post'];
                $baseUrl = Constants::BASEURL_MP;

                $data = $paymentInstance->save();

                if (
                    $data[self::STATUS] === self::STATUS_PENDING
                    && $data[self::STATUS_DETAIL] === self::STATUS_PENDING_CHALLENGE
                    && !empty($data[self::THREE_DS_INFO])
                ) {
                    $quoteMpPayment = $this->quoteMpPaymentFactory->create();
                    $quoteMpPayment->setQuoteId($this->checkoutSession->getQuoteId());
                    $quoteMpPayment->setPaymentId($data[self::PAYMENT_ID]);
                    $quoteMpPayment->setThreeDsExternalResourceUrl(
                        $data[self::THREE_DS_INFO][self::EXTERNAL_RESOURCE_URL]
                    );
                    $quoteMpPayment->setThreeDsCreq($data[self::THREE_DS_INFO][self::CREQ]);

                    $this->quoteMpPaymentRepository->save($quoteMpPayment);

                    if (isset($request['external_reference'])) {
                        $quote = $this->checkoutSession->getQuote();
                        $quote->setReservedOrderId($request['external_reference']);
                        $this->cartRepository->save($quote);
                    }

                    throw new LocalizedException(__('3DS'));
                }

                $this->logger->debug(
                    [
                        'url'      => $baseUrl . $uri,
                        'header'   => $this->json->serialize($clientHeaders),
                        'request'  => $serializeRequest,
                        'response' => $this->json->serialize($data),
                    ]
                );
            } catch (InvalidArgumentException $exc) {
                // phpcs:ignore Magento2.Exceptions.DirectThrow
                $this->logger->debug(
                    [
                        'url'      => $baseUrl . $uri,
                        'request'  => $serializeRequest,
                        'error'    => $exc->getMessage(),
                    ]
                );
                throw new Exception('Invalid JSON was returned by the gateway');
            } catch (\Throwable $e) {
                // phpcs:ignore Magento2.Exceptions.DirectThrow
                $this->logger->debug(
                    [
                        'url'      => $baseUrl . $uri,
                        'request'  => $serializeRequest,
                        'error'    => $e->getMessage(),
                    ]
                );
                throw new LocalizedException(__($e->getMessage()));
            }
        }

        if (
            ($data[self::STATUS] === self::STATUS_REJECTED) ||
            ($data[self::STATUS] === self::STATUS_PENDING && $data[self::STATUS_DETAIL] === self::STATUS_PENDING_CHALLENGE)
        ) {
            if ($mpPaymentQuote !== null) {
                $this->quoteMpPaymentRepository->deleteByQuoteId($this->checkoutSession->getQuoteId());
            }
            $data['id'] = null;
        }

        $response = array_merge(
            [
                self::RESULT_CODE  => isset($data['id']) ? 1 : 0,
                self::EXT_ORD_ID   => isset($data['id']) ? $data['id'] : null,
            ],
            $data
        );

        return $response;
    }
}
