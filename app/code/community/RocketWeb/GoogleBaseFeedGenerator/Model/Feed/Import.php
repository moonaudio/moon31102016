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
class RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Import extends Mage_ImportExport_Model_Import
{
    /**
     * Validates source file and returns validation result.
     *
     * @param string $sourceFile Full path to source file
     * @return bool
     */
    public function validateSource($sourceFile)
    {
        $this->addLogComment(Mage::helper('googlebasefeedgenerator')->__('Begin data validation'));

        $file = new Varien_Io_File();
        $file->open();

        $loadedXml = simplexml_load_string($file->read($sourceFile));
        if($loadedXml === FALSE) {
            Mage::throwException(Mage::helper('googlebasefeedgenerator')->__('XML data is not valid. Please check and resubmit the file.'));
        } else {
            $this->setSource($sourceFile);
            return true;
        }
    }

    /**
     * Import source file structure to DB.
     *
     * @return int
     */
    public function importSource()
    {
        $count = 0;
        $sourceFile = $this->getSource();
        if (!empty($sourceFile) && file_exists($sourceFile)) {

            $xml = simplexml_load_file($sourceFile);
            if($xml instanceof SimpleXMLElement) {
                // The following check needs to be refactored since we need to check somehow "item" is array of objects.
                $xmlItems = array();
                if(!empty($xml->item[1])) {
                    foreach($xml->item as $item) {
                        $xmlItems[] = (array)$item;
                    }
                } else {
                    $xmlItems[] = (array)$xml->item;
                }

                // We need to get rid of id in order to create a new instance of a feed
                foreach($xmlItems as $item) {
                    $feed = Mage::getModel('googlebasefeedgenerator/feed');
                    $schedules = array();
                    $ftps = array();
                    foreach ((array)$item as $itemFieldName => $itemField) {
                        // Fill in feed data
                        if (!in_array($itemFieldName, array('id', 'config', 'schedule'))) {
                            $value = @unserialize($itemField->__toString());
                            $value = ($value === false) ? $itemField->__toString() : $value;
                            $feed->setData($itemFieldName, $value);

                            if ($itemFieldName == 'name') {
                                $feed->setData($itemFieldName, $itemField->__toString(). ' (imported)');
                            }
                            if ($itemFieldName == 'status') {
                                $feed->setData($itemFieldName, RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_SCHEDULED);
                            }
                        }
                        // Fill in config data
                        if ($itemFieldName == 'config') {
                            $config = array();
                            foreach ((array)$itemField as $key => $val) {
                                $value = @unserialize($val->__toString());
                                $config[$key] = ($value === false) ? $val->__toString() : $value;
                            }
                            $feed->setConfig($config);
                        }
                        // Fill in schedules data
                        if ($itemFieldName == 'schedule') {
                            $schedule = array();

                            // multiple schedules defined for this feed
                            if (is_array($itemField)) {
                                foreach ($itemField as $scheduleNode) {
                                    foreach ($scheduleNode->children() as $scheduleData) {
                                        $key = $scheduleData->getName();
                                        $schedule[$key] = $scheduleData->__toString();
                                    }

                                    $schedules[] = $schedule;
                                }
                            } else { // only one schedule defined for this feed
                                foreach ($itemField->children() as $scheduleData) {
                                    $key = $scheduleData->getName();
                                    $schedule[$key] = $scheduleData->__toString();
                                }

                                $schedules[] = $schedule;
                            }
                        }
                        // Fill in schedules data
                        if ($itemFieldName == 'ftp_accounts') {
                            $ftp = array();
                            foreach ($itemField->children() as $ftpData) {
                                $key = $ftpData->getName();
                                $ftp[$key] = $ftpData->__toString();
                            }
                            $ftps[] = $ftp;
                        }

                    }
                    $feed->setSchedule($schedules);
                    if ($feed->save()) {
                        $count ++;
                        foreach ($ftps as $ftp) {
                            $ftpModel = Mage::getModel('googlebasefeedgenerator/feed_ftp')
                                ->setData($ftp)
                                ->setFeedId($feed->getId())
                                ->save();
                        }
                    }
                }
            } else {
                Mage::throwException('Bad XML format, please check you file formatting.');
            }
        } else {
            Mage::throwException('Failed to upload the xml file.');
        }

        return $count;
    }

    /**
     * Move uploaded file and create source adapter instance.
     *
     * @throws Mage_Core_Exception
     * @return string Source file path
     */
    public function uploadSource()
    {
        $uploader  = Mage::getModel('core/file_uploader', self::FIELD_NAME_SOURCE_FILE);
        $uploader->skipDbProcessing(true);
        $result    = $uploader->save(self::getWorkingDir());
        $extension = pathinfo($result['file'], PATHINFO_EXTENSION);
        $filename = pathinfo($result['file'], PATHINFO_BASENAME);

        $uploadedFile = $result['path'] . $result['file'];
        if (!$extension) {
            unlink($uploadedFile);
            Mage::throwException(Mage::helper('googlebasefeedgenerator')->__('Uploaded file has no extension'));
        }
        $sourceFile = self::getWorkingDir() . $filename;

        if(strtolower($uploadedFile) != strtolower($sourceFile)) {
            if (file_exists($sourceFile)) {
                unlink($sourceFile);
            }

            if (!@rename($uploadedFile, $sourceFile)) {
                Mage::throwException(Mage::helper('googlebasefeedgenerator')->__('Source file moving failed'));
            }
        }
        return $sourceFile;
    }

}