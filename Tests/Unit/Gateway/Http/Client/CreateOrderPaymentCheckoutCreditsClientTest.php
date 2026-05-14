<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Http\Client\CreateOrderPaymentCheckoutCreditsClient;
use MercadoPago\PP\Sdk\Entity\Preference\Preference;
use MercadoPago\PP\Sdk\Sdk;
use PHPUnit\Framework\TestCase;

class CreateOrderPaymentCheckoutCreditsClientTest extends TestCase
{
    /**
     * @var Config
     */
    private Config $configMock;

    /**
     * @var Logger
     */
    private Logger $loggerMock;

    /**
     * @var Json
     */
    private Json $jsonMock;

    /**
     * @var CreateOrderPaymentCheckoutCreditsClient
     */
    private CreateOrderPaymentCheckoutCreditsClient $client;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->jsonMock   = $this->createMock(Json::class);

        $this->jsonMock->method('serialize')->willReturn('{}');

        $this->client = new CreateOrderPaymentCheckoutCreditsClient(
            $this->loggerMock,
            $this->configMock,
            $this->jsonMock
        );
    }

    /**
     * Returns a TransferInterface mock whose body contains the minimum required fields.
     *
     * @param int $storeId
     *
     * @return TransferInterface
     */
    private function makeTransfer(int $storeId = 1): TransferInterface
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn([
            CreateOrderPaymentCheckoutCreditsClient::STORE_ID          => $storeId,
            'items'                                                     => [
                ['id' => 'item1', 'unit_price' => 500.0, 'quantity' => 1],
            ],
            'transaction_amount'                                        => 500.0,
        ]);
        return $transfer;
    }

    public function testPlaceRequestReturnsParentResponseUnmodifiedOnSuccess(): void
    {
        $preferenceData = ['id' => 'pref_test_123'];

        $preferenceMock = $this->createMock(Preference::class);
        $preferenceMock->method('save')->willReturn($preferenceData);
        $preferenceMock->method('getLastHeaders')->willReturn([]);
        $preferenceMock->method('getUris')->willReturn(['post' => '/v1/asgard/preferences']);

        $sdkMock = $this->createMock(Sdk::class);
        $sdkMock->method('getPreferenceInstance')->willReturn($preferenceMock);

        $this->configMock->method('getSdkInstance')->willReturn($sdkMock);

        $result = $this->client->placeRequest($this->makeTransfer());

        $this->assertSame(1, $result[CreateOrderPaymentCheckoutCreditsClient::RESULT_CODE]);
        $this->assertSame('pref_test_123', $result[CreateOrderPaymentCheckoutCreditsClient::EXT_ORD_ID]);
        $this->assertSame('pref_test_123', $result['id']);
    }

    public function testPlaceRequestThrowsLocalizedExceptionForMlcExcludedDefaultMethodError(): void
    {
        $apiError = 'invalid default_payment_method_id. The default payment method is excluded';
        $this->configMock->method('getSdkInstance')
            ->willThrowException(new Exception($apiError));
        $this->configMock->method('getMpSiteId')->willReturn('MLC');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'The order amount does not meet the minimum required to use installments without card.'
        );

        $this->client->placeRequest($this->makeTransfer());
    }

    public function testPlaceRequestRethrowsOriginalExceptionForNonMlcSite(): void
    {
        $originalMessage = 'invalid default_payment_method_id. The default payment method is excluded';

        $this->configMock->method('getSdkInstance')
            ->willThrowException(new Exception($originalMessage));
        $this->configMock->method('getMpSiteId')->willReturn('MLB');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($originalMessage);

        $this->client->placeRequest($this->makeTransfer());
    }

    public function testPlaceRequestRethrowsOriginalExceptionForUnrelatedErrorInMlc(): void
    {
        $originalMessage = 'Some unrelated API error from MercadoPago';

        $this->configMock->method('getSdkInstance')
            ->willThrowException(new Exception($originalMessage));
        $this->configMock->method('getMpSiteId')->willReturn('MLC');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($originalMessage);

        $this->client->placeRequest($this->makeTransfer());
    }
}
