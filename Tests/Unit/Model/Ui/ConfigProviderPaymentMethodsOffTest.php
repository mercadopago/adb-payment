<?php

namespace Tests\Unit\Model\Ui;

use PHPUnit\Framework\TestCase;
use MercadoPago\PaymentMagento\Tests\Unit\Mocks\Model\Ui\ConfigProviderPaymentMethodsOff\MountPaymentMethodsOffMock;
use MercadoPago\PaymentMagento\Tests\Unit\Mocks\Model\Ui\ConfigProviderPaymentMethodsOff\FilterPaymentMethodsOffConfigActiveMock;
use MercadoPago\PaymentMagento\Tests\Unit\Mocks\Gateway\Config\PaymentMethodsResponseMock;

use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Repository;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\PaymentMagento\Gateway\Config\Config as MercadoPagoConfig;

use MercadoPago\PaymentMagento\Model\Ui\ConfigProviderPaymentMethodsOff;

class ConfigProviderPaymentMethodsOffTest extends TestCase {
    
    /**
     * @var configProviderPaymentMethodsOffMock
     */
    private $configProviderPaymentMethodsOffMock;

    /**
     * @var configProviderPaymentMethodsOff
     */
    private $configProviderPaymentMethodsOff;

        /**
     * @var ConfigPaymentMethodsOff
     */
    private $configMock;

    /**
     * @var CartInterface
     */
    private $cartMock;

     /**
     * @var MercadoPagoConfig
     */
    private $mercadopagoConfigMock;

    /**
     * @var Escaper
     */
    private $escaperMock;

            /**
     * @var Repository
     */
    protected $assetRepoMock;

