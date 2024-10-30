<?php

namespace MercadoPago\Test\Unit\Gateway\Http\Client;


use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Gateway\Http\Client\CreateOrderPaymentCustomClient;

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

class CreateOrderPaymentCustomClientTest extends TestCase {
    private function getTestClass(Sdk $sdkMock): CreateOrderPaymentCustomClient
    {
        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $quoteMpPaymentRepository = $this->createMock(QuoteMpPaymentRepository::class);
        $quoteMpPaymentFactory = $this->createMock(QuoteMpPaymentFactory::class);
        $session = $this->createMock(Session::class);
        $paymentGet = $this->createMock(PaymentGet::class);

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
            $paymentGet
        );
    }

    private function mockSdk(PaymentV21 $payment = null): Sdk
    {
        $paymentInstance = $payment ?? $this->createMock(PaymentV21::class);

        $sdk = $this->createMock(Sdk::class);
        $sdk->expects($this->once())
            ->method('getPaymentV21Instance')
            ->willReturn($paymentInstance);

        return $sdk;
    }

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
            ->willReturn([
                'post' => 'https://api.mercadopago.com'
            ]);

        $mockSdk = $this->mockSdk($paymentInstance);
        $testClass = $this->getTestClass($mockSdk);

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
            ->with([
                CreateOrderPaymentCustomClient::X_MELI_SESSION_ID . 'armor:1234'
            ]);

        $paymentInstance->expects($this->once())
            ->method('save')
            ->willReturn([
                CreateOrderPaymentCustomClient::STATUS => CreateOrderPaymentCustomClient::STATUS_PENDING,
                CreateOrderPaymentCustomClient::STATUS_DETAIL => CreateOrderPaymentCustomClient::STATUS_PENDING,
                'id' => '1234567890'
            ]);

        $paymentInstance->expects($this->once())
            ->method('getUris')
            ->willReturn([
                'post' => 'https://api.mercadopago.com'
            ]);

        $mockSdk = $this->mockSdk($paymentInstance);
        $testClass = $this->getTestClass($mockSdk);

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
}
