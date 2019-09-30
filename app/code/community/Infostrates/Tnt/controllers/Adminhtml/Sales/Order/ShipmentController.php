<?php

if (class_exists('LaPoste_Label_Adminhtml_Sales_Order_ShipmentController', false)) {

    require_once Mage::getModuleDir('controllers', 'LaPoste_Label') . DS . 'Adminhtml' . DS . 'Sales' . DS . 'Order' . DS . 'ShipmentController.php';
    class MiddleManClass extends LaPoste_Label_Adminhtml_Sales_Order_ShipmentController { }
} else {
    require_once Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'Sales' . DS . 'Order' . DS . 'ShipmentController.php';
    class MiddleManClass extends Mage_Adminhtml_Sales_Order_ShipmentController { }
}

class Infostrates_Tnt_Adminhtml_Sales_Order_ShipmentController extends MiddleManClass
{
    /**
     * Create shipping label for specific shipment with validation.
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @return bool
     */
    protected function _createShippingLabel(Mage_Sales_Model_Order_Shipment $shipment)
    {
        if (!$shipment) {
            return false;
        }
        $carrier = $shipment->getOrder()->getShippingCarrier();
        if (!$carrier->isShippingLabelsAvailable()) {
            return false;
        }
        $shipment->setPackages($this->getRequest()->getParam('packages'));
        $response = Mage::getModel('shipping/shipping')->requestToShipment($shipment);
        if ($response->hasErrors()) {
            Mage::throwException($response->getErrors());
        }
        if (!$response->hasInfo()) {
            return false;
        }
        $labelsContent = array();
        $trackingNumbers = array();
        $info = $response->getInfo();

        $_order = $shipment->getOrder();
        $_shippingMethod = explode('_', $_order->getShippingMethod());

        foreach ($info as $inf) {
            if (!empty($inf['tracking_number']) && !empty($inf['label_content'])) {

                if($_shippingMethod[0] != 'tnt' && $_shippingMethod[0] != 'lengow')
                {
                    if (stripos($inf['label_content'], '%PDF-') === false) {
                        $fileName = Mage::getBaseDir('base') . DS . 'var' . DS . 'export' . DS . 'labels' . DS . $inf['label_content'];
                        $labelsContent[] = ($content = @file_get_contents($inf['label_content'])) ? $content : $inf['label_content'];
                    } else {
                        $fileName = $inf['label_content'];
                        $labelsContent[] = $fileName;
                    }
                } else {
                    $labelsContent[] = $inf['label_content'];
                }

                $trackingNumbers[] = $inf['tracking_number'];
            }
        }

        $outputPdf = $this->_combineLabelsPdf($labelsContent);
        $shipment->setShippingLabel($outputPdf->render());
        $carrierCode = $carrier->getCarrierCode();
        $carrierTitle = Mage::getStoreConfig('carriers/'.$carrierCode.'/title', $shipment->getStoreId());
        if ($trackingNumbers) {
            foreach ($trackingNumbers as $trackingNumber) {
                $track = Mage::getModel('sales/order_shipment_track')
                        ->setNumber($trackingNumber)
                        ->setCarrierCode($carrierCode)
                        ->setTitle($carrierTitle);
                $shipment->addTrack($track);
            }
        }
        return true;
    }

    /**
     * Save shipment
     * We can save only new shipment. Existing shipments are not editable.
     */
    public function getConfigData($field)
    {
        $path = 'carriers/tnt/'.$field;

        return Mage::getStoreConfig($path, Mage::app()->getStore());
    }

    public function dateFR($dateUS)
    {
        $tmp = explode('-', $dateUS);
        $dateFR = $tmp[2].'-'.$tmp[1].'-'.$tmp[0];

        return $dateFR;
    }

