<?php

namespace KardiPromoPlugin\Tests\Strategy;

use KardiPromoPlugin\Strategy\FreeItemPromotionStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;

class FreeItemStrategyTest extends TestCase
{
    public function setUp(): void
    {
        $this->calculator = $this->createMock(AbsolutePriceCalculator::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->cart = $this->createMock(Cart::class);
        $this->context = $this->createMock(SalesChannelContext::class);
        $this->taxRules = $this->createMock(TaxRuleCollection::class);
        $this->calculatedTaxCollection = $this->createMock(CalculatedTaxCollection::class);
    }

    #[DataProvider('provideEligibleProducts')]
    public function testRunWhenProductsEligible(array $products, float $expectedDiscountValue)
    {
        $this->translator->method('trans')->willReturn('some_random_name');

        $strategy = new FreeItemPromotionStrategy($this->calculator, $this->translator);
        $lineItems = [];
        foreach ($products as $key => $product) {
            $item = new LineItem((string) ($key+1), LineItem::PRODUCT_LINE_ITEM_TYPE, null, $product['quantity']);
            $item->setPrice(new CalculatedPrice($product['price'], $product['price'], $this->calculatedTaxCollection, $this->taxRules));
            $lineItems[] = $item;
        }
        $lineItemCollection = new LineItemCollection($lineItems);
        $this->cart->method('getLineItems')->willReturn($lineItemCollection);

        $this->calculator->method('calculate')->willReturn(
            new CalculatedPrice($expectedDiscountValue, $expectedDiscountValue, $this->calculatedTaxCollection, $this->taxRules)
        );
        $discountLineItem = $strategy->run($this->cart, $this->context);
        $this->assertInstanceOf(LineItem::class, $discountLineItem);
    }

    #[DataProvider('provideNotEligibleProducts')]
    public function testRunWhenProductsNotEligible(array $products, float $expectedDiscountValue)
    {
        $strategy = new FreeItemPromotionStrategy($this->calculator, $this->translator);
        $lineItems = [];
        foreach ($products as $key => $product) {
            $item = new LineItem((string) ($key+1), LineItem::PRODUCT_LINE_ITEM_TYPE, null, $product['quantity']);
            $item->setPrice(new CalculatedPrice($product['price'], $product['price'], $this->calculatedTaxCollection, $this->taxRules));
            $lineItems[] = $item;
        }
        $lineItemCollection = new LineItemCollection($lineItems);
        $this->cart->method('getLineItems')->willReturn($lineItemCollection);

        $this->calculator->method('calculate')->willReturn(
            new CalculatedPrice($expectedDiscountValue, $expectedDiscountValue, $this->calculatedTaxCollection, $this->taxRules)
        );
        $discountLineItem = $strategy->run($this->cart, $this->context);
        $this->assertNull($discountLineItem);
    }

    #[DataProvider('provideEligibleProducts')]
    public function testGetDiscountValueWhenProductsEligible(array $products, float $expectedDiscountValue)
    {
        $this->translator->method('trans')->willReturn('some_random_name');

        $strategy = new FreeItemPromotionStrategy($this->calculator, $this->translator);
        $lineItems = [];
        foreach ($products as $key => $product) {
            $item = new LineItem((string) ($key+1), LineItem::PRODUCT_LINE_ITEM_TYPE, null, $product['quantity']);
            $item->setPrice(new CalculatedPrice($product['price'], $product['price'], $this->calculatedTaxCollection, $this->taxRules));
            $lineItems[] = $item;
        }
        $lineItemCollection = new LineItemCollection($lineItems);
        $this->cart->method('getLineItems')->willReturn($lineItemCollection);

        $this->calculator->method('calculate')->willReturn(
            new CalculatedPrice($expectedDiscountValue, $expectedDiscountValue, $this->calculatedTaxCollection, $this->taxRules)
        );
        $discountValue = $strategy->getDiscountValue($this->cart, $this->context);
        $this->assertEquals($expectedDiscountValue, $discountValue->getTotalPrice());
    }

    #[DataProvider('provideNotEligibleProducts')]
    public function testGetDiscountValueWhenProductsNotEligible(array $products, float $expectedDiscountValue)
    {
        $strategy = new FreeItemPromotionStrategy($this->calculator, $this->translator);
        $lineItems = [];
        foreach ($products as $key => $product) {
            $item = new LineItem((string) ($key+1), LineItem::PRODUCT_LINE_ITEM_TYPE, null, $product['quantity']);
            $item->setPrice(new CalculatedPrice($product['price'], $product['price'], $this->calculatedTaxCollection, $this->taxRules));
            $lineItems[] = $item;
        }
        $lineItemCollection = new LineItemCollection($lineItems);
        $this->cart->method('getLineItems')->willReturn($lineItemCollection);

        $this->calculator->method('calculate')->willReturn(
            new CalculatedPrice($expectedDiscountValue, $expectedDiscountValue, $this->calculatedTaxCollection, $this->taxRules)
        );
        $discountValue = $strategy->getDiscountValue($this->cart, $this->context);
        $this->assertNull($discountValue);
    }

    public static function provideEligibleProducts(): iterable
    {
        yield [
            [
                [
                    'price' => 50,
                    'quantity' => 10,
                ]
            ],
            100
        ];

        yield [
            [
                [
                    'price' => 50,
                    'quantity' => 12,
                ]
            ],
            100
        ];

        yield [
            [
                [
                    'price' => 50,
                    'quantity' => 21,
                ]
            ],
            200
        ];

        yield [
            [
                [
                    'price' => 50,
                    'quantity' => 21,
                ],
                [
                    'price' => 15,
                    'quantity' => 14,
                ]
            ],
            230
        ];
    }

    public static function provideNotEligibleProducts(): iterable
    {
        yield [
            [
                [
                    'price' => 50,
                    'quantity' => 4,
                ]
            ],
            0
        ];

        yield [
            [
                [
                    'price' => 50,
                    'quantity' => 2,
                ]
            ],
            0
        ];

        yield [
            [
                [
                    'price' => 50,
                    'quantity' => 1,
                ]
            ],
            0
        ];

        yield [
            [
                [
                    'price' => 50,
                    'quantity' => 2,
                ],
                [
                    'price' => 15,
                    'quantity' => 2,
                ]
            ],
            0
        ];
    }
}
