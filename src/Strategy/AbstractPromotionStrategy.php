<?php
declare(strict_types=1);

namespace KardiPromoPlugin\Strategy;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;

abstract class AbstractPromotionStrategy
{
    protected function createDiscount(string $name): LineItem
    {
        $discountLineItem = new LineItem(
            $name,
            CustomPromotionStrategyInterface::PROMOTION_TYPE,
            null,
            1
        );

        $discountLineItem->setLabel($name);
        $discountLineItem->setGood(false);
        $discountLineItem->setStackable(false);
        $discountLineItem->setRemovable(false);

        return $discountLineItem;
    }
}
