<?php
declare(strict_types=1);

namespace Prostor\CumDiscount\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class Thresholds extends AbstractFieldArray
{
    /**
     * Prepare rendering
     */
    protected function _prepareToRender()
    {
        $this->addColumn('amount', [
            'label' => __('Amount'),
            'class' => 'validate-number',
        ]);
        $this->addColumn('discount', [
            'label' => __('Discount (%)'),
            'class' => 'validate-number',
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Threshold');
    }
}
