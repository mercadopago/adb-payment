<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use MercadoPago\AdbPayment\Gateway\Request\DocumentIdentificationDataRequest;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\CustomerFactory;

/**
 * Gateway Requests for Additional Payer Details Data.
 */
class AdditionalInfoPayerDataRequest implements BuilderInterface
{
    /**
     * Payer block name.
     */
    public const PAYER = 'payer';

    /**
     * First Name block name.
     */
    public const FIRST_NAME = 'first_name';

    /**
     * Last Name block name.
     */
    public const LAST_NAME = 'last_name';

    /**
     * Address block name.
     */
    public const ADDRESS = 'address';

    /**
     * Street Name address block name.
     */
    public const STREET_NAME = 'street_name';

    /**
     * Street Number address block name.
     */
    public const STREET_NUMBER = 'street_number';

    /**
     * Number address block name.
     */
    public const NUMBER = 'number';

    /**
     * Street Complement address block name.
     */
    public const STREET_COMPLEMENT = 'complement';

    /**
     * Street Neighborhood address block name.
     */
    public const STREET_NEIGHBORHOOD = 'neighborhood';

    /**
     * City address block name.
     */
    public const CITY = 'city';

     /**
     * State address block name.
     */
    public const STATE = 'state';

    /**
     * Country address block name.
     */
    public const COUNTRY = 'country';

    /**
     * Federal Unit address block name.
     */
    public const FEDERAL_UNIT = 'federal_unit';

    /**
     * Zip Code address block name.
     */
    public const ZIP_CODE = 'zip_code';

    /**
     * Phone block name.
     */
    public const PHONE = 'phone';

     /**
     * Mobile block name.
     */
    public const MOBILE = 'mobile';

    /**
     * Phone Area Code block name.
     */
    public const PHONE_AREA_CODE = 'area_code';

    /**
     * Phone Number block name.
     */
    public const PHONE_NUMBER = 'number';

    /**
     * Identification block name.
     */
    public const IDENTIFICATION = 'identification';

    /**
     * Type identification block name.
     */
    public const TYPE_IDENTIFICATION = 'type';

     /**
     * Number identification block name.
     */
    public const NUMBER_IDENTIFICATION = 'number';

    /**
     * Registration date block name.
     */
    public const REGISTRATION_DATE = 'registration_date';

    /**
     * Registration user block name.
     */
    public const REGISTERED_USER = 'registered_user';

     /**
     * Device id block name.
     */
    public const DEVICE_ID = 'device_id';

     /**
     * Platform email block name.
     */
    public const PLATFORM_EMAIL = 'platform_email';

     /**
     * Register updated at block name.
     */
    public const REGISTER_UPDATED_AT = 'register_updated_at';

     /**
     * User email block name.
     */
    public const USER_EMAIL = 'user_email';

     /**
     * Authentication type block name.
     */
    public const AUTHENTICATION_TYPE = 'authentication_type';

    /**
     * Last purchase block name.
     */
    public const LAST_PURCHASE = 'last_purchase';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @var DocumentIdentificationDataRequest
     */
    protected $documentIdentification;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @param SubjectReader       $subjectReader
     * @param Config              $config
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param DocumentIdentificationDataRequest $documentIdentificationDataRequest
     * @param CustomerSession $customerSession
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        OrderAdapterFactory $orderAdapterFactory,
        DocumentIdentificationDataRequest $documentIdentificationDataRequest,
        CustomerSession $customerSession,
        CustomerFactory $customerFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->documentIdentification = $documentIdentificationDataRequest;
        $this->customerSession = $customerSession;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject): array
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $result = [];

        /** @var OrderAdapterFactory $orderAdapter */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $typeDocument = $payment->getAdditionalInformation('payer_document_type');
       
        $docIdentification = $this->documentIdentification->getFiscalNumber($payment, $orderAdapter);
        if ($docIdentification) {
            $docIdentification = preg_replace('/[^0-9A-Za-z]/', '', $docIdentification);
        }

        $billingAddress = $orderAdapter->getBillingAddress();

        $customerId = $this->customerSession->getCustomerId();
        $lastPurchaseDate = null;
        $registrationDate = null;

        if ($customerId) {
            // Load customer model by customer ID
            $customer = $this->customerFactory->create()->load($customerId);
            
            $registrationDate = $customer->getCreatedAt();

            // Get last order of the customer
            $lastOrder = $customer->getLastOrder();
            
            if ($lastOrder) {
                // Get last order's creation date
                $lastPurchaseDate = $lastOrder->getCreatedAt();
            }
        }

        if ($billingAddress) {
            $phone = preg_replace('/[^0-9]/', '', $billingAddress->getTelephone());
            $phoneAreaCode = substr($phone, 0, 2);
            $phoneNumber = substr($phone, 2);

            $user_email = $billingAddress->getEmail();
            $arr = explode("@", $user_email, 2);
            $platform_email = $arr[1];


            // Rewrite Payer from payment form
            $payerFirstName =
                $payment->getAdditionalInformation('payer_first_name') ?: $billingAddress->getFirstname();
            $payerLastName =
                $payment->getAdditionalInformation('payer_last_name') ?: $billingAddress->getLastname();

            $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::PAYER] = [
                self::FIRST_NAME           => $payerFirstName,
                self::LAST_NAME            => $payerLastName,
                self::REGISTRATION_DATE    => $registrationDate,
                self::REGISTERED_USER      => $customerId ? true : false,
                self::DEVICE_ID            => null, 
                self::PLATFORM_EMAIL       => $platform_email, 
                self::REGISTER_UPDATED_AT  => null,
                self::USER_EMAIL           => $user_email,
                self::AUTHENTICATION_TYPE  => null,
                self::LAST_PURCHASE        => $lastPurchaseDate,
            ];


            $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::PAYER][self::ADDRESS] = [
                self::ZIP_CODE              => preg_replace('/[^0-9]/', '', (string)$billingAddress->getPostcode()),
                self::STREET_NAME           => $this->config->getValueForAddress(
                    $billingAddress,
                    self::STREET_NAME
                ),
                self::NUMBER         => (int) $this->config->getValueForAddress(
                    $billingAddress,
                    self::STREET_NUMBER
                ),
                self::COUNTRY => $billingAddress->getCountryId(),
                self::STREET_COMPLEMENT => $this->config->getValueForAddress(
                    $billingAddress,
                    self::STREET_COMPLEMENT
                ),
                self::CITY => $billingAddress->getCity(),
                self::STATE => $billingAddress->getRegionCode()
            ];

            $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::PAYER][self::PHONE] = [
                self::PHONE_AREA_CODE => $phoneAreaCode,
                self::PHONE_NUMBER    => $phoneNumber,
            ];

            $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::PAYER][self::MOBILE] = [
                self::PHONE_AREA_CODE => $phoneAreaCode, 
                self::PHONE_NUMBER    => $phoneNumber, 
            ];

            $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::PAYER][self::IDENTIFICATION] = [
                self::TYPE_IDENTIFICATION => $typeDocument,
                self::NUMBER_IDENTIFICATION => $docIdentification,
            ];
        }

        return $result;
    }
}
