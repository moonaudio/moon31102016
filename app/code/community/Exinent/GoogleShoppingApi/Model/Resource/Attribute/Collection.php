<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * GoogleShopping Attributes collection
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Resource_Attribute_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Whether to join attribute_set_id to attributes or not
     *
     * @var bool
     */
    protected $_joinAttributeSetFlag = true;

    protected function _construct()
    {
        $this->_init('googleshoppingapi/attribute');
    }

    /**
     * Add attribute set filter
     *
     * @param int $attributeSetId
     * @param string $targetCountry two words ISO format
     * @return Exinent_GoogleShoppingApi_Model_Mysql4_Attribute_Collection
     */
    public function addAttributeSetFilter($attributeSetId, $targetCountry)
    {
        if (!$this->getJoinAttributeSetFlag()) {
            return $this;
        }
        $this->getSelect()->where('attribute_set_id = ?', $attributeSetId);
        $this->getSelect()->where('target_country = ?', $targetCountry);
        return $this;
    }

    /**
     * Add type filter
     *
     * @param int $type_id
     * @return Exinent_GoogleShoppingApi_Model_Mysql4_Attribute_Collection
     */
    public function addTypeFilter($type_id)
    {
        $this->getSelect()->where('main_table.type_id = ?', $type_id);
        return $this;
    }

    /**
     * Load collection data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return  Exinent_GoogleShoppingApi_Model_Mysql4_Attribute_Collection
     */
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }
        if ($this->getJoinAttributeSetFlag()) {
            $this->_joinAttributeSet();
        }
        parent::load($printQuery, $logQuery);
        return $this;
    }

    /**
     * Join attribute sets data to select
     *
     * @return  Exinent_GoogleShoppingApi_Model_Mysql4_Attribute_Collection
     */
    protected function _joinAttributeSet()
    {
        $this->getSelect()
            ->joinInner(
                array('types'=>$this->getTable('googleshoppingapi/types')),
                'main_table.type_id=types.type_id',
                array('attribute_set_id' => 'types.attribute_set_id', 'target_country' => 'types.target_country'));
        return $this;
    }

    /**
     * Get flag - whether to join attribute_set_id to attributes or not
     *
     * @return bool
     */
    public function getJoinAttributeSetFlag()
    {
        return $this->_joinAttributeSetFlag;
    }

    /**
     * Set flag - whether to join attribute_set_id to attributes or not
     *
     * @param bool $flag
     * @return bool
     */
    public function setJoinAttributeSetFlag($flag)
    {
        return $this->_joinAttributeSetFlag = (bool)$flag;
    }
}
