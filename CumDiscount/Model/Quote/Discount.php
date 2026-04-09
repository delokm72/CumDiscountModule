<?php

declare(strict_types=1);

namespace Prostor\CumDiscount\Model\Quote;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Prostor\CumDiscount\Logger\Logger as ProstorLogger;
use Prostor\CumDiscount\Model\Config\Configuration;
use Prostor\CumDiscount\Service\LoyaltyService;

class Discount extends AbstractTotal
{
    private const TOTAL_CODE = 'cumulative_discount';

    /**
     * @param Configuration $configuration
     * @param LoyaltyService $loyaltyService
     * @param ProstorLogger $logger
     */
    public function __construct(
        private readonly Configuration $configuration,
        private readonly LoyaltyService $loyaltyService,
        private readonly ProstorLogger $logger
    ) {
        $this->setCode(self::TOTAL_CODE);
    }

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ): static {
        parent::collect($quote, $shippingAssignment, $total);
        try {
            $items = $shippingAssignment->getItems();
            if (empty($items)) {
                $items = $quote->getAllVisibleItems();
            }

            [$discountAmount, $baseDiscountAmount] = $this->calculateDiscountTotals($quote, $items);

            if ($discountAmount > 0) {
                $total->addTotalAmount($this->getCode(), -$discountAmount);
                $total->addBaseTotalAmount($this->getCode(), -$baseDiscountAmount);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Prostor Cumulative Discount unexpected error ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * @param float $spentAmount
     * @param int $storeId
     * @return float
     */
    private function calculateDiscountPercent(float $spentAmount, int $storeId): float
    {
        $thresholds = $this->configuration->getThresholds($storeId);
        if (empty($thresholds)) {
            return 0.0;
        }

        usort($thresholds, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        foreach ($thresholds as $threshold) {
            if ($spentAmount >= (float)$threshold['amount']) {
                return (float)$threshold['discount'];
            }
        }

        return 0.0;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total): array
    {
        $amount = (float)$total->getData($this->getCode() . '_amount');
        if ($amount == 0.0) {
            $amount = $this->calculateDiscountAmountForQuote($quote);
        }

        if ($amount != 0) {
            return [
                'code' => $this->getCode(),
                'title' => __('Prostor Cumulative'),
                'value' => $amount
            ];
        }
        return [];
    }

    private function calculateDiscountAmountForQuote(Quote $quote): float
    {
        [$discountAmount] = $this->calculateDiscountTotals($quote, $quote->getAllVisibleItems());
        return -$discountAmount;
    }

    /**
     * @param Quote $quote
     * @param array $items
     * @return array{0:float,1:float}
     */
    private function calculateDiscountTotals(Quote $quote, array $items): array
    {
        $storeId = (int)$quote->getStoreId();
        if (!$this->configuration->isEnabled($storeId)) {
            return [0.0, 0.0];
        }

        $customerId = $quote->getCustomerId();
        if (!$customerId || empty($items)) {
            return [0.0, 0.0];
        }

        $spentAmount = $this->loyaltyService->getSpentAmount((int)$customerId, $storeId);
        if ($spentAmount <= 0) {
            return [0.0, 0.0];
        }

        $discountPercent = $this->calculateDiscountPercent($spentAmount, $storeId);
        if ($discountPercent <= 0) {
            return [0.0, 0.0];
        }

        $discountAmount = 0.0;
        $baseDiscountAmount = 0.0;
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $product = $item->getProduct();
            if ($product && (int)$product->getData('promo_excluded') === 1) {
                continue;
            }

            $rowTotal = $item->getRowTotalInclTax() - $item->getDiscountAmount();
            $baseRowTotal = $item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount();
            if ($rowTotal <= 0 || $baseRowTotal <= 0) {
                continue;
            }

            $discountAmount += $rowTotal * ($discountPercent / 100);
            $baseDiscountAmount += $baseRowTotal * ($discountPercent / 100);
        }

        return [$discountAmount, $baseDiscountAmount];
    }
}
