<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
	<head>
    	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Relais Colis</title>
		
		<link rel="stylesheet" href="css/tnt/ui.tabs.css" type="text/css" />
		<link rel="stylesheet" href="css/tnt/ui.dialog.css" type="text/css" />
		<link rel="stylesheet" href="css/tnt/tntB2CRelaisColis.css" type="text/css" />
	</head>	

	<body>	
    	<?php if( isset($_GET['cp']) ) {
        	$cp_origin = $_GET['cp'];
		} else {
			$cp_origin = '';
		} ?> 
		
        <input type="hidden" id="cp_origin" name="cp_origin" value="<?php echo $cp_origin; ?>" />
        
		<!-- Element (obligatoire) à définir pour afficher la liste de relais -->
		<div id="tntB2CRelaisColis" class="exemplePresentation"></div>
		
		<div id="promoRelaisColis">
			<img src="images/tnt/relaisColis/24_relaiscolis.jpg" width="363">
			<!-- div class="logo">
				<div class="image"><img src="images/tnt/relaisColis/logo_24_relaiscolis.jpg" width="130"></div>
				<div class="desc">Votre colis est livré le lendemain matin de son expédition dans le Relais Colis<sup class='tntRCSup'>&#174;</sup> sélectionné.</div>
			</div>
			<div class="description">Avec 4000 Relais Colis<sup class='tntRCSup'>&#174;</sup> 85% de la population française est située à moins de 5 km d'un commerçant Relais Colis<sup class='tntRCSup'>&#174;</sup>.</div-->
		</div>

		<!-- Element (optionel) à définir pour afficher une Google Map associée à la liste de relais
			 Note: les propriétés de css "width" et "height" doivent obligatoirement être définis dans
			 l'attribut "style", sinon la carte ne s'affichera pas
		-->
		<div id="map_canvas" class="exemplePresentation" style="width: 482px; height: 482px"></div>
		
		<script type="text/javascript" src="js/tnt/jquery.js"></script>
		<script type="text/javascript" src="js/tnt/jquery-ui.js"></script>
		<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
		
<?php 	if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {			
			if (intval(substr($_SERVER['HTTP_USER_AGENT'], strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')+5)) <= 8) { ?>
		<script type="text/javascript" src="js/tnt/relaisColisIE7.js"></script>
<?php 		} else { ?>
		<script type="text/javascript" src="js/tnt/relaisColis.js"></script>
<?php 		} 
		} else { ?>
		<script type="text/javascript" src="js/tnt/relaisColis.js"></script>
<?php 	} ?>		
		
		<script type="text/javascript" src="js/tnt/jquery.superbox.js"></script>

	</body>
</html>