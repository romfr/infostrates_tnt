/** Javascript B2C Suivi Colis - version 2.0 - 06/07/2010 **/
var pathToImages = "images/tnt/relaisColis/";
var tntDomain = "www.tnt.fr";

var tntSCMsgHeaderTitle = "Suivi Colis";
var tntSCMsgSubHeaderTitle = "Suivez votre colis 24h sur 24 et 7 jours sur 7 :";
var tntSCMsgBodyLoading = "Chargement en cours...";
var tntSCMsgBodyInput1 = "Entrez votre r&#233;f&#233;rence d'exp&#233;dition :"; 
var tntSCMsgBodyInput2 = "Vous pouvez choisir une autre r&#233;f&#233;rence d'exp&#233;dition :";
var tntSCMsgBodyFldRef = "R&#233;f&#233;rence de l'exp&#233;dition :&#160;";
var tntSCMsgBodyFldDtl = "Date de livraison :&#160;";
var tntSCMsgBodyFldDst = "Destination :&#160;";
var tntSCMsgBodyFldSta = "Statut de votre exp&#233;dition :";
var tntSCMsgBodyFldRel = "Relais Colis<sup class='tntSCSup'>&#174;</sup> :";
var tntSCMsgFooterTitle = "Les solutions de livraisons <div class='tntSCTextBold'>TNT 24h chez Moi</div>&#160;et&#160;<div class='tntSCTextBold'>TNT 24h Relais Colis<sup class='tntSCSup'>&#174;</sup></div><BR>sont des offres exclusives TNT Express France.<BR><BR>Pour toute information: <a href='http://www.tnt.fr' class='tntSCTextBold'>www.tnt.fr</a>";
var tntSCMsgErrModulo = "Votre r&#233;f&#233;rence d'exp&#233;dition est invalide, veuillez v&#233;rifier votre saisie"
var tntSCMsgErrConnexion = "Erreur de connexion";
var tntSCMsgErrBtInvalide = tntSCMsgErrModulo;

function getURLParam(name) {
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]" + name + "=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( window.location.href );
	if( results == null ) return "";
	else return results[1];
};
	
function getDivInput(lblInput, bonTransport) {
	return ("<table>"+
				"<tr>"+
					"<td width='350px'>" + lblInput + "</td>"+
				 	"<td width='160px'><input type='text' id='tntSCInputBT' class='tntSCInput' maxlength='16' size='16' value='" + bonTransport + "'/></td>"+
					"<td><a href='#' onclick='tntB2CSuiviColisGetDetail();'><img class='tntSCButton' src='" + pathToImages + "bt-OK.jpg'></a></td>" + 
				"</tr>"+
			"</table>");
};

function tntB2CSuiviColis() {
		
	// Test si ID de r�f�rence existe, sinon on ne fait rien
	if (!document.getElementById("tntB2CSuiviColis")) {
		alert("ERREUR: Appel incorrect, objet [tntB2CSuiviColis] manquant !");
		return;
	}
	
	var bonTransport = getURLParam("suivi");
	
	var jBaseObj = $("#tntB2CSuiviColis");
	jBaseObj.html(		
		"<div>"+
			"<div id='tntBodySC' class='tntSCBody'>"+
				"<div class='tntSCGray'>&#160;</div>"+
				"<div id='tntBodyContentSC'>" + getDivInput(tntSCMsgBodyInput1, bonTransport) +	"</div>"+
				"<div id='tntSCLoading' style='display:none;'>" + tntSCMsgBodyLoading + "</div>"+
				"<div id='tntSCError' class='tntSCError' style='display:none;'></div>"+
			"</div>" +		
			"<div class='tntSCWhite'>&#160;</div>"+
			"<div id='tntBodySearchSC' class='tntSCBodySearch' style='display:none;'>"+ getDivInput(tntSCMsgBodyInput2, "") + "</div>"+
			"<div class='tntSCWhite'>&#160;</div>"+
		"</div>"+
		"<div>"+
			"<div class='tntSCFooter'>"+
				"<table>"+
					"<tr>"+
						"<td class='tntSCFooterCell1' width='495px'>" + tntSCMsgFooterTitle + "</td>"+
					 	"<td class='tntSCFooterCell2' width='89px'>"+
						"</td>"+
					"</tr>"+
				"</table>"+
			"</div>"+
		"</div>");

	if (bonTransport != "") tntB2CSuiviColisGetDetail();
};

