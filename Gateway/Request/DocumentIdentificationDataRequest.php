<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCc;
use MercadoPago\AdbPayment\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\AdbPayment\Gateway\Config\ConfigPix;
use MercadoPago\AdbPayment\Gateway\Config\ConfigTwoCc;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use MercadoPago\AdbPayment\Helper\DocumentValidator;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use Psr\Log\LoggerInterface;

/**
 * Gateway Requests the Payer Identification Document.
 */
class DocumentIdentificationDataRequest implements BuilderInterface
{
    /**
     * Document Identification name.
     */
    public const IDENTIFICATION = 'identification';

    /**
     * Identification Type block name.
     */
    public const IDENTIFICATION_TYPE = 'type';

    /**
     * Identification Number block name.
     */
    public const IDENTIFICATION_NUMBER = 'number';

    /**
     * CPF document type value.
     */
    public const DOCUMENT_TYPE_CPF = 'CPF';

    /**
     * CNPJ document type value.
     */
    public const DOCUMENT_TYPE_CNPJ = 'CNPJ';

    /**
     * Document kind reported to the metric when the length matches neither CPF nor CNPJ.
     */
    private const DOCUMENT_KIND_UNKNOWN = 'unknown';

    /**
     * MercadoPago site id for Brazil — the only site whose documents (CPF/CNPJ) the
     * Payment API validates by check digit, so backend validation is scoped to it.
     * Other sites accept their documents freely; validating them here would risk
     * rejecting otherwise-valid payments.
     */
    public const SITE_ID_MLB = 'MLB';

    /**
     * Prefix of the Datadog event emitted when the backend rejects a fiscal document. The
     * payment-method flow (cc / twocc / ticket / pix) is appended as a suffix so each flow is a
     * distinct, filterable event name. The ppcore monitor proxy honors the event type (the URL
     * path segment) but not arbitrary custom payload fields, so the flow must live in the name.
     */
    public const METRIC_DOCUMENT_VALIDATION_PREFIX = 'magento_backend_document_validation_';

    /**
     * Maps each payment-method code to its observability flow suffix. A method absent from this
     * map still has its document validated and rejected — it simply emits no metric, keeping
     * observability scoped to the four covered flows without narrowing the validation itself.
     *
     * Other methods that compose this builder are intentionally left out, not overlooked:
     * cc_vault sets no payer_document_type (the saved-card flow carries no document to validate
     * here), and checkout_pro/credits/bank_transfer likewise submit none on-site; the non-MLB
     * methods (pse/webpay/yape) are skipped by the SITE_ID_MLB gate.
     */
    private const FLOW_BY_METHOD = [
        ConfigCc::METHOD                => 'cc',
        ConfigTwoCc::METHOD             => 'twocc',
        ConfigPaymentMethodsOff::METHOD => 'ticket',
        ConfigPix::METHOD               => 'pix',
    ];

