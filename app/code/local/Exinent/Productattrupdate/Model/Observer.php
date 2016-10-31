<?php

class Exinent_Productattrupdate_Model_Observer {

    public function createupdatesortattributes(Varien_Event_Observer $observer) {
        $productObject = $observer->getEvent()->getProduct();
        $ratingInstance = $this->getCategoryAttrInstance($productObject->getRatingCategory());
        $ratingCategoryLabel = $ratingInstance->getAttributeText('rating_category');
        $productRatingInstance = $this->getRatingAttrInstance($productObject->getProductRating());
        $productRatingLabel = $productRatingInstance->getAttributeText('product_rating');
        if ($productObject->getRatingPriority() != null) {
            $categoryNumber = $this->getCategoryNumber($ratingCategoryLabel);
            $ratingNumber = $categoryNumber + $productObject->getRatingPriority();
            $attributeLabel = $this->getAttributeLableId($ratingNumber);
            if ($attributeLabel == null) {
                $this->createAttributeLableOption($ratingNumber);
                $attributeLabel = $this->getAttributeLableId($ratingNumber);
                $productObject->setProductRating($attributeLabel);
            } else {
                $productObject->setProductRating($attributeLabel);
            }
        } else {
            $defaultAttributeLable = $this->getAttributeLableId('49999');
            $productObject->setProductRating($defaultAttributeLable);
        } 
        
    }

    public function getAttributeLableId($ratingNumber) {
        $attr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'product_rating');
        $color_label = $attr->getSource()->getOptionId($ratingNumber);
        return $color_label;
    }

    public function createAttributeLableOption($ratingNumber) {
        $attr_model = Mage::getModel('catalog/resource_eav_attribute');
        $attr = $attr_model->loadByCode('catalog_product', 'product_rating');
        $attr_id = $attr->getAttributeId();
        $option = array();
        $option['attribute_id'] = $attr_id;
        $option['value'][0][0] = $ratingNumber;
        $option['value'][0][1] = $ratingNumber;
        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
        $setup->addAttributeOption($option);
        return;
    }

    public function getCategoryAttrInstance($attrid) {
        $storeId = Mage::app()->getStore()->getStoreId();
        $product = Mage::getModel('catalog/product')->setStoreId($storeId)->setRatingCategory($attrid);
        return $product;
    }

    public function getRatingAttrInstance($attrid) {
        $storeId = Mage::app()->getStore()->getStoreId();
        $product = Mage::getModel('catalog/product')->setStoreId($storeId)->setProductRating($attrid);
        return $product;
    }

    public function getCategoryNumber($ratingCategoryLabel) {
        switch ($ratingCategoryLabel) {
            case 'New':
                return 10000;
            case 'Best':
                return 20000;
            case 'Better':
                return 30000;
            case 'Good':
                return 40000;
        }
    }

public function reIndex() {
      
$indexingProcesses = Mage::getSingleton('index/indexer')->getProcessesCollection(); 

 foreach ($indexingProcesses as $process) {
  $process->reindexEverything();
}

    }

}
