<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Info;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCc;

/**
 * Payment details form block by card.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Card extends \Magento\Payment\Block\Info
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param Context         $context
     * @param ConfigInterface $config
     * @param ConfigCc        $configCc
     * @param array           $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        ConfigCc $configCc,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->configCc = $configCc;

        if (isset($data['pathPattern'])) {
            $this->config->setPathPattern($data['pathPattern']);
        }

        if (isset($data['methodCode'])) {
            $this->config->setMethodCode($data['methodCode']);
        }
    }

    /**
     * Returns label.
     *
     * @param string $field
     *
     * @return Phrase
     */
    public function getLabel($field)
    {
        return __($field);
    }

    /**
     * Get Name for Cc Type.
     *
     * @param string $type
     *
     * @return string
     */
    public function getFullTypeName(string $type): string
    {
        $mapper = $this->configCc->getCcTypesMapper();
        $type = array_search($type, $mapper);

        return  ucfirst($type);
    }

    /**
     * Returns value view.
     *
     * @param string $field
     * @param string $value
     *
     * @return string | Phrase
     */
    public function getValueView($field, $value)
    {
        if ($field === 'card_type') {
            $value = $this->getFullTypeName($value);
        }

        return __($value);
    }

    /**
     * Prepare payment information.
     *
     * @param \Magento\Framework\DataObject|array|null $transport
     *
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();
        $storedFields = explode(',', (string) $this->config->getValue('paymentInfoKeys'));
        if ($this->getIsSecureMode()) {
            $storedFields = array_diff(
                $storedFields,
                explode(',', (string) $this->config->getValue('privateInfoKeys'))
            );
        }

        foreach ($storedFields as $field) {
            if ($payment->getAdditionalInformation($field) !== null) {
                $this->setDataToTransfer(
                    $transport,
                    $field,
                    $payment->getAdditionalInformation($field)
                );
            }
        }

        return $transport;
    }

    /**
     * Sets data to transport.
     *
     * @param \Magento\Framework\DataObject $transport
     * @param string                        $field
     * @param string                        $value
     *
     * @return void
     */
    protected function setDataToTransfer(
        \Magento\Framework\DataObject $transport,
        $field,
        $value
    ) {
        $transport->setData(
            (string) $this->getLabel($field),
            (string) $this->getValueView(
                $field,
                $value
            )
        );
    }
}
