<?php

class Infostrates_Tnt_Model_Shipping_Carrier_Tnt_Source_Labelformat
{
    public function toOptionArray()
    {
        return array(        
            array(
                'value'=>'STDA4',
                'label'=>Mage::helper('tnt')->__('Imprimante A4 standard')),
        	array(
                'value'=>'THERMAL',
                'label'=>Mage::helper('tnt')->__('Imprimante thermique générique')),        	
        	array(
                'value'=>'THERMAL,NO_LOGO,ROTATE_180',
                'label'=>Mage::helper('tnt')->__('Imprimante thermique TNT'))
        );
    }
}
