<?php

namespace KardiPromoPlugin\Tests\Strategy;

use KardiPromoPlugin\Strategy\PercentagePromotionStrategy;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;

class PercentagePromotionStrategyTest extends TestCase
{
    public function setUp(): void
    {
        $this->calculator = $this->createMock(PercentagePriceCalculator::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->taxRules = $this->createMock(TaxRuleCollection::class);
        $this->calculatedTaxCollection = $this->createMock(CalculatedTaxCollection::class);
        $this->cart = $this->createMock(Cart::class);
        $this->context = $this->createMock(SalesChannelContext::class);
        $this->cartPrice = $this->createMock(CartPrice::class);
    }

    /**
     * @dataProvider provideCheapProducts
     */
    public function testRunWhenBelowThreshold(array $products, float $cartValue, float $expectedDiscountValue)
    {

        $this->translator->method('trans')->willReturn('some random name');

        $lineItems = [];
        foreach ($products as $key => $product) {
            $lineItems[] = new LineItem((string)$key, LineItem::PRODUCT_LINE_ITEM_TYPE);
        }
        $this->calculator->method('calculate')->willReturn(
            new CalculatedPrice($expectedDiscountValue, $expectedDiscountValue, $this->calculatedTaxCollection, $this->taxRules)
        );
        $lineItemCollection = new LineItemCollection($lineItems);
        $this->cart->method('getLineItems')->willReturn($lineItemCollection);
        $this->cartPrice->method('getTotalPrice')->willReturn($cartValue);
        $this->cart->method('getPrice')->willReturn($this->cartPrice);
        $strategy = new PercentagePromotionStrategy($this->calculator, $this->translator);
        $discountLineItem = $strategy->run($this->cart, $this->context);

        $this->assertNull($discountLineItem);
    }

    /**
     * @dataProvider provideExpensiveProducts
     */
    public function testRunWhenOverThreshold(array $products, float $cartValue, float $expectedDiscountValue)
    {
        $this->translator->method('trans')->willReturn('some random name');

        $lineItems = [];
        foreach ($products as $key => $product) {
            $lineItems[] = new LineItem((string)$key, LineItem::PRODUCT_LINE_ITEM_TYPE);
        }
        $this->calculator->method('calculate')->willReturn(
            new CalculatedPrice($expectedDiscountValue, $expectedDiscountValue, $this->calculatedTaxCollection, $this->taxRules)
        );
        $lineItemCollection = new LineItemCollection($lineItems);
        $this->cart->method('getLineItems')->willReturn($lineItemCollection);
        $this->cartPrice->method('getTotalPrice')->willReturn($cartValue);
        $this->cart->method('getPrice')->willReturn($this->cartPrice);
        $strategy = new PercentagePromotionStrategy($this->calculator, $this->translator);
        $discountLineItem = $strategy->run($this->cart, $this->context);

        $this->assertInstanceOf(LineItem::class, $discountLineItem);
        $this->assertEquals($discountLineItem->getPrice()->getTotalPrice(), $expectedDiscountValue);
    }

    /**
     * @dataProvider provideExpensiveProducts
     */
    public function testGetDiscountValueWhenOverThreshold(array $products, float $cartValue, float $expectedDiscountValue)
    {
        $lineItems = [];
        foreach ($products as $key => $product) {
            $lineItems[] = new LineItem((string)$key, LineItem::PRODUCT_LINE_ITEM_TYPE);
        }
        $this->calculator->method('calculate')->willReturn(
            new CalculatedPrice($expectedDiscountValue, $expectedDiscountValue, $this->calculatedTaxCollection, $this->taxRules)
        );
        $lineItemCollection = new LineItemCollection($lineItems);
        $this->cart->method('getLineItems')->willReturn($lineItemCollection);
        $this->cartPrice->method('getTotalPrice')->willReturn($cartValue);
        $this->cart->method('getPrice')->willReturn($this->cartPrice);
        $strategy = new PercentagePromotionStrategy($this->calculator, $this->translator);
        $discountValue = $strategy->getDiscountValue($this->cart, $this->context);

        $this->assertEquals($discountValue->getTotalPrice(), $expectedDiscountValue);
    }

    /**
     * @dataProvider provideCheapProducts
     */
    public function testGetDiscountValueWhenBelowThreshold(array $products, float $cartValue, float $expectedDiscountValue)
    {
        $lineItems = [];
        foreach ($products as $key => $product) {
            $lineItems[] = new LineItem((string)$key, LineItem::PRODUCT_LINE_ITEM_TYPE);
        }
        $this->calculator->method('calculate')->willReturn(
            new CalculatedPrice($expectedDiscountValue, $expectedDiscountValue, $this->calculatedTaxCollection, $this->taxRules)
        );
        $lineItemCollection = new LineItemCollection($lineItems);
        $this->cart->method('getLineItems')->willReturn($lineItemCollection);
        $this->cartPrice->method('getTotalPrice')->willReturn($cartValue);
        $this->cart->method('getPrice')->willReturn($this->cartPrice);
        $strategy = new PercentagePromotionStrategy($this->calculator, $this->translator);
        $discountValue = $strategy->getDiscountValue($this->cart, $this->context);
        $this->assertNull($discountValue);
    }

    public static function provideExpensiveProducts(): iterable
    {
        yield [
            [
                [
                    'price' => 500
                ]
            ],
            500.00,
            50
        ];

        yield [
            [
                [
                    'price' => 100
                ],
                [
                    'price' => 200
                ]
            ],
            300.00,
            30
        ];


        yield [
            [
                [
                    'price' => 10
                ],
                [
                    'price' => 10
                ],
                [
                    'price' => 80
                ]
            ],
            100.00,
            10
        ];
    }

    public static function provideCheapProducts(): iterable
    {
        yield [
            [
                [
                    'price' => 15
                ]
            ],
            15.00,
            0
        ];

        yield [
            [
                [
                    'price' => 5
                ],
                [
                    'price' => 5
                ]
            ],
            10.00,
            0
        ];


        yield [
            [
                [
                    'price' => 10
                ],
                [
                    'price' => 10
                ],
                [
                    'price' => 15
                ]
            ],
            35.00,
            0
        ];
    }
}
