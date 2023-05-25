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
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway Requests for Additional Order Item Data.
 */
class AdditionalInfoItemsDataRequest implements BuilderInterface
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
     * @param Image         $image
     * @param SubjectReader $subjectReader
     * @param Config        $config
     */
    public function __construct(
        Image $image,
        SubjectReader $subjectReader,
        Config $config
    ) {
        $this->image = $image;
        $this->subjectReader = $subjectReader;
        $this->config = $config;
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

        $result = [];

        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $items = $order->getItems();
        $itemcount = count($items);

        if ($itemcount) {
            foreach ($items as $item) {
                if ($item->getParentItem()) {
                    continue;
                }

                $product = $item->getProduct();
                $image = $this->image->init($product, 'small_image')
                        ->setImageFile($product->getSmallImage());

                $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::ITEMS][] = [
                    self::ITEM_ID           => $item->getSku(),
                    self::ITEM_TITLE        => $item->getName(),
                    self::ITEM_DESCRIPTION  => $item->getName().'-'.$item->getSku(),
                    self::ITEM_QUANTITY     => $item->getQtyOrdered(),
                    self::ITEM_PICTURE_URL  => $image->getUrl(),
                    self::ITEM_UNIT_PRICE   => $this->config->formatPrice($item->getPrice(), $storeId),
                    self::ITEM_CATEGORY_ID  => $this->config->getMpCategory($storeId),
                ];
            }
        }

        return $result;
    }
}
