<?php
/**
 * Magento Module Exinent_GoogleShoppingApi
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */

/**
 * Category module helper
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Helper_Category
{
    const CATEGORY_APPAREL  = 'Apparel &amp; Accessories';
    const CATEGORY_CLOTHING = 'Apparel &amp; Accessories &gt; Clothing';
    const CATEGORY_SHOES    = 'Apparel &amp; Accessories &gt; Shoes';
    const CATEGORY_BOOKS    = 'Media &gt; Books';
    const CATEGORY_DVDS     = 'Media &gt; DVDs &amp; Videos';
    const CATEGORY_MUSIC    = 'Media &gt; Music';
    const CATEGORY_VGAME    = 'Software &gt; Video Game Software';
    const CATEGORY_OTHER    = 'Other';

    /**
     * Retrieve list of Google Product Categories
     *
     * @return array
     */
    public function getCategories($addOther = true)
    {
        $categories = array(
            self::CATEGORY_APPAREL, self::CATEGORY_CLOTHING, self::CATEGORY_SHOES, self::CATEGORY_BOOKS,
            self::CATEGORY_DVDS, self::CATEGORY_MUSIC, self::CATEGORY_VGAME
        );
        if ($addOther) {
            $categories[] = self::CATEGORY_OTHER;
        }
        return $categories;
    }

    /**
     * Get error message for required attributes
     *
     * @return string
     */
    public function getMessage()
    {
        return sprintf(
            Mage::helper('googleshoppingapi')->__("For information on Google's required attributes for different product categories, please see this link: %s"),
            '<a href="http://www.google.com/support/merchants/bin/answer.py?answer=1344057" target="_blank">'
                . 'http://www.google.com/support/merchants/bin/answer.py?answer=1344057'
                . '</a>'
        );
    }
}
