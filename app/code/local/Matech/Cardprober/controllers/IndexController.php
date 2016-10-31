<?php

class Matech_Cardprober_IndexController extends Mage_Core_Controller_Front_Action {

    public function cardreponseAction() {
        $log = Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/logging');
        $requestParam = $this->getRequest()->getParams();

        if ($this->getRequest()->getParam('token') == Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/token')) {
            if ($log) {

                Mage::log(print_r($this->getRequest()->getRawBody(), true), null, 'efraudprint.log');
            }
            // start processing

            $request_xml = $this->getRequest()->getRawBody();
            $sxe = simplexml_load_string($request_xml);
            foreach ($sxe->order as $order) {
                $orderNo = $order->OrderNumber;
                $orderStatus = $order->Status;

                if ($orderStatus === "Pending") {
                    continue;
                }
                $MageOrder = Mage::getModel('sales/order')->loadByIncrementId($orderNo);
                if ($MageOrder->getId()) {
                    switch ($orderStatus) {
                        case "Awaiting Response":
                            $MageOrder->setState(Matech_Cardprober_Model_Sales_Order::EFRAUD_AWAITING_RESPONSE, true);
                            if ($MageOrder->save()) {
                                if ($log) {
                                    Mage::log("Order saved " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            } else {
                                if ($log) {
                                    Mage::log("Problem in saving Order " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            }
                            //$MageStatus = EFRAUD_AWAITING_RESPONSE;				
                            break;
                        case "Removed from Queue":
                            $MageOrder->setState(Matech_Cardprober_Model_Sales_Order::EFRAUD_REMOVED_FRON_QUEUE, true);
                            if ($MageOrder->save()) {
                                if ($log) {
                                    Mage::log("Order saved " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            } else {
                                if ($log) {
                                    Mage::log("Problem in saving Order " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            }
                            //$MageStatus = EFRAUD_REMOVED_FRON_QUEUE;
                            break;
                        case "ScoreOnly":
                            $MageOrder->setState(Matech_Cardprober_Model_Sales_Order::EFRAUD_SCOREONLY, true);
                            if ($MageOrder->save()) {
                                if ($log) {
                                    Mage::log("Order saved " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            } else {
                                if ($log) {
                                    Mage::log("Problem in saving Order " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            }
                            //$MageStatus = EFRAUD_SCOREONLY;
                            break;
                        case "NotInsured":
                            $MageOrder->setState(Matech_Cardprober_Model_Sales_Order::EFRAUD_NOINSURED, true);
                            if ($MageOrder->save()) {
                                if ($log) {
                                    Mage::log("Order saved " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            } else {
                                if ($log) {
                                    Mage::log("Problem in saving Order " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            }
                            //$MageStatus = EFRAUD_NOINSURED;
                            break;
                        case "ALLOWED":
                            $MageOrder->setState(Matech_Cardprober_Model_Sales_Order::EFRAUD_ALLOWED, true);
                            if ($MageOrder->save()) {
                                if ($log) {
                                    Mage::log("Order saved " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            } else {
                                if ($log) {
                                    Mage::log("Problem in saving Order " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            }
                            //$MageStatus = EFRAUD_ALLOWED;
                            break;
                        case "Rejected":
                            $MageOrder->setState(Matech_Cardprober_Model_Sales_Order::EFRAUD_REJECTED, true);
                            if ($MageOrder->save()) {
                                if ($log) {
                                    Mage::log("Order saved " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            } else {
                                if ($log) {
                                    Mage::log("Problem in saving Order " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            }
                            //$MageStatus = EFRAUD_REJECTED;
                            break;
                        case "FRAUD":
                            $MageOrder->setState(Matech_Cardprober_Model_Sales_Order::EFRAUD_FRAUD, true);
                            if ($MageOrder->save()) {
                                if ($log) {
                                    Mage::log("Order saved " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            } else {
                                if ($log) {
                                    Mage::log("Problem in saving Order " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            }
                            //$MageStatus = EFRAUD_FRAUD;
                            break;
                        case "FRAUD-Missed":
                            $MageOrder->setState(Matech_Cardprober_Model_Sales_Order::EFRAUD_FRAUD_MISSED, true);
                            if ($MageOrder->save()) {
                                if ($log) {
                                    Mage::log("Order saved " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            } else {
                                if ($log) {
                                    Mage::log("Problem in saving Order " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            }
                            //$MageStatus = EFRAUD_FRAUD_MISSED;
                            break;
                        case "Cancelled":
                            $MageOrder->setState(Matech_Cardprober_Model_Sales_Order::EFRAUD_CANCELLED, true);
                            if ($MageOrder->save()) {
                                if ($log) {
                                    Mage::log("Order saved " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            } else {
                                if ($log) {
                                    Mage::log("Problem in saving Order " . $order->OrderNumber, null, 'efraudprint.log');
                                }
                            }
                            //$MageStatus = EFRAUD_CANCELLED;
                            break;
                    }
                } else {
                    echo "Order not found <br/>";
                    if ($log) {
                        Mage::log("Order not found " . $order->OrderNumber, null, 'efraudprint.log');
                    }
                }
            }
        } else {
            if ($log) {
                Mage::log("Access denied " . $_SERVER['REMOTE_ADDR'], null, 'efraudprint.log');
            }
            echo "Access denied";
        }



        //echo 'Setup!';
    }

    public function saveFraudStatusAction() {
        $statuslog = Mage::getModel('cardprober/cardprober');
        $statuslog->setData('order_id', 'asdasd');
        if ($response->code == 'Success') {
            $statuslog->setData('status', 'Submitted');
        }

        $statuslog->setData('message', 'asdasd');
        $statuslog->setData('status_flag', '0');
        $statuslog->save();
    }

}
