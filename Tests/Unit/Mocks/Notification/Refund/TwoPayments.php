<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Mocks\Notification\Refund;

class TwoPayments
{
    private const CUSTOM_TWO_PAYMENT_DATA = [
      "notification_id" => "TP-69544213934",
      "notification_url" => "https =>//albalmeida.ppolimpo.io/mp/notification/checkoutcustom/",
      "status" => "approved",
      "transaction_id" => "69544213934",
      "transaction_type" => "transaction_Payment",
      "preference_id" => "",
      "external_reference" => "000000186",
      "multiple_payment_transaction_id" => "372007",
      "transaction_amount" => 64.0,
      "total_pending" => 0.0,
      "total_approved" => 64.0,
      "total_paid" => 64.0,
      "total_rejected" => 0.0,
      "total_refunded" => 0.0,
      "total_cancelled" => 0.0,
      "total_charged_back" => 0.0,
      "payments_metadata" => [
        "checkout" => "custom",
        "checkout_type" => "credit_card",
        "cpp_extra" => [
          "checkout" => "custom",
          "checkout_type" => "credit_card",
          "module_version" => "1.5.0",
          "platform" => "BP1EF6QIC4P001KBGQ10",
          "platform_version" => "2.4.6-p3",
          "site_id" => "MLA",
          "sponsor_id" => 222568987,
          "store_id" => 1,
          "test_mode" => false
        ],
        "module_version" => "1.5.0",
        "original_notification_url" => "https =>//albalmeida.ppolimpo.io/mp/notification/checkoutcustom/",
        "platform" => "BP1EF6QIC4P001KBGQ10",
        "platform_version" => "2.4.6-p3",
        "site_id" => "MLA",
        "sponsor_id" => 222568987,
        "store_id" => 1,
        "test_mode" => false
      ],
      "payments_details" => [
        [
          "id" => 69348880321,
          "status" => "approved",
          "status_detail" => "accredited",
          "payment_type_id" => "credit_card",
          "payment_method_id" => "amex",
          "total_amount" => 32.0,
          "paid_amount" => 32.0,
          "coupon_amount" => 0.0,
          "shipping_cost" => 0.0,
          "refunds" => [],
          "payment_method_info" => [
            "barcode_content" => "",
            "external_resource_url" => "",
            "payment_method_reference_id" => "",
            "date_of_expiration" => "",
            "installments" => 1,
            "installment_rate" => 0.0,
            "last_four_digits" => "7865",
            "installment_amount" => 32.0
          ]
        ],
        [
          "id" => 69544213934,
          "status" => "approved",
          "status_detail" => "accredited",
          "payment_type_id" => "credit_card",
          "payment_method_id" => "amex",
          "total_amount" => 32.0,
          "paid_amount" => 32.0,
          "coupon_amount" => 0.0,
          "shipping_cost" => 0.0,
          "refunds" => [],
          "payment_method_info" => [
            "barcode_content" => "",
            "external_resource_url" => "",
            "payment_method_reference_id" => "",
            "date_of_expiration" => "",
            "installments" => 1,
            "installment_rate" => 0.0,
            "last_four_digits" => "2624",
            "installment_amount" => 32.0
          ]
        ]
      ]
    ];

    private static function proTwoCard(): array {
      $data = self::CUSTOM_TWO_PAYMENT_DATA;

      $data['notification_id'] = 'M-14427236363';
      $data['notification_url'] = 'https://albalmeida.ppolimpo.io/mp/notification/checkoutpro/';
      $data['transaction_id'] = '14427236363';
      $data['transaction_type'] = 'merchant_order';
      $data['preference_id'] = '001';
      $data['external_reference'] = '000000176';
      $data['multiple_payment_transaction_id'] = '';
      $data['payments_metadata']['checkout'] = 'pro';
      $data['payments_metadata']['checkout_type'] = 'modal';
      $data['payments_metadata']['cpp_extra']['checkout'] = 'pro';
      $data['payments_metadata']['cpp_extra']['checkout_type'] = 'modal';
      $data['payments_metadata']['cpp_extra']['original_notification_url'] = 'https://albalmeida.ppolimpo.io/mp/notification/checkoutpro/';
        
      return $data;
    }

    public static function customTwoCardRefunded(): array {
      $data = self::CUSTOM_TWO_PAYMENT_DATA;

      $data['notification_id'] = 'TP-68569252337';
      $data['status'] = 'refunded';
      $data['total_refunded'] = 64.0;
      $data['payments_details'][0]['status'] = 'refunded';
      $data['payments_details'][0]['status_detail'] = 'by_admin';
      $data['payments_details'][0]['refunds']['1587248514']['status'] = 'approved';
      $data['payments_details'][0]['refunds']['1587248514']['notifying'] = true;
      $data['payments_details'][0]['refunds']['1587248514']['metadata']['status_detail'] = null;
      $data['payments_details'][0]['refunds']['1587248514']['metadata']['origem'] = 'magento';
      $data['payments_details'][1]['status'] = 'refunded';      
      $data['payments_details'][1]['status_detail'] = 'by_admin';
      $data['payments_details'][1]['refunds']['1587250176']['status'] = 'approved';
      $data['payments_details'][1]['refunds']['1587250176']['notifying'] = true;
      $data['payments_details'][1]['refunds']['1587250176']['metadata']['status_detail'] = null;
      $data['payments_details'][1]['refunds']['1587250176']['metadata']['origem'] = 'magento';

      return $data;
    }

    public static function proTwoCardRefunded(): array {
      $data = self::proTwoCard();

      $data['notification_id'] = 'M-14427236363';
      $data['status'] = 'refunded';
      $data['total_refunded'] = 64.0;
      $data['payments_details'][0]['status'] = 'refunded';
      $data['payments_details'][0]['status_detail'] = 'refunded';
      $data['payments_details'][0]['refunds']['1574881669']['status'] = 'approved';
      $data['payments_details'][0]['refunds']['1574881669']['notifying'] = true;
      $data['payments_details'][0]['refunds']['1574881669']['metadata']['status_detail'] = null;
      $data['payments_details'][0]['refunds']['1574881669']['metadata']['origem'] = 'magento';
      $data['payments_details'][1]['status'] = 'refunded';      
      $data['payments_details'][1]['status_detail'] = 'refunded';
      $data['payments_details'][1]['refunds']['1574920057']['status'] = 'approved';
      $data['payments_details'][1]['refunds']['1574920057']['notifying'] = true;
      $data['payments_details'][1]['refunds']['1574920057']['metadata']['status_detail'] = null;
      $data['payments_details'][1]['refunds']['1574920057']['metadata']['origem'] = 'magento';

      return $data;
    }
}