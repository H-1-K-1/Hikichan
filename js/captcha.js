document.addEventListener('DOMContentLoaded', function() {
    var captcha = document.getElementById('captcha_image');
    if (captcha) {
        captcha.src = configRoot + 'inc/captcha/captcha.php?' + Math.random();
    }
});

//Refresh Captcha
function refreshCaptcha(){
	var img = document.images['captcha_image'];
	img.src = img.src.substring(
		0,img.src.lastIndexOf("?")
		)+"?rand="+Math.random()*1000;
}