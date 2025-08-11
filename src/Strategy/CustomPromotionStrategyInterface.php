<?php
declare(strict_types = 1);

namespace KardiPromoPlugin\Strategy;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface CustomPromotionStrategyInterface
{
    public const PROMOTION_TYPE = 'custom_discount';

    public function run(Cart $cart, SalesChannelContext $context): ?LineItem;

    public function getDiscountValue(Cart $cart, SalesChannelContext $context): ?CalculatedPrice;
}
