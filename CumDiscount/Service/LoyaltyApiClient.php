<?php
declare(strict_types=1);

namespace Prostor\CumDiscount\Service;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Prostor\CumDiscount\Logger\Logger as ProstorLogger;

class LoyaltyApiClient
{
    private const MOCK_HOST  = 'mock.test';
    private const MOCK_PARAM = 'amount';

    /**
     * @param Curl                $curl
     * @param SerializerInterface $serializer
     * @param ProstorLogger       $logger
     */
    public function __construct(
        private readonly Curl                $curl,
        private readonly SerializerInterface $serializer,
        private readonly ProstorLogger       $logger
    ) {
    }

    /**
     * @param string      $url
     * @param string|null $token
     * @param int         $timeout
     *
     * @return array
     */
    public function fetchLoyaltyData(string $url, ?string $token = null, int $timeout = 30): array
    {
        // Handle test data if "mock.test" is in URL and amount parameter is present
        if (str_contains($url, self::MOCK_HOST) && str_contains($url, self::MOCK_PARAM . '=')) {
            return $this->getMockLoyaltyData($url);
        }

        try {
            $this->curl->setTimeout($timeout);
            if ($token) {
                $this->curl->addHeader('Authorization', 'Bearer ' . $token);
            }

            $this->curl->get($url);
            $response = $this->curl->getBody();
            $status = $this->curl->getStatus();
            if ($status !== 200) {
                throw new \RuntimeException('Loyalty API error, status: ' . $status);
            }
            if (!$response) {
                $this->logger->warning('Empty response from loyalty API for URL: ' . $url);
                return [];
            }

            return (array)$this->serializer->unserialize($response);
        } catch (\Exception $e) {
            $this->logger->warning('Error fetching loyalty data via CURL: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * @param string $url
     *
     * @return array
     */
    private function getMockLoyaltyData(string $url): array
    {
        $after_amount = explode('amount=', $url)[1] ?? '';
        $amount = explode('/', $after_amount)[0];
        if (!$amount) {
            $amount = 0;
        }
        $customerId = 12345;
        $windowDays = 90;

        return [
            'customer_id'  => $customerId,
            'window_days'  => $windowDays,
            'spent_amount' => (float)$amount,
            'currency'     => 'UAH',
            'as_of'        => date('c'),
        ];
    }
}
