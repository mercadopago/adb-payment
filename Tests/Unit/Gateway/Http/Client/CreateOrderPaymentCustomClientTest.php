<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Gateway\Http\Client\CreateOrderPaymentCustomClient;
use MercadoPago\AdbPayment\Helper\ApiErrorCategoryMapper;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use Magento\Framework\Serialize\Serializer\Json;
use MercadoPago\AdbPayment\Model\QuoteMpPaymentRepository;
use Magento\Checkout\Model\Session;
use MercadoPago\AdbPayment\Model\MPApi\PaymentGet;
use MercadoPago\AdbPayment\Model\QuoteMpPaymentFactory;
use Magento\Payment\Gateway\Http\TransferInterface;
use MercadoPago\PP\Sdk\Sdk;
use MercadoPago\PP\Sdk\Entity\Payment\PaymentV21;
use MercadoPago\AdbPayment\Gateway\Request\MpDeviceSessionId;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use MercadoPago\AdbPayment\Model\QuoteMpPayment;
use InvalidArgumentException;

class CreateOrderPaymentCustomClientTest extends TestCase
{
    private function getTestClass(Sdk $sdkMock, ?MetricsClient $metricsClient = null): CreateOrderPaymentCustomClient
    {
        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $quoteMpPaymentRepository = $this->createMock(QuoteMpPaymentRepository::class);
        $quoteMpPaymentFactory = $this->createMock(QuoteMpPaymentFactory::class);
        $session = $this->createMock(Session::class);
        $paymentGet = $this->createMock(PaymentGet::class);
        $cartRepository = $this->createMock(CartRepositoryInterface::class);

        $config->expects($this->once())
            ->method('getSdkInstance')
            ->willReturn($sdkMock);

        return new CreateOrderPaymentCustomClient(
            $logger,
            $config,
            $json,
            $quoteMpPaymentRepository,
            $quoteMpPaymentFactory,
            $session,
            $paymentGet,
            $cartRepository,
            $metricsClient ?? $this->createMock(MetricsClient::class)
        );
    }

    /**
     * Creates a real ApiException when the class is available, otherwise skips the test.
     * ApiException was introduced in a newer SDK version; older CI environments may not have it.
     */
    private function makeApiException(string $message, ?string $errorCode, int $apiStatus, string $originalMessage): \Throwable
    {
        $class = 'MercadoPago\\PP\\Sdk\\Exceptions\\ApiException';
        if (!class_exists($class)) {
            $this->markTestSkipped("$class not available in this SDK version");
        }
        return new $class($message, $errorCode, $apiStatus, $originalMessage);
    }

    private function mockSdk(?PaymentV21 $payment = null): Sdk
    {
        $paymentInstance = $payment ?? $this->createMock(PaymentV21::class);

        $sdk = $this->createMock(Sdk::class);
        $sdk->expects($this->once())
            ->method('getPaymentV21Instance')
            ->willReturn($paymentInstance);

        return $sdk;
    }

    // ──────────────────────────────────────────────────────────
    // Existing happy-path tests (fixed: MetricsClient injected)
    // ──────────────────────────────────────────────────────────

    public function testPlaceRequestPendingCreditCardWithoutMpDeviceId()
    {
        $paymentInstance = $this->createMock(PaymentV21::class);
        $paymentInstance->expects($this->once())
            ->method('setCustomHeaders')
            ->with([]);
        $paymentInstance->expects($this->once())
            ->method('save')
            ->willReturn([
                CreateOrderPaymentCustomClient::STATUS => CreateOrderPaymentCustomClient::STATUS_PENDING,
                CreateOrderPaymentCustomClient::STATUS_DETAIL => CreateOrderPaymentCustomClient::STATUS_PENDING,
                'id' => '1234567890'
            ]);
        $paymentInstance->expects($this->once())
            ->method('getUris')
            ->willReturn(['post' => 'https://api.mercadopago.com']);

        $testClass = $this->getTestClass($this->mockSdk($paymentInstance));

        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn([
                CreateOrderPaymentCustomClient::STORE_ID => 1,
                CreateOrderPaymentCustomClient::PAYMENT_METHOD_ID => 'credit_card',
            ]);

        $result = $testClass->placeRequest($transferMock);

        $this->assertEquals([
            CreateOrderPaymentCustomClient::RESULT_CODE => 1,
            CreateOrderPaymentCustomClient::EXT_ORD_ID => '1234567890',
            CreateOrderPaymentCustomClient::STATUS => CreateOrderPaymentCustomClient::STATUS_PENDING,
            CreateOrderPaymentCustomClient::STATUS_DETAIL => CreateOrderPaymentCustomClient::STATUS_PENDING,
            'id' => '1234567890'
        ], $result);
    }

