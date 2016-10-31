<?php

require_once Mage::getBaseDir() . '/vendor/google/apiclient/src/Google/Client.php';
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Google Shopping connector
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_GoogleShopping extends Varien_Object {

    const APPNAME = 'Exinent Magento GoogleShopping';

    /**
     * @var Google_Client
     */
    protected $_client = null;

    /**
     * @var Google_Service_ShoppingContent
     */
    protected $_shoppingService = null;

    /**
     * Google Content Config
     *
     * @return Exinent_GoogleShoppingApi_Model_Config
     */
    public function getConfig() {
        return Mage::getSingleton('googleshoppingapi/config');
    }

    /**
     * Retutn Google Content Client Instance
     *
     * @param int $storeId
     * @param string $loginToken
     * @param string $loginCaptcha
     * @return Zend_Http_Client
     */
   public function getClient($storeId) {
      // Mage::log("ghfkjhgjkhdfg",null,"pawanhfghdf.log");
      
        if (isset($this->_client)) {
            // die("jaffa if");
            if ($this->_client->isAccessTokenExpired()) {
                header('Location: ' . Mage::getUrl("adminhtml/googleShoppingApi_oauth/auth", array('store_id' => $storeId)));
                exit;
            }
            return $this->_client;
        }
        
        $adminSession = Mage::getSingleton('admin/session');
        echo '<pre>';print_r($adminSession->getData());
       // echo get_class($adminSession);
        $accessTokens = $adminSession->getGoogleOAuth2Token();
        //echo $accessTokens."-----------". $adminSession;
        
        $clientId = $this->getConfig()->getConfigData('client_id', $storeId);
        echo $clientId;
        $clientSecret = $this->getConfig()->getConfigData('client_secret', $storeId);
        
        $accessToken = $accessTokens[$clientId];
        print_r($accessToken);
        if (!$clientId || !$clientSecret) {
           
            die("result if client ");
            Mage::getSingleton('adminhtml/session')->addError("Please specify Google Content API access data for this store!");
            return false;
        }

        if (!isset($accessToken) || empty($accessToken)) {
            
        die("result if accesstoken ");
            header('Location: ' . Mage::getUrl("adminhtml/googleShoppingApi_oauth/auth", array('store_id' => $storeId)));
            exit;
        }

        echo $accessToken."----"."jfdjgdfjhg";
        die("resultouter");


        $client = new Google_Client();
        $client->setApplicationName(self::APPNAME);
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setScopes('https://www.googleapis.com/auth/content');
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            header('Location: ' . Mage::getUrl("adminhtml/googleShoppingApi_oauth/auth", array('store_id' => $storeId)));
            exit;
        }

        $this->_client = $client;

        return $this->_client;
    }


    /**
     * @return Google_Service_ShoppingContent shopping client
     */
    public function getShoppingService($storeId = null) {
        if (isset($this->_shoppingService)) {
            return $this->_shoppingService;
        }

        $this->_shoppingService = new Google_Service_ShoppingContent($this->getClient($storeId));
        return $this->_shoppingService;
    }

    public function listProducts($storeId = null) {
        $merchantId = $this->getConfig()->getConfigData('merchant_id', $storeId);

        return $this->getShoppingService($storeId)->products->listProducts($merchantId);
        //$products = $this->getShoppingService($storeId)->products->listProducts($merchantId, $parameters);
        //$products->getResources();
// 		echo count($products);
// 		foreach($products as $product) {
// 			echo $product->title."<br/>";
// 		}
    }

    /**
     * @param string product id
     * @param integer store id
     *
     * @return Google_Service_ShoppingContent_Product product
     */
    public function getProduct($productId, $storeId = null) {
        $merchantId = $this->getConfig()->getConfigData('account_id', $storeId);
        $product = $this->getShoppingService($storeId)->products->get($merchantId, $productId);
        return $product;
    }

    /**
     * @param string product id
     * @param integer store id
     */
    public function deleteProduct($productId, $storeId = null) {
        Mage::log($productId, 1, 'googleapi.log');
        $merchantId = $this->getConfig()->getConfigData('account_id', $storeId);
        $result = $this->getShoppingService($storeId)->products->delete($merchantId, $productId);
        return $result;
    }

    /**
     * @param Google_Service_ShoppingContent_Product product
     * @param integer store id
     *
     * @return Google_Service_ShoppingContent_Product product
     */
    public function insertProduct($product, $storeId = null) {
        $merchantId = $this->getConfig()->getConfigData('account_id', $storeId);
        $product->setChannel("online");
        $expDate = date("Y-m-d", (time() + 30 * 24 * 60 * 60)); //product expires in 30 days
        $product->setExpirationDate($expDate);
        $result = $this->getShoppingService($storeId)->products->insert($merchantId, $product);
        return $result;
    }

    /**
     * @param Google_Service_ShoppingContent_Product product
     * @param integer store id
     *
     * @return Google_Service_ShoppingContent_Product product
     */
    public function updateProduct($product, $storeId = null) {
        return $this->insertProduct($product, $storeId);
    }

}