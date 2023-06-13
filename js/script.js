(function($){

	"use strict";

	// Yes / No
	$("#wthf-yes-no span").click(function(){

		// Getting value
		var value = parseInt($(this).attr("data-value"));
		var postID = $("#was-this-helpful").attr("data-post-id");

		// Cant send ajax
		if(getCookie("helpful_id_"+postID)){
			return false;
		}

		// Send Ajax
		$.post(ajaxurl, {action: "wthf_ajax", id: postID, val: value, nonce: nonce_wthf}).done(function(data){
			setCookie("helpful_id_"+postID, "1");
		});

		// Disable and show a thank message
		setTimeout(function(){
			$("#was-this-helpful").addClass("wthf-disabled");
		}, 20);

	});


	// Set Cookie
	function setCookie(name, value) {
		var expires = "";
		var date = new Date();
		date.setTime(date.getTime() + (365*24*60*60*1000));
		expires = "; expires=" + date.toUTCString();
		document.cookie = name + "=" + (value || "")  + expires + "; path=/";
	}


	// Get Cookie
	function getCookie(name) {
	    var nameEQ = name + "=";
	    var ca = document.cookie.split(';');
	    for(var i=0;i < ca.length;i++) {
	        var c = ca[i];
	        while (c.charAt(0)==' ') c = c.substring(1,c.length);
	        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	    }
	    return null;
	}
	

})(jQuery);