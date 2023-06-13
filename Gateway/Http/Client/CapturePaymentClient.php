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
use MercadoPago\AdbPayment\Gateway\Request\CcPaymentDataRequest;
use MercadoPago\AdbPayment\Model\MPApi\PaymentGet;
use MercadoPago\AdbPayment\Gateway\Http\Client\AcceptPaymentClient;
use MercadoPago\AdbPayment\Gateway\Http\Client\CreateOrderPaymentCustomClient;
use MercadoPago\AdbPayment\Gateway\Request\CaptureAmountRequest;

/**
 * Communication with the Gateway to create a payment by custom (Card, Pix, Ticket, Pec).
 */
class CapturePaymentClient implements ClientInterface
{
    public const RESULT_CODE = 'RESULT_CODE';

    public const STORE_ID = 'store_id';

    public const EXT_ORD_ID = 'EXT_ORD_ID';

    public const STATUS = 'status';

    public const STATUS_REJECTED = 'rejected';

    public const IGNORE_TRANSACTION_CREATION = 'IGNORE_TRANSACTION_CREATION';

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
     * @var PaymentGet
     */
    protected $paymentGet;

    /**
     * @var AcceptPaymentClient
     */
    protected $acceptPaymentClient;

    /**
     * @var CreateOrderPaymentCustomClient
     */
    protected $createOrderPaymentCustomClient;

    /**
     * @param Logger            $logger
     * @param ZendClientFactory $httpClientFactory
     * @param Config            $config
     * @param Json              $json
     * @param PaymentGet        $paymentGet
     * @param AcceptPaymentClient    $acceptPaymentClient
     * @param CreateOrderPaymentCustomClient $createOrderPaymentCustomClient
     */
    public function __construct(
        Logger $logger,
        ZendClientFactory $httpClientFactory,
        Config $config,
        Json $json,
        PaymentGet $paymentGet,
        AcceptPaymentClient $acceptPaymentClient,
        CreateOrderPaymentCustomClient $createOrderPaymentCustomClient
    ) {
        $this->config = $config;
        $this->httpClientFactory = $httpClientFactory;
        $this->logger = $logger;
        $this->json = $json;
        $this->paymentGet = $paymentGet;
        $this->acceptPaymentClient = $acceptPaymentClient;
        $this->createOrderPaymentCustomClient = $createOrderPaymentCustomClient;
    }

    private function shouldCaptureOnline(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();

        $mpPaymentId = $request[CcPaymentDataRequest::MP_PAYMENT_ID];
        $storeId = $request['store_id'];

        if ($mpPaymentId === null || $mpPaymentId === '') {
            return false;
        }

        $mpPaymentData = $this->paymentGet->get($mpPaymentId, $storeId);

        return $mpPaymentData['status'] === 'authorized' && $mpPaymentData['captured'] === false && $mpPaymentData['status_detail'] === 'pending_capture';
    }

    private function capturePayment(TransferInterface $transferObject)
    {
        $result = $this->acceptPaymentClient->placeRequest($transferObject);
        $result[self::IGNORE_TRANSACTION_CREATION] = true;
        return $result;
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
        $data = $transferObject->getBody();

        if (!empty($data[CaptureAmountRequest::AMOUNT_PAID]) && $data[CaptureAmountRequest::AMOUNT_PAID] > 0) {
            return ['RESULT_CODE' => 0, 'failsDescription' => 'JHE'];
        }

        if ($this->shouldCaptureOnline($transferObject)) {
            return $this->capturePayment($transferObject);
        }

        return $this->createOrderPaymentCustomClient->placeRequest($transferObject);
    }
}
