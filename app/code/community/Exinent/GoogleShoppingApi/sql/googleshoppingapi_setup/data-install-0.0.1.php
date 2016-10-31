<?php
/**
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */

/**
 * GoogleShopping install
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
/** @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;

if (Mage::helper('googleshoppingapi')->isModuleEnabled('Mage_GoogleShopping')) {
    $typesInsert = $installer->getConnection()
        ->select()
        ->from(
            $installer->getTable('googleshopping/types'),
            array(
                'type_id',
                'attribute_set_id',
                'target_country',
            )
        )
        ->insertFromSelect($installer->getTable('googleshoppingapi/types'));

    $itemsInsert = $installer->getConnection()
        ->select()
        ->from(
            $installer->getTable('googleshopping/items'),
            array(
                'item_id',
                'type_id',
                'product_id',
                'gcontent_item_id',
                'store_id',
                'published',
                'expires'
            )
        )
        ->insertFromSelect($installer->getTable('googleshoppingapi/items'));

    $attributes = '';
    foreach (Mage::getModel('googleshoppingapi/config')->getAttributes() as $destAttribtues) {
        foreach ($destAttribtues as $code => $info) {
            $attributes .= "'$code',";
        }
    }
    $attributes = rtrim($attributes, ',');
    $attributesInsert = $installer->getConnection()
        ->select()
        ->from(
            $installer->getTable('googleshopping/attributes'),
            array(
                'id',
                'attribute_id',
                'gcontent_attribute' => new Zend_Db_Expr("IF(gcontent_attribute IN ($attributes), gcontent_attribute, '')"),
                'type_id',
            )
        )
        ->insertFromSelect($installer->getTable('googleshoppingapi/attributes'));

    $installer->run($typesInsert);
    $installer->run($attributesInsert);
    $installer->run($itemsInsert);
}
