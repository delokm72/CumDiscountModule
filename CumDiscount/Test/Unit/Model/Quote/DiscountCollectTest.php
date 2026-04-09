<?php

declare(strict_types=1);

namespace Prostor\CumDiscount\Test\Unit\Model\Quote;

use Magento\Catalog\Model\Product;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use PHPUnit\Framework\TestCase;
use Prostor\CumDiscount\Model\Config\Configuration;
use Prostor\CumDiscount\Model\Quote\Discount;
use Prostor\CumDiscount\Service\LoyaltyService;
use Prostor\CumDiscount\Logger\Logger as ProstorLogger;

class DiscountCollectTest extends TestCase
{
    public function testAppliesDiscountToNonExcludedItems(): void
    {
        $config = $this->createMock(Configuration::class);
        $loyalty = $this->createMock(LoyaltyService::class);
        $logger = $this->createMock(ProstorLogger::class);
        $discount = new Discount($config, $loyalty, $logger);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStoreId', 'getAllVisibleItems'])
            ->addMethods(['getCustomerId'])
            ->getMock();

        $quote->method('getStoreId')->willReturn(1);
        $quote->method('getCustomerId')->willReturn(123);
        $quote->method('getAllVisibleItems')->willReturn([]);

        $item = $this->getMockBuilder(\stdClass::class)
            ->addMethods([
                'getParentItem',
                'getRowTotalInclTax',
                'getBaseRowTotalInclTax',
                'getDiscountAmount',
                'getBaseDiscountAmount',
                'getProduct',
            ])
            ->getMock();
        $item->method('getParentItem')->willReturn(null);
        $item->method('getRowTotalInclTax')->willReturn(100.0);
        $item->method('getBaseRowTotalInclTax')->willReturn(100.0);
        $item->method('getDiscountAmount')->willReturn(0.0);
        $item->method('getBaseDiscountAmount')->willReturn(0.0);
        $item->method('getProduct')->willReturn(null);

        $address = $this->createMock(Address::class);
        $shipping = $this->createMock(ShippingAssignmentInterface::class);
        $shippingData = $this->createMock(ShippingInterface::class);
        $shippingData->method('getAddress')->willReturn($address);

        $shipping->method('getShipping')->willReturn($shippingData);
        $shipping->method('getItems')->willReturn([$item]);

        $total = $this->createMock(Total::class);

        $config->method('isEnabled')->willReturn(true);
        $config->method('getThresholds')->willReturn([
                                                         ['amount' => 5000, 'discount' => 10],
                                                     ]);

        $loyalty->method('getSpentAmount')->willReturn(6000.0);

        $total->expects($this->once())->method('addTotalAmount')->with('cumulative_discount', -10.0);
        $total->expects($this->once())->method('addBaseTotalAmount')->with('cumulative_discount', -10.0);

        $discount->collect($quote, $shipping, $total);
    }

    public function testSkipsPromoExcludedItems(): void
    {
        $config = $this->createMock(Configuration::class);
        $loyalty = $this->createMock(LoyaltyService::class);
        $logger = $this->createMock(ProstorLogger::class);

        $discount = new Discount($config, $loyalty, $logger);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStoreId', 'getAllVisibleItems'])
            ->addMethods(['getCustomerId'])
            ->getMock();

        $quote->method('getStoreId')->willReturn(1);
        $quote->method('getCustomerId')->willReturn(123);
        $quote->method('getAllVisibleItems')->willReturn([]);

        $product = $this->createMock(Product::class);
        $product->method('getData')->with('promo_excluded')->willReturn(1);

        $item = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getParentItem', 'getProduct'])
            ->getMock();
        $item->method('getParentItem')->willReturn(null);
        $item->method('getProduct')->willReturn($product);

        $address = $this->createMock(Address::class);
        $shipping = $this->createMock(ShippingAssignmentInterface::class);
        $shippingData = $this->createMock(ShippingInterface::class);
        $shippingData->method('getAddress')->willReturn($address);

        $shipping->method('getShipping')->willReturn($shippingData);
        $shipping->method('getItems')->willReturn([$item]);

        $total = $this->createMock(Total::class);

        $config->method('isEnabled')->willReturn(true);
        $config->method('getThresholds')->willReturn([
            ['amount' => 5000, 'discount' => 10],
        ]);
        $loyalty->method('getSpentAmount')->willReturn(6000.0);

        $total->expects($this->never())->method('addTotalAmount');
        $total->expects($this->never())->method('addBaseTotalAmount');

        $discount->collect($quote, $shipping, $total);
    }

    public function testNoDiscountWhenBelowThreshold(): void
    {
        $config = $this->createMock(Configuration::class);
        $loyalty = $this->createMock(LoyaltyService::class);
        $logger = $this->createMock(ProstorLogger::class);

        $discount = new Discount($config, $loyalty, $logger);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStoreId', 'getAllVisibleItems'])
            ->addMethods(['getCustomerId'])
            ->getMock();

        $quote->method('getStoreId')->willReturn(1);
        $quote->method('getCustomerId')->willReturn(123);
        $quote->method('getAllVisibleItems')->willReturn([]);

        $item = $this->getMockBuilder(\stdClass::class)
            ->addMethods([
                             'getParentItem',
                             'getRowTotalInclTax',
                             'getBaseRowTotalInclTax',
                             'getDiscountAmount',
                             'getBaseDiscountAmount',
                             'getProduct',
                         ])
            ->getMock();

        $item->method('getParentItem')->willReturn(null);
        $item->method('getRowTotalInclTax')->willReturn(100.0);
        $item->method('getBaseRowTotalInclTax')->willReturn(100.0);
        $item->method('getDiscountAmount')->willReturn(0.0);
        $item->method('getBaseDiscountAmount')->willReturn(0.0);
        $item->method('getProduct')->willReturn(null);

        $address = $this->createMock(Address::class);
        $shipping = $this->createMock(ShippingAssignmentInterface::class);
        $shippingData = $this->createMock(ShippingInterface::class);
        $shippingData->method('getAddress')->willReturn($address);

        $shipping->method('getShipping')->willReturn($shippingData);
        $shipping->method('getItems')->willReturn([$item]);

        $total = $this->createMock(Total::class);

        $config->method('isEnabled')->willReturn(true);
        $config->method('getThresholds')->willReturn([
                                                         ['amount' => 5000, 'discount' => 10],
                                                     ]);

        $loyalty->method('getSpentAmount')->willReturn(3000.0);

        $total->expects($this->never())->method('addTotalAmount');
        $total->expects($this->never())->method('addBaseTotalAmount');

        $discount->collect($quote, $shipping, $total);
    }
}