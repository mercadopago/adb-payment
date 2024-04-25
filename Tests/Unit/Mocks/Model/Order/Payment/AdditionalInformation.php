<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Mocks\Model\Order\Payment;

class AdditionalInformation
{
    public const ADDITIONAL_INFORMATION_DATA_CUSTOM_ONE_CARD =[
        "method_title" => "Credit or Debit Card",
        "mp_payment_id" => 66817921925,
        "mp_user_id" => "",
        "payer_document_type" => "Otro",
        "payer_document_identification" => "12345678",
        "card_number_token" => "",
        "card_holder_name" => "apro",
        "card_number" => "503175xxxxxx0604",
        "card_type" => "master",
        "card_exp_month" => "12",
        "card_exp_year" => "2025",
        "card_installments" => "1",
        "card_public_id" => "",
        "mp_status" => "approved",
        "mp_status_detail" => "acredited"
    ];

    public static function refundedCustomOneCard(): array {
        $data = self::ADDITIONAL_INFORMATION_DATA_CUSTOM_ONE_CARD;

        $data["mp_status"] = "refunded";
        $data["mp_status_detail"] = "refunded";
        
        return $data;
    }

    public const ADDITIONAL_INFORMATION_DATA_CUSTOM_TWO_CARDS =[
        'method_title' => 'Pay with two cards',
        'mp_payment_id' => 374003,
        'mp_status' => 'approved',
        'payment_0_id' => 69544213934,
        'payer_0_document_type' => 'Otro',
        'payer_0_document_identification' => '12345678',
        'card_0_number_token' => '',
        'card_0_holder_name' => 'apro',
        'card_0_number' => '378318xxxxxx2624',
        'card_0_type' => 'amex',
        'card_0_exp_month' => '12',
        'card_0_exp_year' => '2034',
        'card_0_installments' => '1',
        'card_0_finance_cost' => '',
        'card_0_public_id' => '',
        'card_0_amount' => '32.00',
        'mp_0_user_id' => '',
        'mp_0_status' => 'approved',
        'mp_0_status_detail' => 'accredited',
        'payment_1_id' => 69348880321,
        'payer_1_document_type' => 'Otro',
        'payer_1_document_identification' => '12345678',
        'card_1_number_token' => '',
        'card_1_holder_name' => 'apro',
        'card_1_number' => '376637xxxxxx7865',
        'card_1_type' => 'amex',
        'card_1_exp_month' => '12',
        'card_1_exp_year' => '2034',
        'card_1_installments' => '1',
        'card_1_finance_cost' => '',
        'card_1_public_id' => '',
        'card_1_amount' => '32.00',
        'mp_1_user_id' => '',
        'mp_1_status' => 'approved',
        'mp_1_status_detail' => 'accredited',
    ];

    public static function refundedCustomTwoCards(): array {
        $data = self::ADDITIONAL_INFORMATION_DATA_CUSTOM_TWO_CARDS;

        $data["mp_status"] = "refunded";
        $data["mp_0_status"] = "refunded";
        $data["mp_0_status_detail"] = "refunded";
        $data["mp_1_status"] = "refunded";
        $data["mp_1_status_detail"] = "refunded";
        
        return $data;
    }
    
    public const ADDITIONAL_INFORMATION_DATA_CUSTOM_TICKET =[
        'payment_method_id' => 'pagofacil',
        'payer_document_type' => 'Otro',
        'payer_document_identification' => '12345678',
        'payment_type_id' => 'ticket',
        'method_title' => 'Ticket - MercadoPago',
        'mp_payment_id' => 66817921925,
        'mp_status' => 'approved',
        'mp_status_detail' => 'accredited',
        'barcode' => '',
        'date_of_expiration' => '',
        'financial_institution' => '',
        'external_resource_url' => '',
        'verification_code' => '',
        'message_info' => 'Generate the ticket and pay it wherever you want.',
        'payment_0_id' => 66817921925,
        'payment_0_type' => 'pagofacil',
        'payment_0_total_amount' => 112,
        'payment_0_paid_amount' => 112,
        'payment_0_refunded_amount' => 0,
        'payment_0_card_number' => '',
        'payment_0_installments' => 1,
        'mp_0_status' => 'approved',
        'mp_0_status_detail' => 'accredited',
        'payment_0_expiration' => '',
        'payment_index_list' => [
            0 => 0,
        ],
    ];

    public static function refundedCustomTicket(): array {
        $data = self::ADDITIONAL_INFORMATION_DATA_CUSTOM_TICKET;

        $data["mp_status"] = "refunded";
        $data["mp_status_detail"] = "refunded";
        $data["mp_0_status"] = "refunded";
        $data["mp_0_status_detail"] = "refunded";
        
        return $data;
    }

    public const ADDITIONAL_INFORMATION_DATA_PRO_ACCOUNT_MONEY =[
        'method_title' => 'Checkout Pro',
        'date_of_expiration' => '',
        'init_point' => '',
        'id' => '',
        'payment_0_id' => 69496770722,
        'payment_0_type' => 'account_money',
        'payment_0_total_amount' => 64,
        'payment_0_paid_amount' => 64,
        'payment_0_refunded_amount' => 0,
        'payment_0_card_number' => '',
        'payment_0_installments' => 1,
        'mp_0_status' => 'approved',
        'mp_0_status_detail' => 'accredited',
        'payment_0_expiration' => '',
        'payment_index_list' => [
            0 => 0,
        ],
        'mp_status' => 'approved',
        'mp_status_detail' => 'accredited',
    ];
    public static function refundedProAccountMoney()
    {
        $data = self::ADDITIONAL_INFORMATION_DATA_PRO_ACCOUNT_MONEY;

        $data['mp_0_status'] = 'refunded';
        $data['mp_0_status_detail'] = 'refunded';

        return $data;
    }

