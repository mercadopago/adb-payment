<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Store\Model\StoreManagerInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigCheckoutPro;
use MercadoPago\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\PaymentMagento\Gateway\SubjectReader;

/**
 * Gateway requests for binary mode definition.
 */
class BinaryModeDataRequest implements BuilderInterface
{
    /**
     * Binary Mode block name.
     */
    public const BINARY_MODE = 'binary_mode';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @var ConfigCheckoutPro
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param SubjectReader         $subjectReader
     * @param OrderAdapterFactory   $orderAdapterFactory
     * @param ConfigCheckoutPro     $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        ConfigCheckoutPro $config,
        StoreManagerInterface $storeManager
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
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

        $result = [];

        $result = [
            self::BINARY_MODE => $this->config->isBinaryMode(),
        ];

        return $result;
    }
}
