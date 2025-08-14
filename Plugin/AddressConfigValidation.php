<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Plugin;

use Magento\Config\Model\Config;
use Magento\Framework\Exception\LocalizedException;

class AddressConfigValidation
{
    /**
     * Address configuration fields to validate.
     */
    private const ADDRESS_FIELDS = [
        'street_name',
        'street_number',
        'complement',
        'neighborhood'
    ];

    public function beforeSave(Config $subject)
    {
        $section = $subject->getSection();

        if ($section !== 'payment') {
            return [];
        }

        $addressLines = $this->extractAddressLines($subject);

        if (!$addressLines) {
            return [];
        }

        $this->validateAddressLinesConfig($addressLines);

        return [];
    }

    private function extractAddressLines(Config $subject): ?array
    {
        return $subject->getData()['groups']['mercadopago_base']['groups']['basic_settings']['groups']['address_lines']['fields'] ?? null;
    }

    /**
     * Validate address configuration for duplicates.
     *
     * @param array $addressLines
     *
     * Example: [
     *  'street_name' => [
     *      'value' => '0',
     *  ],
     *  'street_number' => [
     *      'value' => '1',
     *  ],
     *  'complement' => [
     *      'value' => '2',
     *  ],
     *  'neighborhood' => [
     *      'value' => '3',
     *  ],
     * ]
     *
     * @return void
     * @throws LocalizedException
     */
    private function validateAddressLinesConfig(array $addressLines): void
    {
        $valueFieldMap = [];
        $duplicates = [];

        foreach (self::ADDRESS_FIELDS as $field) {
            if (isset($addressLines[$field]['value'])) {
                $value = $addressLines[$field]['value'];

                if (isset($valueFieldMap[$value])) {
                    $duplicates[] = __(
                        'Address Line %1 is selected for both "%2" and "%3"',
                        strval(intval($value) + 1),
                        $field,
                        $valueFieldMap[$value]
                    );
                } else {
                    $valueFieldMap[$value] = $field;
                }
            }
        }

        if (!empty($duplicates)) {
            throw new LocalizedException(
                __('Address configuration error: %1', implode(', ', $duplicates))
            );
        }
    }
}
