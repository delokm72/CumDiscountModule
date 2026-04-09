<?php
declare(strict_types=1);

namespace Prostor\CumDiscount\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class Configuration
{
    private const XML_PATH_ENABLED = 'prostor_cumdiscount/general/enabled';
    private const XML_PATH_URL = 'prostor_cumdiscount/general/url';
    private const XML_PATH_TOKEN = 'prostor_cumdiscount/general/token';
    private const XML_PATH_TIMEOUT = 'prostor_cumdiscount/general/timeout';
    private const XML_PATH_TTL = 'prostor_cumdiscount/general/ttl';
    private const XML_PATH_THRESHOLDS = 'prostor_cumdiscount/general/thresholds';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getUrl(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getToken(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TOKEN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getTimeout(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_TIMEOUT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 30;
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getTtl(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_TTL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 3600;
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getThresholds(?int $storeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_THRESHOLDS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$value) {
            return [];
        }

        try {
            return $this->serializer->unserialize($value);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }
}
