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

require_once 'abstract.php';

/**
 * Google Shopping Feed Generator Shell Script
 *
 * @category    GoogleBaseFeedGenerator
 * @package     GoogleBaseFeedGenerator_Shell
 */
class GoogleBaseFeedGenerator_Shell_Feed extends Mage_Shell_Abstract
{
    /**
     * Fix for servers missing $_SERVER['argv']
     */
    protected function _parseArgs()
    {
        $current = null;
        $argv = !empty($_SERVER['argv']) ? $_SERVER['argv']: array_keys($_GET);

        foreach ($argv as $arg) {
            $match = array();
            if (preg_match('#^--([\w\d_-]{1,})$#', $arg, $match) || preg_match('#^-([\w\d_]{1,})$#', $arg, $match)) {
                $current = $match[1];
                $this->_args[$current] = true;
            } else {
                if ($current) {
                    $this->_args[$current] = $arg;
                } else if (preg_match('#^([\w\d_]{1,})$#', $arg, $match)) {
                    $this->_args[$match[1]] = true;
                }
            }
        }
        return $this;
    }

    public function run()
    {
        $data = array(
            'schedule_id'   => uniqid(rand(), true),
            'verbose'       => $this->getArg('verbose') ? true : false,
            'feed_id'       => $this->getArg('feed_id') ? $this->getArg('feed_id') : false,
            'test_sku'      => $this->getArg('test_sku') ? $this->getArg('test_sku') : false,
//            'test_offset'   => $this->getArg('test_offset') ? $this->getArg('test_offset') : 0,
//            'test_limit'    => $this->getArg('test_limit') ? $this->getArg('test_limit') : 0,
        );

        try {
            set_time_limit(0);
            /* Setting memory limit depends on the number of products exported.*/
            // ini_set('memory_limit','600M');
            error_reporting(E_ALL);

            @Mage::app('admin')->setUseSessionInUrl(false);

            // For when server rewrites are not available
            Mage::register('custom_entry_point', true);

            $data['test_mode'] = $data['test_sku'] || ($data['test_offset'] && $data['test_limit']);
            if ($data['test_sku'] && !$data['feed_id']) {
                Mage::throwException('In order to run a test for --test_sku, you\'ll have to provide the --feed_id as well');
            }

            if ($data['feed_id']) {
                $feed = Mage::getModel('googlebasefeedgenerator/feed')->load($data['feed_id']);
                if (!$feed->getId()) {
                    Mage::throwException(sprintf('Could not load feed configuration for ID \'%s\'.', $data['feed_id']));
                }
                $Generator = Mage::helper('googlebasefeedgenerator')->getGenerator($feed)->addData($data);
                $Generator->run();
            } else {
                $schedule = Mage::getModel('Mage_Cron_Model_Schedule', $data);
                Mage::getModel('googlebasefeedgenerator/observer')->processSchedule();
                Mage::getModel('googlebasefeedgenerator/observer')->processQueue($schedule);
            }

        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php gen_gbase_feed.php --[options]

  feed_id <string>          Specify the feed ID if you need to run only one feed at a time. Missing feed_id will process the schedules.
  test_sku <string>         Generate the feed only for a product sku. To be used for tests and debuging.
  verbose                   Outputs skus and memory during processing
  help                      This help

                            e.g. php gen_gbase_feed.php --feed_id 3 --verbose

USAGE;
    }
}

$shell = new GoogleBaseFeedGenerator_Shell_Feed();
$shell->run();