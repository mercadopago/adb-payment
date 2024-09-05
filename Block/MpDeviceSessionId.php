<?php

namespace MercadoPago\AdbPayment\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Math\Random;
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;

class MpDeviceSessionId extends Template
{
    private const NONCE_LENGTH = 32;

    private DynamicCollector $dynamicCollector;
    private Random $random;

    /**
     * @param Context $context
     * @param DynamicCollector $dynamicCollector
     * @param Random $random
     * @param array $data
     */
    public function __construct(Context $context, DynamicCollector $dynamicCollector,  Random $random, array $data = [])
    {
        parent::__construct($context, $data);

        $this->dynamicCollector = $dynamicCollector;
        $this->random = $random;
    }

    public function getNonce(): string
    {
        $nonce = $this->random->getRandomString(
            self::NONCE_LENGTH,
            Random::CHARS_DIGITS . Random::CHARS_LOWERS
        );

        $policy = new FetchPolicy(
            'script-src',
            false,
            [],
            [],
            false,
            false,
            false,
            [$nonce],
            []
        );

        $this->dynamicCollector->add($policy);

        return base64_encode($nonce);
    }
}
