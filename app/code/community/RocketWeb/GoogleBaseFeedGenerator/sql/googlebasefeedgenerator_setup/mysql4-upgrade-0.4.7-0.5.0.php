<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 */

/**
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @author     RocketWeb
 */

/** @var $installer RocketWeb_GoogleBaseFeedGenerator_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('googlebasefeedgenerator/feed')};
CREATE TABLE `{$this->getTable('googlebasefeedgenerator/feed')}` (
	`id` int(11) unsigned NOT NULL auto_increment,
	`store_id` smallint(5) unsigned NOT NULL,
	`name` text NOT NULL default '',
	`type` varchar(100) NOT NULL default 'generic',
	`status` smallint(5) unsigned NOT NULL default 1,
	`messages` varchar(1500) NOT NULL,
	`updated_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	PRIMARY KEY  (`id`),
	KEY `FK_STORE_ID` (`store_id`),
	KEY `IDX_TYPE` (`type`),
	KEY `IDX_UPDATED_AT` (`updated_at`),
	CONSTRAINT `FK_FEED_STORE` FOREIGN KEY (`store_id`) REFERENCES {$installer->getTable('core_store')} (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('googlebasefeedgenerator/feed_config')};
CREATE TABLE `{$this->getTable('googlebasefeedgenerator/feed_config')}` (
	`id` int(11) unsigned NOT NULL auto_increment,
	`feed_id` int(11) unsigned NOT NULL,
	`path` varchar(255) NOT NULL default 'general',
	`value` text NULL,
	PRIMARY KEY  (`id`),
	KEY `FK_FEED_ID` (`feed_id`),
	KEY `IDX_PATH` (`path`),
	CONSTRAINT `FK_FEED_CONFIG_FEED` FOREIGN KEY (`feed_id`) REFERENCES {$installer->getTable('googlebasefeedgenerator/feed')} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('googlebasefeedgenerator/feed_schedule')};
CREATE TABLE `{$this->getTable('googlebasefeedgenerator/feed_schedule')}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `feed_id` int(11) unsigned NOT NULL,
  `start_at` smallint(6) DEFAULT NULL,
  `processed_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `batch_mode` smallint(6) NOT NULL,
  `batch_limit` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_RW_GFEED_FEED_SCHEDULE_FEED_ID` (`feed_id`),
  CONSTRAINT `FK_RW_GFEED_FEED_SCHEDULE_FEED_ID_RW_GFEED_FEED_ID` FOREIGN KEY (`feed_id`) REFERENCES {$installer->getTable('googlebasefeedgenerator/feed')} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('googlebasefeedgenerator/queue')};
CREATE TABLE `{$this->getTable('googlebasefeedgenerator/queue')}` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `feed_id` int(11) unsigned NOT NULL,
    `message` varchar(1500) NOT NULL,
    `is_read` int(1) NOT NULL default 0,
    `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
    PRIMARY KEY  (`id`),
    KEY `FK_FEED_ID` (`feed_id`),
    CONSTRAINT `FK_FEED_QUEUE_FEED` FOREIGN KEY (`feed_id`) REFERENCES {$installer->getTable('googlebasefeedgenerator/feed')} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();