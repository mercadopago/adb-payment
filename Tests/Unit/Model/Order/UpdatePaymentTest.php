<?php

namespace Tests\Unit\Model\Order;

use PHPUnit\Framework\TestCase;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MercadoPago\AdbPayment\Model\Order\UpdatePayment;
use MercadoPago\AdbPayment\Tests\Unit\Mocks\Model\Order\Payment\AdditionalInformation;
use MercadoPago\AdbPayment\Tests\Unit\Mocks\Notification\Refund\SinglePayment;
use MercadoPago\AdbPayment\Tests\Unit\Mocks\Notification\Refund\TwoPayments;

class UpdatePaymentTest extends TestCase {

    private function prepareTestData(): array
    {
        $order = $this->createMock(Order::class);

        $payment = $this->createMock(Payment::class);

        $order->expects($this->any())
            ->method('getPayment')
            ->willReturn($payment);

        $order->expects($this->any())
            ->method('save');

        $updatePayment = new UpdatePayment();

        return [
            'order' => $order,
            'payment' => $payment,
            'updatePayment' => $updatePayment
        ];

    }

    public function testUpdatePaymentCheckoutProAcountMoney(): void
    {
        $testData = $this->prepareTestData();

        $payment = $testData['payment'];
        $updatePayment = $testData['updatePayment'];
        $notification = SinglePayment::proAccountMoney();

        $payment->expects($this->once())
            ->method('getAdditionalInformation')
            ->willReturn(AdditionalInformation::ADDITIONAL_INFORMATION_DATA_PRO_ACCOUNT_MONEY);


        $payment->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(AdditionalInformation::refundedProAccountMoney());

        $updatePayment->updateInformation($testData['order'], $notification);
    }

    public function testUpdatePaymentCheckoutProOneCard(): void
    {
        $testData = $this->prepareTestData();

        $payment = $testData['payment'];
        $updatePayment = $testData['updatePayment'];
        $notification = SinglePayment::proCard();

        $payment->expects($this->once())
            ->method('getAdditionalInformation')
            ->willReturn(AdditionalInformation::ADDITIONAL_INFORMATION_DATA_PRO_ONE_CARD);

        $payment->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(AdditionalInformation::refundedProOneCard());

        $updatePayment->updateInformation($testData['order'], $notification);
    }

    public function testUpdatePaymentCheckoutProTwoCards(): void
    {
        $testData = $this->prepareTestData();

        $payment = $testData['payment'];
        $updatePayment = $testData['updatePayment'];
        $notification = TwoPayments::proTwoCardRefunded();

        $payment->expects($this->once())
            ->method('getAdditionalInformation')
            ->willReturn(AdditionalInformation::ADDITIONAL_INFORMATION_DATA_PRO_TWO_CARDS);

        $payment->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(AdditionalInformation::refundedProTwoCards());

        $updatePayment->updateInformation($testData['order'], $notification);
    }

    public function testUpdatePaymentCheckoutProTicket(): void
    {
        $testData = $this->prepareTestData();

        $payment = $testData['payment'];
        $updatePayment = $testData['updatePayment'];
        $notification = SinglePayment::proTicket();

        $payment->expects($this->once())
            ->method('getAdditionalInformation')
            ->willReturn(AdditionalInformation::ADDITIONAL_INFORMATION_DATA_PRO_TICKET);

        $payment->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(AdditionalInformation::refundedProTicket());

        $updatePayment->updateInformation($testData['order'], $notification);
    }

    public function testUpdatePaymentCheckoutCustomOneCard(): void
    {
        $testData = $this->prepareTestData();

        $payment = $testData['payment'];
        $updatePayment = $testData['updatePayment'];
        $notification = SinglePayment::SINGLE_PAYMENT_DATA;

        $payment->expects($this->once())
            ->method('getAdditionalInformation')
            ->willReturn(AdditionalInformation::ADDITIONAL_INFORMATION_DATA_CUSTOM_ONE_CARD);

        $payment->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(AdditionalInformation::refundedCustomOneCard());

        $updatePayment->updateInformation($testData['order'], $notification);
    }

    public function testUpdatePaymentCheckoutCustomTicket(): void
    {
        $testData = $this->prepareTestData();

        $order = $testData['order'];
        $payment = $testData['payment'];
        $updatePayment = $testData['updatePayment'];
        $notification = SinglePayment::customTicket();

        $payment->expects($this->once())
            ->method('getAdditionalInformation')
            ->willReturn(AdditionalInformation::ADDITIONAL_INFORMATION_DATA_CUSTOM_TICKET);

        $payment->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(AdditionalInformation::refundedCustomTicket());

        $updatePayment->updateInformation($testData['order'], $notification);
    }
}
