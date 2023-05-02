<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Catalog\Helper\Image;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway requests for defining purchased items and cost details.
 */
class ItemsAndDatailsCostDataRequest implements BuilderInterface
{
    /**
     * Items block name.
     */
    public const ITEMS = 'items';

    /**
     * Item Id block name.
     */
    public const ITEM_ID = 'id';

    /**
     * Item Title block name.
     */
    public const ITEM_TITLE = 'title';

    /**
     * Item Description block name.
     */
    public const ITEM_DESCRIPTION = 'description';

    /**
     * Item Picture Url block name.
     */
    public const ITEM_PICTURE_URL = 'picture_url';

    /**
     * Item Quantity block name.
     */
    public const ITEM_QUANTITY = 'quantity';

    /**
     * Item Unit Price block name.
     */
    public const ITEM_UNIT_PRICE = 'unit_price';

    /**
     * Item Category ID block name.
     */
    public const ITEM_CATEGORY_ID = 'category_id';

    /**
     * @var Image
     */
    protected $image;

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
     * @param Image               $image
     * @param SubjectReader       $subjectReader
     * @param Config              $config
     * @param OrderAdapterFactory $orderAdapterFactory
     */
    public function __construct(
        Image $image,
        SubjectReader $subjectReader,
        Config $config,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->image = $image;
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->orderAdapterFactory = $orderAdapterFactory;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
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

        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();

        $shippingAmount = $orderAdapter->getShippingAmount();
        $itemsShipping = $this->addItemsShipping($orderAdapter, $shippingAmount, $storeId);

        $discountAmount = $orderAdapter->getDiscountAmount();
        $itemsDiscount = $this->addItemsDiscount($orderAdapter, $discountAmount, $storeId);

        $taxAmount = $orderAdapter->getTaxAmount();
        $itemsTax = $this->addItemsTax($taxAmount, $storeId);

        $productItems = $order->getItems();
        $itemsProducts = $this->addItemsProducts($productItems, $storeId);

        $result[self::ITEMS] = array_merge_recursive($itemsProducts, $itemsShipping, $itemsDiscount, $itemsTax);

        return $result;
    }

    /**
     * Add Items Products.
     *
     * @param array $productItems
     * @param int   $storeId
     *
     * @return array
     */
    public function addItemsProducts(array $productItems, int $storeId): array
    {
        $items = [];

        foreach ($productItems as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $product = $item->getProduct();
            $image = $this->image->init($product, 'small_image')
                    ->setImageFile($product->getSmallImage());

            $items[] = [
                self::ITEM_ID           => $item->getSku(),
                self::ITEM_TITLE        => $item->getName(),
                self::ITEM_DESCRIPTION  => $item->getName().'-'.$item->getSku(),
                self::ITEM_QUANTITY     => $item->getQtyOrdered(),
                self::ITEM_PICTURE_URL  => $image->getUrl(),
                self::ITEM_UNIT_PRICE   => $this->config->formatPrice($item->getPrice(), $storeId),
                self::ITEM_CATEGORY_ID  => $this->config->getMpCategory($storeId),
            ];
        }

        return $items;
    }

    /**
     * Add Items Tax.
     *
     * @param float $taxAmount
     * @param int   $storeId
     *
     * @return array
     */
    public function addItemsTax(float $taxAmount, int $storeId): array
    {
        $items = [];

        if ($taxAmount) {
            $items[] = [
                self::ITEM_ID           => __('Tax'),
                self::ITEM_TITLE        => __('Tax'),
                self::ITEM_DESCRIPTION  => __('Tax'),
                self::ITEM_QUANTITY     => 1,
                self::ITEM_UNIT_PRICE   => $this->config->formatPrice($taxAmount),
                self::ITEM_CATEGORY_ID  => $this->config->getMpCategory($storeId),
            ];
        }

        return $items;
    }

    /**
     * Add Items Discount.
     *
     * @param OrderAdapterFactory $orderAdapter
     * @param float               $discountAmount
     * @param int                 $storeId
     *
     * @return array
     */
    public function addItemsDiscount(
        $orderAdapter,
        float $discountAmount,
        int $storeId
    ): array {
        $items = [];

        if ($discountAmount) {
            $items[] = [
                self::ITEM_ID           => $orderAdapter->getDiscountDescription(),
                self::ITEM_TITLE        => $orderAdapter->getDiscountDescription(),
                self::ITEM_DESCRIPTION  => $orderAdapter->getDiscountDescription(),
                self::ITEM_QUANTITY     => 1,
                self::ITEM_UNIT_PRICE   => $this->config->formatPrice($discountAmount),
                self::ITEM_CATEGORY_ID  => $this->config->getMpCategory($storeId),
            ];
        }

        return $items;
    }

    /**
     * Add Items Shipping.
     *
     * @param OrderAdapterFactory $orderAdapter
     * @param float               $shippingAmount
     * @param int                 $storeId
     *
     * @return array
     */
    public function addItemsShipping(
        $orderAdapter,
        float $shippingAmount,
        int $storeId
    ): array {
        $items = [];

        if ($shippingAmount) {
            $items[] = [
                self::ITEM_ID           => $orderAdapter->getShippingMethod(),
                self::ITEM_TITLE        => $orderAdapter->getShippingDescription(),
                self::ITEM_DESCRIPTION  => $orderAdapter->getShippingDescription(),
                self::ITEM_QUANTITY     => 1,
                self::ITEM_UNIT_PRICE   => $this->config->formatPrice($shippingAmount),
                self::ITEM_CATEGORY_ID  => $this->config->getMpCategory($storeId),
            ];
        }

        return $items;
    }
}