    public function testPlaceRequestPendingCreditCardWithMpDeviceId()
    {
        $paymentInstance = $this->createMock(PaymentV21::class);
        $paymentInstance->expects($this->once())
            ->method('setCustomHeaders')
            ->with([CreateOrderPaymentCustomClient::X_MELI_SESSION_ID . 'armor:1234']);
        $paymentInstance->expects($this->once())
            ->method('save')
            ->willReturn([
                CreateOrderPaymentCustomClient::STATUS => CreateOrderPaymentCustomClient::STATUS_PENDING,
                CreateOrderPaymentCustomClient::STATUS_DETAIL => CreateOrderPaymentCustomClient::STATUS_PENDING,
                'id' => '1234567890'
            ]);
        $paymentInstance->expects($this->once())
            ->method('getUris')
            ->willReturn(['post' => 'https://api.mercadopago.com']);

        $testClass = $this->getTestClass($this->mockSdk($paymentInstance));

        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn([
                CreateOrderPaymentCustomClient::STORE_ID => 1,
                CreateOrderPaymentCustomClient::PAYMENT_METHOD_ID => 'credit_card',
                MpDeviceSessionId::MP_DEVICE_SESSION_ID => 'armor:1234'
            ]);

        $result = $testClass->placeRequest($transferMock);

        $this->assertEquals([
            CreateOrderPaymentCustomClient::RESULT_CODE => 1,
            CreateOrderPaymentCustomClient::EXT_ORD_ID => '1234567890',
            CreateOrderPaymentCustomClient::STATUS => CreateOrderPaymentCustomClient::STATUS_PENDING,
            CreateOrderPaymentCustomClient::STATUS_DETAIL => CreateOrderPaymentCustomClient::STATUS_PENDING,
            'id' => '1234567890'
        ], $result);
    }

