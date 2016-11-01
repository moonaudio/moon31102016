<?php
// Version
define('VERSION', '1.5.1');

// Config
require_once('config.php');
   
// Install 
if (!defined('DIR_APPLICATION')) {
	header('Location: install/index.php');
	exit;
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');


// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Config
$config = new Config();
$registry->set('config', $config);

// Database 
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

if (extension_loaded('pdo') && extension_loaded('pdo_mysql') ) 
{
 	$db_pdo=new PDO("mysql:host=".DB_HOSTNAME.";dbname=".DB_DATABASE, DB_USERNAME, DB_PASSWORD);

}
else
{
	echo "There seems to be a PHP configuration problem on this server.<br>
	Issue: The PDO system appears disabled, or failed to load. This ShipRush module requires PHP 5.1 or higher, and <br>
	the PDO module in PHP to be enabled.<br> 
	Please check the php.ini setting and make sure following extensions are enabled:<br><br>
	Linux syntax:<br>
	<strong>extension=pdo.so;</strong><br>
	<strong>extension=pdo_mysql.so;</strong><br><br>
	On Windows web servers:<br>
	<strong>extension=php_pdo.dll;</strong><br>
	<strong>extension=extension=php_pdo_mysql.dll;</strong><br><br>
	If you do not know how to do this, or if you do not have access to the php.ini on your web server, <br>
	please contact your web master and/or web hosting service. Send them the url of your OpenCart store<br>
	and the full text of this message.";
	exit;

}

?>