function tntB2CSuiviColisDisplayErreur(msgErreur) {
	
	$('#tntSCLoading').hide();
	
	var jBodySC = $("#tntBodySC"); 
	jBodySC.css("background-image", "none");
	jBodySC.css("height", "auto");
	
	$("#tntBodySearchSC").hide();
	var bonTransport = $("#tntSCInputBT").val();
	var jBodyContentSC = $("#tntBodyContentSC");
	jBodyContentSC.html(getDivInput(tntSCMsgBodyInput1, bonTransport));
	
	// Gestion erreur et sortie
	jErreurMsg = $("#tntSCError");
	jErreurMsg.html(msgErreur);
	jErreurMsg.show();
		
};

function tntB2CSuiviColisCheckModulo11(bonTransport) {
	var tabModulo = new Array(16,14,12,10,8,6,4,2,3,5,7,9,11,13,15)
	var tabBonTransport = bonTransport.toString().split("");
	var modulo = 0;
	for ( i = 0; i < 15; i++ ) {
		modulo += Number(tabBonTransport[i]) * tabModulo[i];
	}
	modulo = 11 - (modulo % 11);
	if (modulo == 10) modulo = 0;
	if (modulo == 11) modulo = 5;
	return (modulo == Number(tabBonTransport[15]));
};

function tntB2CSuiviColisGetDetail() {
	
	$("#tntSCError").hide();
	
	var bonTransport = $("#tntSCInputBT").val();
	
	if (bonTransport == "") return;
	
	// Verification basique de la validit� du num�ro saisi
	if (isNaN(parseInt(bonTransport)) || bonTransport.length != 16 || !tntB2CSuiviColisCheckModulo11(bonTransport)) {
		tntB2CSuiviColisDisplayErreur(tntSCMsgErrModulo);
		return;
	}

	$('#tntSCLoading').show();
	
	var ajaxUrl;
	var ajaxData;
		
	//ajaxUrl = "http://" + tntDomain + "/public/b2c/suiviColis/rechercheJson.do?bonTransport=" + bonTransport;
	ajaxUrl = "tnt_suiviColis.php?bonTransport=" + bonTransport;
	ajaxData = "";
	
	// Chargement du colis
	$.ajax({
	   type: "GET",
	   url: ajaxUrl,
	   data: ajaxData,
	   dataType: "script",
	   success:function(json){}
	});
};

