<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Data\Order\AddressAdapter;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapter;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\Request\DocumentIdentificationDataRequest;
use MercadoPago\AdbPayment\Gateway\Request\PayerDataRequest;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
     * @var MetricsClient
     */
    protected $metricsClientMock;

    /**
     * @var LoggerInterface
     */
    protected $loggerMock;

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

        $this->metricsClientMock = $this->getMockBuilder(MetricsClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new DocumentIdentificationDataRequest(
            $this->subjectReaderMock,
            $this->orderAdapterFactoryMock,
            $this->configMock,
            $this->metricsClientMock,
            $this->loggerMock
        );
    }

    /**
     * Build a full build() subject: a payment carrying the given form document, and a
     * gateway order reporting the given store id (used to resolve the MP site id).
     *
     * @param string|null $documentType
     * @param string|null $documentNumber
     * @param int         $storeId
     * @param array       $extraInfo Extra additional-information entries ([key, value] pairs),
     *                               e.g. the per-card twocc documents payer_<i>_document_identification.
     * @param string      $methodCode Payment-method code, resolved to the observability flow suffix.
     *
     * @return PaymentDataObjectInterface
     */
    private function createPaymentDataObject(
        $documentType,
        $documentNumber,
        $storeId = 1,
        array $extraInfo = [],
        $methodCode = 'mercadopago_adbpayment_cc'
    ) {
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->willReturnMap(array_merge(
                [
                    ['payer_document_type', $documentType],
                    ['payer_document_identification', $documentNumber],
                ],
                $extraInfo
            ));

        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $methodInstanceMock->expects($this->any())
            ->method('getCode')
            ->willReturn($methodCode);

        $paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->willReturn($methodInstanceMock);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn(
                $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock()
            );

        $gatewayOrderMock = $this->createMock(OrderAdapterInterface::class);
        $gatewayOrderMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDOMock->expects($this->any())->method('getPayment')->willReturn($paymentMock);
        $paymentDOMock->expects($this->any())->method('getOrder')->willReturn($gatewayOrderMock);

        $this->subjectReaderMock->expects($this->any())
            ->method('readPayment')
            ->willReturn($paymentDOMock);

        $this->orderAdapterFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn(
                $this->getMockBuilder(OrderAdapter::class)->disableOriginalConstructor()->getMock()
            );

        return $paymentDOMock;
    }

    public function testBuildThrowsLocalizedExceptionForInvalidCpfOnMlbCcFlow()
    {
        $paymentDO = $this->createPaymentDataObject('CPF', '12345678900');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(DocumentIdentificationDataRequest::METRIC_DOCUMENT_VALIDATION_PREFIX . 'cc', 'CPF');

        $this->expectException(LocalizedException::class);

        $this->request->build(['payment' => $paymentDO]);
    }

    public function testBuildThrowsLocalizedExceptionForInvalidCnpjOnMlbCcFlow()
    {
        $paymentDO = $this->createPaymentDataObject('CNPJ', '11222333000180');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(DocumentIdentificationDataRequest::METRIC_DOCUMENT_VALIDATION_PREFIX . 'cc', 'CNPJ');

        $this->expectException(LocalizedException::class);

        $this->request->build(['payment' => $paymentDO]);
    }

    public function testBuildRejectsPunctuationOnlyDocumentAsUnknownKindCcFlow()
    {
        // A billing vatId of only punctuation (e.g. "---") is truthy but sanitizes to empty,
        // so it is neither CPF nor CNPJ: it must be rejected, and the metric kind is 'unknown'.
        $paymentDO = $this->createPaymentDataObject('CPF', '---');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(DocumentIdentificationDataRequest::METRIC_DOCUMENT_VALIDATION_PREFIX . 'cc', 'unknown');

        $this->expectException(LocalizedException::class);

        $this->request->build(['payment' => $paymentDO]);
    }

    public function testBuildDoesNotValidateBillingFallbackForMultiCardFlow()
    {
        // Multi-card (twocc) sets no non-indexed payer_document_type and carries each card's
        // document in its token. Without any per-card document present, the builder must emit no
        // identification node and must NOT validate the billing-address fallback — validating a
        // source that was never tokenized could reject an otherwise-valid payment. Per-card
        // documents, when present, ARE validated (see the twocc tests below).
        $paymentDO = $this->createPaymentDataObject(null, '12345678900', 1, [], 'mercadopago_adbpayment_twocc');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->never())->method('sendEvent');

        $result = $this->request->build(['payment' => $paymentDO]);

        $this->assertArrayNotHasKey(PayerDataRequest::PAYER, $result);
    }

    public function testBuildThrowsForInvalidPerCardDocumentOnMlbTwoCc()
    {
        // twocc hidden-field flow: no non-indexed document type, but each card carries its own
        // document (payer_<i>_document_identification) — the exact value tokenized. An invalid
        // per-card CPF must be rejected here, since the API accepts twocc without a validated
        // payer.identification and would otherwise take the bad document through the token.
        $paymentDO = $this->createPaymentDataObject(null, null, 1, [
            ['payer_0_document_identification', '12345678900'],
            ['payer_1_document_identification', '11144477735'],
        ], 'mercadopago_adbpayment_twocc');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(DocumentIdentificationDataRequest::METRIC_DOCUMENT_VALIDATION_PREFIX . 'twocc', 'CPF');

        $this->expectException(LocalizedException::class);

        $this->request->build(['payment' => $paymentDO]);
    }

    public function testBuildThrowsForInvalidSecondCardDocumentOnMlbTwoCc()
    {
        // Symmetric to the case above: card 0 carries a VALID CPF and card 1 an invalid one.
        // The loop must not stop at index 0 — a premature return would let the second card's
        // invalid document slip through. The metric must still be tagged twocc.
        $paymentDO = $this->createPaymentDataObject(null, null, 1, [
            ['payer_0_document_identification', '11144477735'],
            ['payer_1_document_identification', '12345678900'],
        ], 'mercadopago_adbpayment_twocc');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(DocumentIdentificationDataRequest::METRIC_DOCUMENT_VALIDATION_PREFIX . 'twocc', 'CPF');

        $this->expectException(LocalizedException::class);

        $this->request->build(['payment' => $paymentDO]);
    }

    public function testBuildPassesForValidPerCardDocumentsOnMlbTwoCc()
    {
        // Both cards carry valid CPFs: no exception, no metric, and no identification node is
        // emitted (twocc never sends payer.identification).
        $paymentDO = $this->createPaymentDataObject(null, null, 1, [
            ['payer_0_document_identification', '11144477735'],
            ['payer_1_document_identification', '52998224725'],
        ], 'mercadopago_adbpayment_twocc');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->never())->method('sendEvent');

        $result = $this->request->build(['payment' => $paymentDO]);

        $this->assertArrayNotHasKey(PayerDataRequest::PAYER, $result);
    }

    public function testBuildTreatsMissingDocumentAsAbsentNotInvalidOnMlb()
    {
        // Single-card flow with payer_document_type set but no number resolving (no form value,
        // no billing vatId, no customer taxvat). The document is absent, not invalid: no exception
        // and no invalid-document metric — the API will report the missing field instead.
        $paymentDO = $this->createPaymentDataObject('CPF', null);

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->never())->method('sendEvent');

        $result = $this->request->build(['payment' => $paymentDO]);

        $this->assertNull(
            $result[PayerDataRequest::PAYER][DocumentIdentificationDataRequest::IDENTIFICATION]['number']
        );
    }

    public function testBuildSkipsPerCardValidationForNonMlbSiteTwoCc()
    {
        // Non-MLB: the API accepts these documents freely, so even an invalid-looking per-card
        // value must not be validated — no exception, no metric.
        $paymentDO = $this->createPaymentDataObject(null, null, 1, [
            ['payer_0_document_identification', '123'],
        ], 'mercadopago_adbpayment_twocc');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLA');

        $this->metricsClientMock->expects($this->never())->method('sendEvent');

        $result = $this->request->build(['payment' => $paymentDO]);

        $this->assertArrayNotHasKey(PayerDataRequest::PAYER, $result);
    }

    public function testBuildPassesForValidCpfOnMlb()
    {
        $paymentDO = $this->createPaymentDataObject('CPF', '11144477735');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->never())->method('sendEvent');

        $result = $this->request->build(['payment' => $paymentDO]);

        $this->assertSame(
            '11144477735',
            $result[PayerDataRequest::PAYER][DocumentIdentificationDataRequest::IDENTIFICATION]['number']
        );
    }

    public function testBuildSkipsValidationForNonMlbSite()
    {
        // Invalid-looking document, but the API accepts non-MLB documents freely, so the
        // builder must not validate it — no exception, no metric.
        $paymentDO = $this->createPaymentDataObject('DNI', '123');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLA');

        $this->metricsClientMock->expects($this->never())->method('sendEvent');

        $result = $this->request->build(['payment' => $paymentDO]);

        $this->assertSame(
            '123',
            $result[PayerDataRequest::PAYER][DocumentIdentificationDataRequest::IDENTIFICATION]['number']
        );
    }

    public function testBuildEmitsTicketFlowEventForInvalidDocumentOnMlb()
    {
        // Boleto/PEC (payment_methods_off) submits a non-indexed payer_document_type, so it runs
        // the single-card validation path — but the flow suffix must come from the method code,
        // tagging the event as the ticket flow, not cc.
        $paymentDO = $this->createPaymentDataObject(
            'CPF',
            '12345678900',
            1,
            [],
            'mercadopago_adbpayment_payment_methods_off'
        );

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(DocumentIdentificationDataRequest::METRIC_DOCUMENT_VALIDATION_PREFIX . 'ticket', 'CPF');

        $this->expectException(LocalizedException::class);

        $this->request->build(['payment' => $paymentDO]);
    }

    public function testBuildEmitsPixFlowEventForInvalidDocumentOnMlb()
    {
        // Pix also submits a non-indexed payer_document_type; the event must be tagged pix.
        $paymentDO = $this->createPaymentDataObject(
            'CNPJ',
            '11222333000180',
            1,
            [],
            'mercadopago_adbpayment_pix'
        );

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(DocumentIdentificationDataRequest::METRIC_DOCUMENT_VALIDATION_PREFIX . 'pix', 'CNPJ');

        $this->expectException(LocalizedException::class);

        $this->request->build(['payment' => $paymentDO]);
    }

    public function testBuildStillRejectsButEmitsNoMetricForUnmappedMethodOnMlb()
    {
        // A method outside the four instrumented flows (e.g. Checkout Pro) resolves to a null flow.
        // The invalid document is STILL rejected — validation is never narrowed — but no metric is
        // emitted, keeping observability scoped to the covered flows.
        $paymentDO = $this->createPaymentDataObject(
            'CPF',
            '12345678900',
            1,
            [],
            'mercadopago_adbpayment_checkout_pro'
        );

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->never())->method('sendEvent');

        $this->expectException(LocalizedException::class);

        $this->request->build(['payment' => $paymentDO]);
    }

    public function testMetricFailureLogsWarningAndDoesNotMaskException(): void
    {
        // Quando sendEvent() lança (ex.: timeout no adapter de métricas), o catch em
        // sendInvalidDocumentMetric() deve logar um warning e retornar — nunca mascarar
        // a LocalizedException que o chamador levanta em seguida.
        $paymentDO = $this->createPaymentDataObject('CPF', '12345678900');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->willThrowException(new \RuntimeException('timeout'));

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'DocumentValidation: failed to emit backend document-validation metric',
                $this->logicalAnd(
                    $this->arrayHasKey('flow_type'),
                    $this->callback(fn($ctx) => $ctx['exception'] instanceof \Throwable)
                )
            );

        $this->expectException(LocalizedException::class);

        $this->request->build(['payment' => $paymentDO]);
    }

    public function testBuildStillRejectsWhenGetMethodInstanceThrowsOnMlb(): void
    {
        // Simula cron reprocessando pedido antigo onde o método não pode ser carregado.
        // resolveFlowType() absorve o Throwable e retorna null — o documento ainda é
        // rejeitado com LocalizedException, mas nenhuma métrica é emitida.
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->willReturnMap([
                ['payer_document_type', 'CPF'],
                ['payer_document_identification', '12345678900'],
            ]);

        $paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->willThrowException(new \RuntimeException('method model not found'));

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn(
                $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock()
            );

        $gatewayOrderMock = $this->createMock(OrderAdapterInterface::class);
        $gatewayOrderMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDOMock->expects($this->any())->method('getPayment')->willReturn($paymentMock);
        $paymentDOMock->expects($this->any())->method('getOrder')->willReturn($gatewayOrderMock);

        $this->subjectReaderMock->expects($this->any())
            ->method('readPayment')
            ->willReturn($paymentDOMock);

        $this->orderAdapterFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn(
                $this->getMockBuilder(OrderAdapter::class)->disableOriginalConstructor()->getMock()
            );

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $this->metricsClientMock->expects($this->never())->method('sendEvent');

        $this->expectException(LocalizedException::class);

        $this->request->build(['payment' => $paymentDOMock]);
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
