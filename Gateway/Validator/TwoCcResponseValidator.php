<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use MercadoPago\PaymentMagento\Gateway\SubjectReader;

/**
 * General Response Validation.
 */
class TwoCcResponseValidator extends AbstractValidator
{
    /**
     * The result code.
     */
    public const RESULT_CODE_SUCCESS = '1';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader          $subjectReader
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
    }

    /**
     * Validate.
     *
     * @param array $validationSubject
     *
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = $this->subjectReader->readResponse($validationSubject);
        $isValid = $response['RESULT_CODE'];
        $errorCodes = [];
        $errorMessages = [];
        if (!$isValid) {

            // error in payments

            if (isset($response['status_detail'])) {
                $errorCodes[] = $response['status_detail'];
                $errorMessages[] = 'rejected';
            }
            if (isset($response['cause'])) {
                foreach ($response['cause'] as $cause) {
                    $errorCodes[] = $cause['code'];
                    $errorMessages[] = $cause['description'];
                }
            }
        }

        return $this->createResult($isValid, $errorMessages, $errorCodes);
    }
}