    public function testPlaceRequestCreditCardWith3DSChallenge()
    {
        $paymentInstance = $this->createMock(PaymentV21::class);
        $paymentInstance->expects($this->once())
            ->method('setCustomHeaders')
            ->with([CreateOrderPaymentCustomClient::X_MELI_SESSION_ID . 'armor:5678']);
        $paymentInstance->expects($this->once())
            ->method('save')
            ->willReturn([
                CreateOrderPaymentCustomClient::STATUS => CreateOrderPaymentCustomClient::STATUS_PENDING,
                CreateOrderPaymentCustomClient::STATUS_DETAIL => CreateOrderPaymentCustomClient::STATUS_PENDING_CHALLENGE,
                CreateOrderPaymentCustomClient::PAYMENT_ID => '9876543210',
                CreateOrderPaymentCustomClient::THREE_DS_INFO => [
                    CreateOrderPaymentCustomClient::EXTERNAL_RESOURCE_URL => 'https://acs.mercadopago.com/challenge',
                    CreateOrderPaymentCustomClient::CREQ => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9'
                ]
            ]);
        $paymentInstance->expects($this->once())
            ->method('getUris')
            ->willReturn(['post' => 'https://api.mercadopago.com/v1/payments']);

        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $quoteMpPaymentRepository = $this->createMock(QuoteMpPaymentRepository::class);
        $quoteMpPaymentFactory = $this->createMock(QuoteMpPaymentFactory::class);
        $session = $this->createMock(Session::class);
        $paymentGet = $this->createMock(PaymentGet::class);
        $cartRepository = $this->createMock(CartRepositoryInterface::class);
        $metricsClient = $this->createMock(MetricsClient::class);
        $quoteMpPaymentMock = $this->createMock(QuoteMpPayment::class);
        $mockSdk = $this->createMock(Sdk::class);
        $mockSdk->expects($this->once())->method('getPaymentV21Instance')->willReturn($paymentInstance);

        $config->expects($this->once())->method('getSdkInstance')->willReturn($mockSdk);
        $session->expects($this->atLeastOnce())->method('getQuoteId')->willReturn(123);
        $quoteMpPaymentRepository->expects($this->once())->method('getByQuoteId')->with(123)->willReturn(null);
        $quoteMpPaymentFactory->expects($this->once())->method('create')->willReturn($quoteMpPaymentMock);
        $quoteMpPaymentMock->expects($this->once())->method('setQuoteId')->with(123)->willReturnSelf();
        $quoteMpPaymentMock->expects($this->once())->method('setPaymentId')->with('9876543210')->willReturnSelf();
        $quoteMpPaymentMock->expects($this->once())->method('setThreeDsExternalResourceUrl')->with('https://acs.mercadopago.com/challenge')->willReturnSelf();
        $quoteMpPaymentMock->expects($this->once())->method('setThreeDsCreq')->with('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9')->willReturnSelf();
        $quoteMpPaymentRepository->expects($this->once())->method('save')->with($quoteMpPaymentMock);

        // 3DS must NOT emit any metric
        $metricsClient->expects($this->never())->method('sendEvent');

        $testClass = new CreateOrderPaymentCustomClient(
            $logger, $config, $json, $quoteMpPaymentRepository, $quoteMpPaymentFactory,
            $session, $paymentGet, $cartRepository, $metricsClient
        );

        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn([
                CreateOrderPaymentCustomClient::STORE_ID => 1,
                CreateOrderPaymentCustomClient::PAYMENT_METHOD_ID => 'credit_card',
                MpDeviceSessionId::MP_DEVICE_SESSION_ID => 'armor:5678'
            ]);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(CreateOrderPaymentCustomClient::THREE_DS_REDIRECT_MESSAGE);

        $testClass->placeRequest($transferMock);
    }

    // ──────────────────────────────────────────────────────────
    // PSW-3970: new metric emission tests
    // ──────────────────────────────────────────────────────────

    public function testRejectedPaymentEmitsMetricWithCategoryFromStatusDetail()
    {
        $paymentInstance = $this->createMock(PaymentV21::class);
        $paymentInstance->method('setCustomHeaders');
        $paymentInstance->expects($this->once())
            ->method('save')
            ->willReturn([
                CreateOrderPaymentCustomClient::STATUS => CreateOrderPaymentCustomClient::STATUS_REJECTED,
                CreateOrderPaymentCustomClient::STATUS_DETAIL => 'cc_rejected_bad_filled_security_code',
                'id' => null,
            ]);
        $paymentInstance->method('getUris')->willReturn(['post' => 'https://api.mercadopago.com']);

        $metricsClient = $this->createMock(MetricsClient::class);
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with('mp_api_error_' . ApiErrorCategoryMapper::CATEGORY_SECURITY_CODE, 'rejected', 'credit_card');

        $testClass = $this->getTestClass($this->mockSdk($paymentInstance), $metricsClient);

        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->method('getBody')->willReturn([
            CreateOrderPaymentCustomClient::STORE_ID => 1,
            CreateOrderPaymentCustomClient::PAYMENT_METHOD_ID => 'credit_card',
        ]);

        $result = $testClass->placeRequest($transferMock);

        $this->assertEquals(0, $result[CreateOrderPaymentCustomClient::RESULT_CODE]);
    }

