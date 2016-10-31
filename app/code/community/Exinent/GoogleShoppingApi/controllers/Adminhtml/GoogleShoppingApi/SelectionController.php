<?php
/**
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */

/**
 * GoogleShopping Products selection grid controller
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Adminhtml_GoogleShoppingApi_SelectionController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Search result grid with available products for Google Content
     */
    public function searchAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('googleshoppingapi/adminhtml_items_product')
                ->setIndex($this->getRequest()->getParam('index'))
                ->setFirstShow(true)
                ->toHtml()
           );
    }

    /**
     * Grid with available products for Google Content
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('googleshoppingapi/adminhtml_items_product')
                ->setIndex($this->getRequest()->getParam('index'))
                ->toHtml()
           );
    }
}
