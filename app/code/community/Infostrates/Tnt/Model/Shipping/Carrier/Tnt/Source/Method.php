<?php

class Infostrates_Tnt_Model_Shipping_Carrier_Tnt_Source_Method
{
    public function toOptionArray()
    {
        return array(            
        	array(
                'value'=>'JZ',
                'label'=>Mage::helper('tnt')->__('TNT 24h à Domicile : Votre colis livré demain matin chez vous.'),
        		'description'=>Mage::helper('tnt')->__("Livraison en main propre contre signature, en 24h après expédition de votre commande, et avant 13h*, du lundi au samedi* de 8H à 13H**. En cas d'absence ou d'impossibilité de livraison, le colis est déposé dans le Relais Colis® le plus proche, et vous pouvez le retirer le jour même dès 14h."),
        		'lien'=> 'images/tnt/24h_domicile.jpg',
        		'logo'=> 'tnt_jz.png'),        	
        	array(
                'value'=>'J',
                'label'=>Mage::helper('tnt')->__("TNT 24h Entreprise : Votre colis livré demain matin à l'adresse indiquée."),
        		'description'=>Mage::helper('tnt')->__("Livraison en main propre contre signature, en 24h après expédition de votre commande, et avant 13h*, du lundi au vendredi de 8H à 13H**. En cas d'absence ou d'impossibilité de livraison, le colis est retourné à l'agence TNT de distribution en attente de vos instructions."),
        		'lien'=> "images/tnt/24h_entreprise.jpg",
        		'logo'=> 'tnt_j.png'),
            array(
                'value'=>'JD',
                'label'=>Mage::helper('tnt')->__('TNT 24h en Relais Colis® : Votre colis livré demain matin dans le Relais Colis® de votre choix.'),
            	'description'=>Mage::helper('tnt')->__("Livraison en 24h, après expédition de votre commande, dans l'un des 4000 Relais Colis® partout en France métropolitaine. Retrait possible dés 14h et distribution après vérification d’une pièce d'identité et contre signature."),
            	'lien'=> "images/tnt/24h_relais.jpg",
            	'logo'=> 'tnt_jd.png'),                  
        );
    }
}
