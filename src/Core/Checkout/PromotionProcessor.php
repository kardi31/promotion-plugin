<?php
declare(strict_types=1);

namespace KardiPromoPlugin\Core\Checkout;

use KardiPromoPlugin\Strategy\CustomPromotionStrategyInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PromotionProcessor implements CartProcessorInterface
{
    public function __construct(private readonly iterable $strategies, private readonly LoggerInterface $logger)
    {
    }

    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $maxValue = 0;
        $highestValueStrategy = null;
        foreach ($this->strategies as $strategy) {
            if (!$strategy instanceof CustomPromotionStrategyInterface) {
                $this->logger->warning(
                    sprintf('%s not an instance of %s',
                        get_class($strategy),
                        CustomPromotionStrategyInterface::class)
                );
                continue;
            }

            $discountValue = $strategy->getDiscountValue($toCalculate, $context);
            if ($discountValue && $discountValue->getTotalPrice() > $maxValue) {
                $highestValueStrategy = $strategy;
                $maxValue = $discountValue->getTotalPrice();
            }
        }

        if ($highestValueStrategy) {
            $discountLineItem = $highestValueStrategy->run($toCalculate, $context);
            if ($discountLineItem instanceof LineItem) {
                $toCalculate->add($discountLineItem);
            }
        }
    }
}
