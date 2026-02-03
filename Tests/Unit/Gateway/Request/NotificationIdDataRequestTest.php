<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Model\InfoInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Request\NotificationIdDataRequest;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use PHPUnit\Framework\TestCase;

/**
 * Test for NotificationIdDataRequest
 */
class NotificationIdDataRequestTest extends TestCase
{
    /**
     * @var NotificationIdDataRequest
     */
    private $request;

    /**
     * @var SubjectReader|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subjectReaderMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    /**
     * Set up test dependencies
     */
    protected function setUp(): void
    {
        $this->subjectReaderMock = $this->createMock(SubjectReader::class);
        $this->configMock = $this->createMock(Config::class);

        $this->request = new NotificationIdDataRequest(
            $this->subjectReaderMock,
            $this->configMock
        );
    }

    /**
     * Create build subject with mocked payment containing additional_data
     *
     * @param mixed $additionalData
     * @return array
     */
    private function createBuildSubjectWithAdditionalData($additionalData): array
    {
        $paymentMock = $this->getMockBuilder(InfoInterface::class)
            ->addMethods(['getData'])
            ->getMockForAbstractClass();
        $paymentMock->expects($this->once())
            ->method('getData')
            ->with('additional_data')
            ->willReturn($additionalData);

        $paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDOMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        return ['payment' => $paymentDOMock];
    }

    /**
     * Assert result has correct structure and notificationId value
     *
     * @param array $result
     * @param mixed $expectedNotificationId
     * @return void
     */
    private function assertNotificationIdResult(array $result, $expectedNotificationId): void
    {
        $this->assertArrayHasKey('notificationId', $result);
        $this->assertEquals($expectedNotificationId, $result['notificationId']);
    }

    /**
     * Test that exception is thrown when payment data object is not provided
     */
    public function testBuildThrowsExceptionWhenPaymentDataObjectNotProvided()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $this->request->build([]);
    }

    /**
     * Test that exception is thrown when payment is not PaymentDataObjectInterface
     */
    public function testBuildThrowsExceptionWhenPaymentIsNotPaymentDataObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $this->request->build(['payment' => 'invalid']);
    }

    /**
     * Test build with null additional_data returns null notificationId
     */
    public function testBuildWithNullAdditionalDataReturnsNullNotificationId()
    {
        $buildSubject = $this->createBuildSubjectWithAdditionalData(null);
        $result = $this->request->build($buildSubject);
        $this->assertNotificationIdResult($result, null);
    }

    /**
     * Test build with empty additional_data returns null notificationId
     */
    public function testBuildWithEmptyAdditionalDataReturnsNullNotificationId()
    {
        $buildSubject = $this->createBuildSubjectWithAdditionalData('');
        $result = $this->request->build($buildSubject);
        $this->assertNotificationIdResult($result, null);
    }

    /**
     * Test build with valid JSON containing notificationId
     */
    public function testBuildWithValidJsonReturnsNotificationId()
    {
        $notificationId = '12345-abcde-67890';
        $additionalData = json_encode(['notificationId' => $notificationId, 'other' => 'data']);

        $buildSubject = $this->createBuildSubjectWithAdditionalData($additionalData);
        $result = $this->request->build($buildSubject);
        $this->assertNotificationIdResult($result, $notificationId);
    }

    /**
     * Test build with valid JSON but no notificationId property
     */
    public function testBuildWithJsonWithoutNotificationIdReturnsNull()
    {
        $additionalData = json_encode(['card_type' => 'visa', 'other' => 'data']);

        $buildSubject = $this->createBuildSubjectWithAdditionalData($additionalData);
        $result = $this->request->build($buildSubject);
        $this->assertNotificationIdResult($result, null);
    }

    /**
     * Test build with invalid JSON returns null notificationId
     */
    public function testBuildWithInvalidJsonReturnsNullNotificationId()
    {
        $additionalData = 'invalid-json-string-{[]}';

        $buildSubject = $this->createBuildSubjectWithAdditionalData($additionalData);
        $result = $this->request->build($buildSubject);
        $this->assertNotificationIdResult($result, null);
    }

    /**
     * Test build with notificationId as null in JSON
     */
    public function testBuildWithNullNotificationIdInJsonReturnsNull()
    {
        $additionalData = json_encode(['notificationId' => null, 'other' => 'data']);

        $buildSubject = $this->createBuildSubjectWithAdditionalData($additionalData);
        $result = $this->request->build($buildSubject);
        $this->assertNotificationIdResult($result, null);
    }

    /**
     * Test build returns correct array structure
     */
    public function testBuildReturnsCorrectArrayStructure()
    {
        $buildSubject = $this->createBuildSubjectWithAdditionalData(null);
        $result = $this->request->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertNotificationIdResult($result, null);
    }
}
