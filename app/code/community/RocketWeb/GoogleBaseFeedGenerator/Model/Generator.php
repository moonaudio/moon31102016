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
 * Class RocketWeb_GoogleBaseFeedGenerator_Model_Generator
 *
 * @method getOutputColumns() array What columns to process and output in the map, defaults to empty (process all columns)
 * @method RocketWeb_GoogleBaseFeedGenerator_Model_Feed getFeed()
 * @method boolean hasFeed()
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Generator extends Varien_Object
{

    const PRODUCT_TYPE_ASSOC = 'simple_associated';

    protected $_lockFile;
    protected $_count_products_exported = -1;
    protected $_count_products_skipped = 0;

    protected $_columns_map = null;
    protected $_empty_columns_replace_map = null;

    protected $_collection = null;
    protected $_current_iter = 0;

    protected function _construct()
    {
        if (!$this->hasFeed() || !($this->getFeed() instanceof RocketWeb_GoogleBaseFeedGenerator_Model_Feed)) {
            Mage::throwException('Generator cannot be initialized without a vald Feed object.');
        }

        $this->addData(array(
            'store_id'              => $this->getFeed()->getStoreId(),
            'website_id'            => $this->getFeed()->getStore()->getWebsiteId(),
            'store_currency_code'   => $this->getFeed()->getStore()->getDefaultCurrencyCode(),
            'batch_mode'            => $this->getFeed()->getSchedule()->getBatchMode(),
            'batch_limit'           => $this->getFeed()->getSchedule()->getBatchLimit(),
            'started_at'            => time(),
            'progress_timing'       => Mage::getModel('core/date')->timestamp(time()),
        ));

        // Initialize locks, skip locks is used with PDP microdata
        if (!$this->getSkipLocks()) {
            Mage::helper('googlebasefeedgenerator')->initSavePath(dirname($this->getFeedPath()));

            $this->_lockFile = @fopen($this->getLockPath(), "w");
            if (!file_exists($this->getLockPath())) {
                Mage::throwException(sprintf('Can\'t create file %s', $this->getLockPath()));
            }

            // If the location is not writable, flock() does not work and it doesn't mean another script instance is running
            if (!is_writable($this->getLockPath())) {
                Mage::throwException(sprintf('Not enough permissions. Location [%s] must be writable', $this->getLockPath()));
            }
        }
    }

    protected function initialize()
    {
        $this->_current_iter = 0;
        $this->getColumnsMap();
        $this->getEmptyColumnsReplaceMap();
        $this->loadAdditionalAttributes();
        $maxProductPrice = (float)$this->getFeed()->getConfig('filters_skip_price_above');
        if ($maxProductPrice > 0) {
            $this->setMaxProductPrice($maxProductPrice);
        }
        $minProductPrice = (float)$this->getFeed()->getConfig('filters_skip_price_below');
        if ($minProductPrice > 0) {
            $this->setMinProductPrice($minProductPrice);
        }
        return $this;
    }

    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Batch
     */
    public function getBatch()
    {
        if ($this->inBatch() && !$this->hasData('batch')) {
            if (!$this->getScheduleId()) {
                Mage::throwException(sprintf('Invalid schedule_id %s', $this->getScheduleId()));
            }
            $this->setData('batch', Mage::getModel(
                'googlebasefeedgenerator/batch', array(
                    'generator' => $this,
                    'schedule_id' => $this->getScheduleId(),
                )
            ));
        }

        return $this->getData('batch');
    }

    /**
     * @return $this
     */
    public function run()
    {
        $time   = Mage::getModel('core/date')->timestamp(time());
        $memory = memory_get_usage(true);

        // Another instance is writing to the feed
        if (!$this->acquireLock()) {
            Mage::throwException(sprintf('Another generator instance is writing the file for [%s]. Try again later.', $this->getFeed()->getName()));
        }

        // Attempt to run a full feed when batch not finished
        if (!$this->inBatch() && $this->batchInProgress()) {
            Mage::throwException(sprintf('Batch generation is in progress. Wait for the batch to finish or force this action by removing [%s]', $this->getBatchLockPath()));
        }


        if ($completedForToday = $this->inBatch() ? $this->getBatch()->completedForToday() : false) {
            Mage::throwException(sprintf('[%s] Feed Completed for Today %s! Wait till tomorrow or remove lock file: %s', $this->getScheduleId(), date('Y-m-d'), $this->getBatchLockPath()));
        }

        $this->log('START');
        if ($this->getData('verbose')) {
            session_start(); // fix for magento 1.4 complaining abut headers. Not sure why 1.4 initiates the session
        }

        $this->initialize();

        if ($this->inBatch()) {

            $this->getBatch()->setData('verbose', $this->getData('verbose'));
            $this->getBatch()->setTotalItems($this->getTotalItems());
            $batch_limit = ($this->getBatchLimit() <= $this->getTotalItems() ? $this->getBatchLimit() : $this->getTotalItems());
            $this->getBatch()->setLimit($batch_limit);

            // Lock cleanup
            $locked = $this->getBatch()->aquireLock();
            if (!$locked && !$completedForToday) {
                $this->log(sprintf('Previous batch did not complete. Clearing lock file %s', $this->getBatchLockPath()), Zend_Log::WARN);
                @unlink($this->getBatchLockPath());
                $this->getBatch()->lock();
            }

            if (!$this->getBatch()->getIsNew()) {
                $data = $this->getBatch()->readFile();
                $this->_current_iter = (int) $this->getBatch()->getOffset() - $this->getBatch()->getLimit();
                $this->_count_products_exported = (int) $data['items_added'];
                $this->_count_products_skipped = (int) $data['items_skipped'];
            }
        }

        $collection = $this->getCollection();
        if (!$this->inBatch() || ($this->inBatch() && $this->getBatch()->getIsNew())) {
            $this->writeFeed($this->getHeader(), false);
            // Clear processes every time but when batch mode and queue not completed
            $this->getTools()->clearProcess();
        }

        $product_types = $this->getFeed()->getConfig('filters_product_types');

        Mage::getSingleton('core/resource_iterator')->walk(
            $collection->getSelect(), array(array($this, 'processProductCallback')), array(
                'product_types' => $product_types,
            )
        );

        $this->closeTemporaryHandle()
            ->copyDataFromTemporaryFeedFile()
            ->setFeedFilePermissions()
            ->releaseLock();

        if ($this->getData('verbose')) {
            echo "---------------------------------------------------------------------\n";
        }
        $this->log(sprintf('Items: %d added, %d skipped | in file %s', $this->getCountProductsExported(), $this->getCountProductsSkipped(), $this->getFeedPath()));

        $t = round(Mage::getModel('core/date')->timestamp(time())-$time);
        $this->log('END / MEMORY USED: ' . $this->formatMemory(memory_get_usage(true) - $memory). ', TIME SPENT: '. sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60));

        return $this;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getProduct($id)
    {
        $product = Mage::getModel('catalog/product')
            ->setStoreId($this->getStoreId())
            ->setId($id);

        $product->getResource()->load($product, $id);

        return $product;
    }

    /**
     * Used on PDP microdata
     *
     * @param $product Mage_Catalog_Model_Product
     * @return array
     */
    public function generateProductMap($product)
    {
        $productMap = $this->getProductMapModel($product)
            ->setColumnsMap($this->getColumnsMap())
            ->setFeed($this->getFeed())
            ->setEmptyColumnsReplaceMap($this->getEmptyColumnsReplaceMap())
            ->initialize();

        return $productMap->map();
    }

    /**
     * Build the map model path based on product type
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $args
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract
     */
    public function getProductMapModel($product, $args = array())
    {
        $key = strtolower($product->getTypeId());
        $file = ucfirst($key);
        $model_args = array('feed' => $this->getFeed(), 'product' => $product);

        // Get SCP model if enabled for configurable products
        if ($product->isConfigurable() && Mage::helper('googlebasefeedgenerator')->isSimplePricingEnabled($product)) {
            $key .= '_scp';
            $file .= DS. 'Scp';
        }

        // If product has at least one parent, process it as associated model
        $parents = array_key_exists('parents', $args) ? $args['parents'] : false;
        if ($parents && array_filter($parents)) {
            $key .= '_associated';
            $file .= DS. 'Associated';
        }

        // Compute map model
        $mapModel = null;
        $file_path = Mage::getConfig()->getModuleDir('model', 'RocketWeb_GoogleBaseFeedGenerator')
            . DS. 'Model'. DS. 'Map'. DS. 'Product'. DS. $file. '.php';

        if (file_exists($file_path)) {
            $mapModel = Mage::getModel('googlebasefeedgenerator/map_product_'. $key, $model_args);
        }

        if (!$mapModel) {
            $mapModel = Mage::getModel('googlebasefeedgenerator/map_product_abstract', $model_args);
        }

        $this->setData('map_key', $key);
        return $mapModel;
    }

    /**
     * @param $args
     */
    public function processProductCallback($args)
    {
        $row = $args['row'];

        // Skip if product type is not enabled
        if (!$this->isProductTypeEnabled($row['type_id'])) {
            return;
        }

        $productParents = $this->getProductParents($row);

        // Memorise possible duplicate items and skip current simple product
        if (!$this->getTestMode() && $this->getTools()->lockDuplicates($row, $productParents)) {
            $this->_count_products_skipped++;
            return;
        }

        // Prepare product and map object
        $product = Mage::getModel('catalog/product')->setStoreId($this->getStoreId())->load($row['entity_id']);

        $productMap = $this->getProductMapModel($product, array('parents' => $productParents))
            ->setColumnsMap($this->getColumnsMap())
            ->setFeed($this->getFeed())
            ->setEmptyColumnsReplaceMap($this->getEmptyColumnsReplaceMap())
            ->initialize();

        $this->addProductToFeed($productMap);
        $this->_current_iter++;

        if ($this->getData('verbose')) {
            echo $this->formatMemory(memory_get_usage(true)) . " - SKU " . $args['row']['sku'] . ", ID " . $args['row']['entity_id'] . "\n";
        }

        $this->logProgress();

        // Free up memory
        $this->getTools()->clearNestedObject($product);
        $this->getTools()->unsConfigurableAttributesAsArray($product);

        if ($this->isCloseToPhpLimit()) {
            // Automatically swicth to batch mode
            if (!$this->inBatch()) {
                $this->log('Automatic switch to batch mode.');
                $this->setBatchMode(1);
                $this->getFeed()->getSchedule()->addData(array(
                    'batch_mode' => 1,
                    'batch_limit' => $this->_current_iter)
                )->save();

                $this->getBatch()->aquireLock();
            }

            // Terminating batch early
            if ($this->inBatch()) {
                $this->getBatch()->updateLockOffset($this->_current_iter);
                $this->releaseLock();
            }
            // Exit the iterator but don't set the feed as failed
            throw new RocketWeb_GoogleBaseFeedGenerator_Model_Exception('EARLY END / PHP Limits reached');
        }

        unset($product, $productMap, $row);
    }

    /**
     * @return $this
     */
    public function logProgress()
    {
        // Get correct magento hour
        $time = new Zend_Date(Mage::getModel('core/date')->timestamp(time()), Zend_Date::TIMESTAMP);

        if ($time->get(Zend_Date::TIMESTAMP) - $this->getProgressTiming() > 15
            || $this->_current_iter <= 1
            || $this->_current_iter == $this->getTotalItems()
            || ($this->inBatch() && ($this->_current_iter % $this->getBatch()->getLimit() == 0 || $this->isCloseToPhpLimit())))
        {
            $percent = sprintf('%d', round($this->_current_iter / $this->getTotalItems() * 100));
            if (!$this->getTestMode()) {
                $this->getFeed()->saveMessages(array(
                    'date' => $time->get(Zend_Date::ISO_8601),
                    'progress' => $percent,
                    'added' => $this->getCountProductsExported(),
                    'skipped' => $this->getCountProductsSkipped()
                ));
            }
            $this->log(sprintf("Processed %s", $percent). '%');
            $this->setProgressTiming($time->get(Zend_Date::TIMESTAMP));
        }
        return $this;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function getCollection()
    {
        if (is_null($this->_collection)) {
            $this->_collection = clone $this->_getCollection();

            if ($this->inBatch()) {
                $this->_collection->getSelect()->limit($this->getBatch()->getLimit(), $this->getBatch()->getOffset() - $this->getBatch()->getLimit());
            }
            elseif ($this->getTestMode())
            {
                if ($this->getTestSku()) {
                    $sku = $this->getTestSku();
                    $search = Mage::getResourceModel('catalog/product_collection')->addAttributeToFilter(
                        array(
                            array('attribute' => 'sku', 'eq' => $sku),
                            array('attribute' => 'entity_id', 'eq' => $sku)
                        )
                    );
                    /** @var Mage_Catalog_Model_Product $prod */
                    if ($prod = $search->getFirstItem()) {
                        $prod->load($prod->getId());
                        if (!$prod->isVisibleInSiteVisibility()) {
                            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($prod->getId());
                            if(!$parentIds) {
                                $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($prod->getId());
                            }
                            if (count($parentIds)) {
                                $prod->load($parentIds[0]);
                                $this->setMessages(array(array('msg' => 'Product entered is not visible in the store on i\'s own. Running test against it\'s parent '. $prod->getTypeId().' ID '. $prod->getId(), 'type' => 'info')));
                            }
                        }
                        $this->_collection->addAttributeToFilter('entity_id', $prod->getId());
                    }
                }
                elseif ($this->getTestOffset() >= 0 && $this->getTestLimit() > 0) {
                    $this->_collection->getSelect()->limit(($this->getTestLimit() > 0 ? $this->getTestLimit() : 0), ($this->getTestOffset() > 0 ? $this->getTestOffset() : 0));
                } else {
                    Mage::throwException(sprintf("Invalid parameters for test mode: sku %s or offset %s and limit %s", $this->getTestSku(), $this->getTestOffset(), $this->getTestLimit()));
                }
            }
        }
        return $this->_collection;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    protected function _getCollection()
    {
        /** @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->setStore($this->getFeed()->getStoreId())
            ->addStoreFilter($this->getFeed()->getStoreId());

        $this->addProductTypeToFilter($collection);

        // Filter visible / enabled products
        $collection->addAttributeToFilter('status', array('neq' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED));
        $collection
            ->addFieldToFilter('visibility', array('neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE));

        $includeAllProducts = $this->getFeed()->getConfig('categories_include_all_products');
        $categoryMap = $this->getFeed()->getConfig('categories_provider_taxonomy_by_category');
        $excludeCategories = array();
        foreach ($categoryMap as $category) {
            if (isset($category['category']) && isset($category['disabled']) && (bool)$category['disabled'] === true) {
                $excludeCategories[] = (int)$category['category'];
            }
        }

        $collection = $this->_addCategoriesToFilter($collection, $excludeCategories, (bool)$includeAllProducts);

        $cfg = $this->getFeed()->getConfig('filters_attribute_sets');
        if (count($cfg) && empty($cfg[0])) {
            array_shift($cfg);
        }
        $attribute_sets = !empty($cfg) ? $cfg : false;
        if ($attribute_sets) {
            $collection->addAttributeToFilter('attribute_set_id', $attribute_sets);
        }

        if (!$this->getFeed()->getConfig('filters_add_out_of_stock')) {
            $collection->addPriceData(null, $this->getData('website_id'));
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        }

        if (!$this->getTestMode() && Mage::getConfig()->getNode('default/debug/sku') != "") {
            $collection->addAttributeToFilter('sku', Mage::getConfig()->getNode('default/debug/sku'));
        }

        return $collection;
    }

    /**
     * Adds category ids to collection filter, adding join to category-product table if needed
     *
     * @param $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     * @param $categoryIds int[]
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    protected function _addCategoriesToFilter($collection, $categoryIds, $includeAllProducts)
    {
        $where = array();

        if (count($categoryIds) > 0 || !$includeAllProducts) {

            $joinCond = 'cat_product.product_id=e.entity_id';
            $fromPart = $collection->getSelect()->getPart(Zend_Db_Select::FROM);

            if (isset($fromPart['cat_product'])) {
                $fromPart['cat_product']['joinCondition'] = $joinCond;
                $collection->getSelect()->setPart(Zend_Db_Select::FROM, $fromPart);
            } else {
                $collection->getSelect()->joinLeft(
                    array('cat_product' => $collection->getTable('catalog/category_product')),
                    $joinCond
                );
            }
        }
        if (!$includeAllProducts) {
            $where[] = 'cat_product.category_id IS NOT NULL';
        }

        if (count($categoryIds) > 0) {
            $cond = $collection->getConnection()->quoteInto('cat_product.category_id NOT IN (' . implode(',', $categoryIds) . ')', "");
            if ($includeAllProducts) {
                $cond .= ' OR cat_product.category_id IS NULL';
            }
            $where[] = $cond;
        }

        if (count($where) > 0) {
            $where = '(' . implode(' AND ', $where) . ')';
            $collection->getSelect()->where($where);
        }
        $collection->getSelect()->group('e.entity_id');
        return $collection;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    protected function addProductTypeToFilter($collection)
    {
        $default_product_types = array(
            Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
            Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
            Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
            Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
            Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
            Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
        );

        $product_types = $this->getFeed()->getConfig('filters_product_types');
        $not_in_product_types = array_diff($default_product_types, $product_types);
        $in_product_types = array_diff($product_types, $default_product_types);

        if (count($in_product_types)) {
            $collection->addAttributeToFilter('type_id', array('in' => $product_types));
        }

        if (count($not_in_product_types) > 0) {
            $collection->addAttributeToFilter('type_id', array('nin' => $not_in_product_types));
        }

        return $collection;
    }

    /**
     * Returns columns map in asc order.
     * Skips columns with attributes that doesn't exist.
     * Caches eav attributes model used.
     *
     *  [column] =>
     *            [column]
     *            [attribute code or directive code]
     *            [default_value]
     *            [order]
     *
     * @return array
     */
    public function getColumnsMap()
    {
        if (!is_null($this->_columns_map)) {
            return $this->_columns_map;
        }

        $tmp = $cfg_map = $this->getFeed()->getConfig('columns_map_product_columns');

        foreach ($tmp as $k => $arr) {
            if (!$this->getFeed()->isAllowedDirective($arr['attribute'])) {
                $attribute = $this->getAttribute($arr['attribute']);
                if ($attribute == false) {
                    $this->log(sprintf("Column '%s' ignored, can't find attribute with code '%s'.", $arr['column'], $arr['attribute']), Zend_Log::WARN);
                    unset($cfg_map[$k]);
                    continue;
                }
                $attribute->setStoreId($this->getData('store_id'));
                $this->setAttribute($attribute);
            }
        }
        $this->_columns_map = array();
        $output_columns = $this->getOutputColumns();
        foreach ($cfg_map as $arr) {
            if (empty($output_columns) || in_array($arr['column'], $output_columns)) {
                $this->_columns_map[$arr['column']] = $arr;
            }
        }

        // Check attribute assigned to availability column (stock status).
        if (!$this->getFeed()->getConfig('general_use_default_stock') && isset($this->_columns_map['availability']) && $this->getFeed()->getConfig('general_stock_attribute_code') !== "") {
            $attribute = $this->getAttribute($this->getFeed()->getConfig('general_stock_attribute_code'));
            if ($attribute !== false) {
                $attribute->setStoreId($this->getData('store_id'));
                $this->setAttribute($attribute);
            } else {
                $this->log(sprintf("Column '%s' ignored, can't find attribute with code '%s'.", $this->_columns_map['availability']['column'], $this->getFeed()->getConfig('general_stock_attribute_code')), Zend_Log::WARN);
                unset($this->_columns_map['availability']);
            }
        }

        $s = array();
        foreach ($this->_columns_map as $column => $arr) {
            $s[$column] = $arr['order'];
        }
        array_multisort($s, $this->_columns_map);

        return $this->_columns_map;
    }

    /**
     * Returns columns map replaced by other attributes when it's value is empty for a product.
     * Sorts result asc by rule order.
     * Caches eav attributes model used.
     * Skips rules with attributes that doesn't exist.
     *
     * @return array
     */
    protected function getEmptyColumnsReplaceMap()
    {
        if (!is_null($this->_empty_columns_replace_map)) {
            return $this->_empty_columns_replace_map;
        }

        $_columns_map = $this->getColumnsMap();
        $tmp = $cfg_map = $this->getFeed()->getConfig('filters_map_replace_empty_columns');

        if (empty($cfg_map)) {
            $tmp = $cfg_map = array();
        }

        foreach ($tmp as $k => $arr) {

            if (!isset($_columns_map[$arr['column']])) {
                unset($cfg_map[$k]);
                continue;
            }

            if (strpos($arr['attribute'], 'rw_gbase_directive_') === false) {
                $attribute = $this->getAttribute($arr['attribute']);
                if ($attribute == false && empty($arr['static'])) {
                    $this->log(sprintf("Rule ('%s', '%s', '%d') is ignored, can't find attribute with code '%s'.", $arr['column'], $arr['attribute'], @$arr['order'], $arr['attribute']), Zend_Log::WARN);
                    unset($cfg_map[$k]);
                    continue;
                } elseif ($attribute) {
                    $attribute->setStoreId($this->getData('store_id'));
                    $this->setAttribute($attribute);
                }
            }
        }

        $this->_empty_columns_replace_map = $cfg_map;

        // Move rules without order to the bottom.
        $s = array();
        foreach ($this->_empty_columns_replace_map as $k => $arr) {
            if (!isset($arr['order']) || (isset($arr['order']) && $arr['order'] == "")) {
                $this->_empty_columns_replace_map[$k]['order'] = 99999;
            }

            $s[$k] = $arr['order'];
        }
        array_multisort($s, $this->_empty_columns_replace_map);

        return $this->_empty_columns_replace_map;
    }

    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Generator
     */
    protected function loadAdditionalAttributes()
    {
        $codes = array('status');
        foreach ($codes as $attribute_code) {
            $this->setAttribute($this->getAttribute($attribute_code));
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        return array_combine(array_keys($this->_columns_map), array_keys($this->_columns_map));
    }

    /**
     * @param $fields
     * @param bool|true $add_new_line
     */
    protected function writeFeed($fields, $add_new_line = true)
    {
        // google error: "Too many column delimiters"
        foreach ($this->_columns_map as $column => $arr) {
            if (isset($fields[$column]) && $fields[$column] == "") {
                $fields[$column] = " ";
            }
        }

        fwrite($this->getTemporaryHandle(), ($add_new_line ? PHP_EOL : '') . implode("\t", $fields));
        $this->_count_products_exported++;

        return $this;
    }

    /**
     * @param  RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract $productMap
     */
    protected function addProductToFeed($productMap)
    {
        if ($productMap->checkSkipSubmission()) {
            return $this;
        }

        $message = $this->checkPriceRangeSkip($productMap->getProduct());
        if ($message !== false) {
            $this->log($message);
            return $this;
        }

        $rows = $productMap->map();

        if ($productMap->isSkip()) {
            return $this;
        }

        foreach ($rows as $row) {
            $this->writeFeed($row);
        }
        return $this;
    }

    /**
     * Gets feed's filepath.
     *
     * @return string
     */
    public function getFeedPath()
    {
        $name = sprintf($this->getFeed()->getFeedFile());
        if ($this->getTestMode()) {
            $name = sprintf($this->getFeed()->getData('test_filename'), $this->getFeed()->getId());
        }

        $filepath = rtrim(Mage::getBaseDir(), DS) . DS . rtrim($this->getFeed()->getConfig('general_feed_dir'), DS) . DS;
        return $filepath. $name;
    }

    /**
     * Moves the feed file to it's final location after being generated in a temporary location.
     * return RocketWeb_GoogleBaseFeedGenerator_Model_Generator
     */
    protected function moveFeedFile()
    {
        rename($this->getFeedPath() . '.tmp', $this->getFeedPath());
        return $this;
    }

    /**
     * Only transfer data from temporary feed file if in
     * batch mode and this is the last batch, or if not in batch mode.
     *
     * return RocketWeb_GoogleBaseFeedGenerator_Model_Generator
     */
    protected function copyDataFromTemporaryFeedFile()
    {
        if ($this->inBatch()) {
            // if this was the last batch
            if ($this->getBatch()->completed()) {
                $this->moveFeedFile();
            }
        } else {
            $this->moveFeedFile();
        }

        return $this;
    }

    /**
     * @return bool|null|resource
     */
    protected function getTemporaryHandle()
    {
        if (!$this->hasData('temporary_handle') || $this->getData('temporary_handle') === null) {
            $mode = "a";
            if (!$this->inBatch() || ($this->inBatch() && $this->getBatch()->getIsNew())) {
                $mode = "w";
            }

            $this->setData('temporary_handle', @fopen($this->getFeedPath() . '.tmp', $mode));
            if ($this->getData('temporary_handle') === false) {
                Mage::throwException(sprintf('Not enough permissions to write to file %s.', $this->getFeedPath()));
            }
        }

        return $this->getData('temporary_handle');
    }

    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Generator
     */
    protected function closeTemporaryHandle()
    {
        @fclose($this->getData('temporary_handle'));
        $this->unsetData('temporary_handle');
        return $this;
    }

    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Generator
     */
    protected function setFeedFilePermissions()
    {
        @chmod(rtrim(Mage::getBaseDir(), DS) . DS . rtrim($this->getFeed()->getConfig('general_feed_dir'), DS), 0755);
        @chmod($this->getFeedPath(), 0664);
        return $this;
    }

    public function getCountProductsExported()
    {
        return $this->_count_products_exported;
    }

    public function getCountProductsSkipped()
    {
        return $this->_count_products_skipped;
    }

    /**
     * Could take negative value to decrease count
     * @param $val
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Generator
     */
    public function updateCountSkip($val = 1)
    {
        $this->_count_products_skipped = $this->_count_products_skipped + $val;
        return $this;
    }

    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Tools
     */
    public function getTools()
    {
        if (!$this->hasData('tools')) {
            $this->setData('tools', Mage::getModel('googlebasefeedgenerator/tools', array(
                    'feed' => $this->getFeed(),
                    'store_id' => $this->getData('store_id')
                )
            ));
        }

        return $this->getData('tools');
    }

    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Log
     */
    public function getLog()
    {
        return Mage::getSingleton('googlebasefeedgenerator/log');
    }

    /**
     * @param $msg
     * @param null $level
     * @param null $writer
     * @param bool|false $extra
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Generator
     */
    public function log($msg, $level = null, $writer = null)
    {
        if (is_null($level)) {
            $level = Zend_Log::INFO;
        }

        $options = array(
            'file' => $this->getFeed()->getLogFile(),
            'force' => true,
        );
        $this->getLog()->write($msg, $level, $writer, $options);

        if (!$this->inBatch()) {
            $this->getLog()->write($msg, $level, RocketWeb_GoogleBaseFeedGenerator_Model_Log::WRITER_MEMORY);
        }

        if ($this->getData('verbose')) {
            echo $msg . "\n";
        }
        return $this;
    }

    /**
     * @param $memory
     * @return string
     */
    public function formatMemory($memory)
    {
        $memory = max(1, $memory);

        $memoryLimit = Mage::helper('googlebasefeedgenerator')->getMemoryLimit();
        $units = array('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb');
        $m = @round($memory / pow(1024, ($i = floor(log($memory, 1024)))), 2);
        $limit = @round($memoryLimit / pow(1024, ($j = floor(log($memoryLimit, 1024)))), 2);
        return sprintf('%4.2f %s/%4.2f %s', $m, $units[$i], $limit, $units[$j]);
    }

    /**
     * Check if we are using too much memory and the script should stop
     *
     * @return bool
     */
    public function isCloseToPhpLimit()
    {
        $timeSpent = (time() - $this->getData('started_at')) * 1.1;
        $timeMax = ini_get('max_execution_time');
        if ($timeMax > 0 && $timeSpent >= $timeMax) {
            $this->log('PHP max_execution_time reached.');
            return true;
        }

        $currentUsage = memory_get_usage(true) * 1.1; // We add 10% overhead so we terminate soon enough
        if ($currentUsage >= Mage::helper('googlebasefeedgenerator')->getMemoryLimit()) {
            $this->log('PHP memory_limit reached.');
            return true;
        }
        return false;
    }

    /**
     * Wrapper for attribute cache in Tools object.
     *
     * @param  $code
     * @return mixed|null
     */
    public function getAttribute($attributeCode)
    {
        return $this->getTools()->getAttribute($attributeCode);
    }

    /**
     * Wrapper for set attribute cache in Tools object
     *
     * @param $attribute
     */
    public function setAttribute($attribute)
    {
        return $this->getTools()->setAttribute($attribute);
    }

    /**
     * Release the lock in case of issues
     */
    public function __destruct()
    {
        @fclose($this->_lockFile);
    }

    /**
     * @return string
     */
    public function getLockPath()
    {
        return rtrim(dirname($this->getFeedPath()), DS)
        . DS. sprintf($this->getFeed()->getData('lock_filename'), $this->getFeed()->getId());
    }

    /**
     * @return string
     */
    public function getBatchLockPath()
    {
        return rtrim(dirname($this->getFeedPath()), DS)
        . DS. sprintf($this->getFeed()->getData('batch_lock_filename'), $this->getFeed()->getId());
    }

    /**
     * Implements the lock feed generation using the file system lock mechanism.
     * @return bool
     */
    public function acquireLock()
    {
        // Test feed writes to a separate file so no need to lock
        if ($this->getTestMode()) {
            return true;
        }

        // Acquire an exclusive lock on file without blocking the script
        if (empty($this->_lockFile) || !flock($this->_lockFile, LOCK_EX | LOCK_NB)) {
            $this->log(sprintf('Can\'t acquire feed lock for [%s]', $this->getFeed()->getName()) . ($this->hasScheduleId() ? sprintf('script [%s]', $this->getScheduleId()) : ''), Zend_Log::ERR);
            $this->log(sprintf('Ensure write proper write permissions to [%s]', $this->getLockPath()));
            return false;
        }

        ftruncate($this->_lockFile, 0); // truncate file
        fwrite($this->_lockFile, date('Y-m-d H:i:s'));
        fflush($this->_lockFile); // flush output before releasing the lock
        return true;
    }

    /**
     * Release the file lock.
     * Will also be done automatically when php runtime ends.
     *
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Generator
     */
    public function releaseLock()
    {
        if ($this->inBatch()) {
            $this->getBatch()->releaseLock();
        }

        flock($this->_lockFile, LOCK_UN);
        return $this;
    }

    /**
     * @return bool
     */
    public function batchInProgress()
    {
        if ($mixed = @file_get_contents($this->getBatchLockPath())) {
            $mixed = @unserialize($mixed);
            if (is_array($mixed) && (int)$mixed['offset'] < (int)$mixed['total'] - (int)$mixed['limit']) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $type
     * @return bool
     */
    public function isProductTypeEnabled($type)
    {
        return in_array($type, $this->getFeed()->getConfig('filters_product_types'));
    }

    /**
     * @return int
     */
    public function getTotalItems()
    {
        if (!$this->hasData('total_items')) {
            $count_coll = clone $this->_getCollection();
            $count_coll->getSelect()->reset(Zend_Db_Select::GROUP);
            $this->setData('total_items', $count_coll->getSize());
        }
        return $this->getData('total_items');
    }

    /**
     * @return bool
     */
    public function inBatch()
    {
        return $this->getBatchMode() && !$this->getTestMode();
    }

    /**
     * Check if product price is not in allowed range
     * 
     * @param Mage_Catalog_Model_Product $product
     * @return boolean | string
     */
    public function checkPriceRangeSkip($product, $additionalText = '')
    {
        $message = false;

        if ($product->hasPrice()) {
            if ($this->hasMaxProductPrice() && ($this->getMaxProductPrice() < $product->getPrice())) {
                $message = 'above';
            } else if ($this->hasMinProductPrice() && ($product->getPrice() < $this->getMinProductPrice())) {
                $message = 'below';
            }
        }
        if ($message) {
            $message = (sprintf('"product id %d sku %s, skipped - product price is %s limit%s"', $product->getId(),
                $product->getSku(), $message, $additionalText));
        }
        return $message;
    }

    /**
     * Build information about parent products into associated array by product type
     *
     * @param $row
     * @return array
     */
    public function getProductParents($row)
    {
        $parents = array('configurable' => false, 'grouped' => false, 'bundle' => false);

        $parents['configurable'] = $this->getTools()->isChildOfProductType($row['type_id'], $row['sku'], Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, $this->getStoreId());
        if (!$parents['configurable']) {
            $parents['configurable'] = $this->getTools()->isChildOfProductType($row['type_id'], $row['sku'], RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Subscription_Configurable::PRODUCT_TYPE_SUBSCTIPTION_CONFIGURABLE, $this->getStoreId());
        }

        $parents['grouped'] = $this->getTools()->isChildOfProductType($row['type_id'], $row['sku'], Mage_Catalog_Model_Product_Type::TYPE_GROUPED, $this->getStoreId());
        if (!$parents['grouped']) {
            $parents['grouped'] = $this->getTools()->isChildOfProductType($row['type_id'], $row['sku'], RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Subscription_Grouped::PRODUCT_TYPE_SUBSCTIPTION_GROUPED, $this->getStoreId());
        }
        $parents['bundle'] = $this->getTools()->isChildOfProductType($row['type_id'], $row['sku'], Mage_Catalog_Model_Product_Type::TYPE_BUNDLE, $this->getStoreId());

        return $parents;
    }
}
