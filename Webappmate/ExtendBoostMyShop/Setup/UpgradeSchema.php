<?php

namespace Webappmate\ExtendBoostMyShop\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;


/**
 * Upgrade the Catalog module DB scheme
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('bms_supplier'),
            'tablerate_condition',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 30,
                'nullable' => true,
                'comment' => 'Supplier tablerate shipping condition'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_item'),
            'supplier_rate',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'default' => '0.0000',
                'nullable' => false,
                'comment' => 'Supplier tablerate price'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_item'),
            'supplier_rate',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'default' => '0.0000',
                'nullable' => false,
                'comment' => 'Supplier tablerate price'
            ]
        );
        $setup->endSetup();
    }

}
