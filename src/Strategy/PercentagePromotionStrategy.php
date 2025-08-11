<?php
declare(strict_types=1);

namespace KardiPromoPlugin\Strategy;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceDefinitionInterface;
use Shopware\Core\Checkout\Cart\Rule\LineItemRule;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;

class PercentagePromotionStrategy extends AbstractPromotionStrategy implements CustomPromotionStrategyInterface
{
    private const PRICE_THRESHOLD = 100;
    private const DISCOUNT_PERCENTAGE_VALUE = 10;
    private const PROMOTION_CODE = 'percentage_promotion';

    public function __construct(
        private readonly PercentagePriceCalculator $calculator,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function run(Cart $cart, SalesChannelContext $context): ?LineItem
    {
        $products = $this->findProducts($cart);
        if ($products->count() === 0) {
            return null;
        }

        $discountValue = $this->getDiscountValue($cart, $context);
        if (!$discountValue) {
            return null;
        }

        $discountLineItem = $this->createDiscount($this->translator->trans(self::PROMOTION_CODE));
        $discountLineItem->setPriceDefinition($this->getPriceDefinition($products));
        $discountLineItem->setPrice($discountValue);

        return $discountLineItem;
    }

    private function findProducts(Cart $cart): LineItemCollection
    {
        return $cart->getLineItems()->filter(function (LineItem $item) {
            if ($item->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                return false;
            }

            return $item;
        });
    }

    private function getPriceDefinition(LineItemCollection $products): PriceDefinitionInterface
    {
        return new PercentagePriceDefinition(
            -1 * self::DISCOUNT_PERCENTAGE_VALUE,
            // even though it's marked as internal, this usage example is in official Shopware docs
            new LineItemRule(LineItemRule::OPERATOR_EQ, $products->getKeys()),
        );
    }

    public function getDiscountValue(Cart $cart, SalesChannelContext $context): ?CalculatedPrice
    {
        $products = $this->findProducts($cart);
        if ($products->count() === 0) {
            return null;
        }

        if ($cart->getPrice()->getTotalPrice() < self::PRICE_THRESHOLD) {
            return null;
        }

        $definition = $this->getPriceDefinition($products);

        return $this->calculator->calculate($definition->getPercentage(), $products->getPrices(), $context);
    }
}
