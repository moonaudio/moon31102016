<?php

echo "<pre>";
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('memory_limit', -1);
require_once 'app/Mage.php';
umask(0);
Mage::app('admin');

$customerCollection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');
foreach($customerCollection as $customer){
	$order = Mage::getResourceModel('sales/order_collection')
    ->addFieldToSelect('entity_id')
    ->addFieldToFilter('customer_id', $customer->getId())
    ->setCurPage(1)
    ->setPageSize(1)
    ->getFirstItem();

if ($order->getId()) {
    echo $customer->getName().",".$customer->getEmail()."<br>";
}
}
die('siri');
opcache_reset();
$new = Mage::helper('core')->escapeHtml('Astell & Kern AK380 Black Digital Music & Media Player');
print_r($new);
//$collection = Mage::getResourceModel('reports/quote_collection');
//foreach($collection as $data){
//    if($data->getCustomerEmail() != ''){
//        print_r($data->getData());
//    }
//}

//$var = new stdClass();
//$var->availability = "in stock";
//    $var->brand = "Chord Electronics";
//    $var->channel = "online";
//    $var->condition = "new";
//    $var->contentLanguage = "en";
//    $var->description = "The 2Qute offers you the ability to listen to music from your computer, coaxial or optical source and deliver it with better quality to your hifi system. The 2Qute is an amalgamation of 2 outstanding products and offers superb value for money for those wanting to enhance their music listening experience.2Qute takes the technology from the Chord Hugo and adds it to the previous model, Chord Qute EX. The result is a new super-DAC for 2015 with class-leading specifications, outstanding technical measurements and proven sonic performance. The 2Qute advances the award-winning Qute EX DAC into 2015 with the latest Hugo specifications. Essentially a Hugo in a Chordette chassis, it brings the latest FPGA DAC technology into an affordable home-system-orientated unit. It contains the same high-performance Spartan 6 FPGA that has enabled Hugo to redefine the DAC genre in 2014. It also boasts astonishingly low distortion levels of 0.0003% and it offers support for up to 32-bit/384kHz audio via coax and USB, and 24-bit/192kHz over optical. DSD64 is supported on all inputs and DSD128 is supported via coax or USB (all via DoP). The new DAC also gains a handy switch to easily move between coax, optical and USB digital inputs.The device features a Class 2 USB input which, because of the 2Qutes home-system orientation compared to Hugos more mobile aspirations, has been galvanically isolated for greater sonic performance. This has been achieved using a novel technique which allows for very high data rates of up to 384kHz; the input is driverless on Apple and Android devices, with (ASIO included) drivers for Windows devices.A note on Upgrading the external Power Supply. It's really not necessary!  The internal configuration of the multiple highly complex dc to dc regulators within the unit were designed with this in mind and there can be no real discernible differences heard.  We know this as Rob at Chord did some extensive testing and listening to make sure there was no difference. We know there was a difference with the Qute EX but not with the 2qute!   Audio Cable recommendations for the 2Qute DAC:The Chord 2Qute has a Digital Coaxial Input, a Digital Optical Input and a Digital USB Input.  Therefore, you can connect a Computer and 2 additional digital sources at the same time. You will also need to add a set of analog cables from the Chord 2Qute to the headphone amp or integrated amplifier in your system.Optical cable for the 1st input:The Silver Dragon Toslink Digital Cable has the bandwidth to deliver up to 24bit 192k resolution as well as DSD signals.  It is made of the finest plastic fiber available and it utilizes a 1300 core optical fiber.Digital Coax Cable for 2nd input:  The Silver Dragon V1 Digital Cable is a pure silver 75ohm digital cable perfect for both audio and video applications.  It is an all silver designed 75ohm digital cable with a characteristic impedance of 75 ohms making this a perfect sonic match for digital audio and video.Analog cable options to connect Headphone Amp or Integrated Amp:The Silver Dragon Interconnect Cable V2 is our top of the line interconnect. It won 2014 Product of the Year by The Absolute Sound magazine & the braided geometry of the silver dragon acts as a noise rejection barrier. We also added an iron curtain with the external shield to truly allow your music to come shining through just a";
//    $var->expirationDate = "2016-04-22";
//    $var->googleProductCategory = "Electronics > Audio > Audio Components > Signal Processors";
//    $var->imageLink = "https://d2rxstqtpnd7ny.cloudfront.net/media/catalog/product/c/h/chord_2_qute_dac.jpg";
//    $var->link = "http://www.moon-audio.com/chord-2-qute-dac.html";
//    $var->mpn = "Chord_2_Qute_DAC";
//    $var->offerId = "1197_1";
//    $var->productType = "Featured > Best Sellers";
//    $var->targetCountry = "US";
//    $var->title = "Chord 2Qute DAC";
//    $var->taxes = Array
//        (
//            0 => Array
//                (
//                    'country' => 'US',
//                    'region' => 'NC',
//                    'rate' => 6.75,
//                    'taxShip' => 1
//                )
//
//        );
//
//    $var->price = new stdClass();
//    $var->price->currency = "USD";
//    $var->price->value = 1795.00;
//print_r($var);
//echo '---------';
//print_r(json_encode($var));
//print_r(json_last_error());
die('vishadfd');
            
        



