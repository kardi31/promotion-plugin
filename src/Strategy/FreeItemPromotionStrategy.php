<?php
declare(strict_types=1);

namespace KardiPromoPlugin\Strategy;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceDefinitionInterface;
use Shopware\Core\Checkout\Cart\Rule\LineItemRule;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;

class FreeItemPromotionStrategy extends AbstractPromotionStrategy implements CustomPromotionStrategyInterface
{
    private const COUNT_THRESHOLD = 5;
    private const PROMOTION_CODE = 'free_item_promotion';

    public function __construct(
        private readonly AbsolutePriceCalculator $absolutePriceCalculator,
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

        $discountPrice = $this->calculateDiscountPrice($products);

        $discountLineItem = $this->createDiscount($this->translator->trans(self::PROMOTION_CODE));
        $discountLineItem->setPriceDefinition($this->getPriceDefinition($discountPrice, $products));
        $discountLineItem->setPrice($discountValue);

        return $discountLineItem;
    }

    public function getDiscountValue(Cart $cart, SalesChannelContext $context): ?CalculatedPrice
    {
        $products = $this->findProducts($cart);
        if ($products->count() === 0) {
            return null;
        }

        $discountPrice = $this->calculateDiscountPrice($products);

        $definition = $this->getPriceDefinition($discountPrice, $products);

        return $this->absolutePriceCalculator->calculate($definition->getPrice(), $products->getPrices(), $context);
    }

    private function findProducts(Cart $cart): LineItemCollection
    {
        return $cart->getLineItems()->filter(function (LineItem $item) {
            if ($item->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                return false;
            }

            // if less products than required by promotion, skip it
            if ($item->getQuantity() < self::COUNT_THRESHOLD) {
                return false;
            }

            return $item;
        });
    }

    private function getPriceDefinition($discountPrice, $products): PriceDefinitionInterface
    {
        return new AbsolutePriceDefinition(
            $discountPrice,
            // even though it's marked as internal, this usage example is in official Shopware docs
            new LineItemRule(LineItemRule::OPERATOR_EQ, $products->getKeys())
        );
    }

    private function calculateDiscountPrice(LineItemCollection $products): float
    {
        $discountPrice = 0;
        foreach ($products as $product) {
            if ($product->getQuantity() >= self::COUNT_THRESHOLD) {
                $discountPrice += $product->getPrice()->getUnitPrice() * floor($product->getQuantity() / self::COUNT_THRESHOLD);
            }
        }

        return $discountPrice;
    }
}
