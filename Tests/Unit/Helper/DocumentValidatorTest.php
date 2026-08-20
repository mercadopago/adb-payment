<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Helper;

use MercadoPago\AdbPayment\Helper\DocumentValidator;
use PHPUnit\Framework\TestCase;

class DocumentValidatorTest extends TestCase
{
    /**
     * @dataProvider documentProvider
     *
     * @param string $document
     * @param bool   $expected
     * @param string $message
     */
    public function testIsValid(string $document, bool $expected, string $message)
    {
        $this->assertSame($expected, DocumentValidator::isValid($document), $message);
    }

    /**
     * @return array
     */
    public function documentProvider(): array
    {
        return [
            'valid CPF'                   => ['11144477735', true, 'valid CPF should pass'],
            'CPF wrong check digit'       => ['12345678900', false, 'CPF with wrong check digit should fail'],
            'CPF repeated digits'         => ['00000000000', false, 'CPF with all repeated digits should fail'],
            'CPF too short'               => ['123', false, 'CPF shorter than 11 digits should fail'],
            'CPF with a letter'           => ['1114447773A', false, 'CPF with a non-digit should fail'],
            'valid numeric CNPJ'          => ['11222333000181', true, 'valid numeric CNPJ should pass'],
            'CNPJ wrong check digit'      => ['11222333000180', false, 'CNPJ with wrong check digit should fail'],
            'CNPJ repeated chars'         => ['11111111111111', false, 'CNPJ with all repeated chars should fail'],
            'valid alphanumeric CNPJ'     => ['12ABC345000188', true, 'valid alphanumeric CNPJ should pass'],
            'alphanumeric CNPJ wrong DV'  => ['12ABC345000189', false, 'alphanumeric CNPJ with wrong DV should fail'],
            'lowercase alphanumeric CNPJ' => ['12abc345000188', true, 'lowercase CNPJ should normalize and pass'],
            'non-numeric check digits'    => ['12ABC3450001A8', false, 'CNPJ with non-numeric check digits should fail'],
            'empty string'                => ['', false, 'empty string should fail'],
            'unexpected length'           => ['123456789012', false, '12-char input is neither CPF nor CNPJ'],
        ];
    }

    public function testIsValidCpfDirectly()
    {
        $this->assertTrue(DocumentValidator::isValidCpf('11144477735'));
        $this->assertFalse(DocumentValidator::isValidCpf('12345678900'));
    }

    public function testIsValidCnpjDirectly()
    {
        $this->assertTrue(DocumentValidator::isValidCnpj('11222333000181'));
        $this->assertFalse(DocumentValidator::isValidCnpj('11222333000180'));
    }
}
