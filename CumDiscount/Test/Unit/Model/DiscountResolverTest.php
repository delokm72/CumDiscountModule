<?php
declare(strict_types=1);

namespace Prostor\CumDiscount\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Prostor\CumDiscount\Model\Config\Configuration;
use Prostor\CumDiscount\Model\Quote\Discount;

class DiscountResolverTest extends TestCase
{
    private $configMock;
    private $discount;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Configuration::class);

        // LoyaltyService тут не потрібен
        $this->discount = new Discount(
            $this->configMock,
            $this->createMock(\Prostor\CumDiscount\Service\LoyaltyService::class),
            $this->createMock(\Prostor\CumDiscount\Logger\Logger::class)
        );
    }

    public function testReturnsHighestApplicableThreshold(): void
    {
        $this->configMock->method('getThresholds')->willReturn([
                                                                   ['amount' => 4000, 'discount' => 3],
                                                                   ['amount' => 8000, 'discount' => 5],
                                                               ]);

        $method = new \ReflectionMethod($this->discount, 'calculateDiscountPercent');
        $method->setAccessible(true);
        $result = $method->invoke($this->discount, 8123.50, 1);

        $this->assertEquals(5.0, $result);
    }

    public function testReturnsLowerThreshold(): void
    {
        $this->configMock->method('getThresholds')->willReturn([
                                                                   ['amount' => 4000, 'discount' => 3],
                                                                   ['amount' => 8000, 'discount' => 5],
                                                               ]);

        $method = new \ReflectionMethod($this->discount, 'calculateDiscountPercent');
        $method->setAccessible(true);

        $result = $method->invoke($this->discount, 5000, 1);

        $this->assertEquals(3.0, $result);
    }

    public function testReturnsZeroIfBelowThreshold(): void
    {
        $this->configMock->method('getThresholds')->willReturn([
                                                                   [
                                                                       'amount'   => 4000,
                                                                       'discount' => 3,
                                                                   ],
                                                               ]);

        $method = new \ReflectionMethod($this->discount, 'calculateDiscountPercent');
        $method->setAccessible(true);
        $result = $method->invoke($this->discount, 1000, 1);

        $this->assertEquals(0.0, $result);
    }
}