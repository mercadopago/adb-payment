<?php

namespace Tests\Unit\Model\Ui;

use PHPUnit\Framework\TestCase;
use Magento\Framework\ObjectManager;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Phrase;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\PaymentMagento\Gateway\Config\Config as MercadoPagoConfig;

use MercadoPago\PaymentMagento\Tests\Unit\Mocks\PaymentsMethodsActiveMock;
use MercadoPago\PaymentMagento\Model\Ui\ConfigProviderPaymentMethodsOff;

class ConfigProviderPaymentMethodsOffTest extends TestCase {
    private const paymentsMethodsActive = [
        0 => [
            "accreditation_time" => 60,
            "additional_info_needed" => [
                "identification_type",
                "identification_number",
                "first_name",
                "last_name",
            ],
            "deferred_capture" => "supported",
            "financial_institutions" => [],
            "id" => "pec",
            "max_allowed_amount" => 2003.49,
            "min_allowed_amount" => 4,
            "name" => "Pagamento na lotérica sem boleto",
            "payment_type_id" => "ticket",
            "processing_modes" => ["aggregator"],
            "secure_thumbnail" =>
                "https://http2.mlstatic.com/storage/logos-api-admin/70965f00-f3c2-11eb-a186-1134488bf456-xl.png",
            "settings" => [],
            "status" => "active",
            "thumbnail" =>
                "https://http2.mlstatic.com/storage/logos-api-admin/70965f00-f3c2-11eb-a186-1134488bf456-xl.png",
        ],
        1 => [
            "accreditation_time" => 1440,
            "additional_info_needed" => [
                "identification_type",
                "identification_number",
                "first_name",
                "last_name",
            ],
            "deferred_capture" => "does_not_apply",
            "financial_institutions" => [],
            "id" => "bolbradesco",
            "max_allowed_amount" => 100000,
            "min_allowed_amount" => 4,
            "name" => "Boleto",
            "payment_type_id" => "ticket",
            "processing_modes" => ["aggregator"],
            "secure_thumbnail" =>
                "https://http2.mlstatic.com/storage/logos-api-admin/00174300-571e-11e8-8364-bff51f08d440-xl.png",
            "settings" => [],
            "status" => "active",
            "thumbnail" =>
                "https://http2.mlstatic.com/storage/logos-api-admin/00174300-571e-11e8-8364-bff51f08d440-xl.png",
        ]
    ];

    public const expectedArray = [
        0 => [     
            'value' => "bolbradesco",
            'label' => "Boleto",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/00174300-571e-11e8-8364-bff51f08d440-xl.png",
            'payment_method_id' => "bolbradesco",
            'payment_type_id' => "ticket"
            ],
        1 => [
            'value' => "pec",
            'label' => "Pagamento na lotérica sem boleto",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/70965f00-f3c2-11eb-a186-1134488bf456-xl.png",
            'payment_method_id' => "pec",
            'payment_type_id' => "ticket"
        ]
    ];
    /**
     * @var configProviderPaymentMethodsOffMock
     */
    private $configProviderPaymentMethodsOffMock;

    /**
     * @var paymentMethodsResponseMock
     */
    private $paymentMethodsResponseMock;
        /**
     * @var ConfigPaymentMethodsOff
     */
    private $configMock;

    /**
     * @var CartInterface
     */
    private $cartMock;

    /**
     * @var CcConfig
     */
    private $ccConfigMock;

     /**
     * @var MercadoPagoConfig
     */
    private $mercadopagoConfigMock;

    /**
     * @var Escaper
     */
    private $escaperMock;

    /**
     * @var Source
     */
    private $assetSourceMock;

    public function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)->disableOriginalConstructor()->getMock();
        $this->cartMock = $this->getMockBuilder(CartInterface::class)->disableOriginalConstructor()->getMock();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)->disableOriginalConstructor()->getMock();
        $this->ccConfigMock = $this->getMockBuilder(CcConfig::class)->disableOriginalConstructor()->getMock();
        $this->assetSourceMock = $this->getMockBuilder(Source::class)->disableOriginalConstructor()->getMock();
        $this->mercadopagoConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();

        $this->configProviderPaymentMethodsOffMock = $this->getMockBuilder(ConfigProviderPaymentMethodsOff::class)->setConstructorArgs([
            'config' => $this->configMock,
            'cart' => $this->cartMock,
            'ccConfig' => $this->ccConfigMock,
            'escaper' => $this->escaperMock,
            'assetSource' => $this->assetSourceMock,
            'mercadopagoConfig' => $this->mercadopagoConfigMock
        ])->getMock();
    }

    public function testFilterPaymentMethodsReturnSuccess(): void
    {
        $configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock, 
            $this->cartMock, 
            $this->ccConfigMock, 
            $this->escaperMock, 
            $this->assetSourceMock, 
            $this->mercadopagoConfigMock
        );

        $teste = Self::paymentsMethodsActive;


        $result_filter = $configProviderPaymentMethodsOff->filterPaymentMethods($teste);

        $this->assertEquals(Self::expectedArray, $result_filter);
    }
}