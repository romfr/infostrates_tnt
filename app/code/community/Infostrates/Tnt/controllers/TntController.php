<?php

class Infostrates_Tnt_TntController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $zipcode = $this->getRequest()->getParam('zipcode');
        $zipcode = trim(urldecode($zipcode));
        $zipcode = mb_convert_encoding($zipcode, 'UTF-8');

        if (empty($zipcode)) {
            Mage::log('TNTRelais: No zip code - error message');
            echo '<ul class="messages"><li class="error-msg"><ul><li>'.Mage::helper('tnt')->__('Le code postal est obligatoire !').'</li></ul></li></ul>';
        } else {
            $results = Mage::getModel('tnt/shipping_carrier_tnt')->_tnt_citiesGuide($zipcode);
            if (!is_array($results)) {
                echo '<ul class="messages"><li class="error-msg"><ul><li>'.Mage::helper('tnt')->__($results).'</li></ul></li></ul>';
            } else {
                $points = array();
                foreach ($results as $result) {
                    $points[] = Mage::getModel('tnt/shipping_carrier_tnt')->_tnt_dropOffPoints($result['zipCode'], $result['name']);
                }
                if (!is_array($points)) {
                    echo '<ul class="messages"><li class="error-msg"><ul><li>'.Mage::helper('tnt')->__($points).'</li></ul></li></ul>';
                } else {
                    $this->filterPoints($points);
                }
            }
        }
    }

    /**
     * liste les PR retournés par le WS.
     *
     * @param Array $tnt_items
     *
     * @return string
     */
    private function filterPoints($tnt_items)
    {
        $offset = 1;
        $allReadyIn = array();
        $html = '<label>'.Mage::helper('tnt')->__('Choisissez votre point relais pour la livraison').': </label><ul>';
        foreach ($tnt_items as $tmp_items) {
            foreach ($tmp_items as $item) {
                if (!in_array($item['xETTCode'], $allReadyIn)) {
                    $allReadyIn[] = $item['xETTCode'];
                    if ($offset <= 50) {
                        $html .= '<li>
                        <input name="tnt_relais" type="radio" id="tnt_relais'.$offset.'" class="radio" value="'.$item['address1'].'&&&'.$item['name'].' '.$item['xETTCode'].'&&&'.$item['zipCode'].'&&&'.$item['city'].'" />
                        <label for="tnt_relais'.$offset.'">
                            <span class="s1">'.$item['name'].' : '.$item['address1'].' - '.$item['zipCode'].' - '.$item['city'].'</span>
                            <span class="s2"> <a href="'.$item['geolocalisationUrl'].'" target="_blank">voir</a></span>
                        </label>
                    </li>';
                    }
                    ++$offset;
                }
            }
        }
        $html .= '</ul>';
        echo $html;
    }

    /**
     * Update les données d'expédition du client par celles du PR.
     * 
     * @return string
     */
    public function changeshippingaddressAction()
    {
        if ($shipping = $this->getRequest()->getParams(array('street', 'description', 'postcode', 'city', 'info_comp'))) {
            $current = Mage::getSingleton('checkout/session')->getQuote();
            Mage::register('current_quote', $current);
            $address = $current->getShippingAddress();
            (string) $postcode = $shipping ['postcode'];
            if (substr($postcode, 0, 2) == 20) {
                $regioncode = substr($postcode, 0, 3);
                switch ($regioncode) {
                    case 202 :
                        $regioncode = '2B';
                        break;
                    default:
                        $regioncode = '2A';
                        break;
                }
            } else {
                $regioncode = substr($postcode, 0, 2);
            }
            Mage::app()->getLocale()->setLocaleCode('en_US');
            $region = Mage::getModel('directory/region')->loadByCode($regioncode, $address->getCountryId());
            $regionname = $region->getDefaultName();
            $regionid = $region->getRegionId();
            $address->setRegion($regionname);
            $address->setRegionId($regionid);
            $address->setPostcode($postcode);
            $address->setStreet(urldecode($shipping['street']));
            $address->setCity(urldecode($shipping ['city']));
            $address->setCompany(trim(urldecode($shipping ['description'])));
            $address->setTntInfosComp(trim(urldecode($shipping ['info_comp'])));
            $address->save();
            $current->setShippingAddress($address);
            $current->save();
        }
    }

    public function villeAction()
    {
        $zipcode = $this->getRequest()->getParam('zipcode');
        $zipcode = trim(urldecode($zipcode));
        $zipcode = mb_convert_encoding($zipcode, 'UTF-8');

        //je stock les infos de l'adresse de livraison au cas ou
        $street = $this->getRequest()->getParam('street');
        $street = trim(urldecode($street));
        $street = mb_convert_encoding($street, 'UTF-8');

        $city = $this->getRequest()->getParam('city');
        $city = trim(urldecode($city));
        $city = mb_convert_encoding($city, 'UTF-8');

        $company = $this->getRequest()->getParam('company');
        $company = trim(urldecode($company));
        $company = mb_convert_encoding($company, 'UTF-8');

        if (empty($zipcode)) {
            Mage::log('TNTCheck CP/Villes: No zip code - error message');
            echo '<ul class="messages"><li class="error-msg"><ul><li>'.Mage::helper('tnt')->__('Le code postal est obligatoire !').'</li></ul></li></ul>';
        } else {
            $allVilles = Mage::getModel('tnt/shipping_carrier_tnt')->_tnt_citiesGuide($zipcode);

            if (!is_array($allVilles)) {
                echo '<ul class="messages"><li class="error-msg"><ul><li>'.Mage::helper('tnt')->__($allVilles).'</li></ul></li></ul>';
            } else {
                $this->filterVilles($allVilles, $street, $zipcode, $city, $company);
            }
        }
    }

    private function filterVilles($allVilles, $street, $zipcode, $city, $company)
    {
        $offset = 0;
        $html = '';

        if (count($allVilles) > 1) {
            $html .= '<label>'.Mage::helper('tnt')->__('Choisissez précisemment la ville de livraison').': </label><ul>';
            foreach ($allVilles as $ville) {
                if ($ville['name'] == strtoupper($city)) {
                    $correspondance = '<input name="tnt_ville" type="radio" id="tnt_ville1" class="radio" value="'.$street.'&&&'.$company.'&&&'.$zipcode.'&&&'.$ville['name'].'" checked="checked" style="display:none;" />';
                    break;
                }

                ++$offset;
                if ($offset <= 50) {
                    $html .= '<li>               
                    <input name="tnt_ville" type="radio" id="tnt_ville'.$offset.'" class="radio" value="'.$street.'&&&'.$company.'&&&'.$zipcode.'&&&'.$ville['name'].'" />              
                    <label for="tnt_ville'.$offset.'">
                        <span class="s1">'.$ville['name'].'</span>                  
                    </label>
                </li>';
                }
            }
            $html .= '</ul>';

            if (isset($correspondance)) {
                $html = $correspondance;
            }
        } else {
            $html .= '<input name="tnt_ville" type="radio" id="tnt_ville1" class="radio" value="'.$street.'&&&'.$company.'&&&'.$zipcode.'&&&'.$allVilles[0]['name'].'" checked="checked" style="display:none;" />';
        }
        echo $html;
    }
}