    public const ADDITIONAL_INFORMATION_DATA_PRO_CREDITS =[
        'method_title' => 'Checkout Pro',
        'date_of_expiration' => '',
        'init_point' => '',
        'id' => '',
        'payment_0_id' => 69297067573,
        'payment_0_type' => 'consumer_credits',
        'payment_0_total_amount' => 64,
        'payment_0_paid_amount' => 64,
        'payment_0_refunded_amount' => 0,
        'payment_0_card_number' => '',
        'payment_0_installments' => 1,
        'mp_0_status' => 'approved',
        'mp_0_status_detail' => 'accredited',
        'payment_0_expiration' => '',
        'payment_index_list' => [
            0 => 0,
        ],
        'mp_status' => 'approved',
        'mp_status_detail' => 'accredited',
    ];
    
    public static function refundedProCredits()
    {
        $data = self::ADDITIONAL_INFORMATION_DATA_PRO_CREDITS;

        $data['mp_status'] = 'refunded';
        $data['mp_status_detail'] = 'refunded';
        $data['mp_0_status'] = 'refunded';
        $data['mp_0_status_detail'] = 'refunded';

        return $data;
    }

    public const ADDITIONAL_INFORMATION_DATA_PRO_ONE_CARD =[
        'method_title' => 'Checkout Pro',
        'date_of_expiration' => '',
        'init_point' => '',
        'id' => '',
        'payment_0_id' => 69496770722,
        'payment_0_type' => 'debvisa',
        'payment_0_total_amount' => 112,
        'payment_0_paid_amount' => 112,
        'payment_0_refunded_amount' => 0,
        'payment_0_card_number' => '5619',
        'payment_0_installments' => 1,
        'mp_0_status' => 'approved',
        'mp_0_status_detail' => 'accredited',
        'payment_0_expiration' => '',
        'payment_index_list' => [
            0 => 0,
        ],
        'mp_status' => 'approved',
        'mp_status_detail' => 'accredited',
    ];

    public static function refundedProOneCard()
    {
        $data = self::ADDITIONAL_INFORMATION_DATA_PRO_ONE_CARD;

        $data['mp_0_status'] = 'refunded';
        $data['mp_0_status_detail'] = 'refunded';

        return $data;
    }

    public const ADDITIONAL_INFORMATION_DATA_PRO_TWO_CARDS =[
        'method_title' => 'Checkout Pro',
        'date_of_expiration' => '',
        'init_point' => '',
        'id' => '',
        'payment_0_id' => 69544213934,
        'payment_0_type' => 'amex',
        'payment_0_total_amount' => 32,
        'payment_0_paid_amount' => 32,
        'payment_0_refunded_amount' => 6,
        'payment_0_card_number' => '2624',
        'payment_0_installments' => 1,
        'mp_0_status' => 'approved',
        'mp_0_status_detail' => 'accredited',
        'payment_0_expiration' => '',
        'payment_1_id' => 69348880321,
        'payment_1_type' => 'amex',
        'payment_1_total_amount' => 32,
        'payment_1_paid_amount' => 32,
        'payment_1_refunded_amount' => 0,
        'payment_1_card_number' => '7865',
        'payment_1_installments' => 1,
        'mp_1_status' => 'approved',
        'mp_1_status_detail' => 'accredited',
        'payment_1_expiration' => '',
        'payment_index_list' => [
            0 => 0,
            1 => 1,
        ],
        'mp_status' => 'approved',
        'mp_status_detail' => 'accredited',
    ];

    public static function refundedProTwoCards()
    {
        $data = self::ADDITIONAL_INFORMATION_DATA_PRO_TWO_CARDS;

        $data['mp_0_status'] = 'refunded';
        $data['mp_0_status_detail'] = 'refunded';
        $data['mp_1_status'] = 'refunded';
        $data['mp_1_status_detail'] = 'refunded';

        return $data;
    }

    public const ADDITIONAL_INFORMATION_DATA_PRO_TICKET =[
        'method_title' => 'Checkout Pro',
        'date_of_expiration' => '',
        'init_point' => '',
        'id' => '',
        'payment_0_id' => 69496770722,
        'payment_0_type' => 'rapipago',
        'payment_0_total_amount' => 112,
        'payment_0_paid_amount' => 112,
        'payment_0_refunded_amount' => 0,
        'payment_0_card_number' => '',
        'payment_0_installments' => 1,
        'mp_0_status' => 'approved',
        'mp_0_status_detail' => 'accredited',
        'payment_0_expiration' => '',
        'payment_index_list' => [
            0 => 0,
        ],
        'mp_status' => 'approved',
        'mp_status_detail' => 'accredited',
    ];

    public static function refundedProTicket()
    {
        $data = self::ADDITIONAL_INFORMATION_DATA_PRO_TICKET;

        $data['mp_0_status'] = 'refunded';
        $data['mp_0_status_detail'] = 'refunded';

        return $data;
    }
}