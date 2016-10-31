<?php
/**
 * Feel free to contact me via Facebook
 * http://www.facebook.com/rebimol
 *
 *
 * @author 		Vladimir Popov
 * @copyright  	Copyright (c) 2011 Vladimir Popov
 * @license    	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS `{$this->getTable('webforms/webforms')}`;
CREATE TABLE IF NOT EXISTS `{$this->getTable('webforms/webforms')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `success_text` text NOT NULL,
  `registered_only` tinyint(1) NOT NULL,
  `send_email` tinyint(1) NOT NULL,
  `duplicate_email` tinyint(1) NOT NULL,
  `email` varchar(255) NOT NULL,
  `survey` tinyint(1) NOT NULL,
  `created_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$this->getTable('webforms/fields')}`;
CREATE TABLE IF NOT EXISTS `{$this->getTable('webforms/fields')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webform_id` int(11) NOT NULL,
  `fieldset_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `size` varchar(20) NOT NULL,
  `value` text NOT NULL,
  `email_subject` tinyint(1) NOT NULL,
  `css_class` varchar(255) NOT NULL,
  `css_style` varchar(255) NOT NULL,
  `position` int(11) NOT NULL,
  `required` tinyint(1) NOT NULL,
  `created_time` datetime NOT NULL,
  `update_time` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$this->getTable('webforms/fieldsets')}`;
CREATE TABLE IF NOT EXISTS `{$this->getTable('webforms/fieldsets')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webform_id` int(11) NOT NULL,
  `name` varchar(100)  NOT NULL,
  `position` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  `update_time` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$this->getTable('webforms/results')}`;
CREATE TABLE IF NOT EXISTS `{$this->getTable('webforms/results')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webform_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `customer_ip` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  `update_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$this->getTable('webforms/results_values')}`;
CREATE TABLE IF NOT EXISTS `{$this->getTable('webforms/results_values')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `result_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `value` text  NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `result_id` (`result_id`,`field_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{$this->getTable('webforms/webforms')}` VALUES(1, 'Contact Us', '<p>You can use this extension to create and manage web-forms on your sites.</p>\r\n<ul class=\"disc\">\r\n<li>Native Magento form proccessing.</li>\r\n<li>No limits to site views, fields or localization.</li>\r\n<li>All results are stored in database and are accessible anytime.</li>\r\n<li>Set up notification e-mails for new submissions.</li>\r\n<li>Style it to your needs.</li>\r\n<li>Use Prototype validation classes for precize field entries.</li>\r\n<li>Add Google reCaptcha for unregistered users.</li>\r\n<li>Insert it via widget on any page you like.</li>\r\n<li>Come on! It`s absolutely free!</li>\r\n</ul>', '<p>Thank you for contacting me.</p>\r\n<p>You should get notification e-mail to the address you specified in the form.</p>\r\n<p>If you wonder how survey results are proccessed. Its exported to Excel XML file from administration panel, then analyzed in Excel application.</p>\r\n<p>You can contact me on Facebook&nbsp;<a href=\"http://www.facebook.com/rebimol\">http://www.facebook.com/rebimol</a><br /><br /></p>', 0, 1, 1, '', 0, '2011-06-27 09:54:10', '2011-06-28 04:28:04', 1);
INSERT INTO `{$this->getTable('webforms/webforms')}` VALUES(3, 'Customer Support', '<p><span style=\"color: #cc0000;\">ALWAYS INCLUDE YOUR ORDER NUMBER IN YOUR COMMUNICATION</span>&nbsp;</p>\r\n<p>We can only read and reply to queries in&nbsp;<strong>English</strong>.</p>\r\n<p>Our current average respons time is&nbsp;<span style=\"color: #cc0000;\"><strong>24 hours</strong></span>.&nbsp;<br /><br /></p>', '<p>Thank you for contacting our support!</p>\r\n<p>We will get back to you shortly.</p>\r\n<p>Feel free to call us <span style=\"color: #cc0000;\"><strong>+4 (123) 123-1234</strong></span></p>', 1, 1, 1, 'rebimol@gmail.com', 0, '2011-06-28 06:22:37', '2011-06-28 08:14:55', 1);
INSERT INTO `{$this->getTable('webforms/webforms')}` VALUES(4, 'Sample Survey', '<p>Please, fill up the form and see how survey works!</p>', '<p>If you wonder how survey data is analized:</p>\r\n<ul class=\"disc\">\r\n<li>Its exported to CSV or Excel XML</li>\r\n<li>Analyzed in Excel or any other your favorite application</li>\r\n</ul>', 1, 1, 1, '', 1, '2011-06-28 08:37:26', '2011-06-28 08:53:36', 1);

INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(1, 1, 1, 'First name', 'text', 'standard', '{{firstname}}', 0, 'validate-alpha', '', 10, 1, '2011-06-27 09:58:43', '2011-06-27 10:03:03', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(2, 1, 1, 'Last name', 'text', 'standard', '{{lastname}}', 0, 'validate-alpha', '', 20, 1, '2011-06-27 10:16:03', '2011-06-27 10:16:03', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(3, 1, 1, 'E-mail address', 'email', 'wide', '{{email}}', 0, '', '', 30, 1, '2011-06-27 10:16:43', '2011-06-27 10:16:43', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(4, 1, 2, 'Do you need custom web-forms on your site?', 'select/radio', 'standard', 'Yes\r\nNo', 0, '', '', 40, 0, '2011-06-27 10:18:29', '2011-06-28 03:51:42', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(5, 1, 2, 'Did you try other web-forms extensions?', 'select/radio', 'standard', 'Yes\r\nNo', 0, '', '', 50, 0, '2011-06-27 10:19:40', '2011-06-28 03:52:23', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(6, 1, 2, 'How did you find this page?', 'select/checkbox', 'standard', 'Search engine\r\nMagento connect\r\nForums\r\nBlogs\r\nFriends\r\nLink from the other site', 0, '', '', 70, 1, '2011-06-27 10:24:46', '2011-06-28 03:53:32', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(7, 1, 2, 'What other web-forms extensions have you tried?', 'text', 'wide', '', 0, '', '', 60, 0, '2011-06-27 10:28:39', '2011-06-28 04:25:04', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(8, 1, 3, 'Subject', 'select', 'wide', 'I like your extension\r\nI found a bug\r\nIt needs more important features\r\nOther\r\n', 1, '', 'font-weight:bold', 80, 1, '2011-06-28 03:47:53', '2011-06-28 03:47:53', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(9, 1, 3, 'Comments', 'textarea', 'wide', '', 0, '', 'font-style:italic; color:#333', 90, 1, '2011-06-28 03:48:37', '2011-06-28 03:48:37', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(10, 1, 2, 'Do you like this extension?', 'select/radio', 'standard', 'Yes\r\nYes, but it needs more features\r\nI`m not sure yet\r\nI don`t know how to use it\r\nNo, it lacks important features\r\nNo, it absolytely doesn`t suit my needs', 0, '', '', 65, 0, '2011-06-28 03:55:13', '2011-06-28 03:56:37', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(15, 3, 8, 'Subject', 'text', 'wide', '', 1, '', '', 10, 1, '2011-06-28 06:23:51', '2011-06-28 08:07:49', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(16, 3, 8, 'Message', 'textarea', 'wide', '', 0, '', '', 60, 1, '2011-06-28 06:24:27', '2011-06-28 06:41:02', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(17, 3, 8, 'Criticality', 'select', 'standard', 'Low\r\nNormal\r\nHigh', 1, '', '', 30, 1, '2011-06-28 06:25:47', '2011-06-28 06:29:06', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(18, 3, 8, 'Category', 'select', 'standard', 'Update shipping address\r\nTracking number request\r\nWhen will my order be shipped?\r\nI have a problem with the website\r\nI want to cancel my order\r\nGeneral inquiries', 1, '', '', 50, 1, '2011-06-28 06:28:19', '2011-06-28 06:29:30', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(19, 3, 8, 'Phone', 'text', 'standard', '', 0, 'validate-phoneStrict', '', 15, 0, '2011-06-28 06:30:32', '2011-06-28 06:30:32', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(31, 3, 8, 'Order ID', 'text', 'standard', '', 1, 'validate-number', '', 12, 0, '2011-06-28 08:08:31', '2011-06-28 08:08:31', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(32, 4, 9, 'Gender', 'select/radio', 'wide', 'female\r\nmale', 0, '', '', 10, 1, '2011-06-28 08:39:54', '2011-06-28 08:42:12', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(33, 4, 9, 'U.S. ethnic code', 'select/radio', 'wide', 'White (Non Hispanic)\r\nCambodian, Laotian, or Vietnamese whose family immigrated after 1975\r\nOther Asian or Pacific Islander\r\nAmerican Indian or Alaskan Native\r\nHispanic/Latin American\r\nBlack/African-American', 0, '', '', 20, 1, '2011-06-28 08:41:11', '2011-06-28 08:48:35', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(34, 4, 9, 'College rank', 'select/radio', 'wide', 'freshman\r\nsophomore\r\njunior\r\nsenior\r\nspecial\r\ngraduate', 0, '', '', 30, 1, '2011-06-28 08:41:57', '2011-06-28 08:48:48', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(35, 4, 9, 'Which of the following science courses have you completed in high school or college (check all that apply)', 'select/checkbox', 'wide', 'biology\r\nchemistry\r\nphysics', 0, '', '', 40, 1, '2011-06-28 08:42:47', '2011-06-28 08:49:11', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(36, 4, 9, 'Which of the following math courses have you completed in high school or college (check all that apply)', 'select/checkbox', 'wide', 'basic math\r\nalgebra\r\ngeometry\r\npre-calculus/trigonometry\r\ncalculus', 0, '', '', 35, 1, '2011-06-28 08:43:37', '2011-06-28 08:48:59', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(37, 4, 9, 'How many hours per week will you be working at a paid job this semester?', 'select/radio', 'wide', 'none\r\n1-5\r\n6-10\r\n11-15\r\n16-20\r\n21-30\r\n31-40', 0, '', '', 60, 1, '2011-06-28 08:44:33', '2011-06-28 08:49:24', 1);
INSERT INTO `{$this->getTable('webforms/fields')}` VALUES(38, 4, 9, 'Do you expect to have child care responsibilities this semester that will sometimes conflict with classes?', 'select/radio', 'wide', 'yes\r\nno', 0, '', '', 70, 1, '2011-06-28 08:45:06', '2011-06-28 08:49:35', 1);

INSERT INTO `{$this->getTable('webforms/fieldsets')}` VALUES(1, 1, 'Personal Info', 10, '2011-06-27 09:54:43', '2011-06-27 09:54:43', 1);
INSERT INTO `{$this->getTable('webforms/fieldsets')}` VALUES(2, 1, 'Survey', 20, '2011-06-27 09:55:08', '2011-06-27 09:55:22', 1);
INSERT INTO `{$this->getTable('webforms/fieldsets')}` VALUES(3, 1, 'Message', 30, '2011-06-27 09:55:48', '2011-06-27 09:55:48', 1);
INSERT INTO `{$this->getTable('webforms/fieldsets')}` VALUES(8, 3, 'Trouble Ticket', 10, '2011-06-28 06:23:11', '2011-06-28 06:23:11', 1);
INSERT INTO `{$this->getTable('webforms/fieldsets')}` VALUES(9, 4, 'Background Questions', 10, '2011-06-28 08:37:54', '2011-06-28 08:37:54', 1);
");

$installer->endSetup();
?>