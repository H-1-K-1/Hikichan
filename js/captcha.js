function loadCaptcha() {
    var captchas = document.querySelectorAll('.captcha_image');
    captchas.forEach(function(captcha) {
        captcha.src = configRoot + 'inc/captcha/captcha.php?' + Math.random();
    });
}
document.addEventListener('DOMContentLoaded', loadCaptcha);

// Refresh Captcha for all
function refreshCaptcha(){
    // Only refresh the first captcha image, then sync all others to its src
    var captchas = document.querySelectorAll('.captcha_image');
    if (captchas.length === 0) return;
    var url = configRoot + 'inc/captcha/captcha.php?refresh=1&' + Math.random();
    captchas[0].src = url;
    // When the first image loads, set all others to the same src
    captchas[0].onload = function() {
        for (var i = 1; i < captchas.length; i++) {
            captchas[i].src = url;
        }
    };
}