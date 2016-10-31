<?php

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Catalog_Category_Checkboxes_Tree
    extends Mage_Adminhtml_Block_Catalog_Category_Checkboxes_Tree
{

    protected function _prepareLayout()
    {
        $this->setTemplate('googlebasefeedgenerator/system/config/tree.phtml');
    }

    public function getLoadTreeUrl($expanded = null)
    {
        $params = array('_current' => true, 'id' => null, 'store' => null);
        return Mage::helper("adminhtml")->getUrl('*/*/categoriesJson', $params);
    }

    // FIX: found one store where getRoot was not returning the object,
    // and figured that parent::parent::getRoot() works
    public function getRoot($parentNodeCategory=null, $recursionLevel=3)
    {
        // copied from parent Mage_Adminhtml_Block_Catalog_Category_Checkboxes_Tree
        $root = $this->getRootByIds($this->getCategoryIds());
        if ($root) {
            return $root;
        }

        // copied from parent::parent Mage_Adminhtml_Block_Catalog_Category_Abstract
        if (!is_null($parentNodeCategory) && $parentNodeCategory->getId()) {
            return $this->getNode($parentNodeCategory, $recursionLevel);
        }
        $root = Mage::registry('root');
        if (is_null($root)) {
            $storeId = (int) $this->getRequest()->getParam('store');

            if ($storeId) {
                $store = Mage::app()->getStore($storeId);
                $rootId = $store->getRootCategoryId();
            }
            else {
                $rootId = Mage_Catalog_Model_Category::TREE_ROOT_ID;
            }

            $tree = Mage::getResourceSingleton('catalog/category_tree')
                ->load(null, $recursionLevel);

            if ($this->getCategory()) {
                $tree->loadEnsuredNodes($this->getCategory(), $tree->getNodeById($rootId));
            }

            $tree->addCollectionData($this->getCategoryCollection());

            $root = $tree->getNodeById($rootId);

            if ($root && $rootId != Mage_Catalog_Model_Category::TREE_ROOT_ID) {
                $root->setIsVisible(true);
            }
            elseif($root && $root->getId() == Mage_Catalog_Model_Category::TREE_ROOT_ID) {
                $root->setName(Mage::helper('catalog')->__('Root'));
            }

            Mage::register('root', $root);
        }

        return $root;
    }
}