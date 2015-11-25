<?php

class Infostrates_Tnt_Block_Sales_Impression extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'tnt';
        $this->_controller = 'sales_shipment';
        $this->_headerText = Mage::helper('tnt')->__('Suivi des expéditions TNT');
        parent::__construct();
        $this->_removeButton('add');
    }
}