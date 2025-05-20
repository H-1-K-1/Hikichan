/* 
	Designed on kissu.moe for easily customizable css of the post reply bg 
	Just copy it who cares......
*/
$(document).ready(function(){

var unset_spread = false;
var default_spread = "";
var unset_mascot = false;
     var generateBannerOptionData = function (){	
		// add the tab for viewing the format settings
		if (window.Options && !Options.get_tab('Styling & Mascots')) {
			Options.add_tab('custom_styling', '500px', _('Styling & Mascots'));
			Options.extend_tab('custom_styling', '\
				Post Reply BG URL: <input type="text" id="spreadbg-input">\
				 <button onclick="" id="spreadbg-button">Set BG</button><br/>\
				Y Shift : <input class="axisfield" type="number" id="spreadbg-Y" value=0>\
X Shift : <input  class="axisfield" type="number" id="spreadbg-X" value=0><br/>\
				A decent sample idea https://kissu.moe/static/spreads/7e31-.png <br/>\
				Leave blank if unwanted and to disable\
                                <hr/>\
				Mascot URL: <input type="text" id="mascot-input">\
				<button onclick="" id="mascotimg-button">Set Mascot</button><br/>\
                                Y Shift : <input  class="axisfield" type="number" id="mascotimg-Y" value=0>\
 X Shift : <input  class="axisfield" type="number" id="mascotimg-X" value=0><br/>\
Set a mascot to use to decorate your page or leave blank if not wanted.<br/>\
				For example: https://kissu.moe/static/mascots/hibikimon.png \
			');

try{	
			$("#spreadbg-button").click(function(){
				localStorage.SpreadBG= $("#spreadbg-input").val();
				modifyBackgroundFromStorage();
			}); //add click handling to store data and follow up with modifyBackgroundFromStorage();
			if(typeof localStorage.SpreadBG == 'undefined' || localStorage.SpreadBG == 'undefined'){
				localStorage.SpreadBG = default_spread;
				unset_spread = true;
			}
			$("#spreadbg-input").val(localStorage.SpreadBG); // fill with local store contents if exists otherwise use a default

                        $("#spreadbg-Y").change(function(){ 
                                localStorage.SpreadBG_Y  = $(this).val();
                                modifyBackgroundFromStorage();
                        });
                        $("#spreadbg-Y").val(localStorage.SpreadBG_Y);


			$("#spreadbg-X").change(function(){
				localStorage.SpreadBG_X  = $(this).val();
				modifyBackgroundFromStorage();
			});
			$("#spreadbg-X").val(localStorage.SpreadBG_X);

                        $("#mascotimg-button").click(function(){
				localStorage.MascotIMG = $("#mascot-input").val();
				setupMascotIMG();
			});
			if(typeof localStorage.MascotIMG == 'undefined' || localStorage.MascotIMG == 'undefined'){
				unset_mascot = true;
			}
			$("#mascot-input").val(localStorage.MascotIMG);
                        $("#mascotimg-Y").change(function(){ 
                                localStorage.MascotIMG_Y  = $(this).val();
                                setupMascotIMG();
                        });
                        $("#mascotimg-Y").val(localStorage.MascotIMG_Y);

                        $("#mascotimg-X").change(function(){ 
                                localStorage.MascotIMG_X  = $(this).val();
                                setupMascotIMG();
                        });
                        $("#mascotimg-X").val(localStorage.MascotIMG_X);
}
catch(e){
	alert("Exception thrown, do you have localstorage support?");
}
		}		
     }

     var modifyBackgroundFromStorage = function (){
	if(unset_spread)
		$(".vichan").css("background-image", "url(" + default_spread + ")");
	else
		$(".vichan").css("background-image", "url(" + localStorage.SpreadBG+ ")");
	unset_spread = false;
	
	if(typeof localStorage.SpreadBG_X != 'undefined' && localStorage.SpreadBG_X != 'undefined')
		$(".vichan").css("background-position-x", localStorage.SpreadBG_X + "em");
        if(typeof localStorage.SpreadBG_Y != 'undefined' && localStorage.SpreadBG_Y != 'undefined')
                $(".vichan").css("background-position-y", localStorage.SpreadBG_Y + "em");

     }

    var setupMascotIMG = function(){
	if(!unset_mascot){
		if($("#mascot-img").length == 0){
       			$("<img id='mascot-img' src='" + localStorage.MascotIMG + "'/>").appendTo($("body"));
		}
		else{
			$("#mascot-img").attr("src", localStorage.MascotIMG); 
		}	
        if(typeof localStorage.MascotIMG_X != 'undefined' && localStorage.MascotIMG_X != 'undefined')
		$("#mascot-img").css("right", localStorage.MascotIMG_X + "em");
      	else
	       $("#mascot-img").css("right", "0");

	 if(typeof localStorage.MascotIMG_Y != 'undefined' && localStorage.MascotIMG_Y != 'undefined')
		$("#mascot-img").css("bottom", localStorage.MascotIMG_Y + "em");
	else
		$("#mascot-img").css("bottom", "0");

		$("#mascot-img").css("max-width", "50%");
		$("#mascot-img").css("max-height", "50%");
		$("#mascot-img").css("z-index", "1");
		$("#mascot-img").css("position", "fixed");
	}
unset_mascot=false;
    }

    		generateBannerOptionData();
    		modifyBackgroundFromStorage();
		setupMascotIMG();
});