    public function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)->disableOriginalConstructor()->getMock();
        $this->cartMock = $this->getMockBuilder(CartInterface::class)->disableOriginalConstructor()->getMock();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)->disableOriginalConstructor()->getMock();
        $this->assetRepoMock = $this->getMockBuilder(Repository::class)->disableOriginalConstructor()->getMock();
        $this->mercadopagoConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();

        $this->configProviderPaymentMethodsOffMock = $this->getMockBuilder(ConfigProviderPaymentMethodsOff::class)->setConstructorArgs([
            'config' => $this->configMock,
            'cart' => $this->cartMock,
            'escaper' => $this->escaperMock,
            'mercadopagoConfig' => $this->mercadopagoConfigMock,
            'assetRepo' => $this->assetRepoMock
        ])->getMock();

        $this->configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock, 
            $this->cartMock, 
            $this->escaperMock, 
            $this->mercadopagoConfigMock,
            $this->assetRepoMock
        );
    }

    /**
     * Tests function mountPaymentMethodsOff()
     */

    public function test_mountPaymentMethodsOff_empty(): void
    {
        $result = $this->configProviderPaymentMethodsOff->mountPaymentMethodsOff([]);

        $this->assertEmpty($result);
    }

    public function test_mountPaymentMethodsOff_without_payment_places(): void
    {
        $response = PaymentMethodsResponseMock::WITHOUT_PAYMENT_PLACES['response'];
        $result = $this->configProviderPaymentMethodsOff->mountPaymentMethodsOff($response);

        $this->assertEquals(MountPaymentMethodsOffMock::EXPECTED_WITHOUT_PAYMENT_PLACES, $result);
        foreach ($result as $payment) {
            $this->assertTrue(in_array($payment['payment_type_id'], ConfigProviderPaymentMethodsOff::PAYMENT_TYPE_ID_ALLOWED));
        }
    }

    public function test_mountPaymentMethodsOff_with_payment_places(): void
    {
        $response = PaymentMethodsResponseMock::WITH_PAYMENT_PLACES['response'];
        $result = $this->configProviderPaymentMethodsOff->mountPaymentMethodsOff($response);

        $this->assertEquals(MountPaymentMethodsOffMock::EXPECTED_WITH_PAYMENT_PLACES, $result);
        foreach ($result as $payment) {
            $this->assertTrue(in_array($payment['payment_type_id'], ConfigProviderPaymentMethodsOff::PAYMENT_TYPE_ID_ALLOWED));
        }
    }

    public function test_mountPaymentMethodsOff_without_payment_places_and_with_inactive(): void
    {
        $response = PaymentMethodsResponseMock::WITHOUT_PAYMENT_PLACES_AND_WITH_INACTIVE['response'];
        $result = $this->configProviderPaymentMethodsOff->mountPaymentMethodsOff($response);

        $this->assertEquals(MountPaymentMethodsOffMock::EXPECTED_WITHOUT_PAYMENT_PLACES_AND_WITH_INACTIVE, $result);
        foreach ($result as $payment) {
            $this->assertTrue(in_array($payment['payment_type_id'], ConfigProviderPaymentMethodsOff::PAYMENT_TYPE_ID_ALLOWED));
        }
    }

    public function test_mountPaymentMethodsOff_with_payment_places_and_inactive(): void
    {
        $response = PaymentMethodsResponseMock::WITH_PAYMENT_PLACES_AND_INACTIVE['response'];

        $result = $this->configProviderPaymentMethodsOff->mountPaymentMethodsOff($response);

        $this->assertEquals(MountPaymentMethodsOffMock::EXPECTED_WITH_PAYMENT_PLACES_AND_INACTIVE, $result);
        foreach ($result as $payment) {
            $this->assertTrue(in_array($payment['payment_type_id'], ConfigProviderPaymentMethodsOff::PAYMENT_TYPE_ID_ALLOWED));
        }
    }

    /**
     * Tests function filterPaymentMethodsOffConfigActive()
    */

    public function test_filterPaymentMethodsOffConfigActive_empty(): void
    {
        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive([], '');

        $this->assertEmpty($result);
    }

    public function test_filterPaymentMethodsOffConfigActive_paymentMethods_empty(): void
    {
        $paymentMethodsOffActive = '7eleven,serfin';

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive([], $paymentMethodsOffActive);

        $this->assertEmpty($result);
    }

    public function test_filterPaymentMethodsOffConfigActive_paymentMethodsOffActive_null(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, null);

        $this->assertEquals($paymentMethods, $result);
    }

    public function test_filterPaymentMethodsOffConfigActive_paymentMethodsOffActive_empty(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, '');

        $this->assertEquals($paymentMethods, $result);
    }

    public function test_filterPaymentMethodsOffConfigActive_paymentMethodsOffInactive_7eleven(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;
        $paymentMethodsOffInactive = '7eleven';

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, $paymentMethodsOffInactive);
        $this->assertEquals(FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS_WITHOUT_7ELEVEN, $result);
    }

    public function test_filterPaymentMethodsOffConfigActive_paymentMethodsOffInactive_7eleven_serfin(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;
        $paymentMethodsOffInactive = '7eleven,serfin';

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, $paymentMethodsOffInactive);
        $this->assertEquals(FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS_WITHOUT_7ELEVEN_AND_SERFIN, $result);
    }

    public function test_filterPaymentMethodsOffConfigActive_paymentMethodsOffActive_does_not_exist(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;
        $paymentMethodsOffActive = 'does_not_exist';

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, $paymentMethodsOffActive);
        $this->assertEquals(FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS, $result);
    }

    public function test_filterPaymentMethodsOffConfigActive_paymentMethodsOffInactive_whitout_7eleven_and_does_not_exist(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;
        $paymentMethodsOffInactive = '7eleven,does_not_exist';

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, $paymentMethodsOffInactive);
        $this->assertEquals(FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS_WITHOUT_7ELEVEN, $result);
    }

    /**
     * Tests function getPaymentMethodsOffActive()
    */

    public function test_getPaymentMethodsOffActive_empty(): void
    {
        $storeId = 1;

        $this->configMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('getPaymentMethodsOffActive')
            ->with($storeId)
            ->willReturn(null);
 
        $this->mercadopagoConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();
        $this->mercadopagoConfigMock->expects($this->any())
            ->method('getMpPaymentMethods')
            ->with($storeId)
            ->willReturn(PaymentMethodsResponseMock::SUCCESS_FALSE);

        $this->configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock, 
            $this->cartMock, 
            $this->escaperMock, 
            $this->mercadopagoConfigMock,
            $this->assetRepoMock
        );

        $result = $this->configProviderPaymentMethodsOff->getPaymentMethodsOffActive($storeId);
        $this->assertEmpty($result);
    }
    
    public function test_getPaymentMethodsOffActive_not_empty(): void
    {
        $storeId = 1;

        $this->configMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('getPaymentMethodsOffActive')
            ->with($storeId)
            ->willReturn(null);
 
        $this->mercadopagoConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();
        $this->mercadopagoConfigMock->expects($this->any())
            ->method('getMpPaymentMethods')
            ->with($storeId)
            ->willReturn(PaymentMethodsResponseMock::WITH_PAYMENT_PLACES);

        $this->configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock, 
            $this->cartMock, 
            $this->escaperMock, 
            $this->mercadopagoConfigMock,
            $this->assetRepoMock
        );

        $result = $this->configProviderPaymentMethodsOff->getPaymentMethodsOffActive($storeId);
        $this->assertNotEmpty($result);
    }

    public function test_getLogo_empty(): void
    { 
        $this->assetRepoMock = $this->getMockBuilder(Repository::class)->disableOriginalConstructor()->getMock();
        $this->assetRepoMock->expects($this->any())
            ->method('getUrl')
            ->with(ConfigProviderPaymentMethodsOff::PATH_LOGO)
            ->willReturn(null);

        $this->configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock, 
            $this->cartMock, 
            $this->escaperMock, 
            $this->mercadopagoConfigMock,
            $this->assetRepoMock
        );

        $result = $this->configProviderPaymentMethodsOff->getLogo();
        $this->assertEmpty($result);
    }

    public function test_getLogo_not_empty(): void
    { 
        $this->assetRepoMock = $this->getMockBuilder(Repository::class)->disableOriginalConstructor()->getMock();
        $this->assetRepoMock->expects($this->any())
            ->method('getUrl')
            ->with(ConfigProviderPaymentMethodsOff::PATH_LOGO)
            ->willReturn('images/boleto/logo.svg');

        $this->configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock, 
            $this->cartMock, 
            $this->escaperMock, 
            $this->mercadopagoConfigMock,
            $this->assetRepoMock
        );

        $result = $this->configProviderPaymentMethodsOff->getLogo();

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('title', $result);
    }

    public function test_getConfig_empty(): void
    { 
        $storeId = 1;

        $this->cartMock = $this->getMockBuilder(CartInterface::class)->disableOriginalConstructor()->getMock();
        $this->cartMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->configMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('isActive')
            ->with($storeId)
            ->willReturn(false);

        $this->configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock, 
            $this->cartMock, 
            $this->escaperMock, 
            $this->mercadopagoConfigMock,
            $this->assetRepoMock
        );

        $result = $this->configProviderPaymentMethodsOff->getConfig();
        $this->assertEmpty($result);
    }

    public function test_getConfig_not_empty(): void
    { 
        $storeId = 2;

        $this->cartMock = $this->getMockBuilder(CartInterface::class)->disableOriginalConstructor()->getMock();
        $this->cartMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->configMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('isActive')
            ->with($storeId)
            ->willReturn(true);
        $this->configMock->expects($this->any())
            ->method('getTitle')
            ->with($storeId)
            ->willReturn('Title Payment');
        $this->configMock->expects($this->any())
            ->method('hasUseNameCapture')
            ->with($storeId)
            ->willReturn(true);
        $this->configMock->expects($this->any())
            ->method('hasUseDocumentIdentificationCapture')
            ->with($storeId)
            ->willReturn(true);
        $this->configMock->expects($this->any())
            ->method('getExpirationFormat')
            ->with($storeId)
            ->willReturn('d/m/Y');
        $this->configMock->expects($this->any())
            ->method('getPaymentMethodsOffActive')
            ->with($storeId)
            ->willReturn(null);

        $this->assetRepoMock = $this->getMockBuilder(Repository::class)->disableOriginalConstructor()->getMock();
        $this->assetRepoMock->expects($this->any())
            ->method('getUrl')
            ->with(ConfigProviderPaymentMethodsOff::PATH_LOGO)
            ->willReturn('images/boleto/logo.svg');
        
        $this->mercadopagoConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();
        $this->mercadopagoConfigMock->expects($this->any())
            ->method('getMpPaymentMethods')
            ->with($storeId)
            ->willReturn(PaymentMethodsResponseMock::WITH_PAYMENT_PLACES);

        $this->configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock, 
            $this->cartMock, 
            $this->escaperMock, 
            $this->mercadopagoConfigMock,
            $this->assetRepoMock
        );

        $result = $this->configProviderPaymentMethodsOff->getConfig();

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey(ConfigPaymentMethodsOff::METHOD, $result['payment']);
        $this->assertArrayHasKey('isActive', $result['payment'][ConfigPaymentMethodsOff::METHOD]);    
        $this->assertArrayHasKey('title', $result['payment'][ConfigPaymentMethodsOff::METHOD]);    
        $this->assertArrayHasKey('name_capture', $result['payment'][ConfigPaymentMethodsOff::METHOD]);    
        $this->assertArrayHasKey('document_identification_capture', $result['payment'][ConfigPaymentMethodsOff::METHOD]);    
        $this->assertArrayHasKey('expiration', $result['payment'][ConfigPaymentMethodsOff::METHOD]);    
        $this->assertArrayHasKey('logo', $result['payment'][ConfigPaymentMethodsOff::METHOD]);    
        $this->assertArrayHasKey('payment_methods_off_active', $result['payment'][ConfigPaymentMethodsOff::METHOD]);    
    }
}