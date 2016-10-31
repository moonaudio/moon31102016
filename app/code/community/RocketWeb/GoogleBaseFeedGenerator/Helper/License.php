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
 * @copyright Copyright (c) 2015 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */
class RocketWeb_GoogleBaseFeedGenerator_Helper_License extends Mage_Core_Helper_Abstract
{
    const XML_PATH_KEY = 'rocketweb_googlebasefeedgenerator/general/license_key';
    const MAIN_LICENSE_FILE = 'rw-gsf-license.txt';
    const LICENSE_CHECK_URL = 'http://www.rocketweb.com/license/index/check/';

    protected function _getMainLicenseFilePath()
    {
        return Mage::getBaseDir('var') . '/' . self::MAIN_LICENSE_FILE;
    }

    /**
     * Activates a license key the first time you save it
     * TODO: Event does not contain $config anymore
     *
     * @return bool
     */
    public function activateLicense(Varien_Event_Observer $observer)
    {
        $config = $observer->getEvent()->getObject();
        if ($config->getSection() !== 'rocketweb_googlebasefeedgenerator') {
            return;
        }

        $old_key = Mage::getConfig()->getNode(self::XML_PATH_KEY)->asArray();
        $new_key = $config->groups['file']['fields']['license_key']['value'];

        if (empty($new_key) && !empty($old_key)) {
            //delete existing license
            unlink($this->_getMainLicenseFilePath());
            Mage::getSingleton('adminhtml/session')->addWarning('Google Shopping Feed: License was removed, extension is now inactive');
            return false;
        }

        if ($old_key == $new_key) {
            return true;
        }

        $domain = str_replace(array('http://', 'https://'), array('', ''), Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));
        $parts = explode('/', trim($domain));
        $domain = $parts[0];
        $params = array(
            'key' => $new_key,
            'domain' => $domain
        );
        $params_string = '';
        foreach ($params as $key => $value) {
            $params_string .= $key . '=' . $value . '&';
        }
        rtrim($params_string, '&');

        $ch = curl_init();

        curl_setopt_array(
            $ch, array(
                CURLOPT_URL => self::LICENSE_CHECK_URL,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $params_string
            )
        );

        $response = json_decode(curl_exec($ch));
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response instanceof stdClass && $response->valid) {
            $license_file = fopen($this->_getMainLicenseFilePath(), 'w');
            fclose($license_file);

            if ($license_file === false) {
                Mage::getSingleton('adminhtml/session')->addError('Google Shopping Feed: License is not activated (could not save license file)');
                unlink($this->_getMainLicenseFilePath());
                return false;
            }

            Mage::getSingleton('adminhtml/session')->addSuccess('Google Shopping Feed: License is activated! You can now generate feeds.');
            return true;
        } else {
            Mage::log('Google Shopping Feed - License activation error: ' . $response->error . ' (' . $http_status . ')');
            Mage::getSingleton('adminhtml/session')->addError('Google Shopping Feed: License is not activated (license key is not valid)');
            unlink($this->_getMainLicenseFilePath());
            return false;
        }
    }
}