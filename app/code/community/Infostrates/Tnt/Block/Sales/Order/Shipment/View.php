<?php
class Infostrates_Tnt_Block_Sales_Order_Shipment_View extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {    	
    	$this->_objectId    = 'shipment_id';
        $this->_controller  = 'sales_order_shipment';
        $this->_mode        = 'view';

        parent::__construct();

        $this->_removeButton('reset');
        $this->_removeButton('delete');
        $this->_updateButton('save', 'label', Mage::helper('sales')->__('Send Tracking Information'));
        $this->_updateButton('save', 'onclick', "setLocation('".$this->getEmailUrl()."')");
        
        //Ajout de l'impression de l'étiquette
        $_order = $this->getShipment()->getOrder();
        
        $_shippingMethod = explode("_",$_order->getShippingMethod());
        if ($_shippingMethod[0] == 'tnt')  {
            $this->_addButton('etiquette', array(
                'label'     => Mage::helper('tnt')->__('Etiquette TNT'),
                'class'     => 'save',
                'onclick'   => 'window.open(\'' . $this->getPrintTntUrl() . '\')'                
                )
            );
        }
        
        if ($this->getShipment()->getId()) {
            $this->_addButton('print', array(
                'label'     => Mage::helper('sales')->__('Print'),
                'class'     => 'save',
                'onclick'   => 'setLocation(\''.$this->getPrintUrl().'\')'
                )
            );
        }
    }

    /**
     * Retrieve shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment()
    {
        return Mage::registry('current_shipment');
    }

    public function getHeaderText()
    {
        if ($this->getShipment()->getEmailSent()) {
            $emailSent = Mage::helper('sales')->__('Shipment email sent');
        }
        else {
            $emailSent = Mage::helper('sales')->__('Shipment email not sent');
        }

        $header = Mage::helper('sales')->__('Shipment #%s (%s)', $this->getShipment()->getIncrementId(), $emailSent);
        return $header;
    }

    public function getBackUrl()
    {
        return $this->getUrl(
            '*/sales_order/view',
            array(
                'order_id'  => $this->getShipment()->getOrderId(),
                'active_tab'=> 'order_shipments'
            ));
    }

    public function getEmailUrl()
    {
        return $this->getUrl('*/sales_order_shipment/email', array('shipment_id'  => $this->getShipment()->getId()));
    }

    public function getPrintUrl()
    {
        return $this->getUrl('*/*/print', array(
            'invoice_id' => $this->getShipment()->getId()
        ));
    }
    
    public function getPrintTntUrl()
    {
        return $this->getUrl('tnt/sales_impression/print', array(
            'shipment_id' => $this->getShipment()->getId()
        ));
    }
    
    public function updateBackButtonUrl($flag)
    {
        if ($flag) {
            return $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/sales_shipment/') . '\')');
        }
        return $this;
    }
}