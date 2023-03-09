<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Block\Sales\Info;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigTwoCc;

/**
 * Payment details form block by card.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class TwoCars extends \Magento\Payment\Block\Info
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var ConfigTwoCc
     */
    protected $configTwoCc;

    /**
     * @param Context         $context
     * @param ConfigInterface $config
     * @param ConfigCc        $configTwoCc
     * @param array           $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        ConfigTwoCc $configTwoCc,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->configTwoCc = $configTwoCc;

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
        $mapper = $this->configTwoCc->getCcTypesMapper();
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

        return $value;
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
