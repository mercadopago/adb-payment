<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use Magento\Sales\Model\Order\Payment;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Data\Order\AddressAdapter;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapter;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\Request\DocumentIdentificationDataRequest;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use PHPUnit\Framework\TestCase;

class DocumentIdentificationDataRequestTest extends TestCase
{
    /**
     * @var SubjectReader
     */
    protected $subjectReaderMock;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactoryMock;

    /**
     * @var Config
     */
    protected $configMock;

    /**
     * @var DocumentIdentificationDataRequest
     */
    protected $request;

    public function setUp(): void
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAdapterFactoryMock = $this->getMockBuilder(OrderAdapterFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new DocumentIdentificationDataRequest(
            $this->subjectReaderMock,
            $this->orderAdapterFactoryMock,
            $this->configMock
        );
    }

    /**
     * Build a payment mock that returns the given document number and type via additional information.
     *
     * @param string|null $documentNumber
     * @param string|null $documentType
     *
     * @return Payment
     */
    private function createPaymentMock($documentNumber, $documentType)
    {
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->willReturnMap([
                ['payer_document_identification', $documentNumber],
                ['payer_document_type', $documentType],
            ]);

        return $paymentMock;
    }

    /**
     * Build an order adapter mock for the fallback path (customer taxvat / billing vat id).
     *
     * @param string|null $taxvat
     * @param string|null $vatId
     *
     * @return OrderAdapter
     */
    private function createOrderAdapterMock($taxvat = null, $vatId = null)
    {
        $addressAdapterMock = $this->getMockBuilder(AddressAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $addressAdapterMock->expects($this->any())
            ->method('getVatId')
            ->willReturn($vatId);

        $orderAdapterMock = $this->getMockBuilder(OrderAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderAdapterMock->expects($this->any())
            ->method('getCustomerTaxvat')
            ->willReturn($taxvat);

        $orderAdapterMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($addressAdapterMock);

        return $orderAdapterMock;
    }

    public function testGetFiscalNumberReturnsUppercaseForLowercaseCnpjFromForm()
    {
        $payment = $this->createPaymentMock('12abc34501de35', 'CNPJ');
        $orderAdapter = $this->createOrderAdapterMock();

        $this->assertSame(
            '12ABC34501DE35',
            $this->request->getFiscalNumber($payment, $orderAdapter)
        );
    }

    public function testGetFiscalNumberKeepsUppercaseCnpjFromForm()
    {
        $payment = $this->createPaymentMock('12ABC34501DE35', 'CNPJ');
        $orderAdapter = $this->createOrderAdapterMock();

        $this->assertSame(
            '12ABC34501DE35',
            $this->request->getFiscalNumber($payment, $orderAdapter)
        );
    }

    public function testGetFiscalNumberKeepsCpfUnchanged()
    {
        $payment = $this->createPaymentMock('12345678909', 'CPF');
        $orderAdapter = $this->createOrderAdapterMock();

        $this->assertSame(
            '12345678909',
            $this->request->getFiscalNumber($payment, $orderAdapter)
        );
    }

    public function testGetFiscalNumberDoesNotUppercaseNonCnpjType()
    {
        $payment = $this->createPaymentMock('abc123', 'CURP');
        $orderAdapter = $this->createOrderAdapterMock();

        $this->assertSame(
            'abc123',
            $this->request->getFiscalNumber($payment, $orderAdapter)
        );
    }

    public function testGetFiscalNumberUppercasesCnpjFromCustomerTaxvatFallback()
    {
        $payment = $this->createPaymentMock(null, 'CNPJ');
        $orderAdapter = $this->createOrderAdapterMock('12abc34501de35');

        $this->configMock->expects($this->any())
            ->method('getAddtionalValue')
            ->with('get_document_identification_from')
            ->willReturn('customer');

        $this->assertSame(
            '12ABC34501DE35',
            $this->request->getFiscalNumber($payment, $orderAdapter)
        );
    }

    public function testGetFiscalNumberUppercasesCnpjFromBillingVatIdFallback()
    {
        $payment = $this->createPaymentMock(null, 'CNPJ');
        $orderAdapter = $this->createOrderAdapterMock(null, '12abc34501de35');

        $this->configMock->expects($this->any())
            ->method('getAddtionalValue')
            ->with('get_document_identification_from')
            ->willReturn('address');

        $this->assertSame(
            '12ABC34501DE35',
            $this->request->getFiscalNumber($payment, $orderAdapter)
        );
    }

    public function testGetFiscalNumberReturnsNullWhenNoDocument()
    {
        $payment = $this->createPaymentMock(null, 'CNPJ');
        $orderAdapter = $this->createOrderAdapterMock(null, null);

        $this->configMock->expects($this->any())
            ->method('getAddtionalValue')
            ->with('get_document_identification_from')
            ->willReturn('customer');

        $this->assertNull($this->request->getFiscalNumber($payment, $orderAdapter));
    }
}
