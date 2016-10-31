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
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @copyright  Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */

/**
 * Class RocketWeb_GoogleBaseFeedGenerator_Model_Feed
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Feed extends Mage_Core_Model_Abstract
{
    protected $_status;
    protected $_store;

    protected function _construct()
    {
        $this->_init('googlebasefeedgenerator/feed');
        $this->_status = Mage::getModel('googlebasefeedgenerator/feed_status');
    }

    /**
     * Load the extra objects: status and config_data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterLoad()
    {
        // Fill in default feed settings from config.xml
        $feed_data = Mage::getConfig()->getNode('default/feed');
        foreach ($feed_data->asArray() as $key => $value) {
            if (!$this->hasData($key)) {
                if (in_array($key, array('exclude_attributes'))) {
                    $value = explode(',', $value);
                }
                $this->setData($key, $value);
            }
        }

        // Load default configs from feed_type.xml
        $file = Mage::getModuleDir('etc', 'RocketWeb_GoogleBaseFeedGenerator'). DS. 'feeds'. DS. $this->getType(). '.xml';
        if (!is_readable($file)) { // fallback on generic columns
            $file = Mage::getModuleDir('etc', 'RocketWeb_GoogleBaseFeedGenerator'). DS. 'feeds'. DS. 'generic.xml';
        }
        if (is_readable($file)) {
            $conf = new RocketWeb_GoogleBaseFeedGenerator_Model_Core_Config();
            $conf->loadFile($file);
            foreach ($conf->getNode()->asArray() as $node => $key) {
                $this->setData($node, $key);
            }
        }
        if ($this->hasData('default_feed_config') && is_array($this->getData('default_feed_config'))) {
            $defaultConfig = $this->getData('default_feed_config');
            if (isset($defaultConfig['schedule']) && is_array($defaultConfig['schedule']) ){
                $defaultSchedule = array();
                foreach($defaultConfig['schedule'] as $key => $value) {
                    if (!$value) {
                        $value = Mage::getConfig()->getNode('default/general/' . $key)->asArray();
                    }
                    $defaultSchedule[$key] = $value;
                }
                $defaultConfig['schedule'] = $defaultSchedule;
                $this->setData('default_feed_config', $defaultConfig);
            }
        }
        // Fill in default store_id
        if (!$this->hasData('store_id')) {
            $this->setData('store_id', Mage::helper('googlebasefeedgenerator')->getDefaultStoreId());
        }

        // Fill in the messages displayed in grid
        $messages = unserialize($this->getMessages());
        $this->setMessages(array_merge(
            array('date' => date("Y-m-d H:i:s"), 'progress' => 0, 'added' => 0, 'skipped' => 0),
            is_array($messages) ? $messages : array()
        ));
        return parent::_afterLoad();
    }

    /**
     * Prepare the messages
     */
    protected function _beforeSave()
    {
        if(is_array($this->getMessages())) {
            $this->setMessages(serialize($this->getMessages()));
        }
    }

    /**
     * Save the config values attached to the feed object
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterSave()
    {
        $config_groups = array_keys($this->getData('default_feed_config'));

        $shippingSettingsChanged = false;

        foreach ($this->getConfig() as $key => $value) {
            $tab = substr($key, 0, strpos($key, '_'));
            if (in_array($tab, $config_groups)) {
                $lookup = Mage::getModel('googlebasefeedgenerator/config')->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('feed_id', $this->getId())
                    ->addFieldToFilter('path', $key)
                    ->load();
                $model = count($lookup) == 0 ? Mage::getModel('googlebasefeedgenerator/config') : $lookup->getFirstItem();

                // check if any of the shipping settings have changed
                if (!$shippingSettingsChanged && $model->getId() && self::_isShippingConfig($key)) {
                    if ($model->getValue() != $value) {
                        $shippingSettingsChanged = true;
                        self::clearShippingCache($this->getId());
                    }
                }

                $model->addData(array(
                    'feed_id' => $this->getId(),
                    'path' => $key,
                    'value' => $value));
                $model->save();
            }
        }

        $schedule = $this->getSchedule();
        if ($schedule && is_array($schedule)) {
            $processedSchedules = array();
            foreach ($schedule as $scheduleId => $data) {
                $model = Mage::getModel('googlebasefeedgenerator/feed_schedule')->load($scheduleId);
                if ($model->getId() && isset($data['delete']) && $data['delete']) {
                    $model->delete();
                } else {
                    $batchMode = $data['batch_mode'];
                    if ($batchMode && isset($data['batch_limit']) && $data['batch_limit']) {
                        $batchLimit = $data['batch_limit'];
                    } else {
                        $batchLimit = Mage::getConfig()->getNode('default/general/batch_limit')->asArray();
                    }
                    $startAt = $data['start_at'];
                    if (array_key_exists($startAt, $processedSchedules) && 
                        ($startAt != 0 || ($startAt == 0 && !$this->getIsClone()))) {

                        Mage::throwException('You cannot make several schedules on the same hour');
                    }
                    $processedSchedules[$startAt] = $startAt;
                    $model->setStartAt($startAt)
                        ->setBatchLimit($batchLimit)
                        ->setBatchMode($batchMode)
                        ->setFeedId($this->getId())
                        ->save();
                }
            }
        }

        $ftpAccounts = $this->getFtp();
        if ($ftpAccounts && is_array($ftpAccounts)) {
            foreach ($ftpAccounts as $id => $accountData) {
                $model = Mage::getModel('googlebasefeedgenerator/feed_ftp')->load($id);
                $delete = (isset($accountData['delete']) && $accountData['delete']) ? true : false;
                if (!$delete) {
                    foreach ($accountData as $key => $value) {
                        $model->setData($key, $value);
                    }
                    $model->setFeedId($this->getId())
                        ->save();
                } else if ($model->getId()) {
                    $model->delete();
                }
            }
        }
        return parent::_afterSave();
    }

    /**
     * Clear the shipping entries associated with this feed from the rw_gfeed_shipping table.
     */
    public static function clearShippingCache($feedId = false)
    {
        $collection = Mage::getModel('googlebasefeedgenerator/shipping')->getCollection()
            ->addFieldToSelect('id');

        if ($feedId !== false) {
            $collection->addFieldToFilter('feed_id', $feedId);
        }

        $collection->load();

        /** @var RocketWeb_GoogleBaseFeedGenerator_Model_Shipping $cachedShippingValue */
        foreach($collection as $cachedShippingValue) {
            //TODO: this foreach should be improved with a single DELETE query
            try {
                $cachedShippingValue->delete();
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::throwException('Error removing shipping cache entry: ' . $e->getMessage());
            }
        }
    }

    /**
     * Test if a config path is part of the Shipping tab.
     *
     * @param string $configPath
     * @return bool
     */
    protected static function _isShippingConfig($configPath)
    {
        $configKeys = array(
            'shipping_methods',
            'shipping_country',
            'shipping_only_minimum',
            'shipping_only_free_shipping',
            'shipping_add_tax_to_price'
        );

        return in_array($configPath, $configKeys);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        if (!$this->_status->hasData()) {
            $this->_status->load($this);
        }
        return $this->_status;
    }

    /**
     * Use resource to save the feed so that config is not saved.
     *
     * @param $value
     * @return $this
     */
    public function saveStatus($value)
    {
        $this->setStatus($value);
        $this->_beforeSave();
        $this->getResource()->save($this);
        return $this;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getMessage($key)
    {
        $data = $this->getMessages();
        return array_key_exists($key, $data) ? $data[$key] : '';
    }

    /**
     * Use resource to save the feed so that config is not saved.
     *
     * @param $value
     * @return $this
     */
    public function saveMessages($value)
    {
        $this->setMessages($value);
        $this->_beforeSave();
        $this->getResource()->save($this);
        return $this;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        $code = $this->getStatus()->getCode();
        return $code == RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_DISABLED;
    }

    /**
     * @return bool
     */
    public function isAllowed()
    {
        $code = $this->getStatus()->getCode();

        return !in_array($code, array(
            RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_DISABLED,
//            RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_PENDING,
//            RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_PROCESSING
        ));
    }


    /**
     * Checks if directive is allowed for this feed
     * @param string $code
     * @return bool $code
     */
    public function isAllowedDirective($code)
    {
        $directives = $this->getData('directives');
        if (array_key_exists($code, $directives)) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param $section
     * @param $key
     * @return bool
     */
    public function isAllowedConfig($section, $key)
    {
        $map = $this->getData('default_feed_config');
        return array_key_exists($section, $map) && array_key_exists($key, $map[$section]);
    }

    /**
     * * Get the configuration array of the feed, loads data from feed_config
     * and fills in default values from config.xml
     *
     * If $key is specified, it will pull the value from it
     *
     * @param null $key
     * @param boolean $defaultData
     * @return mixed|null
     */
    public function getConfig($key = null, $defaultData = true)
    {
        if (!is_null($key)) {
            $cfg = $this->getConfig(null, $defaultData);
            return array_key_exists($key, $cfg) ? $cfg[$key] : null;
        }

        if (!$this->hasData('config') || !$defaultData) {
            $data = array();

            // Load saved config data
            $config_collection = Mage::getModel('googlebasefeedgenerator/config')->getCollection()
                ->addFieldToFilter('feed_id', $this->getId());

            foreach($config_collection as $item) {
                $data[$item->getPath()] = $item->getValue();
            }

            if ($defaultData) {
                // Fill in missing configuration keys with default data from config.xml
                $config = Mage::getModel('googlebasefeedgenerator/config')->setFeed($this);

                foreach ($this->getData('default_feed_config') as $section => $node) {
                    foreach ($node as $key => $value) {
                        $path = $section. '_'. $key;
                        if (!array_key_exists($path, $data)) {
                            // force data backend processing
                            $config->addData(array('path' => $path, 'value' => $value));
                            $data[$config->getPath()] = $config->getValue();
                        }
                    }
                }
            } else {
                return $data;
            }

            $this->setData('config', $data);
        }

        return $this->getData('config');
    }

    /**
     * Used as a callback in RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Grid
     *
     * @return string
     */
    public function getFeedUrl()
    {
        $url = '';
        $feed_dir = $this->getConfig('general_feed_dir');

        if (!empty($feed_dir)) {
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB). $feed_dir. DS. $this->getFeedFile();
        }
        return $url;
    }

    /**
     * @return string
     */
    public function getFeedFile()
    {
        return sprintf($this->getData('feed_filename'), $this->getId());
    }

    /**
     * @return string
     */
    public function getLogFile()
    {
        return sprintf($this->getData('log_filename'), $this->getId());
    }

    /**
     * Convert object attributes to XML
     *
     * @param  array $arrAttributes array of required attributes
     * @param string $rootName name of the root element
     * @return string
     */
    protected function __toXml(array $arrAttributes = array(), $rootName = 'item', $addOpenTag=false, $addCdata=true)
    {
        $xml = '';
        if ($addOpenTag) {
            $xml.= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        }
        if (!empty($rootName)) {
            $xml.= '<'.$rootName.'>'."\n";
        }
        $xmlModel = new Varien_Simplexml_Element('<node></node>');
        $arrData = $this->toArray($arrAttributes);
        unset($arrData['id']);
        foreach ($arrData as $fieldName => $fieldValue) {
            $fieldValue = is_array($fieldValue) ? serialize($fieldValue) : $fieldValue;
            $fieldValue = $addCdata ? "<![CDATA[$fieldValue]]>" : $xmlModel->xmlentities($fieldValue);
            $xml.= "\t<$fieldName>$fieldValue</$fieldName>\n";
        }

        $schedule = Mage::getModel('googlebasefeedgenerator/feed_schedule')->getCollection()
            ->addFieldToFilter('feed_id', $this->getId());
        $schedule->getSelect()->reset(Zend_Db_Select::COLUMNS)
            ->columns(array('start_at', 'batch_limit', 'batch_mode'));
        foreach ($schedule->getItems() as $item) {
            $xml .= Mage::helper('googlebasefeedgenerator')->arrayToXml($item->getData(), 'schedule', $addCdata);
        }

        $ftpAccounts = $schedule = Mage::getModel('googlebasefeedgenerator/feed_ftp')->getCollection()
            ->addFieldToFilter('feed_id', $this->getId());
        $ftpAccounts->getSelect()->reset(Zend_Db_Select::COLUMNS)
            ->columns(array('username', 'password', 'host', 'port', 'path', 'mode'));
        foreach ($ftpAccounts->getItems() as $item) {
            $xml .= Mage::helper('googlebasefeedgenerator')->arrayToXml($item->getData(), 'ftp_accounts', $addCdata);
        }

        // Load default values that make up sample config, also usefull for debuging.
        $this->load($this->getId());
        $xml .= Mage::helper('googlebasefeedgenerator')->arrayToXml($this->getConfig(null, false), 'config', $addCdata);

        if (!empty($rootName)) {
            $xml.= '</'.$rootName.'>'."\n";
        }
        return $xml;
    }

    /**
     * Loads schedule for the current feed.
     *
     * @return Mage_Core_Model_Abstract
     */
    public function getSchedule()
    {
        if (!$this->hasData('schedule')) {
            $schedule = Mage::getModel('googlebasefeedgenerator/feed_schedule')->getCollection()
                ->addFieldToFilter('feed_id', $this->getId())
                ->getFirstItem();
            $this->setData('schedule', $schedule);
        }
        return $this->getData('schedule');
    }

    public function getStore()
    {
        if (is_null($this->_store)) {
            $this->_store = Mage::app()->getStore($this->getStoreId());
            $currency = Mage::getModel('directory/currency')->load($this->getConfig('general_currency'));
            $this->_store->setData('current_currency', $currency);
        }
        return $this->_store;
    }
}
