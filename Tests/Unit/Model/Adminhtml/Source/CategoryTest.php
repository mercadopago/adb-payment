<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Adminhtml\Source;

use MercadoPago\AdbPayment\Model\Adminhtml\Source\Category;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Category source model.
 */
class CategoryTest extends TestCase
{
    /**
     * @var Category
     */
    private $category;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->category = new Category();
    }

    /**
     * @return void
     */
    public function testToOptionArrayReturns24Entries(): void
    {
        $options = $this->category->toOptionArray();

        $this->assertCount(24, $options);
    }

    /**
     * @return void
     */
    public function testToOptionArrayFirstEntryIsPlaceholder(): void
    {
        $options = $this->category->toOptionArray();

        $this->assertSame('', $options[0]['value']);
    }

    /**
     * @return void
     */
    public function testToOptionArrayEachEntryHasValueAndLabel(): void
    {
        $options = $this->category->toOptionArray();

        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
    }

    /**
     * @return void
     */
    public function testToOptionArrayContainsExpectedCategoryIds(): void
    {
        $options = $this->category->toOptionArray();
        $values = array_column($options, 'value');

        $expected = [
            'art', 'baby', 'coupons', 'donations', 'computing', 'cameras',
            'video_games', 'television', 'car_electronics', 'electronics',
            'automotive', 'entertainment', 'fashion', 'games', 'home',
            'musical', 'phones', 'services', 'learnings', 'tickets',
            'travels', 'virtual_goods', 'others',
        ];

        foreach ($expected as $id) {
            $this->assertContains($id, $values, "Category '{$id}' not found in options");
        }
    }
}
