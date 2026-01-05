<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Http\Client\Order;

use Exception;
use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use MercadoPago\AdbPayment\Helper\IdempotencyKeyGenerator;
use MercadoPago\AdbPayment\Helper\HttpErrorCodeExtractor;
use MercadoPago\AdbPayment\Helper\OrderApiResponseValidator;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

/**
 * HTTP client to create Order using Plugins & Platforms Order API.
 * Endpoint: POST /plugins-platforms/v1/orders
 */
class CreateOrderClient implements ClientInterface
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
     * External Status - Block name.
     */
    public const STATUS = 'status';
 
    /**
     * External Status Detail - Block name.
     */
    public const STATUS_DETAIL = 'status_detail';
 
    /**
     * External Status Failed - Block name.
     */
    public const STATUS_FAILED = 'failed';

    /**
     * Idempotency Key - block name.
     */
    public const X_IDEMPOTENCY_KEY = 'x-idempotency-key';
    /**
     * ML session id header name
     */
    public const X_MELI_SESSION_ID = 'X-meli-session-id';

    /**
     * Device session id - block name
     */
    public const DEVICE_SESSION_ID = 'mp_device_session_id';
    /**
     * Test token header name (optional).
     */
    public const X_TEST_TOKEN = 'x-test-token';

	/**
	 * Content-Type JSON header literal.
	 */
	public const CONTENT_TYPE_JSON = 'Content-Type: application/json';

	/**
	 * Orders endpoint path.
	 */
	public const ORDERS_URI = '/plugins-platforms/v1/orders';

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
     * @var MetricsClient
     */
    protected $metricsClient;

    /**
	 * @param Logger $logger
	 * @param Config $config
	 * @param Json   $json
	 * @param MetricsClient $metricsClient
     */
    public function __construct(
		Logger $logger,
		Config $config,
		Json $json,
		MetricsClient $metricsClient
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->json = $json;
        $this->metricsClient = $metricsClient;
    }

    /**
     * Places request to Order API to create PIX order.
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $requester = new CurlRequester();
        $baseUrl = $this->config->getApiUrl();
		$client  = new HttpClient($baseUrl, $requester);

        $request = $transferObject->getBody();
        $storeId = $request[self::STORE_ID] ?? null;

		$meliSessionId = $request[self::DEVICE_SESSION_ID] ?? null;

		$idempotencyKey = IdempotencyKeyGenerator::generate($request) ?? null;

		$clientHeaders = $this->buildClientHeaders($storeId, $idempotencyKey, $meliSessionId);

        $request = $this->sanitizeRequest($request);

        $uri = self::ORDERS_URI;

        try {
            $payload = '';
            $payload = $this->json->serialize($request);
            $result = $client->post($uri, $clientHeaders, $payload);
            $data = $result->getData();

            $this->logger->debug(
                [
                    'url'      => $baseUrl . $uri,
                    'request'  => $payload,
                    'response' => $this->json->serialize($data),
                ]
            );

			$response = $this->normalizeOrderApiResponse($data);
            $this->sendResponseMetric($response);

            return $response;
        } catch (InvalidArgumentException $exc) {
            $this->logger->debug(
                [
                    'url'     => $baseUrl . $uri,
                    'request' => $payload,
                    'error'   => $exc->getMessage(),
                ]
            );
            $this->sendOrderErrorMetric('400', $exc->getMessage());
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        } catch (\Throwable $e) {
            $this->logger->debug(
                [
                    'url'     => $baseUrl . $uri,
                    'request' => $payload,
                    'error'   => $e->getMessage(),
                ]
            );

            $errorCode = HttpErrorCodeExtractor::extract($e);
            $this->sendOrderErrorMetric($errorCode, $e->getMessage());
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($e->getMessage());
        }
    }

    /**
	 * Build client headers for the Order API request.
	 *
	 * @param mixed  $storeId
	 * @param string|null $idempotencyKey
	 * @param string|null $meliSessionId
	 * @return array
	 */
	public function buildClientHeaders($storeId, ?string $idempotencyKey, ?string $meliSessionId): array
	{
		$headers = $this->config->getClientHeadersMpPluginsPhpSdk($storeId);
		$headers[] = self::CONTENT_TYPE_JSON;

		if ($idempotencyKey) {
			$headers[] = self::X_IDEMPOTENCY_KEY . ': ' . $idempotencyKey;
		}

		if ($meliSessionId) {
			$headers[] = self::X_MELI_SESSION_ID . ': ' . $meliSessionId;
		}

		if ($this->config->isTestMode($storeId)) {
			$headers[] = self::X_TEST_TOKEN . ': true';
		}

		return $headers;
	}

	/**
	 * Remove internal keys from request body.
	 *
	 * @param array $request
	 * @return array
	 */
	public function sanitizeRequest(array $request): array
	{
		unset(
			$request[self::STORE_ID],
			$request[self::DEVICE_SESSION_ID]
		);

		return $request;
	}

	/**
	 * Normalize Order API response into module response format.
	 *
	 * @param mixed $data
	 * @return array
	 */
	public function normalizeOrderApiResponse($data): array
	{
		$dataArray = is_array($data) ? $data : [];
		$status = $dataArray[self::STATUS] ?? null;
		$id = $dataArray['id'] ?? null;
		if ($status === self::STATUS_FAILED) {
			$id = null;
		}
		return array_merge(
			[
				self::RESULT_CODE => ($id !== null ? 1 : 0),
				self::EXT_ORD_ID  => $id,
			],
			$dataArray
		);
	}

	/**
	 * Send appropriate metric based on API response.
	 *
	 * @param array $response
	 * @return void
	 */
	private function sendResponseMetric(array $response): void
	{
		if (!OrderApiResponseValidator::isError($response)) {
			$this->sendOrderCreatedMetric();
			return;
		}

		$this->sendOrderErrorMetric(
			OrderApiResponseValidator::getErrorCode($response),
			OrderApiResponseValidator::getErrorMessage($response)
		);
	}

	/**
	 * Send metric for successful order creation.
	 *
	 * @return void
	 */
	private function sendOrderCreatedMetric(): void
	{
		try {
			$this->metricsClient->sendEvent(
				'magento_order_api_create_success',
				'success',
				'origin_magento'
			);
		} catch (\Throwable $e) {
			$this->logger->debug(['metric_error' => $e->getMessage()]);
		}
	}

	/**
	 * Send metric for order creation error.
	 *
	 * @param string $errorCode HTTP error code or generic error code
	 * @param string $errorMessage Error message description
	 * @return void
	 */
	private function sendOrderErrorMetric(string $errorCode, string $errorMessage): void
	{
		try {
			$this->metricsClient->sendEvent(
				'magento_order_api_create_error',
				$errorCode,
				$errorMessage
			);
		} catch (\Throwable $e) {
			$this->logger->debug([
				'metric_error' => $e->getMessage(),
				'metric_error_class' => get_class($e),
				'metric_error_trace' => $e->getTraceAsString()
			]);
		}
	}
}


