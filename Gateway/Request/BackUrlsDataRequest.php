<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway Requests for Callback Urls.
 */
class BackUrlsDataRequest implements BuilderInterface
{
    /**
     * Back Urls block name.
     */
    public const BACK_URLS = 'back_urls';

    /**
     * Success block name.
     */
    public const SUCCESS = 'success';

    /**
     * Pending block name.
     */
    public const PENDING = 'pending';

    /**
     * Pedding block name.
     */
    public const FAILURE = 'failure';

    /**
     * Path to Success - url magento.
     */
    public const PATH_TO_SUCCESS = 'checkout/onepage/success';

    /**
     * Path to Failure - url magento.
     */
    public const PATH_TO_FAILURE = 'checkout/onepage/failure';

    /**
     * @var UrlInterface
     */
    protected $frontendUrlBuilder;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param UrlInterface  $frontendUrlBuilder
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        UrlInterface $frontendUrlBuilder,
        SubjectReader $subjectReader
    ) {
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->subjectReader = $subjectReader;
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

        $result = [];

        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();

        $urlSuccess = $this->frontendUrlBuilder->setScope($storeId)->getUrl(
            self::PATH_TO_SUCCESS,
            ['_nosid' => false]
        );
        $urlFailure = $this->frontendUrlBuilder->setScope($storeId)->getUrl(
            self::PATH_TO_FAILURE,
            ['_nosid' => false]
        );

        $result[self::BACK_URLS] = [
            self::SUCCESS => $urlSuccess,
            self::PENDING => $urlSuccess,
            self::FAILURE => $urlFailure,
        ];

        return $result;
    }
}
