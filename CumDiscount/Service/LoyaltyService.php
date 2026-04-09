<?php
declare(strict_types=1);

namespace Prostor\CumDiscount\Service;

use Prostor\CumDiscount\Model\Config\Configuration;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;

class LoyaltyService
{
    private const API_PATH         = '/loyalty/cumulative';
    private const CACHE_KEY_PREFIX = 'prostor_cumdiscount_';
    private const CACHE_TAG        = 'PROSTOR_CUMDISCOUNT';
    private const WINDOW_DAYS      = 90;

    /**
     * @param Configuration    $configuration
     * @param CacheInterface   $cache
     * @param Json             $serializer
     * @param LoyaltyApiClient $apiClient
     */
    public function __construct(
        private readonly Configuration    $configuration,
        private readonly CacheInterface   $cache,
        private readonly Json             $serializer,
        private readonly LoyaltyApiClient $apiClient
    ) {
    }

    /**
     * @param int      $customerId
     * @param int|null $storeId
     *
     * @return float
     */
    public function getSpentAmount(int $customerId, ?int $storeId = null): float
    {
        $baseUrl = $this->configuration->getUrl($storeId);
        if (!$baseUrl) {
            return 0.0;
        }

        $cacheKey = $this->getCacheKey($customerId, $storeId);
//        $cached = $this->cache->load($cacheKey);
//        if ($cached) {
//            try {
//                $data = $this->serializer->unserialize($cached);
//                return (float)($data['spent_amount'] ?? 0.0);
//            } catch (\Throwable) {
//            }
//        }

        $url =
            rtrim($baseUrl, '/') . self::API_PATH . '?customer_id=' . $customerId . '&window_days=' . self::WINDOW_DAYS;

        $data = $this->apiClient->fetchLoyaltyData(
            $url,
            $this->configuration->getToken($storeId),
            $this->configuration->getTimeout($storeId)
        );
        if (!isset($data['spent_amount']) || !is_numeric($data['spent_amount'])) {
            return 0.0;
        }
        $ttl = $this->configuration->getTtl($storeId);
        $this->cache->save(
            $this->serializer->serialize($data),
            $cacheKey,
            [self::CACHE_TAG],
            $ttl
        );

        return (float)$data['spent_amount'];
    }

    private function getCacheKey(int $customerId, int $storeId): string
    {
        return self::CACHE_KEY_PREFIX . $storeId . '_' . $customerId;
    }
}