    public function saveAction()
    {
        Mage::log("parent class :", null, "testrewrite.log");
        Mage::log(get_parent_class($this), null, "testrewrite.log");
        Mage::log("current class :", null, "testrewrite.log");
        Mage::log(get_class($this), null, "testrewrite.log");

        Mage::log('save Shipment', null, 'colissimo.log');
        $data = $this->getRequest()->getPost('shipment');
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
            $shipment = $this->_initShipment();
            if (!$shipment) {
                $this->_forward('noRoute');
                return;
            }

            $_order = $shipment->getOrder();
            $_shippingMethod = explode('_', $_order->getShippingMethod());
            //Expédition via TNT on créé une expé. et on récupère le tracking num via le WS.
            if ($_shippingMethod[0] == 'tnt') {
                // On met en place les paramètres de la requète pour l'expédition
                $send_city = $this->getConfigData('ville');
                $rec_typeid = '';
                $rec_name = '';
                $poids_colis_max = $this->getConfigData('max_package_weight');

                if ($_shippingMethod['1'] == 'A' || $_shippingMethod['1'] == 'T' || $_shippingMethod['1'] == 'M' || $_shippingMethod['1'] == 'J') {
                    $rec_type = 'ENTERPRISE';
                    $rec_name = trim($_order->getShippingAddress()->getCompany());
                } elseif ($_shippingMethod['1'] == 'AZ' || $_shippingMethod['1'] == 'TZ' || $_shippingMethod['1'] == 'MZ' || $_shippingMethod['1'] == 'JZ') {
                    $rec_type = 'INDIVIDUAL';
                    $poids_colis_max = ($this->getConfigData('max_package_weight') - 10);
                } else {
                    $rec_type = 'DROPOFFPOINT';
                    $extt = explode(' ', trim($_order->getShippingAddress()->getCompany()));
                    $rec_typeid = end($extt);
                    $rec_name = str_replace($rec_typeid, '', $_order->getShippingAddress()->getCompany());
                    $poids_colis_max = ($this->getConfigData('max_package_weight') - 10);
                }

                $rec_address1 = $_order->getShippingAddress()->getStreet(1);
                $rec_address2 = $_order->getShippingAddress()->getStreet(2);

                if ($rec_address2 == '') {
                    if (strlen($rec_address1) > 32) {
                        $rec_address2 = substr($rec_address1, 32, 64);
                    }
                }

                $nb_colis = $this->getRequest()->getPost('nb_colis');
                $date_expe = $this->dateFR($this->getRequest()->getPost('shippingDate'));

                $parcelsRequest = array();

                $poids_restant = $_order->getWeight();
                for ($i = 1;$i <= $nb_colis;++$i) {
                    $colis = 'colis'.$i;
                    $parcelWeight = $this->getRequest()->getPost($colis);

                    $parcelsRequest[] = array('sequenceNumber' => $i,'customerReference' => $_order->getRealOrderId(), 'weight' => $parcelWeight);
                }

                $rec_city = $_order->getShippingAddress()->getCity();

                $instructions = '';
                $phoneNumber = '';
                $accessCode = '';
                $floorNumber = '';
                $buildingId = '';

                $info_comp = explode('&&&', $_order->getShippingAddress()->getTntInfosComp());

                if (count($info_comp) > 0) {
                    if (count($info_comp) == 1) {
                        $instructions = substr($info_comp[0], 0, 60);
                    } else {
                        $phoneNumber = $info_comp[0];
                        $accessCode = $info_comp[1];
                        $floorNumber = $info_comp[2];
                        $buildingId = $info_comp[3];
                    }
                }
                if ($phoneNumber == '') {
                    $phoneNumber = $_order->getShippingAddress()->getTelephone();
                }

                $phoneNumber = str_replace(' ', '', $phoneNumber);
                if (preg_match('/^0033/', $phoneNumber)) {
                    $phoneNumber = substr_replace($phoneNumber, '0', 0, 4);
                }
                $phoneNumber = str_replace('+33', '0', $phoneNumber);
                $phoneNumber = str_replace('(+33)', '0', $phoneNumber);
                $phoneNumber = str_replace('-', '', $phoneNumber);
                $phoneNumber = str_replace('.', '', $phoneNumber);
                $phoneNumber = str_replace(',', '', $phoneNumber);
                $phoneNumber = str_replace('/', '', $phoneNumber);

                $sender = array('zipCode' => $this->getConfigData('code_postal'), 'city' => $send_city);
                $receiver = array('zipCode' => $_order->getShippingAddress()->getPostcode(), 'city' => $rec_city, 'type' => $rec_type);
                $feasi_params = array('shippingDate' => $date_expe, 'accountNumber' => $this->getConfigData('account'), 'sender' => $sender, 'receiver' => $receiver);
                $feasi_result = Mage::getModel('tnt/shipping_carrier_tnt')->_tnt_feasibility($feasi_params);

                if (is_string($feasi_result)) {
                    throw new Exception($feasi_result);
                }

                //correction du bug Paypal qui concatene nom/prenom et vide le nom de l'adresse de facturation !!
                if (trim($_order->getShippingAddress()->getLastname()) == '' && trim($_order->getShippingAddress()->getFirstname()) != '') {
                    $nom = '';
                    $prenom = $_order->getShippingAddress()->getFirstname();
                    $tab_nom = explode(' ', $prenom);

                    for ($i = 0;$i < count($tab_nom);++$i) {
                        if ($i == 0) {
                            $prenom = substr($tab_nom[$i], 0, 12);
                        } else {
                            $nom .= $tab_nom[$i].' ';
                        }
                    }

                    $nom = trim($nom);
                    $nom = substr($nom, 0, 19);
                } else {
                    $nom = substr($_order->getShippingAddress()->getLastname(), 0, 19);
                    $prenom = substr($_order->getShippingAddress()->getFirstname(), 0, 12);
                }

                $params = array('parameters' => array('shippingDate' => $date_expe,
                                                        'accountNumber' => $this->getConfigData('account'),
                                                        'sender' => array('name' => substr($this->getConfigData('raison_sociale'), 0, 32),
                                                                                    'address1' => substr($this->getConfigData('adresse'), 0, 32),
                                                                                    'address2' => substr($this->getConfigData('adresse2'), 0, 32),
                                                                                    'zipCode' => substr($this->getConfigData('code_postal'), 0, 5),
                                                                                    'city' => substr($send_city, 0, 27),
                                                                                    ),
                                                        'receiver' => array('type' => $rec_type,
                                                                                    'typeId' => $rec_typeid,
                                                                                    'name' => substr($rec_name, 0, 32),
                                                                                    'address1' => substr($rec_address1, 0, 32),
                                                                                    'address2' => substr($rec_address2, 0, 32),
                                                                                    'zipCode' => substr($_order->getShippingAddress()->getPostcode(), 0, 5),
                                                                                    'city' => substr($rec_city, 0, 27),
                                                                                    'instructions' => $instructions,
                                                                                    'contactLastName' => $nom,
                                                                                    'contactFirstName' => $prenom,
                                                                                    'emailAddress' => substr($_order->getCustomerEmail(), 0, 80),
                                                                                    'phoneNumber' => substr($phoneNumber, 0, 10),
                                                                                    'accessCode' => substr($accessCode, 0, 7),
                                                                                    'floorNumber' => substr($floorNumber, 0, 2),
                                                                                    'buldingId' => substr($buildingId, 0, 3),
                                                                                    'sendNotification' => '1',
                                                                                    ),
                                                       'serviceCode' => $_shippingMethod[1],
                                                       'quantity' => $nb_colis,
                                                       'parcelsRequest' => $parcelsRequest,
                                                       'labelFormat' => $this->getConfigData('label_format'),
                                                        ),
                            );

                $parcels = Mage::getModel('tnt/shipping_carrier_tnt')->_tnt_exp_crea($params);

                if (is_string($parcels)) {
                    throw new Exception($this->__($parcels));
                }

                //on créé le fichier PDF
                $path = Mage::getBaseDir('media').'/pdf_bt/';
                $filename = $_order->getRealOrderId().'.pdf';

                if ($parcels['pdfLabels'] && !file_exists($path.$filename)) {
                    if ($handle = fopen($path.$filename, 'x+')) {
                        fwrite($handle, $parcels['pdfLabels']);
                        fclose($handle);
                    } else {
                        throw new Exception($this->__('Impossible de créer le BT. Vérifiez que le repertoire /media/pdf_bt/ à les droits en écriture.'));
                    }
                }

                foreach ($parcels as $parcel) {
                    if (is_array($parcel)) {
                        $track = Mage::getModel('sales/order_shipment_track')
                            ->setNumber($parcel['parcelNumber'])
                            ->setCarrier('TNT')
                            ->setCarrierCode($_shippingMethod[0])
                            ->setTitle('TNT')
                            ->setPopup(1);
                        $shipment->addTrack($track);
                    }
                }
            }

            $shipment->register();
            $comment = '';
            if (!empty($data['comment_text'])) {
                $shipment->addComment(
                    $data['comment_text'],
                    isset($data['comment_customer_notify']),
                    isset($data['is_visible_on_front'])
                );
                if (isset($data['comment_customer_notify'])) {
                    $comment = $data['comment_text'];
                }
            }

            if (!empty($data['send_email'])) {
                $shipment->setEmailSent(true);
            }

            $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
            $responseAjax = new Varien_Object();
            $isNeedCreateLabel = isset($data['create_shipping_label']) && $data['create_shipping_label'];

            if ($isNeedCreateLabel && $this->_createShippingLabel($shipment)) {
                $responseAjax->setOk(true);
            }

            $this->_saveShipment($shipment);

            $shipment->sendEmail(!empty($data['send_email']), $comment);

            $shipmentCreatedMessage = $this->__('The shipment has been created.');
            $labelCreatedMessage    = $this->__('The shipping label has been created.');

            $this->_getSession()->addSuccess($isNeedCreateLabel ? $shipmentCreatedMessage . ' ' . $labelCreatedMessage
                : $shipmentCreatedMessage);
            Mage::getSingleton('adminhtml/session')->getCommentText(true);
        } catch (Mage_Core_Exception $e) {
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage($e->getMessage());
            } else {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage(
                    Mage::helper('sales')->__('An error occurred while creating shipping label.'));
            } else {
                $this->_getSession()->addError($this->__('Cannot save shipment.'));
                $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
            }

        }
        if ($isNeedCreateLabel) {
            $this->getResponse()->setBody($responseAjax->toJson());
        } else {
            $this->_redirect('*/sales_order/view', array('order_id' => $shipment->getOrderId()));
        }
    }
}
