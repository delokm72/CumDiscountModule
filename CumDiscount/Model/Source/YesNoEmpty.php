<?php

declare(strict_types=1);

namespace Prostor\CumDiscount\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class YesNoEmpty extends AbstractSource
{

    public function getAllOptions(): array
    {
        return [
            ['label' => '-- Please Select --', 'value' => ''],
            ['label' => 'Yes', 'value' => 1],
            ['label' => 'No', 'value' => 0],
        ];
    }
}