/**
 * Handle: avhamazonmetabox Version: 2.0 Deps: jquery Enqueue: true
 */
var avhamazonmetabox = function () {};
 
avhamazonmetabox.prototype = {
    options           : {},

    generateShortCode : function() {
        var content = this['options']['content'],
        	attrs = '';
        delete this['options']['content'];
        
        
        jQuery.each(this['options'], function(name, value){
            if (value != '') {            	
        		attrs += ' ' + name + '="' + value + '"';
            }
        });
        if (content != '') {
        	returnvalue = '[avhamazon' + attrs + ']' + content + '[/avhamazon]';
        } else {
        	returnvalue = '[avhamazon' + attrs + ']';
        }
        return returnvalue;
    },

    sendToEditor      : function(w,f) {
        this.options = {};
        var $this = this,
        	scname,
        	name,
        	collection;
    	switch (w) {
    		case 'asin':
        		collection = jQuery(f).find("input[id^=avhamazon_scasin]:not(input:radio),input[id^=avhamazon_scasin]:radio:checked,select[id^=avhamazon_scasin]");
        		break;
        	case 'wishlist':
        		collection = jQuery(f).find("input[id^=avhamazon_scwishlist]:not(input:radio),input[id^=avhamazon_scwishlist]:radio:checked,select[id^=avhamazon_scwishlist]");
        		break;
        }
        
        collection.each(function () {
        	scname = this.name.split('_');
            name = this.name.substring(scname[0].length+scname[1].length+2, this.name.length);
            $this['options'][name] = this.value;
        });
        
        send_to_editor(this.generateShortCode());
        return false;
    }
};

jQuery(document).ready(function($) {
	$('#avhamazon_submit_wishlist').click( avhamazon_metabox_submit_wishlist );
	$('#avhamazon_wishlist_show').find('input').keydown(avhamazon_metabox_wishlist);
	$('#avhamazon_wishlist_show').find('select').keydown(avhamazon_metabox_wishlist);
	$('#avhamazon_submit_asin').click( avhamazon_metabox_submit_asin );
	$('#avhamazon_asin_show').find('input').keydown(avhamazon_metabox_asin);
	$('#avhamazon_asin_show').find('select').keydown(avhamazon_metabox_asin);
	$('#avhamazon_wishlist_loading').hide();
	$('#avhamazon_asin_loading').hide();
	
	// Metabox tabs
	$('#avhamazon_tabs a').click(function(){
		var t = $(this).attr('href');
		$(this).parent().addClass('avhamazon-tabs-selected').siblings('li').removeClass('avhamazon-tabs-selected');
		$(this).parent().parent().siblings('.avhamazon-tabs-panel').hide();
		$(t).show();
		if ( '#avhamazon_tab_wishlist' == t )
			deleteUserSetting('avhamazonwish');
		else
			setUserSetting('avhamazonwish','asin');
		return false;
	});
	if ( getUserSetting('avhamazonwish') )
		$('#avhamazon_tabs a[href="#avhamazon_tab_asin"]').click();
	}
	);

function avhamazon_metabox_submit_wishlist() {
	var values= new Array,
		nonce;
	values[0] = jQuery('#avhamazon_scwishlist_wishlist').attr('value');
	values[1] = jQuery('#avhamazon_scwishlist_locale').attr('value');
	nonce     = jQuery('#avhamazon_ajax_nonce').attr('value');
	if (values[0]) {
		avhamazon_metabox_submit('wishlist','#avhamazon_wishlist_output','#avhamazon_wishlist_loading', values, nonce);
	} else {
		alert ('No Wish List ID given');
	}
	return false;
}

function avhamazon_metabox_wishlist( event ) {
	var charcode = (event.which) ? event.which : window.event.keyCode ;
	if ( charcode == 13 ) {
		return avhamazon_metabox_submit_wishlist( event );
	}
	return true;
}
		
function avhamazon_metabox_submit_asin( event ) {
	var values = new Array,
		nonce;
	event.preventDefault;
	values[0] = jQuery('#avhamazon_asin_nr').attr('value');
	values[1] = jQuery('#avhamazon_scasin_locale').attr('value');
	nonce     = jQuery('#avhamazon_ajax_nonce').attr('value');
	if (values[0]) {
		avhamazon_metabox_submit('asin','#avhamazon_asin_output','#avhamazon_asin_loading',values,nonce);
	} else {
		alert ('No ASIN given');
	}
	return false;
}
	
function avhamazon_metabox_asin( event ) {
	var charcode = (event.which) ? event.which : window.event.keyCode ;
	if ( charcode == 13 ) {
		return avhamazon_metabox_submit_asin( event );
	}
	return true;
}
	
function avhamazon_metabox_submit(avhamazon_action, avhamazon_output, avhamazon_loading, avhamazon_values, avhamazon_nonce) {
	jQuery(avhamazon_output).hide();
	jQuery(avhamazon_loading).show();
	jQuery('avhamazon_wishlist_loading_pic').css('visibility','visible');
	jQuery.post(
		ajaxurl,
		{ action: 'avhamazon_metabox', 'cookie': encodeURIComponent(document.cookie), 'avhamazon_mb_action': avhamazon_action, 'avhamazon_mb_values[]': avhamazon_values,'avhamazon_ajax_nonce': avhamazon_nonce }, 
		function(data, textStatus) {
			jQuery(avhamazon_loading).hide();
			jQuery(avhamazon_output).html(data.substr(0,data.length-1));
			jQuery(avhamazon_output).find('#avhamazon_sendtoeditor').each(function() {
				jQuery(this).click( avhamazon_metabox_sendtoeditor );
			});
			jQuery(avhamazon_output).show();
			}
	);
}
	
function avhamazon_metabox_sendtoeditor( ) {
	var tabid=this.name;
	return (avhamazon.sendToEditor(tabid, this.form));
};