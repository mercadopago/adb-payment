<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Adminhtml\System\Config\Tracking;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use MercadoPago\AdbPayment\Gateway\Config\Config;

class Tracking extends Template
{

  /**
   * @var Session
   */
  protected $session;

  /**
   * @var Config
   */
  protected $config;

  /**
   * @var string
   */
  protected $_template = 'MercadoPago_AdbPayment::melidata/tracking.phtml';

  /**
   * @param Context   $context
   * @param Session   $session
   * @param Config    $config
   * @param array     $data
   */
  public function __construct(
    Context $context,
    Session $session,
    Config $config,
    array $data = []
  ) {
    parent::__construct($context, $data);
    $this->session = $session;
    $this->config = $config;
  }

  /**
   * @return String
   */
  public function getConfigData()
  {

    return [
      'moduleVersion' => $this->config->getModuleVersion(),
      'platformVersion' => $this->config->getMagentoVersion(),
      'siteId' => $this->config->getMpSiteId() ? $this->config->getMpSiteId() : "Credencial não cadastrada",
    ];
  }
}
