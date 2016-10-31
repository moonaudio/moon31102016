<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Google Content Type resource model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Resource_Type extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('googleshoppingapi/types', 'type_id');
    }

    /**
     * Return Type ID by Attribute Set Id and target country
     *
     * @param Exinent_GoogleShoppingApi_Model_Type $model
     * @param int $attributeSetId Attribute Set
     * @param string $targetCountry Two-letters country ISO code
     * @return Exinent_GoogleShoppingApi_Model_Type
     */
    public function loadByAttributeSetIdAndTargetCountry($model, $attributeSetId, $targetCountry)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
            ->where('attribute_set_id=?', $attributeSetId)
            ->where('target_country=?', $targetCountry);

        $data = $this->_getReadAdapter()->fetchRow($select);
        $data = is_array($data) ? $data : array();
        $model->setData($data);
        return $model;
    }
}