function tntB2CSuiviColisDisplayDetail(jsondoc) {

	$('#tntSCLoading').hide();

	$("#tntBodySearchSC").show();
	var jBodySC = $("#tntBodySC");

	var bonTransport = jsondoc[0];
	var dateLivraison = jsondoc[3];
	var destination = jsondoc[2];
	var messages = "";
	var nomRelais = "";
	var adrRelais = "";
	var cpoRelais = "";
	var vilRelais = "";
	var refRelais = "";
	var status = jsondoc[5][0];
	var etape = jsondoc[7];
	
	if(jsondoc[6].length != 0){
		var nomRelais = jsondoc[6][0];
		var adrRelais = jsondoc[6][1];
		var cpoRelais = jsondoc[6][2];
		var vilRelais = jsondoc[6][3];
		var refRelais = jsondoc[6][4];
	}
	
	var affRefRelais = "";
	if(refRelais != ""){	
		var  urlGeo = "http://" + tntDomain + "/public/geolocalisation/index.do?xett=" + refRelais;
		affRefRelais = "<a href =" +urlGeo+" target='_blank'><img src='" + pathToImages + "picto_localiser.jpg' alt='*' border = 'none' height='41px' width='58px'/></a>";
	}
	
	for (i = 0; i < jsondoc[5].length; i++){
		if (messages == "") messages = jsondoc[5][i];
		else messages += "<br/>" + jsondoc[5][i];
	}

	var titreRelais = "";
	if (nomRelais != "" || adrRelais != "" || cpoRelais != "" || vilRelais != "") titreRelais = tntSCMsgBodyFldRel;
	
	var jBodyContentSC = $("#tntBodyContentSC");
	
	jBodyContentSC.html("<div style='width:482px;margin: auto;'><div id='myEtape'></div></div>");
	
	jBodyContentSC.append("<table border='0' cellpadding='0' cellspacing='0' align='center' class='tb_suivi'>"+			
				"<tbody>"+
					"<tr>"+
						"<td width='10px'/>"+
						"<td width='100px' />"+
						"<td width='135px' />"+
						"<td width='75px' />"+
						"<td width='150px' />"+
						"<td width='10px' />"+
					"</tr>"+
					"<tr>"+
						"<td class='section' height='25'>&nbsp;</td>"+
						"<td class='section' colspan='5' style='padding-left: 10px;'><b>D&#233;tail du colis</b></td>"+
					"</tr>"+
					"<tr><td colspan='6' height='2px'></td></tr>"+
					"<tr>"+
						"<td width='10px'></td>"+
						"<td width='100px'><b>Bon de transport</b></td>"+
						"<td width='135px' id='ancestor'>" + bonTransport + "</td>"+
						"<td width='75px' style='color:#FF6600;'></td>"+
						"<td width='150px' style='color:#FF6600;'></td>"+
						"<td width='10px'></td>"+
					"</tr>"+
					"<tr><td colspan='6' height='1px'></td></tr>"+
					"<tr>"+
						"<td width='10px'>&nbsp;</td>"+
						"<td width='100px'><b>Destination</b></td>"+
						"<td width='135px'>" + destination + "</td>"+
						"<td width='75px'></td>"+
						"<td width='150px'></td>"+
						"<td width='10px'>&nbsp;</td>"+
					"</tr>"+
					"<tr><td colspan='6' height='2px'></td></tr>"+
					"<tr><td colspan='6' height='3px'></td></tr>"+
					"<tr><td colspan='6' height='1px' bgcolor='#cbcbcb'></td></tr>"+
					"<tr><td colspan='6' height='3px'></td></tr>"+
					"<tr><td colspan='6' height='6px'></td></tr>"+
					"<tr>"+
						"<td>&nbsp;</td>"+
						"<td colspan='5' valign='top'>"+
							"<b>Statut de votre exp&#233;dition :</b>"+
						"</td>"+
					"</tr>"+
					"<tr>"+
						"<td>&nbsp;</td>"+
						"<td colspan='5' valign='top' style='padding-left: 10px;' class='orange'>" + messages + "</td>"+
					"</tr>"+			
					"<tr><td colspan='6' height='6px'></td></tr>"+
					"<tr>"+
						"<td>&nbsp;</td>"+
						"<td colspan='5' valign='top'>"+
							"<b>" + titreRelais + "</b>"+
						"</td>"+
					"</tr>"+
					"<tr>"+
						"<td>&nbsp;</td>"+
						"<td colspan='3' valign='top' style='padding-left: 10px;' class='orange'>" + nomRelais +" "+ refRelais + "<br/>" + adrRelais + "<br/>" + cpoRelais + "&#160;" + vilRelais + "</td>"+
						"<td colspan='2' valign='middle'>" + affRefRelais + "</td>"+
					"</tr>"+					
					"<tr><td colspan='6' height='6px'></td></tr>"+	
				"</tbody>"+
			"</table>");
	
	createEtape(etape);
	
	// RAZ zone de saisie					  
	$("#tntSCInputBT").val("");
};

function erreurColis(codeErreur){
	switch (codeErreur) {
		case 1: tntB2CSuiviColisDisplayErreur(tntSCMsgErrConnexion); break;
		case 2: tntB2CSuiviColisDisplayErreur(tntSCMsgErrBtInvalide); break;
		default: tntB2CSuiviColisDisplayErreur(tntSCMsgErrBtInvalide); break;
	}
}

$().ready(tntB2CSuiviColis);