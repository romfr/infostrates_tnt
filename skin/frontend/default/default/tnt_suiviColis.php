<?php
function suivi_colis($suivi) {
	$url = "http://www.tnt.fr/service/tracking?wsdl";
	
	$username = '';
	$password = '';
		
	$authheader = sprintf('
						<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
						  <wsse:UsernameToken>
							<wsse:Username>%s</wsse:Username>
							<wsse:Password>%s</wsse:Password>
						 </wsse:UsernameToken>
						</wsse:Security>', htmlspecialchars($username), htmlspecialchars( $password ));

	$authvars = new SoapVar($authheader, XSD_ANYXML);
	$header = new SoapHeader("http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd", "Security", $authvars);

	$soap = new SoapClient($url, array('trace'=>1));
	$soap->__setSOAPHeaders(array($header));
		
	try {
		$result = $soap->trackingByConsignment( array('parcelNumber' => $suivi) );
		$etape = getStage($result);
		
		if( is_array($result->Parcel->longStatus) ) {
			$statut = $result->Parcel->longStatus[0];
			$message = $result->Parcel->longStatus[1];
		} else {
			$statut = $result->Parcel->longStatus;
			$message = '';
		}
		
		$result = 'tntB2CSuiviColisDisplayDetail(["'.$result->Parcel->consignmentNumber.'","'.$result->Parcel->reference.'","'.$result->Parcel->receiver->city.'","","",["'.$statut.'","'.$message.'"],[],"'.$etape.'"])';
	} catch (Exception $e) {
		$result = $e->getMessage();
	}
	
	return $result;
}

function getStage( $info ) {
	$tntEndStatusCode = array( '000', '410', '515', '517', '529', '549', '552', '557', '560', '626', '700', '701', '702', '703', '704', '998', '999', 'R', 'Z', '2' );
	$tntIncidentStatusCode = array( '110', '113', '121', '124', '131', '132', '200', '210', '211', '212', '213', '215', '216', '217', '218', '300', '310', '311', '312', '313', '314', '315', '316', '317', '318', '319', '320', '321', '322', '323', '324', '325', '326', '327', '328', '412', '414', '415', '500', '561', '600', '610', '611', '612', '613', '614', '615', '616', '617', '618', '619', '620', '621', '622', '623', '624', '625', '634', '820', '821', '822', '823', '824', '833', 'A', 'C', 'F', 'G', 'I', 'K', '3', '5' );
	
	if ( !empty( $info->Parcel->statusCode ) )
	{
		if ( in_array( $info->Parcel->statusCode, $tntIncidentStatusCode ) )
		{
			return 4;
		}
		if ( in_array( $info->Parcel->statusCode, $tntEndStatusCode ) )
		{
			return 7;
		}
	}
	if ( !empty( $info->Parcel->events->arrivalDate ) )
	{
		return 3;
	}
	if ( !empty( $info->Parcel->processDate ) )
	{
		return 2;
	}
	return 0;
}

if( isset($_GET) && $_GET['bonTransport'] != '' ) {
	$result = suivi_colis($_GET['bonTransport']);
	echo $result;
	exit;	
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Suivi Colis</title>	
		
		<link rel="stylesheet" href="css/tnt/tntB2CSuiviColis.css" type="text/css" />
	</head>
	<body>
		<?php if( isset($_GET['suivi']) ) {
        	$suivi = $_GET['suivi'];
		} else {
			$suivi = '';
		} ?>
		<input type="hidden" id="suivi" name="suivi" value="<?php echo $suivi; ?>" />
		<div id="tntB2CSuiviColis"></div>
		
		<script type="text/javascript" src="js/tnt/jquery.js"></script>
		<script type="text/javascript" src="js/tnt/swfobject.js"></script>
		<script type="text/javascript" src="js/tnt/suiviColis.js"></script>
		<script type="text/javascript">
			function createEtape(etape) {
    			swfobject.embedSWF("images/tnt/swf/banniere_TNT_"+etape+".swf", "myEtape", "482", "159", "9.0.0");
    		}
    	</script>
	</body>
</html>