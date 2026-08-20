<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Validator;

use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use MercadoPago\AdbPayment\Gateway\Validator\GeneralResponseValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GeneralResponseValidatorTest extends TestCase
{
    /**
     * @var GeneralResponseValidator
     */
    private $validator;

    /**
     * @var ResultInterfaceFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var SubjectReader|MockObject
     */
    private $subjectReader;

    /**
     * @var array Arguments captured from the last resultFactory->create() call.
     */
    private $created;

    protected function setUp(): void
    {
        $this->created = [];

        $this->subjectReader = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['readResponse'])
            ->getMock();

        $resultMock = $this->createMock(ResultInterface::class);

        $this->resultFactory = $this->getMockBuilder(ResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        // Capture the createResult() arguments so we can assert exactly what the
        // validator built (isValid, failsDescription, errorCodes).
        $this->resultFactory->method('create')->willReturnCallback(
            function (array $args) use ($resultMock) {
                $this->created = $args;
                return $resultMock;
            }
        );

        $this->validator = new GeneralResponseValidator(
            $this->resultFactory,
            $this->subjectReader
        );
    }

    /**
     * Drive the validator with a given MP response payload.
     *
     * @param array $response
     * @return void
     */
    private function validateResponse(array $response): void
    {
        $this->subjectReader->method('readResponse')->willReturn($response);
        $this->validator->validate(['response' => $response]);
    }

    /**
     * bin_not_found (code 10105) must surface as an error code + description so the
     * downstream error mapper can translate it. This is the PSW-3964 scenario.
     */
    public function testValidateSurfacesBinNotFoundCause(): void
    {
        $this->validateResponse([
            'RESULT_CODE' => 0,
            'cause'       => [
                ['code' => 10105, 'description' => 'bin_not_found'],
            ],
        ]);

        $this->assertFalse($this->created['isValid']);
        $this->assertContains(10105, $this->created['errorCodes']);
        $this->assertContains('bin_not_found', $this->created['failsDescription']);
    }

    /**
     * A rejected payment (status_detail present) is surfaced as a 'rejected' description
     * keyed by the status_detail code.
     */
    public function testValidateSurfacesStatusDetailRejection(): void
    {
        $this->validateResponse([
            'RESULT_CODE'   => 0,
            'status_detail' => 'cc_rejected_bad_filled_card_number',
        ]);

        $this->assertFalse($this->created['isValid']);
        $this->assertContains('cc_rejected_bad_filled_card_number', $this->created['errorCodes']);
        $this->assertContains('rejected', $this->created['failsDescription']);
    }

    /**
     * status_detail and cause are both surfaced when present in the same response.
     */
    public function testValidateSurfacesStatusDetailAndCauseTogether(): void
    {
        $this->validateResponse([
            'RESULT_CODE'   => 0,
            'status_detail' => 'rejected_high_risk',
            'cause'         => [
                ['code' => 10105, 'description' => 'bin_not_found'],
            ],
        ]);

        $this->assertFalse($this->created['isValid']);
        $this->assertSame(['rejected_high_risk', 10105], $this->created['errorCodes']);
        $this->assertSame(['rejected', 'bin_not_found'], $this->created['failsDescription']);
    }

    /**
     * Defensive XML fallback path (PSW-3989 / Flow A): if the API ever switches from
     * HTTP 400 to status: rejected (HTTP 200) for INVALID_USER_IDENTIFICATION_NUMBER,
     * GeneralResponseValidator must surface the status_detail and cause code 2067 so
     * the XML error mapper can pick them up. The primary fix (Flow B / stripos in
     * CreateOrderPaymentCustomClient) is tested in CreateOrderPaymentCustomClientTest.
     */
    public function testValidateSurfacesInvalidUserIdentificationNumber(): void
    {
        $this->validateResponse([
            'RESULT_CODE'   => 0,
            'status_detail' => 'invalid_user_identification_number',
            'cause'         => [
                ['code' => 2067, 'description' => 'INVALID_USER_IDENTIFICATION_NUMBER'],
            ],
        ]);

        $this->assertFalse($this->created['isValid']);
        $this->assertContains('invalid_user_identification_number', $this->created['errorCodes']);
        $this->assertContains(2067, $this->created['errorCodes']);
        $this->assertContains('rejected', $this->created['failsDescription']);
    }

    /**
     * Multiple causes are all surfaced in order.
     */
    public function testValidateSurfacesMultipleCauses(): void
    {
        $this->validateResponse([
            'RESULT_CODE' => 0,
            'cause'       => [
                ['code' => 10105, 'description' => 'bin_not_found'],
                ['code' => 3034, 'description' => 'invalid_card_number'],
            ],
        ]);

        $this->assertSame([10105, 3034], $this->created['errorCodes']);
        $this->assertSame(['bin_not_found', 'invalid_card_number'], $this->created['failsDescription']);
    }

    /**
     * An approved response (RESULT_CODE = 1) is valid and carries no errors.
     */
    public function testValidateApprovedResponseHasNoErrors(): void
    {
        $this->validateResponse([
            'RESULT_CODE' => 1,
        ]);

        $this->assertTrue($this->created['isValid']);
        $this->assertSame([], $this->created['errorCodes']);
        $this->assertSame([], $this->created['failsDescription']);
    }

    /**
     * A valid response (RESULT_CODE = 1) suppresses any status_detail/cause present —
     * errors are surfaced only when the payment is invalid. Regression guard for the
     * `if (!$isValid)` gate in validate().
     */
    public function testValidateApprovedResponseSuppressesStatusDetailAndCause(): void
    {
        $this->validateResponse([
            'RESULT_CODE'   => 1,
            'status_detail' => 'accredited',
            'cause'         => [['code' => 10105, 'description' => 'bin_not_found']],
        ]);

        $this->assertTrue($this->created['isValid']);
        $this->assertSame([], $this->created['errorCodes']);
        $this->assertSame([], $this->created['failsDescription']);
    }
}
