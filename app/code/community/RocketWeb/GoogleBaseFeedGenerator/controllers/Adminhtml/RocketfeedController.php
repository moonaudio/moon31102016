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
 * @copyright  Copyright (c) 2015 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */

class RocketWeb_GoogleBaseFeedGenerator_Adminhtml_RocketfeedController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Initialize action
     *
     * Here, we set the breadcrumbs and the active menu
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _initAction()
    {
        $this->loadLayout()
            // Make the active menu match the menu config nodes (without 'children' inbetween)
            ->_setActiveMenu('catalog/googlebasefeedgenerator_feed')
            ->_title($this->__('Catalog'))->_title($this->__('Catalog'))
            ->_addBreadcrumb($this->__('Catalog'), $this->__('Catalog'))
            ->_addBreadcrumb($this->__('Rocket Feeds'), $this->__('Rocket Feeds'));

        return $this;
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/rocketweb_googlebasefeedgenerator');
    }

    /**
     * Load $feed from param 'id' and do basic checks for controller
     *
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Feed
     */
    protected function _getFeed()
    {
        $feed_id = $this->getRequest()->getParam('id');

        if (!$feed_id) {
            Mage::throwException(sprintf("Missing / Invalid parameter id: %s", $feed_id));
        }

        $feed = Mage::getModel('googlebasefeedgenerator/feed')->load($feed_id);
        if (!$feed->getId()) {
            Mage::throwException(sprintf("Feed id %s no longer exists.", $feed_id));
        }
        return $feed;
    }

    /**
     * default action
     */
    public function indexAction()
    {
        $this->_forward('list');
	}

    /**
     * load the feeds grid
     */
    public function listAction()
    {
        $this->_initAction()
            ->_addBreadcrumb($this->__('Rocket Feeds'), $this->__('Rocket Feeds'));
        if (!Mage::helper('googlebasefeedgenerator')->cronHasHeartbeat()) {
            $this->_addContent($this->getLayout()->createBlock('core/text', 'adminhtml_feed.grid.notice')->setText('<ul class="messages"><li class="warning-msg"><ul><li><span>'.
                $this->__('No heartbeat detected. Feeds won\'t generate, please check magento cron is running.'). '</span></li></ul></li></ul>'
            ));
        }

        $this->_addContent($this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed'))
            ->_addContent($this->getLayout()->createBlock('core/text', 'adminhtml_feed.grid.help')->setText(
                '<ul class="messages"><li class="notice-msg"><ul><li>'
                . sprintf(Mage::helper('googlebasefeedgenerator')->__('Please use our %s to help you set up feeds.'), '<a target="_blank" href="https://wiki.rocketweb.com/display/RSF" title="Quick Start">User Guide</a>'). '</li><li>'
                . Mage::helper('googlebasefeedgenerator')->__('Once a feed is pending processing, it will start with a delay corresponding to your cron.php frequency.'). ' '
                . sprintf(Mage::helper('googlebasefeedgenerator')->__('To enable microdata on your product pages, check out %s section'), '<a href="'. $this->getUrl('adminhtml/system_config/edit', array('section' => 'rocketweb_googlebasefeedgenerator')). '" title="General Config">General Config</a>')
                . '</li></ul></li></ul>'
            ));

        $head = Mage::app()->getLayout()->getBlock('head');
        $head->addItem('js_css', 'prototype/windows/themes/default.css');
        $head->addCss('lib/prototype/windows/themes/magento.css');

        $cell = $this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed_grid_schedulegridcell');
        Mage::app()->getLayout()->getBlock('adminhtml_feed.grid')->setScheduleGridCellBlock($cell);

        $this->renderLayout();
    }

    /**
     * load the feeds grid through ajax
     */
    public function gridAction()
    {
        $this->loadLayout();
        $grid = $this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed');

        $cell = $this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed_grid_schedulegridcell');
        Mage::app()->getLayout()->getBlock('adminhtml_feed.grid')->setScheduleGridCellBlock($cell);

        $this->getResponse()->setBody($grid->getGridHtml());
    }

    /**
     * Load the new feed form
     */
    public function newAction()
    {
        $this->loadLayout();
        $this->_initAction()
            ->_addBreadcrumb($this->__('New Feed'), $this->__('New Feed'))
            ->_addContent($this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed_new')
                ->setData('action', $this->getUrl('*/*/edit'))
            );
        $this->renderLayout();
    }

    /**
     * load the edit feed form
     */
    public function editAction()
    {
        $this->loadLayout();
        $feed_id  = $this->getRequest()->getParam('id', 0);

        /** @var RocketWeb_GoogleBaseFeedGenerator_Model_Feed $model */
        $model = Mage::getModel('googlebasefeedgenerator/feed')
            ->setType($this->getRequest()->getParam('type', RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Type::TYPE_GENERIC))
            ->setName($this->getRequest()->getParam('name', ''))
            ->load($feed_id);

        Mage::register('googlebasefeedgenerator_feed', $model);
        Mage::register('googlebasefeedgenerator_feed_layout', $this->getLayout());

        Mage::getSingleton('googlebasefeedgenerator/feed_taxonomy')->prepareTaxonomyFiles($model);

        $title = $model->getId() ? sprintf($this->__('Edit %s'), $model->getName()) : sprintf($this->__('New %s Feed'), ucfirst($this->getRequest()->getParam('type', RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Type::TYPE_GENERIC)));
        $this->_title($title);

        $this->_initAction()
            ->_addBreadcrumb($title, $title)
            ->_addContent($this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed_edit')->setData('action', $this->getUrl('*/*/save')))
            ->_addLeft($this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed_edit_tabs'));
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->renderLayout();
    }

    /**
     * Save the Feed and it's configs
     */
    public function saveAction()
    {
        if ($postData = $this->getRequest()->getPost()) {
            try {
                if (isset($postData['config']['taxonomy_json'])) {
                    $json = Mage::helper('core')->jsonDecode($postData['config']['taxonomy_json']);
                    if ($json !== false) {
                        $postData['config']['categories_provider_taxonomy_by_category'] = $json;
                    }
                    unset($postData['config']['taxonomy_json']);
                }
                $time = new Zend_Date(Mage::getModel('core/date')->timestamp(time()), Zend_Date::TIMESTAMP);
                $feed = Mage::getModel('googlebasefeedgenerator/feed')
                    ->setType($this->getRequest()->getParam('type', RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Type::TYPE_GENERIC))
                    ->load(array_key_exists('id', $postData) ? $postData['id'] : 0)
                    ->addData($postData)
                    ->setUpdatedAt($time->get(Zend_Date::ISO_8601));
                $feed->save();

                $this->_getSession()->addSuccess($this->__('The Feed has been saved.'));

                if (!array_key_exists('id', $postData)) {
                    $this->_redirect('*/*/');
                    return;
                }
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('An error occurred while saving this feed.'));
            }

            $this->_redirectReferer();
        }
    }

    /**
     * Delete feed.
     */
    public function deleteAction() {

        $feedId = $this->getRequest()->getParam('id');
        try {
            Mage::getModel('googlebasefeedgenerator/feed')->load($feedId)->delete();
            $this->_getSession()->addSuccess($this->__('One feed has been deleted.'));
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while deleting this feed.'));
        }

        $this->_redirect('*/*/');
    }

    /**
     * Generate feed pop-up
     */
    public function generateAction()
    {
        try {
            $feed = $this->_getFeed();
            if (!$feed->isAllowed()) {
                throw new Mage_Core_Exception($this->__('Cannot add feed to the processing queue. Try disable and enable it back, to clear status.'));
            }
            Mage::getModel('googlebasefeedgenerator/queue')->send($feed, 'manual');
            $feed->saveStatus(RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_PENDING);
            if ($feed->getSchedule()->getBatchMode()) {
                @unlink(Mage::helper('googlebasefeedgenerator')->getGenerator($feed)->getBatchLockPath());
            }
            $this->_getSession()->addSuccess($this->__('Feed has been added to the processing queue.'));
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while adding feed to processing queue.'));
        }

        $this->_redirect('*/*/');
	}

    /**
     * Generate all feeds now action
     */
    public function generateAllAction()
    {
        try {
            $cnt = 0;
            $feeds = Mage::getModel('googlebasefeedgenerator/feed')->getCollection();
            foreach ($feeds as $feed) {
                $feed->load($feed->getId());
                if ($feed->isAllowed()) {
                    Mage::getModel('googlebasefeedgenerator/queue')->send($feed, 'manual');
                    $feed->saveStatus(RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_PENDING);
                    if ($feed->getSchedule()->getBatchMode()) {
                        @unlink(Mage::helper('googlebasefeedgenerator')->getGenerator($feed)->getBatchLockPath());
                    }
                    $cnt++;
                }
            }
            if ($cnt > 0) {
                $this->_getSession()->addSuccess(sprintf($this->__('%s feed(s) have been added to processing queue.'), $cnt));
            } else {
                $this->_getSession()->addError($this->__('No feeds could be added to processing queue.'));
            }
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while adding feed to processing queue.'));
        }

        $this->_redirect('*/*/');
    }

    /**
     * View log file action
     */
    public function viewlogAction()
    {
        $this->loadLayout('popup');
        try {
            $feed = $this->_getFeed();

            /* @var $generator RocketWeb_GoogleBaseFeedGenerator_Model_Generator */
            $generator = Mage::helper('googlebasefeedgenerator')->getGenerator($feed);

            $this->_addContent(
                $this->getLayout()->createBlock('core/template', 'rw_google_feed_log',
                    array('feed' => $feed, 'generator' => $generator, 'template' => 'googlebasefeedgenerator/system/log.phtml',
                        'download_url' => $this->getUrl('*/*/downloadlog', array('id' => $feed->getId())),
                        'clear_url' => $this->getUrl('*/*/clearlog', array('id' => $feed->getId())))
                ));

        }
        catch (Exception $e) {
            $block = $this->getLayout()->getMessagesBlock();
            $block->addError($e->getMessage());
        }

        $this->renderLayout();
    }

    /**
     * Delete the log file.
     */
    public function clearlogAction()
    {
        try {
            $feed = $this->_getFeed();
            $filePath = Mage::getBaseDir('log'). DS. $feed->getLogFile();

            if (is_file($filePath) && is_readable($filePath)) {
                file_put_contents(Mage::getBaseDir('log'). DS. $feed->getLogFile(), '');
                unlink(Mage::getBaseDir('log'). DS. $feed->getLogFile());
            } else {
                throw new Mage_Core_Exception($this->__('Log file could not be deleted!'));
            }
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while deleting log.'));
        }

        $this->_redirect('*/*/viewlog', array('id' => $feed->getId()));
    }

    /**
     * @return bool|Mage_Core_Controller_Varien_Action
     */
    public function downloadlogAction()
    {
        try {
            $feed = $this->_getFeed();
            $filePath = Mage::getBaseDir('log'). DS. $feed->getLogFile();

            if (!is_file($filePath) || !is_readable($filePath)) {
                throw new Mage_Core_Exception($this->__('Log file could not be read!'));
            }

            return $this->_prepareDownloadResponse(
                basename($filePath),
                array('value' => $filePath, 'type'  => 'filename'),
                "text/plain",
                filesize($filePath)
            );
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while saving this feed.'));
        }

        $this->_redirectReferer();
    }

    /**
     * Mass delete feed(s)
     */
    public function massDeleteAction() {

        try {
            $feedIds = $this->getRequest()->getParam('ids');
            if(!is_array($feedIds)) {
                throw new Mage_Core_Exception($this->__('Please select feed(s).'));
            }

            $model = Mage::getModel('googlebasefeedgenerator/feed');
            foreach ($feedIds as $feedId) {
                $model->load($feedId)->delete();
            }
            $this->_getSession()->addSuccess($this->__('Total of %d record(s) were deleted.', count($feedIds)));
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while deleting feeds.'));
        }
        $this->_redirect('*/*/');
    }

    /**
     * Enable feed(s)  action
     */
    public function massEnableAction()
    {
        $this->_massStatus(RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_SCHEDULED);
    }

    /**
     * Disable feed(s)  action
     */
    public function massDisableAction()
    {
        $this->_massStatus(RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_DISABLED);
        // remove the queue leftovers
        $feedIds = (array)$this->getRequest()->getParam('ids');
        $queue = Mage::getModel('googlebasefeedgenerator/queue')->getCollection()
            ->addFieldToFilter('feed_id', array('in' => $feedIds));
        foreach ($queue as $message) {
            $message->setData('is_read', 1)->delete();
        }
    }

    /**
     * Toggle status enable / disable
     * @param $status
     */
    protected function _massStatus($status)
    {
        $feedIds = (array)$this->getRequest()->getParam('ids');

        try {
            $model = Mage::getModel('googlebasefeedgenerator/feed');
            foreach ($feedIds as $feedId) {
                $model->load($feedId)->saveStatus($status);
            }
            $this->_getSession()->addSuccess($this->__('Total of %d record(s) have been updated.', count($feedIds)));
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while updating status.'));
        }
        $this->_redirect('*/*/');
    }

    /**
     * Clone the current feed
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function massCloneAction()
    {
        $feedIds = $this->getRequest()->getParam('ids');

        if(!is_array($feedIds)) {
            $this->_getSession()->addError($this->__('Please select feed(s).'));
        } else {
            $cnt = 0;
            $feed = Mage::getModel('googlebasefeedgenerator/feed');
            foreach ($feedIds as $feedId) {
                $config = $feed->load($feedId)->getConfig();
                if (!empty($config)) {
                    $new_feed = Mage::getModel('googlebasefeedgenerator/feed')
                        ->setData($feed->getData())
                        ->setId(null)
                        ->setName($feed->getName() . ' (cloned)')
                        ->setConfig($feed->getConfig());

                    if ($new_feed->getStatus()->getCode() != RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_DISABLED) {
                        $new_feed->setStatus(RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_SCHEDULED);
                    }
                    $scheduleModel = Mage::getModel('googlebasefeedgenerator/feed_schedule');
                    $schedules = $scheduleModel->getCollection()
                        ->addFieldToFilter('feed_id', $feedId)
                        ->load();
                    $usedHours = array();
                    $parsedSchedules = array();
                    foreach ($schedules as $schedule) {
                        $startAt = $scheduleModel->getNextStartAt($usedHours);
                        $parsedSchedules['new_' . rand('10000000000', '99999999999')] = array(
                            'batch_mode'    => $schedule->getBatchMode(),
                            'batch_limit'   => $schedule->getBatchLimit(),
                            'start_at'      => $startAt,
                        );
                        $usedHours[] = $startAt;
                    }
                    $new_feed->setSchedule($parsedSchedules)
                        ->setIsClone(true)
                        ->save();
                    $cnt++;
                }
            }
            $this->_getSession()->addSuccess(sprintf($this->__('Total of %s feed(s) have been clonned.'), $cnt));
        }

        $this->_redirect('*/*/');
        return;
    }

    /**
     * Export feed configuration for debugging or move to live servers
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function exportAction()
    {
        $feed_id  = $this->getRequest()->getParam('id');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<items>';

        if ($feed_id) {
            $feed = Mage::getModel('googlebasefeedgenerator/feed')->load($feed_id);
            $xml .= $feed->toXml();
        }
        else {
            // Get the feed collection from grid to preserve filters, current page, etc.
            /** @var RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Grid $grid */
            $grid = $this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed_grid');
            $grid->getXml();
            $grid->getCollection()->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns('id');
            $grid->getCollection()->load();
            foreach ($grid->getCollection() as $feed) {
                /*
                 * We load the model each time because we want to get all of the feed information not just the set of fields
                 * defined by the collection from above.
                 */
                $feed = Mage::getModel('googlebasefeedgenerator/feed')->load($feed->getId());
                $xml .= $feed->toXml();
            }
        }
        $xml .= '</items>';

        return $this->_prepareDownloadResponse(
            'feeds_' . Mage::getModel('core/date')->date('Y-m-d') . '.xml',
            $xml,
            'application/xml'
        );
    }

    /**
     * Export sample Feed
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function exportSampleAction()
    {
        $model = Mage::getModel('googlebasefeedgenerator/feed')->load(0)
            ->setName('Sample Feed');
        $websites = Mage::app()->getWebsites();
        if (is_array($websites) && isset($websites[1])) {
            $model->setStoreId($websites[1]->getDefaultStore()->getId());
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml.= '<items>';
        $xml .= $model->toXml();
        $xml.= '</items>';

        return $this->_prepareDownloadResponse(
            'feeds_' . Mage::getModel('core/date')->date('Y-m-d') . '.xml',
            $xml,
            'application/xml'
        );
    }

    /**
     * Export the current feed
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function importAction()
    {
        $maxUploadSize = Mage::helper('importexport')->getMaxUploadSize();
        $this->_getSession()->addNotice(
            $this->__('Total size of uploadable files must not exceed %s', $maxUploadSize)
        );

        $this->_initAction()
            ->_addBreadcrumb($this->__('Rocket Feeds'), $this->__('Rocket Feeds'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/template', 'import.form.before', array('template' => 'importexport/import/form/before.phtml')))
            ->_addContent($this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed_import_edit'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/template', 'import.form.after', array('template' => 'importexport/import/form/after.phtml')))
            ->renderLayout();
    }

    /**
     * Start import process action.
     *
     * @return void
     */
    public function startImportAction()
    {
        $data = $this->getRequest()->getPost();
        if ($data) {
            $this->loadLayout(false);

            /** @var $resultBlock Mage_ImportExport_Block_Adminhtml_Import_Frame_Result */
            $resultBlock = $this->getLayout()
                ->createBlock(
                    'importexport/adminhtml_import_frame_result',
                    'import.frame.result',
                    array(
                        'template' => 'importexport/import/frame/result.phtml',
                        'output'   => 'toHtml',
                        'alias'    => 'import_frame_result'
                        )
                );
            $importModel = Mage::getModel('googlebasefeedgenerator/feed_import');

            try {
                $sourceFile = $importModel->setData($data)->uploadSource();
                $importModel->validateSource($sourceFile);
                $cnt = $importModel->importSource();
                $resultBlock->addAction('show', 'import_validation_container')
                    ->addAction('innerHTML', 'import_validation_container_header', $this->__('Status'));
            } catch (Exception $e) {
                $resultBlock->addError($e->getMessage());
                echo $resultBlock->toHtml();
                return;
            }

            $this->_getSession()->addSuccess(sprintf($this->__('%s feed(s) have been imported.'), $cnt));
        } else {
            $this->_getSession()->addError($this->__('Nothing to import.'));
        }

        echo "<script type='text/javascript'>window.top.location.replace('". $this->getUrl('*/*/'). "')</script>";
    }

    /**
     * Test feed action
     */
    public function testAction()
    {
        $this->loadLayout('popup');

        try {
            $feed = $this->_getFeed();
            Mage::register('googlebasefeedgenerator_feed', $feed);

            $this->_addBreadcrumb($this->__('Test Feed'), $this->__('Test Feed'))
                ->_addContent($this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed_test', 'rw_google_feed_test')
                    ->setData('action', $this->getUrl('*/*/test')));

            if ($postData = $this->getRequest()->getPost()) {
                $this->_addContent($this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed_test_results', 'rw_google_feed_test_results', $postData));
            }

        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while loading test.'));
        }

        $this->renderLayout();
    }

    /**
     * Updates the feed's schedule data
     */
    public function scheduleAction() {

        $params = $this->getRequest()->getParams();
        $scheduleModel = Mage::getModel('googlebasefeedgenerator/feed_schedule')
            ->load($params['feed_id'], 'feed_id')
            ->addData($params)
            // set the data in the past so the schedule can work for today
            ->setData('processed_at', Mage::getModel('core/date')->timestamp(time())-24*60*60)
            ->save();

        $jsonData = json_encode(
            array(
                'isUpdated' => true,
                'readableFormat' => $scheduleModel->__toString()
            )
        );
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }

    /**
     * Fill in taxonomy options for category widget
     */
    public function autocompleteAction()
    {
        /** @var RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed */
        try {
            $feed = $this->_getFeed();
            $feed->setCategoryLocale($feed->getConfig('categories_locale'));
        } catch (Mage_Core_Exception $e) {
            $type = $this->getRequest()->getParam('type');
            $locale = $this->getRequest()->getParam('locale');
            $feed = Mage::getModel('googlebasefeedgenerator/feed');
            $feed->setType($type)
                ->setCategoryLocale($locale);
        }
        $results = array();

        if ($feed) {
            $partial = $this->getRequest()->getParam('partial');

            $taxonomyModel = Mage::getSingleton('googlebasefeedgenerator/feed_taxonomy');
            $results = $taxonomyModel->prepareTaxonomyFiles($feed)->getAutocompleteList($partial);
        }
        $jsonData = Mage::helper('core')->jsonEncode($results);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }

    /**
     * Get tree node (Ajax version). Used in Custom Options tab.
     * Taken from Mage Adminhtml/Promo/WidgetController
     */
    public function categoriesJsonAction()
    {
        if ($categoryId = (int)$this->getRequest()->getPost('id')) {
            $this->getRequest()->setParam('id', $categoryId);

            if (!$category = $this->_initCategory()) {
                return;
            }
            $this->getResponse()->setBody(
                $this->getLayout()->createBlock('adminhtml/catalog_category_tree')
                    ->getTreeJson($category)
            );
        }
    }

    /**
     * Initialize category object in registry
     * Taken from Mage Adminhtml/Promo/WidgetController
     *
     * @return Mage_Catalog_Model_Category
     */
    protected function _initCategory()
    {
        $categoryId = (int)$this->getRequest()->getParam('id', false);
        $storeId = (int)$this->getRequest()->getParam('store');

        $category = Mage::getModel('catalog/category')->setStoreId($storeId);

        if ($categoryId) {
            $category->load($categoryId);
            if ($storeId) {
                $rootId = Mage::app()->getStore($storeId)->getRootCategoryId();
                if (!in_array($rootId, $category->getPathIds())) {
                    return false;
                }
            }
        }

        Mage::register('category', $category);
        Mage::register('current_category', $category);

        return $category;
    }

    /**
     * Test ftp account connection
     */
    public function testftpAction()
    {
        if (!$this->getRequest()->isAjax()) {
            return false;
        }
        $account = new Varien_Object($this->getRequest()->getPost());
        $encrypted = false;

        if ($account->getPassword() == RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Ftp::OBSCURED_VALUE) {
            $ftpAccount = Mage::getModel('googlebasefeedgenerator/feed_ftp')->load($account->getId());
            $account->setPassword($ftpAccount->getPassword());
            $encrypted = true;
        }

        $account->setTimeout(10);
        $result = Mage::helper('googlebasefeedgenerator')->ftpUpload($account, $encrypted);
        $result = $result === true ? 1 : json_encode($this->__('Validation failed. Please check credentials'));
        $this->getResponse()->setBody($result);
    }
}
