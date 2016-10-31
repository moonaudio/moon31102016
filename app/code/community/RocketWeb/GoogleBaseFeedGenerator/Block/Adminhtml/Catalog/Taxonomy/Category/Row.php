<?php
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   RocketWeb_GoogleBaseFeedGenerator
 * @copyright Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */

/**
 * Class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Catalog_Taxonomy_Category_Row
 *
 * @method $this setCategory() setCategory(array $category)
 * @method array getCategory()
 * @method $this setChildren() setChildren(array $children)
 * @method array getChildren()
 * @method $this setNames() setNames(array $names)
 * @method array getNames()
 * @method $this setParent() setParent(RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Catalog_Taxonomy_Category $parent)
 * @method RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Catalog_Taxonomy_Category getParent()
 */
class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Catalog_Taxonomy_Category_Row
    extends Mage_Core_Block_Template
{
    /**
     * Setting the template
     *
     * @param array $args
     */
    public function __construct(array $args = array())
    {
        parent::__construct($args);
        $this->setTemplate('googlebasefeedgenerator/catalog/taxonomy/row.phtml');
    }
}