    public function testRejectedPaymentWithInternalMappedStatusDetailEmitsInternalMetric()
    {
        // cc_rejected_other_reason IS in STATUS_DETAIL_MAP — it explicitly maps to CATEGORY_INTERNAL.
        $paymentInstance = $this->createMock(PaymentV21::class);
        $paymentInstance->method('setCustomHeaders');
        $paymentInstance->expects($this->once())
            ->method('save')
            ->willReturn([
                CreateOrderPaymentCustomClient::STATUS => CreateOrderPaymentCustomClient::STATUS_REJECTED,
                CreateOrderPaymentCustomClient::STATUS_DETAIL => 'cc_rejected_other_reason',
                'id' => null,
            ]);
        $paymentInstance->method('getUris')->willReturn(['post' => 'https://api.mercadopago.com']);

        $metricsClient = $this->createMock(MetricsClient::class);
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with('mp_api_error_' . ApiErrorCategoryMapper::CATEGORY_INTERNAL, 'rejected', 'credit_card');

        $testClass = $this->getTestClass($this->mockSdk($paymentInstance), $metricsClient);

        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->method('getBody')->willReturn([
            CreateOrderPaymentCustomClient::STORE_ID => 1,
            CreateOrderPaymentCustomClient::PAYMENT_METHOD_ID => 'credit_card',
        ]);

        $testClass->placeRequest($transferMock);
    }

    public function testRejectedPaymentWithTrulyUnknownStatusDetailEmitsInternalMetric()
    {
        // A status_detail not present in STATUS_DETAIL_MAP at all → falls through to CATEGORY_INTERNAL.
        $paymentInstance = $this->createMock(PaymentV21::class);
        $paymentInstance->method('setCustomHeaders');
        $paymentInstance->expects($this->once())
            ->method('save')
            ->willReturn([
                CreateOrderPaymentCustomClient::STATUS => CreateOrderPaymentCustomClient::STATUS_REJECTED,
                CreateOrderPaymentCustomClient::STATUS_DETAIL => 'cc_completely_unknown_status_for_testing',
                'id' => null,
            ]);
        $paymentInstance->method('getUris')->willReturn(['post' => 'https://api.mercadopago.com']);

        $metricsClient = $this->createMock(MetricsClient::class);
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with('mp_api_error_' . ApiErrorCategoryMapper::CATEGORY_INTERNAL, 'rejected', 'credit_card');

        $testClass = $this->getTestClass($this->mockSdk($paymentInstance), $metricsClient);

        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->method('getBody')->willReturn([
            CreateOrderPaymentCustomClient::STORE_ID => 1,
            CreateOrderPaymentCustomClient::PAYMENT_METHOD_ID => 'credit_card',
        ]);

        $testClass->placeRequest($transferMock);
    }

    /**
     * Flow B (PSW-3989): ApiException with original_message matching
     * INVALID_IDENTIFICATION_SDK_MESSAGE must throw the oriented LocalizedException
     * and still emit the identification metric. The expectExceptionMessage assertion
     * also guards against silent regression if the SDK string changes.
     */
    public function testApiExceptionWithInvalidUserIdentificationNumberThrowsOrientedMessage(): void
    {
        $paymentInstance = $this->createMock(PaymentV21::class);
        $paymentInstance->method('setCustomHeaders');
        $paymentInstance->expects($this->once())
            ->method('save')
            ->willThrowException($this->makeApiException(
                'Bad Request',
                'bad_request',
                400,
                '400 BAD_REQUEST "' . CreateOrderPaymentCustomClient::INVALID_IDENTIFICATION_SDK_MESSAGE . '"'
            ));
        $paymentInstance->method('getUris')->willReturn(['post' => 'https://api.mercadopago.com']);

        $metricsClient = $this->createMock(MetricsClient::class);
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with('mp_api_error_' . ApiErrorCategoryMapper::CATEGORY_IDENTIFICATION, '400', 'credit_card');

        $testClass = $this->getTestClass($this->mockSdk($paymentInstance), $metricsClient);

        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->method('getBody')->willReturn([
            CreateOrderPaymentCustomClient::STORE_ID          => 1,
            CreateOrderPaymentCustomClient::PAYMENT_METHOD_ID => 'credit_card',
        ]);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The identification number entered is invalid. Please check it and try again.');

        $testClass->placeRequest($transferMock);
    }

