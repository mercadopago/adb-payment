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
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use Magento\Store\Model\StoreManagerInterface;
use MercadoPago\AdbPayment\Model\Console\Command\Adminstrative\FetchMerchant;

/**
 * Gateway Requests for Additional Seller Data.
 */
class AdditionalInfoSellerDataRequest implements BuilderInterface
{
    /**
     * Seller block name.
     */
    public const SELLER = 'seller';

     /**
     * ID block name.
     */
    public const ID = 'id';

     /**
     * Registration Date block name.
     */
    public const REGISTRATION_DATE = 'registration_date';

     /**
     * Business Type block name.
     */
    public const BUSINESS_TYPE = 'business_type';

    /**
     * Status block name.
     */
    public const STATUS = 'status';

    /**
     * Store ID block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * User Platform mail block name.
     */
    public const USER_PLATFORM_MAIL = 'user_platform_mail';

    /**
     * Email block name.
     */
    public const EMAIL = 'email';

    /**
     * Collector block name.
     */
    public const COLLECTOR = 'collector';

    /**
     * Website block name.
     */
    public const WEBSITE = 'website';

    /**
     * Platform Url block name.
     */
    public const PLATFORM_URL = 'platform_url';

    /**
     * Referral URL block name.
     */
    public const REFERRAL_URL = 'referral_url';

    /**
     * Register Updated At block name.
     */
    public const REGISTER_UPDATED_AT = 'register_updated_at';

    /**
     * Document block name.
     */
    public const DOCUMENT = 'document';

    /**
     * Name block name.
     */
    public const NAME = 'name';

    /**
     * Hired Plan block name.
     */
    public const HIRED_PLAN = 'hired_plan';

    /**
     * Identification block name.
     */
    public const IDENTIFICATION = 'identification';

    /**
     * Type block name.
     */
    public const TYPE = 'type';

    /**
     * Number block name.
     */
    public const NUMBER = 'number';

    /**
     * Phone block name.
     */
    public const PHONE = 'phone';

    /**
     * Area Code block name.
     */
    public const AREA_CODE = 'area_code';

    /**
     * Address block name.
     */
    public const ADDRESS = 'address';

    /**
     * Zip Code block name.
     */
    public const ZIP_CODE = 'zip_code';

    /**
     * Street Name block name.
     */
    public const STREET_NAME = 'street_name';

    /**
     * City block name.
     */
    public const CITY = 'city';

    /**
     * Country block name.
     */
    public const COUNTRY = 'country';

    /**
     * State block name.
     */
    public const STATE = 'state';

    /**
     * Complement block name.
     */
    public const COMPLEMENT = 'complement';


    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var FetchMerchant
     */
    protected $fetchMerchant;

    /**
     * @param SubjectReader       $subjectReader
     * @param Config              $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        StoreManagerInterface $storeManager,
        FetchMerchant $fetchMerchant
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->fetchMerchant = $fetchMerchant;
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
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $userData = $this->getUserData($storeId);

        $user_email = $userData['email'];
        $arr = explode("@", $user_email, 2);
        $platform_email = $arr[1];

        $result = [];

        $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::SELLER] = [
            self::ID                    => $userData['id'],
            self::REGISTRATION_DATE     => $userData['registration_date'],
            self::BUSINESS_TYPE         => null,
            self::STATUS                => $userData['status'],
            self::STORE_ID              => $storeId,
            self::USER_PLATFORM_MAIL    => $platform_email,
            self::EMAIL                 => $user_email,
            self::COLLECTOR             => $userData['id'],
            self::WEBSITE               => $this->storeManager->getWebsite()->getName(),
            self::PLATFORM_URL          => $this->storeManager->getStore()->getBaseUrl(),
            self::REFERRAL_URL          => $this->storeManager->getStore()->getBaseUrl(),
            self::REGISTER_UPDATED_AT   => null,
            self::DOCUMENT              => $userData['identification_number'],
            self::NAME                  => $userData['name'],
            self::HIRED_PLAN            => null
        ];

        $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::SELLER][self::IDENTIFICATION] = [
            self::TYPE      => $userData['identification_type'],
            self::NUMBER    => $userData['identification_number']
        ];

        $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::SELLER][self::PHONE] = [
            self::AREA_CODE => $userData['phone_area'],
            self::NUMBER    => $userData['phone_number']
        ];

        $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::SELLER][self::ADDRESS] = [
            self::ZIP_CODE      => $userData['zip_code'],
            self::STREET_NAME   => $userData['street'],
            self::CITY          => $userData['city'],
            self::COUNTRY       => $userData['country'],
            self::STATE         => $userData['state'],
            self::COMPLEMENT    => null,
            self::NUMBER        => preg_replace('/[^0-9]/', '', $userData['street'])
        ];

        return $result;
    }

    /**
     * Get User Data.
     *
     * @param int  $storeId
     *
     * @return array
     */
    public function getUserData($storeId) {
        $usersMe = $this->fetchMerchant->getUsersMe($storeId);
        $registreData = null;

        if ($usersMe['success']) {
            $response = $usersMe['response'];
            $registreData = [
                'id'      => $response['id'],
                'email'   => $response['email'],
                'name'    => $response['first_name'].' '.$response['last_name'],
                'registration_date' => $response['registration_date'],
                'country' => $response['country_id'],
                'identification_number' => $response['identification']['number'],
                'identification_type' => $response['identification']['type'],
                'street' => (string) $response['address']['address'],
                'city' => $response['address']['city'],
                'state' => $response['address']['state'],
                'zip_code' => $response['address']['zip_code'],
                'phone_area' => $response['phone']['area_code'],
                'phone_number' => $response['phone']['number'],
                'status' => $response['status']['site_status']
            ];
        }

        return $registreData;
    }
}
