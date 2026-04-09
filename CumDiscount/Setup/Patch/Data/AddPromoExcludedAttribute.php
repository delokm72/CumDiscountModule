<?php

declare(strict_types=1);

namespace Prostor\CumDiscount\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Prostor\CumDiscount\Model\Source\YesNoEmpty;

class AddPromoExcludedAttribute implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory          $eavSetupFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory          $eavSetupFactory
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            'promo_excluded',
            [
                'type'                    => 'int',
                'label'                   => 'Promo Excluded',
                'input'                   => 'select',
                'source'                  => YesNoEmpty::class,
                'required'                => false,
                'sort_order'              => 100,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'used_in_product_listing' => false,
                'user_defined'            => true,
                'visible'                 => true,
                'system'                  => true,
                'default'                 => '0',
                'group'                   => 'General',
                'is_used_in_grid'         => false,
                'is_visible_in_grid'      => false,
                'is_filterable_in_grid'   => false,
            ]
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