    public function testApiExceptionEmitsMetricWithCategoryFromOriginalMessage()
    {
        $paymentInstance = $this->createMock(PaymentV21::class);
        $paymentInstance->method('setCustomHeaders');
        $paymentInstance->expects($this->once())
            ->method('save')
            ->willThrowException($this->makeApiException(
                'Bad Request',
                'bad_request',
                400,
                '400 BAD_REQUEST "cc_rejected_bad_filled_security_code"'
            ));
        $paymentInstance->method('getUris')->willReturn(['post' => 'https://api.mercadopago.com']);

        $metricsClient = $this->createMock(MetricsClient::class);
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with('mp_api_error_' . ApiErrorCategoryMapper::CATEGORY_SECURITY_CODE, '400', 'credit_card');

        $testClass = $this->getTestClass($this->mockSdk($paymentInstance), $metricsClient);

        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->method('getBody')->willReturn([
            CreateOrderPaymentCustomClient::STORE_ID => 1,
            CreateOrderPaymentCustomClient::PAYMENT_METHOD_ID => 'credit_card',
        ]);

        $this->expectException(LocalizedException::class);
        $testClass->placeRequest($transferMock);
    }

    public function testApiExceptionWithEmptyOriginalMessageEmitsInternalMetric()
    {
        $paymentInstance = $this->createMock(PaymentV21::class);
        $paymentInstance->method('setCustomHeaders');
        $paymentInstance->expects($this->once())
            ->method('save')
            ->willThrowException($this->makeApiException('Bad Request', 'bad_request', 400, ''));
        $paymentInstance->method('getUris')->willReturn(['post' => 'https://api.mercadopago.com']);

        $metricsClient = $this->createMock(MetricsClient::class);
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with('mp_api_error_' . ApiErrorCategoryMapper::CATEGORY_INTERNAL, '400', 'credit_card');

        $testClass = $this->getTestClass($this->mockSdk($paymentInstance), $metricsClient);

        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->method('getBody')->willReturn([
            CreateOrderPaymentCustomClient::STORE_ID => 1,
            CreateOrderPaymentCustomClient::PAYMENT_METHOD_ID => 'credit_card',
        ]);

        $this->expectException(LocalizedException::class);
        $testClass->placeRequest($transferMock);
    }

    public function testInvalidJsonExceptionEmitsInternalMetricWith400()
    {
        $paymentInstance = $this->createMock(PaymentV21::class);
        $paymentInstance->method('setCustomHeaders');
        $paymentInstance->expects($this->once())
            ->method('save')
            ->willThrowException(new InvalidArgumentException('Invalid JSON'));
        $paymentInstance->method('getUris')->willReturn(['post' => 'https://api.mercadopago.com']);

        $metricsClient = $this->createMock(MetricsClient::class);
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with('mp_api_error_' . ApiErrorCategoryMapper::CATEGORY_INTERNAL, '400', 'credit_card');

        $testClass = $this->getTestClass($this->mockSdk($paymentInstance), $metricsClient);

        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->method('getBody')->willReturn([
            CreateOrderPaymentCustomClient::STORE_ID => 1,
            CreateOrderPaymentCustomClient::PAYMENT_METHOD_ID => 'credit_card',
        ]);

        $this->expectException(\Exception::class);
        $testClass->placeRequest($transferMock);
    }

    public function testSuccessfulPaymentDoesNotEmitErrorMetric()
    {
        $paymentInstance = $this->createMock(PaymentV21::class);
        $paymentInstance->method('setCustomHeaders');
        $paymentInstance->expects($this->once())
            ->method('save')
            ->willReturn([
                CreateOrderPaymentCustomClient::STATUS => 'approved',
                'id' => '111222333',
            ]);
        $paymentInstance->method('getUris')->willReturn(['post' => 'https://api.mercadopago.com']);

        $metricsClient = $this->createMock(MetricsClient::class);
        $metricsClient->expects($this->never())->method('sendEvent');

        $testClass = $this->getTestClass($this->mockSdk($paymentInstance), $metricsClient);

        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->method('getBody')->willReturn([
            CreateOrderPaymentCustomClient::STORE_ID => 1,
            CreateOrderPaymentCustomClient::PAYMENT_METHOD_ID => 'credit_card',
        ]);

        $result = $testClass->placeRequest($transferMock);

        $this->assertEquals(1, $result[CreateOrderPaymentCustomClient::RESULT_CODE]);
    }
}
