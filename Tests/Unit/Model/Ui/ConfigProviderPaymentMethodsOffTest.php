<?php

namespace Tests\Unit\Model\Ui;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Tests\Unit\Mocks\Model\Ui\ConfigProviderPaymentMethodsOff\MountPaymentMethodsOffMock;
use MercadoPago\AdbPayment\Tests\Unit\Mocks\Model\Ui\ConfigProviderPaymentMethodsOff\FilterPaymentMethodsOffConfigActiveMock;
use MercadoPago\AdbPayment\Tests\Unit\Mocks\Gateway\Config\PaymentMethodsResponseMock;

use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Repository;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;

use MercadoPago\AdbPayment\Model\Ui\ConfigProviderPaymentMethodsOff;

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
    private $mpConfigMock;

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
        $this->mpConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();

        $this->configProviderPaymentMethodsOffMock = $this->getMockBuilder(ConfigProviderPaymentMethodsOff::class)->setConstructorArgs([
            'config' => $this->configMock,
            'cart' => $this->cartMock,
            'escaper' => $this->escaperMock,
            'mercadopagoConfig' => $this->mpConfigMock,
            'assetRepo' => $this->assetRepoMock
        ])->getMock();

        $this->configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock,
            $this->cartMock,
            $this->escaperMock,
            $this->mpConfigMock,
            $this->assetRepoMock
        );
    }

    /**
     * Tests function mountPaymentMethodsOff()
     */

    public function testMountPaymentMethodsOffEmpty(): void
    {
        $result = $this->configProviderPaymentMethodsOff->mountPaymentMethodsOff([]);

        $this->assertEmpty($result);
    }

    public function testMountPaymentMethodsOffWithoutPaymentPlaces(): void
    {
        $response = PaymentMethodsResponseMock::WITHOUT_PAYMENT_PLACES['response'];
        $result = $this->configProviderPaymentMethodsOff->mountPaymentMethodsOff($response);

        $this->assertEquals(MountPaymentMethodsOffMock::EXPECTED_WITHOUT_PAYMENT_PLACES, $result);
        foreach ($result as $payment) {
            $this->assertTrue(in_array($payment['payment_type_id'], ConfigProviderPaymentMethodsOff::PAYMENT_TYPE_ID_ALLOWED));
        }
    }

    public function testMountPaymentMethodsOffWithPaymentPlaces(): void
    {
        $response = PaymentMethodsResponseMock::WITH_PAYMENT_PLACES['response'];
        $result = $this->configProviderPaymentMethodsOff->mountPaymentMethodsOff($response);

        $this->assertEquals(MountPaymentMethodsOffMock::EXPECTED_WITH_PAYMENT_PLACES, $result);
        foreach ($result as $payment) {
            $this->assertTrue(in_array($payment['payment_type_id'], ConfigProviderPaymentMethodsOff::PAYMENT_TYPE_ID_ALLOWED));
        }
    }

    public function testMountPaymentMethodsOffWithoutPaymentPlacesAndWithInactive(): void
    {
        $response = PaymentMethodsResponseMock::WITHOUT_PAYMENT_PLACES_AND_WITH_INACTIVE['response'];
        $result = $this->configProviderPaymentMethodsOff->mountPaymentMethodsOff($response);

        $this->assertEquals(MountPaymentMethodsOffMock::EXPECTED_WITHOUT_PAYMENT_PLACES_AND_WITH_INACTIVE, $result);
        foreach ($result as $payment) {
            $this->assertTrue(in_array($payment['payment_type_id'], ConfigProviderPaymentMethodsOff::PAYMENT_TYPE_ID_ALLOWED));
        }
    }

    public function testMountPaymentMethodsOffWithPaymentPlacesAndInactive(): void
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

    public function testFilterPaymentMethodsOffConfigActiveEmpty(): void
    {
        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive([], '');

        $this->assertEmpty($result);
    }

    public function testFilterPaymentMethodsOffConfigActivePaymentMethodsEmpty(): void
    {
        $methodsOffActive = '7eleven,serfin';

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive([], $methodsOffActive);

        $this->assertEmpty($result);
    }

    public function testFilterPaymentMethodsOffConfigActivePaymentMethodsOffActiveNull(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, null);

        $this->assertEquals($paymentMethods, $result);
    }

    public function testFilterPaymentMethodsOffConfigActivePaymentMethodsOffActiveEmpty(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, '');

        $this->assertEquals($paymentMethods, $result);
    }

    public function testFilterPaymentMethodsOffConfigActivePaymentMethodsOffInactive7eleven(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;
        $methodsOffInactive = '7eleven';

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, $methodsOffInactive);
        $this->assertEquals(FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS_WITHOUT_7ELEVEN, $result);
    }

    public function testFilterPaymentMethodsOffConfigActivePaymentMethodsOffInactive7elevenSerfin(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;
        $methodsOffInactive = '7eleven,serfin';

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, $methodsOffInactive);
        $this->assertEquals(FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS_WITHOUT_7ELEVEN_AND_SERFIN, $result);
    }

    public function testFilterPaymentMethodsOffConfigActivePaymentMethodsOffActiveDoesNotExist(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;
        $methodsOffActive = 'does_not_exist';

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, $methodsOffActive);
        $this->assertEquals(FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS, $result);
    }

    public function testFilterPaymentMethodsOffConfigActivePaymentMethodsOffInactiveWhitout7elevenAndDoesNotExist(): void
    {
        $paymentMethods = FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS;
        $methodsOffInactive = '7eleven,does_not_exist';

        $result = $this->configProviderPaymentMethodsOff->filterPaymentMethodsOffConfigActive($paymentMethods, $methodsOffInactive);
        $this->assertEquals(FilterPaymentMethodsOffConfigActiveMock::EXPECTED_PAYMENT_METHODS_WITHOUT_7ELEVEN, $result);
    }

    /**
     * Tests function getPaymentMethodsOffActive()
    */

    public function testGetPaymentMethodsOffActiveEmpty(): void
    {
        $storeId = 1;

        $this->configMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('getPaymentMethodsOffActive')
            ->with($storeId)
            ->willReturn(null);

        $this->mpConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();
        $this->mpConfigMock->expects($this->any())
            ->method('getMpPaymentMethods')
            ->with($storeId)
            ->willReturn(PaymentMethodsResponseMock::SUCCESS_FALSE);

        $this->configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock,
            $this->cartMock,
            $this->escaperMock,
            $this->mpConfigMock,
            $this->assetRepoMock
        );

        $result = $this->configProviderPaymentMethodsOff->getPaymentMethodsOffActive($storeId);
        $this->assertEmpty($result);
    }

    public function testGetPaymentMethodsOffActiveNotEmpty(): void
    {
        $storeId = 1;

        $this->configMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('getPaymentMethodsOffActive')
            ->with($storeId)
            ->willReturn(null);

        $this->mpConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();
        $this->mpConfigMock->expects($this->any())
            ->method('getMpPaymentMethods')
            ->with($storeId)
            ->willReturn(PaymentMethodsResponseMock::WITH_PAYMENT_PLACES);

        $this->configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock,
            $this->cartMock,
            $this->escaperMock,
            $this->mpConfigMock,
            $this->assetRepoMock
        );

        $result = $this->configProviderPaymentMethodsOff->getPaymentMethodsOffActive($storeId);
        $this->assertNotEmpty($result);
    }

    public function testGetLogoEmpty(): void
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
            $this->mpConfigMock,
            $this->assetRepoMock
        );

        $result = $this->configProviderPaymentMethodsOff->getLogo();
        $this->assertEmpty($result);
    }

    public function testGetLogoNotEmpty(): void
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
            $this->mpConfigMock,
            $this->assetRepoMock
        );

        $result = $this->configProviderPaymentMethodsOff->getLogo();

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('title', $result);
    }

    public function testGetConfigEmpty(): void
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
            $this->mpConfigMock,
            $this->assetRepoMock
        );

        $result = $this->configProviderPaymentMethodsOff->getConfig();
        $this->assertEmpty($result);
    }

    public function testGetConfigNotEmpty(): void
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

        $this->mpConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();
        $this->mpConfigMock->expects($this->any())
            ->method('getMpPaymentMethods')
            ->with($storeId)
            ->willReturn(PaymentMethodsResponseMock::WITH_PAYMENT_PLACES);

        $this->configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock,
            $this->cartMock,
            $this->escaperMock,
            $this->mpConfigMock,
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
