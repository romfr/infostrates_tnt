function moreinfos(infos) { 
    if( jQuery('#tnt_description_'+infos).is(":visible") ) {
        jQuery('#tnt_description_'+infos).hide();
        jQuery('.more_'+infos).show();
        jQuery('.less_'+infos).hide();
    } else {
        jQuery('.more_'+infos).hide();
        jQuery('.less_'+infos).show();
        jQuery('#tnt_description_'+infos).show();
    }
}

function radioCheck(){
    if ($('s_method_tnt_JD') && $("s_method_tnt_JD").checked){
        
        jQuery("#tnt_cp").hide();
        $("tnt_relais1").value = '';
        jQuery('#openRelais').click();
        $("tnt_pr").show();
        $("tnt_pr_choix").hide();
        
    } else {
       
       fetchVille($("city_url").value, 'villes');
       jQuery("#tnt_cp").show();
       
       if ($('s_method_tnt_JZ') && $("s_method_tnt_JZ").checked){
           if($("tnt_pr")) { $("tnt_pr").hide(); }
            $("tnt_relais1").value = '';
            $("tnt_pr_choix").innerHTML = '';
            $("tnt_pr_choix").hide();
           
           $("comp_entreprise").hide();
           $("comp_domicile").show();
       }
       if ($('s_method_tnt_J') && $("s_method_tnt_J").checked){
           if($("tnt_pr")) { $("tnt_pr").hide(); }
            $("tnt_relais1").value = '';
            $("tnt_pr_choix").innerHTML = '';
            $("tnt_pr_choix").hide();
           
           $("comp_domicile").hide();
           $("comp_entreprise").show();
       }
    }
}

function fetchPoint(url,area){
    var zipcode = escape($("relais_zipcode").value);
    new Ajax.Request(url,{
        method:'post',
        parameters:{zipcode:zipcode},
        onLoading:function(){
         $("loadingpointswait").show();
        },
        onComplete:function(){
        $("loadingpointswait").hide();
        },
        onSuccess:function(transport){
        $(area).update(transport.responseText);
        }
    });
}

function fetchVille(url,area){
    var street = escape($("street").value); 
    var zipcode = escape($("zipcode").value);
    var city = escape($("city").value);
    var company = escape($("company").value);
    
    new Ajax.Request(url,{
        method:'post',
        parameters:{street:street,zipcode:zipcode,city:city,company:company},
        onLoading:function(){
         $("loadingvilleswait").show();
        },
        onComplete:function(){
        $("loadingvilleswait").hide();
        },
        onSuccess:function(transport){
        $(area).update(transport.responseText);
        }
    });
}

function shippingMethodTnt(url){

    var shippingstring = new Array();
    var info_comp = '';
    
    if($("s_method_tnt_JD") && $("s_method_tnt_JD").checked){
        
        if( $("tnt_relais1").value != '' ) {
            var radioValue = $("tnt_relais1").value;
        }
        
        if(radioValue){
            shippingstring=radioValue.split("&&&");
        }
        else {
            alert ("Vous devez choisir un Relais Colis®");
            return false;
        }
    } else if( ($("s_method_tnt_AZ") && $("s_method_tnt_AZ").checked) 
            || ($("s_method_tnt_TZ") && $("s_method_tnt_TZ").checked)
            || ($("s_method_tnt_MZ") && $("s_method_tnt_MZ").checked)
            || ($("s_method_tnt_JZ") && $("s_method_tnt_JZ").checked)
            || ($("s_method_tnt_A") && $("s_method_tnt_A").checked)
            || ($("s_method_tnt_T") && $("s_method_tnt_T").checked)
            || ($("s_method_tnt_M") && $("s_method_tnt_M").checked)
            || ($("s_method_tnt_J") && $("s_method_tnt_J").checked) ){
        var radioGrp = document['forms']['co-shipping-method-form']['tnt_ville'];
            
        if (radioGrp){
            for(i=0; i < radioGrp.length; i++){
                if (radioGrp[i].checked == true) {
                    var radioValue = radioGrp[i].value;
                }
            }
        }
        
        if(!radioValue && $("tnt_ville1").checked){
            var radioValue = $("tnt_ville1").value;
        }
        
        if(radioValue){
            shippingstring=radioValue.split("&&&");
        }
        else {
            alert ("Vous devez choisir une ville de livraison");
            return false;
        }
        
        if($("s_method_tnt_JZ") && $("s_method_tnt_JZ").checked) {
            
            if( $("portable").value != '' ) {
                var regex = new RegExp(/^(06|07|08)[0-9]{8}/gi);

                if(regex.test($("portable").value)) {
                }
                else {
                    alert("Vérifiez le numéro de téléphone portable");
                    return false;
                }
            }
            
            info_comp = $("portable").value+"&&&"+$("code").value+"&&&"+$("etage").value+"&&&"+$("batiment").value;
        }
        if($("s_method_tnt_J") && $("s_method_tnt_J").checked) {
            info_comp = $("compl").value;
        }
    } else {
        if ($("street") != null && $("company") != null && $("zipcode") != null && $("city") != null) {
            shippingstring[0] = $("street").value;
            shippingstring[1] = $("company").value;
            shippingstring[2] = $("zipcode").value;
            shippingstring[3] = $("city").value;
        }
    }
    
    if( shippingstring.length != 0 ) {
        var street = shippingstring[0]; 
        var description = shippingstring[1];
        var postcode = shippingstring[2];
        var city = shippingstring[3];
        new Ajax.Request(url,{
                 method:'post',
                 parameters:{street:street,description:description,postcode:postcode,city:city,info_comp:info_comp}
                 });
    }

    if($("s_method_tnt_JD") && $("s_method_tnt_JD").checked){
        var newShippingAddress = jQuery('.name-firstname input').val()+' '+jQuery('.name-lastname input').val()+'<br />'+description+'<br />'+street+'<br />'+city+', '+postcode+'<br />France';
        jQuery('#shipping-progress-opcheckout dd address').html(newShippingAddress);
    }
    
    if ($("tnt_pr_choix") != null) { 
        $("tnt_pr_choix").innerHTML = '';
        $("tnt_pr_choix").hide();
    }
    
    shippingMethod.save();
}
