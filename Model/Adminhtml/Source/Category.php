<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Categories Options in Mercado Pago.
 */
class Category implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $options[] = [
            'value' => '',
            'label' => __('Please select a category'),
        ];
        foreach ($this->getAllCategories() as $category) {
            $options[] = [
                'value' => $category['id'],
                'label' => __($category['description']),
            ];
        }

        return $options;
    }

    /**
     * Returns static list of MercadoPago item categories.
     *
     * @return array
     */
    protected function getAllCategories(): array
    {
        return [
            ['id' => 'art', 'description' => 'Collectibles & Art'],
            ['id' => 'baby', 'description' => 'Toys for Baby, Stroller, Stroller Accessories, Car Safety Seats'],
            ['id' => 'coupons', 'description' => 'Coupons'],
            ['id' => 'donations', 'description' => 'Donations'],
            ['id' => 'computing', 'description' => 'Computers & Tablets'],
            ['id' => 'cameras', 'description' => 'Cameras & Photography'],
            ['id' => 'video_games', 'description' => 'Video Games & Consoles'],
            ['id' => 'television', 'description' => 'LCD, LED, Smart TV, Plasmas, TVs'],
            [
                'id' => 'car_electronics',
                'description' => 'Car Audio, Car Alarm Systems & Security, Car DVRs, Car Video Players, Car PC',
            ],
            ['id' => 'electronics', 'description' => 'Audio & Surveillance, Video & GPS, Others'],
            ['id' => 'automotive', 'description' => 'Parts & Accessories'],
            [
                'id' => 'entertainment',
                'description' => 'Music, Movies & Series, Books, Magazines & Comics, Board Games & Toys',
            ],
            [
                'id' => 'fashion',
                'description' => "Men's, Women's, Kids & baby, Handbags & Accessories,"
                    . " Health & Beauty, Shoes, Jewelry & Watches",
            ],
            ['id' => 'games', 'description' => 'Online Games & Credits'],
            ['id' => 'home', 'description' => 'Home appliances. Home & Garden'],
            ['id' => 'musical', 'description' => 'Instruments & Gear'],
            ['id' => 'phones', 'description' => 'Cell Phones & Accessories'],
            ['id' => 'services', 'description' => 'General services'],
            ['id' => 'learnings', 'description' => 'Trainings, Conferences, Workshops'],
            [
                'id' => 'tickets',
                'description' => 'Tickets for Concerts, Sports, Arts, Theater, Family,'
                    . ' Excursions tickets, Events & more',
            ],
            ['id' => 'travels', 'description' => 'Plane tickets, Hotel vouchers, Travel vouchers'],
            [
                'id' => 'virtual_goods',
                'description' => 'E-books, Music Files, Software, Digital Images, PDF Files and any item'
                    . ' which can be electronically stored in a file, Mobile Recharge, DTH Recharge'
                    . ' and any Online Recharge',
            ],
            ['id' => 'others', 'description' => 'Other categories'],
        ];
    }
}
