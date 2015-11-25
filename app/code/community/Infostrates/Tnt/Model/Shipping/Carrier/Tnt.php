<?php

class Infostrates_Tnt_Model_Shipping_Carrier_Tnt
extends Mage_Shipping_Model_Carrier_Abstract
implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_code = 'tnt';
    protected $_request = null;
    protected $_result = null;

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
    	$quote = Mage::getSingleton('checkout/session')->getQuote();
    	
    	if (!$this->getConfigFlag('active') || $quote->getShippingAddress()->getCountryId() != 'FR') {
            return false;
        }        
        
        //corrige le bug de la fonction date() de PHP dans Magento par l'utilisation du timestamp de magento...
        //$timestamp = Mage::getModel('core/date')->timestamp(time());
        
        //permet de passer outre les jours fériés en imposant le 10/01/xx à la faisabilité TNT
        $currentTimestamp = Mage::getModel('core/date')->timestamp(time());
        $currentYear = date('Y', $currentTimestamp);
        
        $anyDate = $currentYear.'-01-10 00:00:00';
        $dateTimestamp = Mage::getModel('core/date')->timestamp(strtotime($anyDate));
        
        if( $currentTimestamp < $dateTimestamp ) {
        	$timestamp = Mage::getModel('core/date')->timestamp(strtotime($anyDate));
        } else {
        	$currentYear++;
        	$anyDate = $currentYear.'-01-10 00:00:00';
        	$timestamp = Mage::getModel('core/date')->timestamp(strtotime($anyDate));
        }
        
        if( date('D', $timestamp) == 'Sat' ) {
        	$date  = date('Y-m-d', mktime(0, 0, 0, date("m",$timestamp)  , date("d",$timestamp)+2, date("Y",$timestamp)));        	
        } elseif( date('D',$timestamp) == 'Sun' ) {
        	$date  = date('Y-m-d', mktime(0, 0, 0, date("m",$timestamp)  , date("d",$timestamp)+1, date("Y",$timestamp)));
        } else {
        	$date = date('Y-m-d',$timestamp);
        }
        //vérification des informations du compte saisies dans l'admin
        $sender = array('zipCode' => $this->getConfigData('code_postal'), 'city' => $this->getConfigData('ville'));
        $receiver = array('zipCode' => $this->getConfigData('code_postal'), 'city' => $this->getConfigData('ville'));
        $feasi_params = array('shippingDate' => $date, 'accountNumber' => $this->getConfigData('account'), 'sender' => $sender, 'receiver' => $receiver );
		$feasi_result = Mage::getModel('tnt/shipping_carrier_tnt')->_tnt_feasibility( $feasi_params );
						
		if( is_string($feasi_result) ) {
			return false;
		}

        $this->setRequest($request);

		$r = $this->_rawRequest;
		
		$allMethods = Mage::getModel('tnt/shipping_carrier_tnt_source_method')->toOptionArray();
        		
		$allowedMethods = explode(",", $this->getConfigData('allowed_methods'));

		$servicesDispos = array();

        foreach ($allMethods as $oneMethod) {
        	if( in_array($oneMethod['value'], $allowedMethods) ) {
        		if( $oneMethod['value'] == 'JD' ||
        			(!preg_match("/Z/", $oneMethod['value']) && $quote->getShippingAddress()->getCompany() != '') || 
        			(preg_match("/Z/", $oneMethod['value']) && $quote->getShippingAddress()->getCompany() == '') ) {
		        	$servicesDispos[] = array("serviceCode" => $oneMethod['value'], "serviceLabel" => $oneMethod['label'], "serviceDescription" => $oneMethod['description'], "serviceLien" => $oneMethod['lien'], "serviceLogo" => $oneMethod['logo']);
        		}
        	}
        }        
		$this->_result = $this->_parseTntFeasibilityResponse($servicesDispos);
        
		return $this->getResult();
    }

    public function setRequest(Mage_Shipping_Model_Rate_Request $request)
    {
        $this->_request = $request;

        $r = new Varien_Object();

        if ($request->getLimitMethod()) {
            $r->setService($request->getLimitMethod());
        }
        
        if ($request->getTntAccount()) {
            $r->setTntAccount($request->getTntAccount());
        } else {
            $r->setTntAccount($this->getConfigData('account'));
        }

        if ($request->getExpZipCode()) {
            $r->setExpZipCode($request->getExpZipCode());
        } else {
            $r->setExpZipCode(Mage::getStoreConfig('carriers/tnt/code_postal', $this->getStore()));
        }
        
    	if ($request->getExpCity()) {
            $r->setExpCity($request->getExpCity());
        } else {
            $r->setExpCity(Mage::getStoreConfig('carriers/tnt/ville', $this->getStore()));
        }

        if ($request->getDestPostcode()) {
            $r->setDestPostcode($request->getDestPostcode());
        } else {
        }
        
    	if ($request->getDestCity()) {
            $r->setDestCity($request->getDestCity());
        } else {
        }

        $this->_rawRequest = $r;

        return $this;
    }

    public function getResult()
    {
       return $this->_result;
    }	
    
    //permet de requeter le WS
    protected function ws_tnt_communication($fonction, $parametres) {
    	$url = $this->getConfigData('gateway_url');
    	$username = $this->getConfigData('identifiant');
        $password = $this->getConfigData('mdp');
    	
    	$authheader = sprintf('
			<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
			  <wsse:UsernameToken>
				<wsse:Username>%s</wsse:Username>
				<wsse:Password>%s</wsse:Password>
			 </wsse:UsernameToken>
			</wsse:Security>', htmlspecialchars($username), htmlspecialchars( $password ));
    	
    	$authvars = new SoapVar($authheader, XSD_ANYXML);
		$header = new SoapHeader("http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd", "Security", $authvars);

		//$soap = new SoapClient($url, array('trace'=>1));
        $soap = new SoapClient($url, array('trace'=> 1,
                'stream_context'=>stream_context_create(array(
                    'http'=> array(
                        'user_agent' => 'PHP/SOAP',
                        'accept' => 'application/xml'
                    )
                ))
            )
        );


        $soap->__setSOAPHeaders(array($header));
			
		try {
			if($fonction == 'feasibility') {					
				$result = $soap->feasibility( array('parameters' => $parametres) );
			}
	    	if($fonction == 'dropOffPoints') {				
				$result = $soap->dropOffPoints( $parametres );				
			}
	    	if($fonction == 'citiesGuide') {				
				$result = $soap->citiesGuide( $parametres );				
			}
	    	if($fonction == 'expeditionCreation') {				
				$result = $soap->expeditionCreation( $parametres );
	    	}
		} catch (Exception $e) {
			$result = $e->getMessage();
		}
		
		return $result;
    }
    
    /* 
     * Test de feasibility via le WS
     * $params = array(params,array sender,array receiver)
     */
	public function _tnt_feasibility($params)
    {    	
    	$debugData = array();
        $fonction = 'feasibility';
    	$results = array();
	        
		$result = $this->ws_tnt_communication($fonction, $params);
		
		if( is_string($result) ) {
			$debugData['result'] = array('error' => $result);    		
    		$results = $result;
		} else {		
	        $nb = count($result->Service);
	        
	    	if ($nb > 1) {
				// Cas où une liste de services est renvoyée
				foreach ($result->Service as $Service) {
					$results[] = array("serviceCode" => $Service->serviceCode, "serviceLabel" => $Service->serviceLabel." (".$Service->dueDate.")");
				}
	    	} elseif($nb == 1) {
	    		$Service = $result->Service;
	    		$results[] = array("serviceCode" => $Service->serviceCode, "serviceLabel" => $Service->serviceLabel." (".$Service->dueDate.")");
			} else {
				// Cas où aucun service n'est renvoyé
			}
		}
    		
    	$this->_debug($debugData);
        
        return $results;
    }
    
	//Retourne les services choisis par le commercant
    protected function _parseTntFeasibilityResponse($servicesDispos)
    {
    	$already_showed = array();    	
    	
    	$result = Mage::getModel('shipping/rate_result');
    	$rate = Mage::getModel('shipping/rate_result_method');
    	
    	$totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals(); //Total object
        $totalCart = $totals["subtotal"]->getValue(); //Subtotal value
		
		if(isset($totals['discount']) && $totals['discount']->getValue()) {
			$totalCart = $totalCart + $totals['discount']->getValue();
        }
		
		$items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
		$shipping_weight = 0;
		$heaviest_item = 0;
		
		foreach($items as $item) {
			if( $item->getProductType() == 'configurable' ) {
			} else {
				//check l'article le plus lourd
				if( $item->getWeight() > $heaviest_item) {				
					$heaviest_item = $item->getWeight();
				}
				$shipping_weight += ($item->getWeight() * $item->getQty()) ;
			}
		}		
		
		//si le poids total du colis dépasse la limite pour TNT (définie par le commercant)
		//si la limite est à 0 alors on passe en mode 'Pas de limite'
		if( $this->getConfigData('tnt_max_weight') != 0 && $shipping_weight > $this->getConfigData('tnt_max_weight') ) {
			return $result;
		}
		
		$filter_jdjz = 0;
		if( $heaviest_item > ($this->getConfigData('max_package_weight')-10) ) { // > 20 je ne propose pas JD/JZ
			$filter_jdjz = 1;
		}	
		
		if( $heaviest_item > $this->getConfigData('max_package_weight') ) { // > 30 je ne propose pas J/A, donc je ne propose pas de produits TNT
			return $result;
		}		
		 		
    	if (empty($servicesDispos)) {
	        $error = Mage::getModel('shipping/rate_result_error');
	        $error->setCarrier($this->_code);
	        $error->setCarrierTitle($this->getConfigData('title'));
	        $error->setErrorMessage($this->getConfigData('specificerrmsg'));
	    	$result->append($error);
	    } else {
	    	foreach ($servicesDispos as $m) {
	    		
	    		if( $filter_jdjz == 1 && ($m['serviceCode'] == 'JZ' || $m['serviceCode'] == 'JD') ) {
	    			//on n'ajoute pas de le service
	    		} else {
		    		if(in_array($m['serviceLabel'],$already_showed)) { continue; } else { $already_showed[] = $m['serviceLabel']; }
	
	                $rate = Mage::getModel('shipping/rate_result_method');
	                $rate->setCarrier($this->_code);
	                $rate->setCarrierTitle($this->getConfigData('title'));
	                $rate->setMethod( $m['serviceCode'] );
					$rate->setMethodTitle( $m['serviceLabel'] );
					$rate->setMethodDescription( $m['serviceDescription']."|||".$m['serviceLien']."|||".$m['serviceLogo'] );
					
	            	if( $this->getConfigData($m['serviceCode'].'_free') != 0 && $this->getConfigData($m['serviceCode'].'_free') <= $totalCart ) {
						$tarif = '0';
					} else {
						$table = explode(",", $this->getConfigData($m['serviceCode'].'_amount'));
						
			            $tarifTrouve=true;
			            //si le commercant choisi un forfait plutot qu'une table de correspondance poids:prix ou panier:prix
			            if(count($table) == 1) {
			            	$tarif = $table[0];
			            } else {
			            	for ($i = 0; $i < sizeof($table); $i++) {
				            	$tmp = explode(":", $table[$i]);

                                //si le commercant choisi une table de correspondance panier:prix
                                if( preg_match("/€/", $tmp[0]) ) {
                                    $prix = str_replace('€', '', $tmp[0]);

                                    if ($totalCart > $prix)
                                        continue;
                                    if (($totalCart <= $prix) AND $tarifTrouve) {
                                        $tarif=$tmp[1];
                                        $tarifTrouve=false;
                                    }
                                } else {
                                    if ($shipping_weight > $tmp[0])
                                        continue;
                                    if (($shipping_weight <= $tmp[0]) AND $tarifTrouve) {
                                        $tarif=$tmp[1];
                                        $tarifTrouve=false;
                                    }
                                }
				            }
			            }
					}
					
					//si il s'agit d'un envoi vers la Corse je gère le supplément
		            $cp = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getPostcode();
		            $cp_tmp = substr($cp, 0, 3);
		            if( $cp_tmp == '200' ||  $cp_tmp == '201' ||  $cp_tmp == '202' ) {
		            	if( $this->getConfigData('corse_free') != 0 && $this->getConfigData('corse_free') <= $totalCart ) {
							//$tarif = $tarif;
						} else {
		            		$tarif += $this->getConfigData('corse_amount');
						}
		            }
					
					$rate->setPrice( $tarif );
	                $result->append($rate);
	    		}
			}
	    }
        
        return $result;
    }
    
	//Récupération des villes en fonction du CP via le WS
	public function _tnt_citiesGuide($cp)
    {    	
    	$debugData = array();
        $fonction = 'citiesGuide';
        $city = '';

		$parametres = array('zipCode' => $cp);
					
		$result = $this->ws_tnt_communication($fonction, $parametres);
			
		if( is_string($result) ) {
			$debugData['result'] = array('error' => $result);
    		$city = $result;
		} else {
	        if( isset($result->City) && count($result->City) > 1 ) {
				foreach ($result->City as $City) {	
					$city[] = array("name" => $City->name, "zipCode" => $City->zipCode);
				}
			} elseif( isset($result->City) && count($result->City) > 0 ) {
				$City = $result->City;
				$city[] = array("name" => $City->name, "zipCode" => $City->zipCode);
			} else {
				// Cas où aucune ville n'est renvoyée
				$city = "Le code postal de votre adresse de livraison n'est pas correct.";
			}
		}
    		
	    $this->_debug($debugData);
        
        return $city;
    }
    
    //Récupération des points relais pour l'CP/ville par defaut via le WS
	public function _tnt_dropOffPoints($cp,$ville)
    {    	
    	$debugData = array();
        $fonction = 'dropOffPoints';
        $pointsDispos = array();

		$parametres = array('zipCode' => $cp, 'city' => $ville);
					
		$result = $this->ws_tnt_communication($fonction, $parametres);
		
		if( is_string($result) ) {			
    		$debugData['result'] = array('error' => $result);
	   		$pointsDispos = $result;
		} else {				        
			if (isset($result->DropOffPoint) && count($result->DropOffPoint) > 1) {
				foreach ($result->DropOffPoint as $Point) {
					$pointsDispos[] = array("xETTCode" => $Point->xETTCode, 
											"name" => $Point->name, 
											"address1" => $Point->address1, 
											"zipCode" => $Point->zipCode, 
											"city" => $Point->city, 
											"geolocalisationUrl" => $Point->geolocalisationUrl,
											"openingHours" => 	array(  "monday" => $Point->openingHours->monday,
															 			"tuesday" => $Point->openingHours->tuesday,
																		"wednesday" => $Point->openingHours->wednesday,
																		"thursday" => $Point->openingHours->thursday,
																		"friday" => $Point->openingHours->friday,
																		"saturday" => $Point->openingHours->saturday,
																		"sunday" => $Point->openingHours->sunday
																));
				}
			} elseif(isset($result->DropOffPoint) && count($result->DropOffPoint) == 1) {
				$Point = $result->DropOffPoint;
				$pointsDispos[] = array("xETTCode" => $Point->xETTCode, 
											"name" => $Point->name, 
											"address1" => $Point->address1, 
											"zipCode" => $Point->zipCode, 
											"city" => $Point->city, 
											"geolocalisationUrl" => $Point->geolocalisationUrl,
											"openingHours" => 	array(  "monday" => $Point->openingHours->monday,
															 			"tuesday" => $Point->openingHours->tuesday,
																		"wednesday" => $Point->openingHours->wednesday,
																		"thursday" => $Point->openingHours->thursday,
																		"friday" => $Point->openingHours->friday,
																		"saturday" => $Point->openingHours->saturday,
																		"sunday" => $Point->openingHours->sunday
																));
			} else {
				// Cas où aucun PR n'est renvoyé				
			}
		}				
    		
	    $this->_debug($debugData);
        
        return $pointsDispos;
    }
    
	//Création d'une expé via le WS
	public function _tnt_exp_crea($params)
    {
    	$debugData = array();
        $fonction = 'expeditionCreation';
        $parcels = array();
        
        $result = $this->ws_tnt_communication($fonction, $params);
        
        if( is_string($result) ) {
	   		$debugData['result'] = array('error' => $result);
	    	$parcels = $result;
		} else {
			$parcels['pdfLabels'] = $result->Expedition->PDFLabels;
							        
			if( count($result->Expedition->parcelResponses) > 1 ) {
				foreach ($result->Expedition->parcelResponses as $parcelResponses) {
					$parcels[] = array("sequenceNumber" => $parcelResponses->sequenceNumber,
									"parcelNumber" => $parcelResponses->parcelNumber,
									"trackingURL" => $parcelResponses->trackingURL);
				}
			} elseif( count($result->Expedition->parcelResponses) > 0 ) {
				$parcelResponses = $result->Expedition->parcelResponses;
				$parcels[] = array("sequenceNumber" => $parcelResponses->sequenceNumber,
									"parcelNumber" => $parcelResponses->parcelNumber,
									"trackingURL" => $parcelResponses->trackingURL);
			} else {
				// Cas où aucune expé n'est renvoyée				
			}
		}		
    		
	    $this->_debug($debugData);
        
        return $parcels;
    }   

	public function getAllowedMethods() {
        $allowed = explode(',', $this->getConfigData('allowed_methods'));
        $arr = array();
        foreach ($allowed as $k) {
            $arr[$k] = $this->getCode('method', $k);
        }
        return $arr;
    }
    
    
	public function isTrackingAvailable()
	{
		return true;
	}
	
	public function getTrackingInfo($tracking_number)
	{
		$tracking_result = $this->getTracking($tracking_number);

		if ($tracking_result instanceof Mage_Shipping_Model_Tracking_Result)
		{
			if ($trackings = $tracking_result->getAllTrackings())
			{
				return $trackings[0];
			}
		}
		elseif (is_string($tracking_result) && !empty($tracking_result))
		{
			return $tracking_result;
		}
		
		return false;
	}
	
	protected function getTracking($tracking_number)
	{
		$tracking_url = $this->getConfigData('tracking_url').$tracking_number; 

		$tracking_result = Mage::getModel('shipping/tracking_result');

		$tracking_status = Mage::getModel('shipping/tracking_result_status');
		$tracking_status->setCarrier($this->_code)
						->setCarrierTitle($this->getConfigData('title'))
						->setTracking($tracking_number)
						->setUrl($tracking_url);
		$tracking_result->append($tracking_status);

		return $tracking_result;
	}
}
