<?php

declare(strict_types=1);

namespace Prostor\CumDiscount\Test\Unit\Service;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Prostor\CumDiscount\Logger\Logger as ProstorLogger;
use Prostor\CumDiscount\Model\Config\Configuration;
use Prostor\CumDiscount\Service\LoyaltyApiClient;
use Prostor\CumDiscount\Service\LoyaltyService;

class LoyaltyServiceTest extends TestCase
{
    public function testReturnsAmountFromApi(): void
    {
        $config = $this->createMock(Configuration::class);
        $cache = $this->createMock(CacheInterface::class);
        $serializer = $this->createMock(Json::class);
        $api = $this->createMock(LoyaltyApiClient::class);
        $logger = $this->createMock(ProstorLogger::class);

        $config->method('getUrl')->willReturn('http://test');
        $config->method('getToken')->willReturn('token');
        $config->method('getTimeout')->willReturn(30);
        $config->method('getTtl')->willReturn(3600);

        $cache->method('load')->willReturn(false);
        $serializer->method('serialize')->willReturn('{"spent_amount":8123.5}');
        $cache->expects($this->once())->method('save');

        $api->expects($this->once())
            ->method('fetchLoyaltyData')
            ->willReturn(['spent_amount' => 8123.50]);

        $service = new LoyaltyService($config, $cache, $serializer, $api, $logger);

        $this->assertEquals(8123.50, $service->getSpentAmount(123, 1));
    }

    public function testReturnsZeroWhenApiReturnsInvalidPayload(): void
    {
        $config = $this->createMock(Configuration::class);
        $cache = $this->createMock(CacheInterface::class);
        $serializer = $this->createMock(Json::class);
        $api = $this->createMock(LoyaltyApiClient::class);
        $logger = $this->createMock(ProstorLogger::class);

        $config->method('getUrl')->willReturn('http://test');
        $config->method('getToken')->willReturn('token');
        $config->method('getTimeout')->willReturn(30);
        $cache->method('load')->willReturn(false);

        $api->expects($this->once())
            ->method('fetchLoyaltyData')
            ->willReturn([]);

        $cache->expects($this->once())->method('save');
        $service = new LoyaltyService($config, $cache, $serializer, $api, $logger);

        $this->assertEquals(0.0, $service->getSpentAmount(123, 1));
    }
}