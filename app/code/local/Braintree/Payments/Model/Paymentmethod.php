<?php
/**
* Braintree Payments Extension
*
* This source file is subject to the Braintree Payment System Agreement (https://www.braintreepayments.com/legal)
*
* DISCLAIMER
* This file will not be supported if it is modified.
*
* @copyright   Copyright (c) 2014 Braintree. (https://www.braintreepayments.com/)
*/

class Braintree_Payments_Model_Paymentmethod extends Mage_Payment_Model_Method_Cc
{
    const CAPTURE_ON_INVOICE        = 'invoice';
    const CAPTURE_ON_SHIPMENT       = 'shipment';
    const CHANNEL_NAME              = 'Magento';
    const PAYMENT_METHOD_CODE       = 'braintree';
    const REGISTER_NAME             = 'braintree_save_card';
    const CONFIG_MASKED_FIELDS      = 'masked_fields';
    const CACHE_KEY_CREDIT_CARDS    = 'braintree_cc';

    protected $_formBlockType = 'braintree_payments/form';
    protected $_infoBlockType = 'braintree_payments/info';

    protected $_code                    = 'braintree';
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc               = false;
    protected $_canRefundInvoicePartial = true;
    protected $_merchantAccountId       = '';
    protected $_useVault                = false;

    protected $_requestMaskedFields     = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        if ($this->getConfigData('active') == 1) {
            $this->_initEnvironment(null);
        }
    }

    /**
     * Assign corresponding data
     * 
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function assignData($data)
    {
        parent::assignData($data);
        $this->getInfoInstance()->setCcLast4($data->getCcLast4());
        return $this;
    }

    /**
     * Validate data
     * 
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function validate()
    {
        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            $billingCountry = $info->getOrder()->getBillingAddress()->getCountryId();
        } else {
            $billingCountry = $info->getQuote()->getBillingAddress()->getCountryId();
        }

        if (!$this->canUseForCountry($billingCountry)) {
            Mage::throwException(Mage::helper('braintree_payments')
                ->__('Selected payment type is not allowed for billing country.'));
        }

        $ccType = false;
        if ($info->getCcType()) {
            $ccType = $info->getCcType();
        } else {
            $post = Mage::app()->getRequest()->getPost();
            if (isset($post['payment']['cc_token']) && ($token = $post['payment']['cc_token'])) {
                $ccType = false;
                $useCache = $this->getConfigData('usecache');
                $cachedValues = $useCache ? Mage::app()->loadCache(self::CACHE_KEY_CREDIT_CARDS) : false;
                if ($cachedValues) {
                    try {
                        $data = unserialize($cachedValues);
                    } catch (Exception $e) {
                        $data = false;
                    }
                    if ($data && array_key_exists($token, $data)) {
                        $ccType = $data[$token];
                    }
                }
                if (!$ccType) {
                    try {
                        $creditCard = Braintree_CreditCard::find($token);
                        $this->_debug($token);
                        $this->_debug($creditCard);
                        $ccType = Mage::helper('braintree_payments')->getCcTypeCodeByName($creditCard->cardType);
                        if ($cachedValues && $data) {
                            $data = array_merge($data, array($token => $ccType));
                        } else {
                            $data = array($token => $ccType);
                        }
                        if ($useCache) {
                            Mage::app()->saveCache(serialize($data), self::CACHE_KEY_CREDIT_CARDS);
                        }
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
            }
        }

        if ($ccType) {
            $error = $this->_canUseCcTypeForCountry($billingCountry, $ccType);
            if ($error) {
                Mage::throwException($error);
            }
        }
        
        return $this;
    }

    /**
     * Array of customer credit cards
     * 
     * @return array
     */
    public function currentCustomerStoredCards ()
    {
        if ($this->useVault() && Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerId = $this->generateCustomerId(Mage::getSingleton('customer/session')->getCustomerId(),
                Mage::getSingleton('customer/session')->getCustomer()->getEmail());
            try {
                $ret = Braintree_Customer::find($customerId)->creditCards;
                $this->_debug($customerId);
                $this->_debug($ret);
                return $ret;
            } catch (Braintree_Exception $e) {
                return array();
            }
        }
        return array();
    }

    /**
     * Returns stored card by token
     * 
     * @return Braintree_CreditCard | null
     */
    public function storedCard($token)
    {
        try {
            $ret = Braintree_CreditCard::find($token);
            $this->_debug($token);
            $this->_debug($ret);
            return $ret;
        } catch (Braintree_Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Authorizes specified amount
     * 
     * @param Varien_Object $payment
     * @param decimal $amount
     */
    public function authorize (Varien_Object $payment, $amount)
    {
        $this->_authorize($payment, $amount, false);
    }

    /**
     * Authorizes specified amount
     * 
     * @param Varien_Object $payment
     * @param decimal $amount
     * @param boolean $capture
     * @return Braintree_Payments_Model_Paymentmethod
     */
    protected function _authorize (Varien_Object $payment, $amount, $capture, $token = false)
    {
        try {
            $order = $payment->getOrder();
            $orderId = $order->getIncrementId();
            $billing = $order->getBillingAddress();
            $shipping = $order->getShippingAddress();
            $transactionParams = array(
                'channel'   => $this->_getChannel(),
                'orderId'   => $orderId,
                'amount'    => $amount,
                'customer'  => array(
                    'firstName' => $billing->getFirstname(),
                    'lastName'  => $billing->getLastname(),
                    'company'   => $billing->getCompany(),
                    'phone'     => $billing->getTelephone(),
                    'fax'       => $billing->getFax(),
                    'email'     => $order->getCustomerEmail(),
                )
            );
            $customerId = $this->generateCustomerId($order->getCustomerId(), $order->getCustomerEmail());
            $post = Mage::app()->getRequest()->getPost();
            if ($order->getCustomerId() && $this->useVault()) {
                if (isset($post['payment']['store_in_vault']) && $post['payment']['store_in_vault']) {
                    // to avoid card save several times during multishipping
                    if (!Mage::registry(self::REGISTER_NAME)) {
                        Mage::register(self::REGISTER_NAME, true);
                        $transactionParams['options']['storeInVaultOnSuccess'] = true;
                    }
                } else {
                    $transactionParams['options']['storeInVault'] = false;
                }
                if ($this->exists($customerId)) {
                    $transactionParams['customerId'] = $customerId;
                    unset($transactionParams['customer']);
                } else {
                    $transactionParams['customer']['id'] = $customerId;
                }
            }

            if ($capture) {
                $transactionParams['options']['submitForSettlement'] = true;
            }

            if ($this->_merchantAccountId) {
                $transactionParams['merchantAccountId'] = $this->_merchantAccountId;
            }

            if (!$token && isset($post['payment']['cc_token']) && $post['payment']['cc_token']) {
                $token = $post['payment']['cc_token'];
            }
            if ($token) {
                $transactionParams['paymentMethodToken'] = $token;
                $transactionParams['customerId'] = $customerId;
            } else {
                $transactionParams['creditCard'] = array(
                    'cardholderName'    => $billing->getFirstname() . ' ' . $billing->getLastname(),
                    'number'            => $payment->getCcNumber(),
                    'cvv'               => $payment->getCcCid(),
                    'expirationDate'    => $payment->getCcExpMonth() . '/' . $payment->getCcExpYear()
                );
                $transactionParams['billing']  = $this->toBraintreeAddress($billing);
                $transactionParams['shipping'] = $this->toBraintreeAddress($shipping);
                $transactionParams['options']['addBillingAddressToPaymentMethod']  = true;
            }

            if ($this->getConfigData('fraudprotection') && isset($post['device_data'])) {
                $transactionParams['deviceData'] = $post['device_data'];
            }

            $this->_debug($transactionParams);
            try {
                $result = Braintree_Transaction::sale($transactionParams);
                $this->_debug($result);
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::throwException(Mage::helper('braintree_payments')->__('Please try again later'));
            }
            if ($result->success) {
                $this->setStore($payment->getOrder()->getStoreId());
                $payment = $this->_processSuccessResult($payment, $result, $amount);
            } else {
                Mage::throwException(Mage::helper('braintree_payments/error')->parseBraintreeError($result));
            }
        } catch (Exception $e) {
            Mage::unregister(self::REGISTER_NAME);
            Mage::throwException($e->getMessage());
        }
        return $this;
    }

    /**
     * Returns extra transaction information, to be logged as part of the order payment
     *
     * @param $transaction
     * @return array
     */
    protected function _getExtraTransactionInformation($transaction) {
        $data = array();
        $loggedFields = array(
            'avsErrorResponseCode',
            'avsPostalCodeResponseCode',
            'avsStreetAddressResponseCode',
            'cvvResponseCode',
            'gatewayRejectionReason',
            'processorAuthorizationCode',
            'processorResponseCode',
            'processorResponseText'
        );
        foreach($loggedFields as $loggedField) {
            if(!empty($transaction->{$loggedField})) {
                $data[$loggedField] = $transaction->{$loggedField};
            }
        }
        return $data;
    }

    /**
     * Captures specified amount
     * 
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function capture(Varien_Object $payment, $amount)
    {
        try {
            if ($payment->getCcTransId()) {
                $collection = Mage::getModel('sales/order_payment_transaction')
                    ->getCollection()
                    ->addFieldToFilter('payment_id', $payment->getId())
                    ->addFieldToFilter('txn_type', Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                if ($collection->getSize() > 0) {
                    $collection = Mage::getModel('sales/order_payment_transaction')
                        ->getCollection()
                        ->addPaymentIdFilter($payment->getId())
                        ->addTxnTypeFilter(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH)
                        ->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC)
                        ->setOrder('transaction_id', Varien_Data_Collection::SORT_ORDER_DESC)
                        ->setPageSize(1)
                        ->setCurPage(1);
                    $authTransaction = $collection->getFirstItem();
                    if (!$authTransaction->getId()) {
                        Mage::throwException(Mage::helper('braintree_payments')->__('Please try again later'));
                    }
                    if (($token = $authTransaction->getAdditionalInformation('token'))) {
                        //order was placed using saved card or card was saved during checkout token
                        $found = true;
                        try {
                            Braintree_CreditCard::find($token);
                        } catch (Exception $e) {
                            $found = false;
                        }
                        if ($found) {
                            $this->_initEnvironment($payment->getOrder()->getStoreId());
                            $this->_authorize($payment, $amount, true, $token);
                        } else {
                            // case if payment token is no more applicable. attempt to clone transaction
                            $result = $this->_cloneTransaction($amount, $authTransaction->getTxnId());
                            if ($result && $result->success) {
                                $payment = $this->_processSuccessResult($payment, $result, $amount);
                            } else if ($result === false) {
                                Mage::throwException(Mage::helper('braintree_payments')->__('Please try again later'));
                            } else {
                                Mage::throwException(
                                    Mage::helper('braintree_payments/error')->parseBraintreeError($result));
                            }
                        }
                    } else {
                        // order was placed without saved card and card wasn't saved during checkout
                        $result = $this->_cloneTransaction($amount, $authTransaction->getTxnId());
                        if ($result && $result->success) {
                            $payment = $this->_processSuccessResult($payment, $result, $amount);
                        } else if ($result === false) {
                            Mage::throwException(Mage::helper('braintree_payments')->__('Please try again later'));
                        } else {
                            Mage::throwException(
                                Mage::helper('braintree_payments/error')->parseBraintreeError($result));
                        }
                    }
                } else {
                    $result = Braintree_Transaction::submitForSettlement($payment->getCcTransId(), $amount);
                    $this->_debug($payment->getCcTransId().' - '.$amount);
                    $this->_debug($result);
                    if ($result->success) {
                        $payment->setIsTransactionClosed(0)
                            ->setAmountPaid($result->transaction->amount)
                            ->setShouldCloseParentTransaction(false);
                    } else {
                        Mage::throwException(Mage::helper('braintree_payments/error')->parseBraintreeError($result));
                    }
                }
            } else {
                $this->_authorize($payment, $amount, true);
            }
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('braintree_payments')
                ->__('There was an error capturing the transaction.') . ' ' . $e->getMessage());
        }
        return $this;
    }

    /**
     * Refunds specified amount
     * 
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $transactionId = Mage::helper('braintree_payments')->clearTransactionId($payment->getRefundTransactionId());
        try {
            $transaction = Braintree_Transaction::find($transactionId);
            $this->_debug($payment->getCcTransId());
            $this->_debug($transaction);
            if ($transaction->status === Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT) {
                if ($transaction->amount != $amount ) {
                    Mage::throwException(
                        Mage::helper('braintree_payments')->__(
                            'This refund is for a partial amount but the Transaction has not settled. ' .
                            'Please wait 24 hours before trying to issue a partial refund.'
                        ));
                } else {
                    Mage::throwException(
                        Mage::helper('braintree_payments')->__(
                            'The Transaction has not settled. ' .
                            'Please wait 24 hours before trying to issue a refund or use Void option.'
                        ));
                }
            }

            $result = $transaction->status === Braintree_Transaction::SETTLED
                ? Braintree_Transaction::refund($transactionId, $amount)
                : Braintree_Transaction::void($transactionId);
            $this->_debug($result);
            if ($result->success) {
                $payment->setIsTransactionClosed(1);
            } else {
                Mage::throwException(Mage::helper('braintree_payments/error')->parseBraintreeError($result));
            }
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('braintree_payments')
                ->__('There was an error refunding the transaction.') . ' ' . $e->getMessage());
        }
        return $this;
    }

    /**
     * Voids transaction
     * 
     * @param Varien_Object $payment
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function void(Varien_Object $payment)
    {
        $transactionIds = array();
        $invoice = Mage::registry('current_invoice');
        $message = false;
        if ($invoice && $invoice->getId() && $invoice->getTransactionId()) {
            $transactionIds[] = Mage::helper('braintree_payments')->clearTransactionId($invoice->getTransactionId());
            
        } else {
            $collection = Mage::getModel('sales/order_payment_transaction')
                ->getCollection()
                ->addFieldToSelect('txn_id')
                ->addOrderIdFilter($payment->getOrder()->getId())
                ->addTxnTypeFilter(array(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, 
                    Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE));
            $fetchedIds = $collection->getColumnValues('txn_id');
            foreach ($fetchedIds as $transactionId) {
                $txnId = Mage::helper('braintree_payments')->clearTransactionId($transactionId);
                if (!in_array($txnId, $transactionIds)) {
                    $transactionIds[] = $txnId;
                }
            }
        }
        foreach ($transactionIds as $transactionId) {
            $transaction = Braintree_Transaction::find($transactionId);
            if ($transaction->status !== Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT && 
                $transaction->status !== Braintree_Transaction::AUTHORIZED) {
                
                Mage::throwException(Mage::helper('braintree_payments')
                    ->__('Some transactions are already settled or voided and cannot be voided.'));
            }
            if ($transaction->status === Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT) {
                $message = Mage::helper('braintree_payments')->__('Voided capture.') ;
            }
        }
        $errors = '';
        foreach ($transactionIds as $transactionId) {

            $this->_debug('void-' . $transactionId);
            $result = Braintree_Transaction::void($transactionId);
            $this->_debug($result);
            if (!$result->success) {
                $errors .= ' ' . Mage::helper('braintree_payments/error')->parseBraintreeError($result);
            } else if ($message) {
                $payment->setMessage($message);
            }
        }
        if ($errors) {
            Mage::throwException(Mage::helper('braintree_payments')->__('There was an error voiding the transaction.')
                . $errors);
            
        } else {
            $match = true;
            foreach ($transactionIds as $transactionId) {
                $collection = Mage::getModel('sales/order_payment_transaction')
                    ->getCollection()
                    ->addFieldToFilter('parent_txn_id', array('eq' => $transactionId))
                    ->addFieldToFilter('txn_type', Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID);
                if ($collection->getSize() < 1) {
                    $match = false;
                }
            }
            if ($match) {
                $payment->setIsTransactionClosed(1);
            }
        }
        return $this;
    }

    /**
     * Deletes customer
     * 
     * @param int $customerID
     */
    public function deleteCustomer($customerID)
    {
        try {
            Braintree_Customer::delete($customerID);
        } catch (Braintree_Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Delete card by token
     * 
     * @param string $token
     * @return Braintree_CreditCard | boolean
     */
    public function deleteCard($token)
    {
        try {
            $ret = Braintree_CreditCard::delete($token);
            $this->_debug($token);
            $this->_debug($ret);
            return $ret;
        } catch (Braintree_Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * If customer exists in Braintree
     * 
     * @param int $customerId
     * @return boolean
     */
    public function exists($customerId)
    {
        try {
            Braintree_Customer::find($customerId);
        } catch (Braintree_Exception $e) {
            return false;
        }
        return true;        
    }

    /**
     * Convert magento address to array for braintree
     * 
     * @param Mage_Customer_Model_Address $address
     * @return array
     */
    public function toBraintreeAddress($address)
    {
        if ($address) {
            return array(
                'firstName'         => $address->getFirstname(),
                'lastName'          => $address->getLastname(),
                'company'           => $address->getCompany(),
                'streetAddress'     => $address->getStreet(1),
                'extendedAddress'   => $address->getStreet(2),
                'locality'          => $address->getCity(),
                'region'            => $address->getRegion(),
                'postalCode'        => $address->getPostcode(),
                'countryCodeAlpha2' => $address->getCountry(), // alpha2 is the default in magento
            );
        } else {
            return array();
        }
    }

    /**
     * If vault can be used
     * 
     * @return boolean
     */
    public function useVault()
    {
        return $this->_useVault;
    }

    /**
     * Voids transaction on cancel action
     * 
     * @param Varien_Object $payment
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function cancel(Varien_Object $payment)
    {
        try{
            $this->void($payment);
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * 
     * @param string $country
     * @param string $ccType
     */
    protected function _canUseCcTypeForCountry($country, $ccType)
    {
        try {
            $countriesCardTypes = unserialize($this->getConfigData('countrycreditcard'));
        } catch (Exception $e) {
            $countriesCardTypes = false;
        }
        $countryFound = false;
        if ($countriesCardTypes) {
            if (array_key_exists($country, $countriesCardTypes)) {
                if (!in_array($ccType, $countriesCardTypes[$country])) {
                    return Mage::helper('braintree_payments')
                        ->__('Credit card type is not allowed for your country.');
                }
                $countryFound = true;
            }
        }
        if (!$countryFound) {
            $availableTypes = explode(',',$this->getConfigData('cctypes'));
            if (!in_array($ccType, $availableTypes)){
                return Mage::helper('braintree_payments')
                    ->__('Credit card type is not allowed for this payment method.');
            }
        }
        return false;
    }

    /**
     * Check whether payment method is applicable to quote
     * Purposed to allow use in controllers some logic that was implemented in blocks only before
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param int|null $checksBitMask
     * @return bool
     */
    public function isApplicableToQuote($quote, $checksBitMask)
    {
        if (parent::isApplicableToQuote($quote, $checksBitMask)) {
            $availableCcTypes = $this->getApplicableCardTypes($quote->getBillingAddress()->getCountryId());
            if (!$availableCcTypes) {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }

    /**
     * If there are any card types for country
     * 
     * @param string $country
     * @return array
     */
    public function getApplicableCardTypes($country)
    {
        try {
            $countriesCardTypes = unserialize($this->getConfigData('countrycreditcard'));
        } catch (Exception $e) {
            $countriesCardTypes = false;
        }
        if ($countriesCardTypes && array_key_exists($country, $countriesCardTypes)) {
            $allowedTypes = $countriesCardTypes[$country];
        } else {
            $allowedTypes = explode(',', $this->getConfigData('cctypes'));
        }
        return $allowedTypes;
    }

    /**
     * Format param "channel" for transaction
     * 
     * @return string
     */
    protected function _getChannel()
    {
        return self::CHANNEL_NAME . ' ' . Mage::getEdition() . ' ' . Mage::getVersion();
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if (null === $storeId) {
            if (Mage::app()->getStore()->getCode() == Mage_Core_Model_Store::ADMIN_CODE) {
                $storeId = Mage::getSingleton('adminhtml/session_quote')->getStoreId();
            } else {
                $storeId = $this->getStore();
            }
        }
        $path = 'payment/'.$this->getCode().'/'.$field;
        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * If duplicate credit cards are allowed
     * 
     * @return boolean
     */
    protected function _allowDuplicateCards()
    {
        return $this->_allowDuplicates;
    }

    /**
     * Saves Credit Card and customer (if new) in vault
     * 
     * @throws Mage_Core_Exception
     * @return string
     */
    public function saveInVault($token = false, $post = false)
    {
        if (!$post) {
            $post = Mage::app()->getRequest()->getPost();
        }
        $post = $this->_protectArray($post);
        $requestType = 'card';
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        if (!$customerId) {
            Mage::throwException(Mage::helper('braintree_payments')->__('Invalid Customer ID provided'));
        }
        $customerId = $this->generateCustomerId($customerId,
            Mage::getSingleton('customer/session')->getCustomer()->getEmail());
        if (!$this->_validateCustomerCcData($post)) {
            Mage::throwException(Mage::helper('braintree_payments')->__('Invalid Credit Card Data provided'));
        }
        if (!$this->_validateCustomerAddressData($post)) {
            Mage::throwException(Mage::helper('braintree_payments')->__('Invalid Address Data provided'));
        }
        $request = array(
            'number'            => $post['credit_card']['cc_number'],
            'expirationDate'    => $post['credit_card']['cc_exp_month'] . '/' . $post['credit_card']['cc_exp_year'],
            'cardholderName'    => $post['credit_card']['cardholder_name'],
            'cvv'               => $post['credit_card']['cc_cid'],
            'billingAddress'    => array(
                'firstName'         => $post['credit_card']['billing_address']['first_name'],
                'lastName'          => $post['credit_card']['billing_address']['last_name'],
                'streetAddress'     => $post['credit_card']['billing_address']['street_address'],
                'locality'          => $post['credit_card']['billing_address']['locality'],
                'postalCode'        => $post['credit_card']['billing_address']['postal_code'],
                'countryCodeAlpha2' => $post['credit_card']['billing_address']['country_code_alpha2'],
            ),
        );
        if (isset($post['credit_card']['billing_address']['extended_address']) 
            && $post['credit_card']['billing_address']['extended_address']) {

            $request['billingAddress']['extendedAddress'] = $post['credit_card']['billing_address']['extended_address'];
        }
        if (isset($post['credit_card']['billing_address']['region']) 
            && $post['credit_card']['billing_address']['region']) {

            $request['billingAddress']['region'] = $post['credit_card']['billing_address']['region'];
        }
        if ($token) {
            // update card
            if (isset($post['credit_card']['options']['make_default']) 
                && $post['credit_card']['options']['make_default']) {
                
                if (!isset($request['options']) || (isset($request['options']) && !is_array($request['options']))) {
                    $request['options'] = array();
                }
                $request['options']['makeDefault'] = true;
            }
            $request['billingAddress']['options'] = array('updateExisting' => true);
            $result = Braintree_CreditCard::update($token, $request);
            $this->_debug($token);
            $this->_debug($request);
            $this->_debug($result);
        } else {
            if (!$this->_allowDuplicateCards()) {
                $request['options'] = array('failOnDuplicatePaymentMethod' => true);
            }
            if ($this->exists($customerId)) {
                // add new card for existing customer
                $request['customerId'] = $customerId;
                $result = Braintree_CreditCard::create($request);
                $this->_debug($request);
                $this->_debug($result);
            } else {
                // add new card and new customer
                $extendedRequest = array(
                    'id'            => $customerId,
                    'firstName'     => $post['credit_card']['billing_address']['first_name'],
                    'lastName'      => $post['credit_card']['billing_address']['last_name'],
                    'email'         => Mage::getSingleton('customer/session')->getCustomer()->getEmail(),
                    'creditCard'    => $request,
                );
                if (isset($post['credit_card']['billing_address']['company']) 
                    && $post['credit_card']['billing_address']['company']) {

                    $extendedRequest['company'] = $post['credit_card']['billing_address']['company'];
                }
                $result = Braintree_Customer::create($extendedRequest);
                $this->_debug($extendedRequest);
                $this->_debug($result);
                $requestType = 'customer';
            }
        }
        if (!$result->success) {
            Mage::throwException(Mage::helper('braintree_payments/error')->parseBraintreeError($result));
        }
        if ($requestType == 'customer') {
            $token = $result->customer->creditCards[0]->token;
        } else {
            $token = $result->creditCard->token;
        }
        return $token;
    }

    /**
     * Validate if all required credit card data entered
     * 
     * @param array $customerData
     * @return boolean
     */
    protected function _validateCustomerCcData($customerData)
    {
        if (isset($customerData['credit_card']) && isset($customerData['credit_card']['cc_number']) && 
            $customerData['credit_card']['cc_number'] && isset($customerData['credit_card']['cc_cid']) &&
            $customerData['credit_card']['cc_cid'] && isset($customerData['credit_card']['cardholder_name']) &&
            $customerData['credit_card']['cardholder_name'] && isset($customerData['credit_card']['cc_exp_month']) &&
            $customerData['credit_card']['cc_exp_month'] && isset($customerData['credit_card']['cc_exp_year']) &&
            $customerData['credit_card']['cc_exp_year']) {
            
            return true;
        }
        return false;
    }

    /**
     * Validate if all required address data entered
     * 
     * @param array $customerData
     * @return boolean
     */
    protected function _validateCustomerAddressData($customerData)
    {
        if (isset($customerData['credit_card']) &&
            isset($customerData['credit_card']['billing_address']) &&
            isset($customerData['credit_card']['billing_address']['first_name']) &&
            $customerData['credit_card']['billing_address']['first_name'] &&
            isset($customerData['credit_card']['billing_address']['last_name']) &&
            $customerData['credit_card']['billing_address']['last_name'] &&
            isset($customerData['credit_card']['billing_address']['street_address'])
            && $customerData['credit_card']['billing_address']['street_address'] &&
            isset($customerData['credit_card']['billing_address']['locality'])
            && $customerData['credit_card']['billing_address']['locality'] &&
            isset($customerData['credit_card']['billing_address']['postal_code']) &&
            $customerData['credit_card']['billing_address']['postal_code'] &&
            isset($customerData['credit_card']['billing_address']['country_code_alpha2']) &&
            $customerData['credit_card']['billing_address']['country_code_alpha2']) {
            
            return true;
        }
        return false;
    }

    /**
     * Clones existing transaction
     * 
     * @param decimal $amount
     * @param string $transactionId
     */
    protected function _cloneTransaction($amount, $transactionId)
    {
        $this->_debug('clone-' . $transactionId . ' amount=' . $amount);
        try {
            $result = Braintree_Transaction::cloneTransaction($transactionId, array(
                'amount'    => $amount,
                'options'   => array(
                    'submitForSettlement' => true
                )
            ));
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
        $this->_debug($result);
        return $result;
    }

    /**
     * Initializes environment
     * 
     * @param int $storeId
     */
    protected function _initEnvironment($storeId)
    {
        // For compatibility with old extension versions where "development" was available
        if ($this->getConfigData('environment', $storeId) == 
            Braintree_Payments_Model_Source_Environment::ENVIRONMENT_PRODUCTION) {
            
            Braintree_Configuration::environment(Braintree_Payments_Model_Source_Environment::ENVIRONMENT_PRODUCTION);
        } else {
            Braintree_Configuration::environment(Braintree_Payments_Model_Source_Environment::ENVIRONMENT_SANDBOX);
        }
        Braintree_Configuration::merchantId($this->getConfigData('merchant_id', $storeId));
        Braintree_Configuration::publicKey($this->getConfigData('public_key', $storeId));
        Braintree_Configuration::privateKey($this->getConfigData('private_key', $storeId));
        $this->_merchantAccountId = $this->getConfigData('merchant_account_id', $storeId);
        $this->_useVault = $this->getConfigData('use_vault', $storeId);
        $this->_allowDuplicates = $this->getConfigData('duplicate_card', $storeId);        
    }

    /**
     * Processes successful authorize/clone result
     * 
     * @param Varien_Object $payment
     * @param Braintree_Result_Successful $result
     * @param decimal amount
     * @return Varien_Object
     */
    protected function _processSuccessResult($payment, $result, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)
            ->setCcTransId($result->transaction->id)
            ->setLastTransId($result->transaction->id)
            ->setTransactionId($result->transaction->id)
            ->setIsTransactionClosed(0)
            ->setCcLast4($result->transaction->creditCardDetails->last4)
            ->setAdditionalInformation($this->_getExtraTransactionInformation($result->transaction))
            ->setAmount($amount)
            ->setShouldCloseParentTransaction(false);
        if (isset($result->transaction->creditCard['token']) && $result->transaction->creditCard['token']) {
            $payment->setTransactionAdditionalInfo('token', $result->transaction->creditCard['token']);
        }
        return $payment;
    }

    /**
     * Stripes tags in array
     * 
     * @param array $data
     */
    protected function _protectArray($data)
    {
        $callableFunction = function (&$param) {
            $param = Mage::helper('core')->stripTags($param);
        }; 
        array_walk_recursive($data, $callableFunction);
        return $data;
    }

    /**
     * Log debug data to file
     *
     * @param mixed $debugData
     */
    protected function _debug($debugData)
    {
        if (is_array($debugData)) {
            if (!$this->_maskedFields) {
                $this->_maskedFields = explode(',', $this->getConfigData(self::CONFIG_MASKED_FIELDS));
            }

            $callableFunction = function (&$param, $key, $maskedFields) {
                if (in_array($key, $maskedFields)) {
                    $param = '****';
                }
            };
            array_walk_recursive($debugData, $callableFunction, $this->_maskedFields);            
        }
        parent::_debug($debugData);
    }

    /**
     * To check billing country is allowed for the payment method
     *
     * @return bool
     */
    public function canUseForCountry($country)
    {
        /*
        for specific country, the flag will set up as 1
        */
        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = explode(',', $this->getConfigData('specificcountry'));
            if (!in_array($country, $availableCountries)) {
                return false;
            }
        } else if (Mage::getModel('braintree_payments/system_config_source_country')->isCountryRestricted($country)) {
            return false;
        }
        return true;
    }

    /**
     * Generates md5 hash to be used as customer id
     * 
     * @param string $customerId
     * @param string $email
     * @return string
     */
    public function generateCustomerId($customerId, $email)
    {
        return md5($customerId . '-' . $email);
    }
}
