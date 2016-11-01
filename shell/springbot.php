<?php
/**
 * There are a few similarities to this and Mage_Shell_Abstract. Springbot still
 * officially supports Magento 1.3.*, therefore, we needed to roll our own.
 */
class Springbot_Shell
{
	private $_action;
	private $_type;
	private $_appCode     = 'admin';
	private $_appType     = 'store';
	private $_magentoRootDir;
	private $_args;

	public function __construct()
	{
		require_once $this->getApplicationPath() . $this->getMagePath();
		Mage::app($this->_appCode, $this->_appType);
		$this->_parseArgs();
	}

	public function run()
	{
		try {
			Springbot_Log::debug("Running {$this->_action}:{$this->_type}");
			$class = Springbot_Services_Registry::getInstance("{$this->_action}:{$this->_type}");
			if($class) {
				$ret = $class->setData($this->_args)->run();
			}
			echo $ret;
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
			echo $e->getMessage() . PHP_EOL;
			exit(1);
		}
	}

	public function getMagePath()
	{
		return 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
	}

	public function getApplicationPath()
	{
		if(!isset($this->_magentoRootDir)) {
			if(file_exists($this->getMagePath())) {
				$this->_magentoRootDir = getcwd() . DIRECTORY_SEPARATOR;
			}
			else {
				for ($i = 0, $d = ''; !file_exists($d.$this->getMagePath()) && $i++ < 10; $d .= '../');
				$this->_magentoRootDir = getcwd() . DIRECTORY_SEPARATOR . $d;
			}

			if(!file_exists($this->_magentoRootDir . $this->getMagePath())) {
				throw new Exception("Cannot find Mage root path!");
			}
		}
		return $this->_magentoRootDir;
	}

	private function _parseArgs()
	{
		try {
			$argv = $_SERVER['argv'];
			$opts = getopt('s:i:h:c:o:dfrv:m:j:n:p:');
			list($this->_action, $this->_type) = explode(':', end($argv));
			$this->_args = Springbot_Services_Registry::parseOpts($opts);
			if(!isset($this->_action) || !isset($this->_type)) {
				throw new Exception;
			}
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
			$this->_usage();
			exit;
		}
	}

	private function _usage()
	{
		echo "Usage:  \033[1mphp shell/springbot.php -s\033[0m \033[4mstore_id\033[0m \033[1m-i\033[0m \033[4mstart_id\033[0m:\033[4mend_id\033[0m \033[1maction:type\033[0m\n\n";
	}
}

$shell = new Springbot_Shell;
$shell->run();
