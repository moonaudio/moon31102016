<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 */

/**
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @author     RocketWeb
 */

/** @var $installer RocketWeb_GoogleBaseFeedGenerator_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

$time = new Zend_Date(Mage::getModel('core/date')->timestamp(time()), Zend_Date::TIMESTAMP);

// Update old installs to new config tables, create the initial feeds
$feeds = array();
$stores = $installer->getConnection()->fetchAll("SELECT * FROM `{$this->getTable('core_store')}`");
$rows = $installer->getConnection()->fetchAll("SELECT * from `{$this->getTable('core_config_data')}` WHERE path LIKE 'rocketweb_googlebasefeedgenerator/%'");

if (count($rows)) {
    foreach ($stores as $s) {
        $feeds[$s['store_id']] = array();
        foreach ($rows as $row) {
            if ($row['scope'] == 'default' && $row['scope_id'] == 0) {
                $feeds[$s['store_id']][$row['path']] = $row['value'];
            }
        }
        foreach ($rows as $row) {
            if ($row['scope'] == 'websites' && $row['scope_id'] == $s['website_id']) {
                $feeds[$s['store_id']][$row['path']] = $row['value'];
            }
        }
        foreach ($rows as $row) {
            if ($row['scope'] == 'stores' && $row['scope_id'] == $s['store_id']) {
                $feeds[$s['store_id']][$row['path']] = $row['value'];
            }
        }
    }

    $installer->getConnection()->beginTransaction();
    foreach ($feeds as $store_id => $conf) {
        if ($store_id > 0 && count($conf) && $stores[$store_id]['name'] != 'Admin') {

            $active = array_key_exists('rocketweb_googlebasefeedgenerator/settings/is_turned_on', $conf) ? $conf['rocketweb_googlebasefeedgenerator/settings/is_turned_on'] : false;
            if (!$active) {
                $active = array_key_exists('rocketweb_googlebasefeedgenerator/file/is_turned_on', $conf) ? $conf['rocketweb_googlebasefeedgenerator/file/is_turned_on'] : false;
            }

            $installer->getConnection()->insert($this->getTable('googlebasefeedgenerator/feed'), array(
                'store_id' => $store_id,
                'name' => 'Default - '. $stores[$store_id]['name'],
                'type' => RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Type::TYPE_GOOGLE_SHOPPING,
                'status' => $active ? RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_SCHEDULED : RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_DISABLED,
                'updated_at' => $time->get(Zend_Date::ISO_8601)
            ));

            $feed_id = $installer->getConnection()->lastInsertId();
            foreach ($conf as $path => $value) {
                $path = str_replace('/', '_', str_replace('rocketweb_googlebasefeedgenerator/', '', $path));
                $path = str_replace('file_', 'general_', $path);

                if (in_array($path, array('general_is_turned_on'))) {
                    continue;
                }

                if ($path == 'filters_product_types' && empty($value)) {
                    continue;
                }

                $path = str_replace('configurable_products_', 'configurable_', $path);
                $path = str_replace('grouped_products_', 'grouped_', $path);
                $path = str_replace('bundle_products_', 'bundle_', $path);

                $path = str_replace('columns_google_product_category_by_category', 'categories_provider_taxonomy_by_category', $path);
                $path = str_replace('columns_product_type_by_category', 'categories_product_type_by_category', $path);
                $path = str_replace('columns_adwords_price_buckets', 'filters_adwords_price_buckets', $path);

                $path = str_replace('columns_apply_catalog_price_rules', 'general_apply_catalog_price_rules', $path);
                $path = str_replace('columns_format_prices_locale', 'general_format_prices_locale', $path);
                $path = str_replace('columns_use_default_stock', 'general_use_default_stock', $path);
                $path = str_replace('columns_stock_attribute_code', 'general_stock_attribute_code', $path);
                $path = str_replace('columns_max_title_length', 'general_max_title_length', $path);
                $path = str_replace('columns_max_description_length', 'general_max_description_length', $path);

                $path = str_replace('product_options_options_mode', 'options_mode', $path);
                $path = str_replace('product_options_vary_categories', 'options_vary_categories', $path);

                $installer->getConnection()->insert($this->getTable('googlebasefeedgenerator/feed_config'), array(
                    'feed_id'   => $feed_id,
                    'path'      => $path,
                    'value'     => $value
                ));
            }
            /**
             * Adding general_currency to the feed
             */
            $store = Mage::app()->getStore($store_id);
            if ($store) {
                $installer->getConnection()->insert($this->getTable('googlebasefeedgenerator/feed_config'), array(
                    'feed_id'   => $feed_id,
                    'path'      => 'general_currency',
                    'value'     => $store->getDefaultCurrencyCode()
                ));
            }
            /*
             * Installing default schedule config
             */
            $default_limit = Mage::getConfig()->getNode('default/general/batch_limit')->asArray();
            $start_at = Mage::getModel('googlebasefeedgenerator/feed_schedule')->getNextStartAt();
            $installer->getConnection()->insert($this->getTable('googlebasefeedgenerator/feed_schedule'), array(
                'feed_id'       => $feed_id,
                'start_at'      => $start_at,
                'processed_at'  => 0,
                'batch_mode'    => array_key_exists('rocketweb_googlebasefeedgenerator/file/use_batch_segmentation', $conf) ? $conf['rocketweb_googlebasefeedgenerator/file/use_batch_segmentation'] : false,
                'batch_limit'   => array_key_exists('rocketweb_googlebasefeedgenerator/file/batch_limit', $conf) ? $conf['rocketweb_googlebasefeedgenerator/file/batch_limit'] : $default_limit
            ));
        }
    }
    $installer->getConnection()->commit();
}