$flag = Mage::getSingleton('googleshoppingapi/flag')->load(13);
$flag->setState('0')->save();
die('vishal-morenewnew');
$flag = Mage::getSingleton('googleshoppingapi/flag')->load(13);
$flag->setState('0')->save();
print_r($flag);
die('djfkdjfk');
$itemsCollection = Mage::getResourceModel('googleshoppingapi/item_collection');
        foreach ($itemsCollection as $items) {
            $date1 = date('Y-m-d');
            $date2 = $items->getExpires();
            $time = strtotime($date2);
            $newformat = date('Y-m-d', $time);
            if ($date1 > $newformat) {
                Mage::log($items->getName().' was deleted successfully',1,'exinentgoogleshopping.log');
                $items->delete();
            }
        }
die('vishal');
$folders = array('app/code/local/', 'app/code/community/');//folders to parse
$configFiles = array();
foreach ($folders as $folder){
    $files = glob($folder.'*/*/etc/config.xml');//get all config.xml files in the specified folder
    $configFiles = array_merge($configFiles, $files);//merge with the rest of the config files
}
$rewrites = array();//list of all rewrites

foreach ($configFiles as $file){
    $dom = new DOMDocument;
    $dom->loadXML(file_get_contents($file));
    $xpath = new DOMXPath($dom);
        $path = '//rewrite/*';//search for tags named 'rewrite'
        $text = $xpath->query($path);
        foreach ($text as $rewriteElement){
            $type = $rewriteElement->parentNode->parentNode->parentNode->tagName;//what is overwritten (model, block, helper)
            $parent = $rewriteElement->parentNode->parentNode->tagName;//module identifier that is being rewritten (core, catalog, sales, ...)
            $name = $rewriteElement->tagName;//element that is rewritten (layout, product, category, order)
            foreach ($rewriteElement->childNodes as $element){
                $rewrites[$type][$parent.'/'.$name][] = $element->textContent;//class that rewrites it
            }
        }
}
echo "<pre>";print_r($rewrites);
die();
$itemsCollection = Mage::getResourceModel('googleshoppingapi/item_collection');
foreach ($itemsCollection as $items) {
    $date1 = date('Y-m-d');
    $date2 = $items->getExpires();
    $time = strtotime($date2);
    $newformat = date('Y-m-d',$time);
    if ($date1 > $newformat) {
        print_r($items->getData());
    }
}
die('vishal');
$flag = Mage::getSingleton('googleshoppingapi/flag')->load(13);
$flag->setState('0')->save();
print_r($flag);
die();
//$productCollection = Mage::getModel('catalog/product')->getCollection();
//foreach($productCollection as $product){
//    $productObject = Mage::getModel('catalog/product')->load($product->getId());
//    if($productObject->getGoogleShoppingCategory() == 2){
//       $productObject->setGoogleShoppingCategory(1)->save();
//    }
//    print_r($productObject->getId().'------------------'.$productObject->getGoogleShoppingCategory());
//    echo '<br>';
//}
//10 ---- 1677
//
//$_category = Mage::getModel('catalog/category')->load(717);    
//$category = Mage::getModel('catalog/category')->getCollection();
//foreach($category as $cat){
//    if($cat->getPath() != '1'){
//        $catd = Mage::getModel('catalog/category')->load($cat->getId());
//        print_r($catd->getName().$catd->getCustomLayoutUpdate());
//    }
//}
//$category->setCustomLayoutUpdate($_category->getCustomLayoutUpdate())->save();
//print_r($category->getCustomLayoutUpdate());
die('here');
//$productCollection->addAttributeToSelect('inchoo_featured_product')
//        ->addAttributeToSelect('name');
//$attr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'product_rating');
//foreach($productCollection as $product) {
//    if($product['inchoo_featured_product'])
//    echo $product->getSku().','.$product->getName()."\n";
//    $_product = Mage::getModel('catalog/product')
//                ->setStoreId(1)
//                ->setProductRating($product->getProductRating());
//        $brandLabel = $_product->getAttributeText('product_rating');
//    echo $product->getId().' - '.$product->getProductRating().' - '.$brandLabel.'<br>';
//    $productObject = Mage::getModel('catalog/product')->load($product->getId());
//    if($productObject->getProductRating() == '1078' || $productObject->getProductRating() == '') {
//        $productObject->setProductRating(1152);
//        $productObject->setRatingCategory(1082);
//        $productObject->save();
//    }
//}
//$attr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'product_rating');
//        $color_label = $attr->getSource()->getOptionId('49999');
//        echo $color_label;
die('here');
//$count = 1;
//if (($handle = fopen("test.csv", "r")) !== FALSE) {
//    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
//        $url = $data[0];
//        //$handle = curl_init($url);
//        //curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
//        //$response = curl_exec($handle);
//      //  $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
//        print_r($url);
//    //    print_r($httpCode);
////        if ($httpCode == 404) {
////            echo 'Handle 404 here.';
////        } else {
////            echo 'works';
////        }
//        $count++;
//        if($count == 20){
//            break;
//        }
//    }
//}
//$productObject = Mage::getModel('catalog/product')->load(1221);
//print_r($productObject->getAttributeText('manufacturer'));
//
//die('final');
//$model = Mage::getSingleton('customer/session')->getCustomer();
//print_r($model);
//$model = Mage::getModel('customer/customer')->load(180);
//$baddress = Mage::getModel('sales/order_address')->load(81);
//$invoiceObject = Mage::getModel('sales/order_invoice')->load($id);
//$model = Mage::getModel('sales/order')->load(58);
//echo "<pre>";
//print_r($model->getInvoiceCollection()->getData());
//echo "<pre>";
//print_r($model->getShipmentsCollection()->getData());
//die('invoice collection');
//$payment=$model->getPayment()->getMethodInstance()->getCode();
//$model->setData('qb_status','New');
//$model->save();
//foreach($model->getShipmentsCollection() as $shipment)
//{
//print_r($shipment->getData());
//}
//$model->setStatus('shipped');
//$model->save();
//print_r($model->getShippingMethod())."<br />";
//echo $model->getShippingMethod() . "<br />";
//print_r($model->getData());
//die('here');
/* $order_items = $model->getItemsCollection();

  // Parepare Item Qtys For Shipment
  foreach ($order_items as $shipItems)
  {
  $totalQty += $shipItems->getQtyShipped();
  }
  echo $totalQty;
  echo $model->getTotalQtyOrdered();
  if($totalQty == $model->getTotalQtyOrdered())
  {
  echo 'run';
  }
  else
  {
  echo 'nooooooo';
  }
  //$model->getTransactionId();
  //$model->setStatus('processing');
  //$model->save();
  //$model = Mage::getModel('catalog/product')->load(492);
  //print_r($model->getPrice());
  //$model->setCustomerGroupId(7);
  Print_r($model->getData());

  //for($i=214;$i<=273;$i++)
  //{
  //    $model = Mage::getModel('sales/order')->load($i);
  //    $model->setQbStatus('Synced');
  //    $model->save();
  //}
 * */
// $payments = Mage::getSingleton('payment/config')->getActiveMethods();
//$payMethods = array();
//foreach ($payments as $paymentCode=>$paymentModel) 
//{
//  
//   
// 
//    $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
//    
//    $payMethods[$paymentCode] = $paymentTitle;
//     echo $paymentCode.'======================================================'. $payMethods[$paymentCode]."<br />";
//} 
//print_r($payMethods[$paymentCode]);
// $shipping = Mage::getSingleton('shipping/config')->getActiveCarriers();
// $shipMethods = array();
// foreach ($shipping as $shippingCode=>$shippingModel)
// {
//     $shippingTitle = Mage::getStoreConfig('carriers/'.$shippigCode.'/title');
//      $shipMethods[$shippigCode] = $shippingTitle;
//     
//     echo $shippingCode.'======================================================'. $shipMethods[$shippigCode]."<br />";
// }
//$methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
//$shipMethods = array();
//foreach ($methods as $shippigCode => $shippingModel) {
//    $shippingTitle = Mage::getStoreConfig('carriers/' . $shippigCode . '/title');
//    $shipMethods[$shippigCode] = $shippingTitle;
//    echo $shippigCode . '======================================================' . $shipMethods[$shippigCode] . "<br />";
//}