    /**
     * Number of cards in a multi-card (twocc) payment. Each card's fiscal document is
     * stored per index as payer_0_document_identification / payer_1_document_identification.
     */
    private const MULTI_CARD_COUNT = 2;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var MetricsClient
     */
    protected $metricsClient;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param Config              $config
     * @param MetricsClient       $metricsClient
     * @param LoggerInterface     $logger
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config,
        MetricsClient $metricsClient,
        LoggerInterface $logger
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
        $this->metricsClient = $metricsClient;
        $this->logger = $logger;
    }

    /**
     * Get Value For Document Identification.
     *
     * @param OrderAdapterFactory $orderAdapter
     *
     * @return string
     */
    public function getValueForDocumentIdentification($orderAdapter)
    {
        $obtainTaxDocFrom = $this->config->getAddtionalValue('get_document_identification_from');

        $docIdentification = $orderAdapter->getCustomerTaxvat();

        if ($obtainTaxDocFrom === 'address') {
            $docIdentification = $orderAdapter->getBillingAddress()->getVatId();
        }

        return $docIdentification;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $typeDocument = $payment->getAdditionalInformation('payer_document_type');
        $flowType = $this->resolveFlowType($payment);
        $result = [];

        /** @var OrderAdapterFactory $orderAdapter */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $docIdentification = $this->getFiscalNumber($payment, $orderAdapter);

        // Whether a document was actually provided — captured before sanitization so a value made
        // only of punctuation (e.g. "---") still counts as provided (and is rejected below), while
        // a truly absent null/empty does not.
        $hasDocument = ($docIdentification !== null && $docIdentification !== '');

        if ($docIdentification) {
            // preg_replace returns null on an (unlikely) PCRE failure; coalesce so a null
            // never reaches the string-typed validator and turns into a TypeError/500.
            $docIdentification = preg_replace('/[^0-9A-Za-z]/', '', $docIdentification) ?? '';
        }

        if ($typeDocument) {
            // Single-card flow emits a payer.identification node. Validate the document it emits —
            // but only when one was actually provided: an absent (null/empty) fiscal number is a
            // missing field, not an invalid one (the API reports it), so raising "invalid" would
            // mislead. A provided value that sanitizes to '' (e.g. "---") is garbage and is rejected.
            if ($hasDocument) {
                $this->validateBrazilianDocument($paymentDO, (string) $docIdentification, $flowType);
            }

            $result[PayerDataRequest::PAYER][self::IDENTIFICATION] = [
                self::IDENTIFICATION_TYPE   => $typeDocument,
                self::IDENTIFICATION_NUMBER => $docIdentification,
            ];
        } else {
            // No non-indexed payer_document_type was submitted: either twocc (which stores each
            // card's document in payer_<i>_document_identification), or a method that does not use
            // document identification at all (e.g. Checkout Pro, Yape — both include this builder
            // in their composite). For twocc, validate the per-card values (the ones tokenized) so
            // an invalid CPF/CNPJ is caught here too, without validating the billing-address
            // fallback — which may not match what was tokenized and could reject an otherwise-valid
            // payment. For the other methods the loop is a no-op (no per-card keys are present).
            $this->validateMultiCardDocuments($paymentDO, $payment, $flowType);
        }

        return $result;
    }

    /**
     * Get Fiscal Number.
     *
     * @param InfoInterface       $payment
     * @param OrderAdapterFactory $orderAdapter
     *
     * @return string
     */
    public function getFiscalNumber($payment, $orderAdapter): ?string
    {
        $docIdentification = null;

        if ($payment->getAdditionalInformation('payer_document_identification')) {
            $docIdentification = $payment->getAdditionalInformation('payer_document_identification');
        }

        if (!$docIdentification) {
            $docIdentification = $this->getValueForDocumentIdentification($orderAdapter);
        }

        // Alphanumeric CNPJ (RFB Technical Note 49) uses uppercase letters. Normalize here so
        // every consumer of the fiscal number (payment + additional info) sends it consistently.
        if ($docIdentification
            && $payment->getAdditionalInformation('payer_document_type') === self::DOCUMENT_TYPE_CNPJ
        ) {
            $docIdentification = strtoupper((string) $docIdentification);
        }

        return $docIdentification;
    }

    /**
     * Resolve the observability flow suffix for the payment method being processed.
     *
     * The single-card validation path is shared by several methods (cc, pix, ticket all submit
     * payer_document_type), so the flow can only be told apart by the method code — not by the
     * document keys. Returns null for any method outside the four covered flows: those documents
     * are still validated and rejected, they just emit no metric.
     *
     * @param InfoInterface $payment
     *
     * @return string|null cc / twocc / ticket / pix, or null when the method is not instrumented.
     */
    private function resolveFlowType(InfoInterface $payment): ?string
    {
        try {
            // getMethodInstance() throws when the method model cannot be loaded (e.g. cron
            // reprocessing an old order, or import). Swallow it: a null flow only skips the
            // metric — the document is still validated and the correct CPF/CNPJ error is raised.
            return self::FLOW_BY_METHOD[$payment->getMethodInstance()->getCode()] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Validate the per-card fiscal documents of a multi-card (twocc) payment.
     *
     * The multi-card flow emits no payer.identification node; each card instead carries its
     * own document inside its token, stored as payer_<i>_document_identification. Those are the
     * exact values sent to the API, so validating them — rather than the billing-address
     * fallback — catches an invalid CPF/CNPJ without risking a false rejection from a mismatched
     * source. A no-op for single-card flows, which set no indexed document keys.
     *
     * @param PaymentDataObjectInterface $paymentDO
     * @param InfoInterface              $payment
     * @param string|null                $flowType Observability flow suffix (twocc), or null.
     *
     * @return void
     * @throws LocalizedException When any per-card document fails CPF/CNPJ check-digit validation.
     */
    private function validateMultiCardDocuments(
        PaymentDataObjectInterface $paymentDO,
        InfoInterface $payment,
        ?string $flowType
    ): void {
        for ($cardIndex = 0; $cardIndex < self::MULTI_CARD_COUNT; $cardIndex++) {
            $document = $payment->getAdditionalInformation('payer_' . $cardIndex . '_document_identification');

            if (!$document) {
                continue;
            }

            // preg_replace returns null on an (unlikely) PCRE failure; coalesce so a null never
            // reaches the string-typed validator and turns into a TypeError/500.
            $document = preg_replace('/[^0-9A-Za-z]/', '', (string) $document) ?? '';

            $this->validateBrazilianDocument($paymentDO, $document, $flowType);
        }
    }

    /**
     * Reject an invalid Brazilian fiscal document before it reaches the Payment API.
     *
     * Defense in depth for Brazil (MLB) only: when the payment form document field is
     * hidden (Capture document identification disabled + a vatId on the billing address),
     * the frontend check-digit rule never runs, so an invalid CPF/CNPJ would otherwise
     * reach the API as INVALID_USER_IDENTIFICATION_NUMBER. Non-MLB sites are skipped —
     * their documents are accepted freely by the API and validating them would risk
     * rejecting valid payments.
     *
     * @param PaymentDataObjectInterface $paymentDO
     * @param string                     $document Normalized (alphanumeric-only) fiscal number.
     * @param string|null                $flowType Observability flow suffix, or null to skip the
     *                                             metric while still rejecting the document.
     *
     * @return void
     * @throws LocalizedException When the document fails CPF/CNPJ check-digit validation.
     */
    private function validateBrazilianDocument(
        PaymentDataObjectInterface $paymentDO,
        string $document,
        ?string $flowType
    ): void {
        $storeId = $paymentDO->getOrder()->getStoreId();

        if ($this->config->getMpSiteId($storeId) !== self::SITE_ID_MLB) {
            return;
        }

        if (DocumentValidator::isValid($document)) {
            return;
        }

        // Emit only for the four instrumented flows; an un-mapped method (null flow) is still
        // rejected below, it just isn't measured — observability is scoped without narrowing
        // the validation.
        if ($flowType !== null) {
            $this->sendInvalidDocumentMetric($document, $flowType);
        }

        throw new LocalizedException(
            __('The CPF/CNPJ informed is invalid. Please correct it and try again.')
        );
    }

    /**
     * Emit the backend-rejection metric for Datadog monitoring.
     *
     * The flow is encoded in the event type (magento_backend_document_validation_<flow>) so each
     * payment method is a distinct, filterable event. Only the document kind (CPF / CNPJ /
     * unknown), derived from length, travels in the value — never the number itself, which is PII
     * (CWE-532). Wrapped in a Throwable guard so a failure in the metrics stack (including \Error,
     * which MetricsClient does not catch) can never mask the LocalizedException the caller raises
     * right after.
     *
     * @param string $document Normalized fiscal number (used only to derive the kind).
     * @param string $flowType Flow suffix (cc / twocc / ticket / pix) appended to the event type.
     *
     * @return void
     */
    private function sendInvalidDocumentMetric(string $document, string $flowType): void
    {
        $length = strlen($document);

        if ($length === DocumentValidator::CPF_LENGTH) {
            $documentKind = self::DOCUMENT_TYPE_CPF;
        } elseif ($length === DocumentValidator::CNPJ_LENGTH) {
            $documentKind = self::DOCUMENT_TYPE_CNPJ;
        } else {
            $documentKind = self::DOCUMENT_KIND_UNKNOWN;
        }

        try {
            $this->metricsClient->sendEvent(
                self::METRIC_DOCUMENT_VALIDATION_PREFIX . $flowType,
                $documentKind
            );
        } catch (\Throwable $e) {
            // Best-effort metric; never let it break the payment flow or mask the exception.
            // Still leave a trace: MetricsClient only catches \Exception, so a structural \Error
            // in the metrics stack would otherwise vanish here and we would lose data silently.
            $this->logger->warning(
                'DocumentValidation: failed to emit backend document-validation metric',
                ['flow_type' => $flowType, 'exception' => $e]
            );
        }
    }
}