// Update process table to use feed_id instead of store_id
if ($installer->getConnection()->tableColumnExists($this->getTable('googlebasefeedgenerator/process'), 'store_id')
    && !$installer->getConnection()->tableColumnExists($this->getTable('googlebasefeedgenerator/process'), 'feed_id')) {
    $installer->getConnection()->changeColumn($this->getTable('googlebasefeedgenerator/process'), 'store_id', 'feed_id', 'int(11) unsigned NOT NULL', true);
    $installer->getConnection()->addForeignKey($installer->getFkName('googlebasefeedgenerator/process', 'feed_id', 'googlebasefeedgenerator/feed', 'id'),
        $this->getTable('googlebasefeedgenerator/process'), 'feed_id', $installer->getTable('googlebasefeedgenerator/feed'), 'id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE);
}

// Update old installs to new taxonomy widget
$feeds = Mage::getModel('googlebasefeedgenerator/feed')->getCollection();

foreach ($feeds as $feed) {
    $feed->load($feed->getId()); // populates default data
    $config = $feed->getConfig();
    $originalCategories = isset($config['categories_provider_taxonomy_by_category']) ? $config['categories_provider_taxonomy_by_category'] : null;
    $categoryTree = isset($config['filters_category_tree_include']) ? $config['filters_category_tree_include'] : null;

    $enabledCategories = array();
    $includeProductsWithoutCategories = false;
    if ($categoryTree == null) {
        $categories = Mage::helper('googlebasefeedgenerator')->getAllCategories($feed);
        $enabledCategories = array_keys($categories);
        $includeProductsWithoutCategories = true;
    } else if (!is_array($categoryTree)) {
        $enabledCategories = explode(',', $categoryTree);
    } else {
        $enabledCategories = $categoryTree;
    }

    $taxonomyCategories = array();
    if ($originalCategories != null && is_array($originalCategories) && count($originalCategories) > 0) {
        foreach($originalCategories as $category) {
            if (isset($category['category'])) {
                $taxonomyCategories[$category['category']] = array(
                    'category' => $category['category'],
                    'value' => isset($category['value']) ? $category['value'] : '',
                    'disabled' => isset($category['disabled']) ? $category['disabled'] : !in_array($category['category'], $enabledCategories)
                );
            }
        }
    }

    foreach($enabledCategories as $categoryId) {
        if (!isset($taxonomyCategories[$categoryId])) {
            $taxonomyCategories[$categoryId] = array(
                'category' => $categoryId,
                'value' => '',
                'disabled' => false
            );
        }
    }

    // Save the new format for taxonomy categories
    $sql = Mage::getModel('googlebasefeedgenerator/config')->getCollection()
        ->addFieldToSelect('id')
        ->addFieldToFilter('feed_id', $feed->getId())
        ->addFieldToFilter('path', 'categories_provider_taxonomy_by_category')
        ->getSelect();
    $configId = $installer->getConnection()->fetchOne($sql);

    Mage::getModel('googlebasefeedgenerator/config')
        ->load($configId)
        ->addData(array(
            'feed_id' => $feed->getId(),
            'path' => 'categories_provider_taxonomy_by_category',
            'value' => $taxonomyCategories
        ))->save();

    // Save the include all categories switch
    $sql = Mage::getModel('googlebasefeedgenerator/config')->getCollection()
        ->addFieldToSelect('id')
        ->addFieldToFilter('feed_id', $feed->getId())
        ->addFieldToFilter('path', 'categories_include_all_products')
        ->getSelect();
    $configId = $installer->getConnection()->fetchOne($sql);

    $model = Mage::getModel('googlebasefeedgenerator/config')
        ->load($configId)
        ->addData(array(
            'feed_id' => $feed->getId(),
            'path' => 'categories_include_all_products',
            'value' => $includeProductsWithoutCategories
        ))->save();
}

$installer->endSetup();