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
 * Class RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Taxonomy
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Taxonomy extends Mage_Core_Model_Abstract
{
    /** const CACHE_PATH Folder in which the taxonomy files are saved */
    const CACHE_PATH = 'taxonomy';
    /** cosnt CACHE_FILE_LIFETIME Age in seconds before taxonomy file is replaced */
    const CACHE_FILE_LIFETIME = 2592000; // 30 days

    /** @var string $_taxonomyCachePath Absolute path to the taxonomy cache folder */
    protected $_taxonomyCachePath = null;
    /** @var array $_taxonomy The taxonomy file */
    protected $_taxonomy = null;

    /**
     * Checks if file needs to be replaced (CACHE_FILE_LIFETIME)
     * and fetches a new one.
     *
     * @param RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed
     * @return $this
     */
    public function prepareTaxonomyFiles(RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed)
    {
        if (!is_array($this->_taxonomy) || count($this->_taxonomy) <= 1) {
            $feedType = $feed->getType();
            if (!$feed->getCategoryLocale()) {
                $locale = $feed->getConfig('categories_locale');
                $feed->setCategoryLocale($locale);
            }
            if (!array_key_exists($feedType, $this->_getSupportedFeedTypes())) {
                return $this;
            }

            try {
                $taxonomyCacheFile = $this->getCacheFile($feed);
                if (!$this->_isCacheFileValid($taxonomyCacheFile)) {
                    $this->_createCacheFile($taxonomyCacheFile, $feed);
                }
            } catch (RocketWeb_GoogleBaseFeedGenerator_Model_Exception_Taxonomy $e) {
                Mage::getSingleton('adminhtml/session')->addWarning($e->getMessage());
            }
        }
        return $this;
    }

    /**
     * Tries to create the cache folder (if it doesn't exists)
     * and returns the full path of the cache file.
     *
     * @param RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed
     * @return string
     * @throws RocketWeb_GoogleBaseFeedGenerator_Model_Exception_Taxonomy
     */
    public function getCacheFile(RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed)
    {
        if (is_null($this->_taxonomyCachePath)) {
            $taxonomyCachePath = Mage::getBaseDir('cache') . DS . self::CACHE_PATH;
            try {
                Mage::helper('googlebasefeedgenerator')->initSavePath($taxonomyCachePath);
            } catch (Exception $e) {
                $this->_throwException(sprintf(
                    "Can't create Taxonomy cache folder %s", $taxonomyCachePath
                ));
            }
            $this->_taxonomyCachePath = $taxonomyCachePath;
        }
        return $this->_taxonomyCachePath . DS . $feed->getType() . '-' . $feed->getCategoryLocale() . '.txt';
    }

    /**
     * Returns a list of possible taxonomy lines based on given string
     *
     * @param string $autocompleteString
     * @return array
     */
    public function getAutocompleteList($autocompleteString)
    {
        $results = array();
        $autocompleteString = trim($autocompleteString);
        foreach ($this->_taxonomy as $category) {
            if (stripos($category, $autocompleteString) !== false) {
                $results[] = $category;
            }
        }

        if (count($results) == 0) {
            $searchStrings = explode(' ', $autocompleteString);
            $tmpSearchStrings = array();
            foreach ($searchStrings as $search) {
                if (empty($search) || $search == '>' || $search == '&') {
                    continue;
                }
                $tmpSearchStrings[] = $search;
            }
            $searchStrings = $tmpSearchStrings;

            if (count($searchStrings) > 1) {
                foreach ($this->_taxonomy as $category) {
                    if (stripos($category, $searchStrings[0]) !== false) {
                        $results[] = $category;
                    }
                }
                unset($searchStrings[0]);

                foreach ($searchStrings as $search) {
                    foreach ($results as $key => $line) {
                        if (stripos($line, $search) === false) {
                            unset($results[$key]);
                        }
                    }
                }

            }

        }
        if (count($results) == 0) {
            $results[] = 'There are no options';
        }

        return array_values($results);
    }

    /**
     * Checks if the given feed type support taxonomy autocomplete
     *
     * @param RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed
     * @return bool
     */
    public function isTaxonomyEnabled(RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed)
    {
        $supportedTypes = $this->_getSupportedFeedTypes();
        return array_key_exists($feed->getType(), $supportedTypes);
    }

    /**
     * *********************************
     * PROTECTED / PRIVATE METHODS
     * *********************************
     */

    /**
     * Checks if cache file is older then CACHE_FILE_LIFETIME
     * Sets the content of $_taxonomy if file is still valid
     *
     * @param string $cacheFile
     * @return bool
     */
    protected function _isCacheFileValid($cacheFile)
    {
        if (file_exists($cacheFile)) {
            $fileContentString = file_get_contents($cacheFile);
            $fileContent = explode("\n", $fileContentString);
            if (count($fileContent) > 1 && is_numeric($fileContent[0])) {
                if ($fileContent[0] + self::CACHE_FILE_LIFETIME > time()) {
                    unset($fileContent[0]);
                    $this->_taxonomy = array_values($fileContent);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Fetches the latest taxonomy file from provider (using cURL)
     * and sets the content into $_taxonomy variable
     *
     * @param string $cacheFile
     * @param RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed
     * @return $this
     * @throws RocketWeb_GoogleBaseFeedGenerator_Model_Exception_Taxonomy
     */
    protected function _createCacheFile($cacheFile, RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed)
    {
        if (($url = $this->_getTaxonomyUrl($feed)) === false) {
            $this->_throwException(sprintf('No valid Taxonomy Url found. Please contact us.'));
        }
        $curl = new Varien_Http_Adapter_Curl();
        $curl->setConfig(array(
            'header' => false,
            'timeout' => 15    //Timeout in no of seconds
        ));
        $curl->write(Zend_Http_Client::GET, $url, '1.0');
        $data = $curl->read();
        if ($data === false) {
            $this->_throwException(sprintf('Taxonomy Url fetch failed. Please contact us.'));
        }
        $curl->close();
        $fileContent = explode("\n", $data);
        if (strpos($fileContent[0], '#') !== false) {
            // We remove the comment from the file
            unset($fileContent[0]);
        }
        $fileContent= $this->_taxonomy = array_values(array_filter($fileContent));

        array_unshift($fileContent, time());
        file_put_contents($cacheFile, implode("\n", $fileContent));

        return $this;
    }

    /**
     * Returns list of feed types that supported for taxonomy autocomplete
     *
     * @return array
     */
    protected function _getSupportedFeedTypes()
    {
        return RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Type::getTaxonomyFeedTypes();
    }

    /**
     * Returns the Url of the taxonomy file provider (Google, ...)
     * for given feed
     *
     * @param RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed
     * @return string
     */
    protected function _getTaxonomyUrl(RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed)
    {
        $urls = RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Type::getTaxonomyFeedUrl();
        return sprintf($urls[$feed->getType()], $feed->getCategoryLocale());
    }

    /**
     * Throws Taxonomy Exception
     *
     * @param string $msg
     * @throws RocketWeb_GoogleBaseFeedGenerator_Model_Exception_Taxonomy
     */
    protected function _throwException($msg = '')
    {
        throw new RocketWeb_GoogleBaseFeedGenerator_Model_Exception_Taxonomy($msg);
    }
}