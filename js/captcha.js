// Add captcha auto-refresh setting below file selector in Options panel
$(function () {
    if (typeof localStorage.captchaAutoRefresh === 'undefined') {
        localStorage.captchaAutoRefresh = 'true'; // default: enabled
    }
    if (window.Options && Options.get_tab('general')) {
        // Wait for file-selector to add its option, then insert after
        setTimeout(function () {
            var $fileSelectorLabel = $("#file-drag-drop").closest('label');
            var $captchaLabel = $(
                "<label id='captcha-auto-refresh'><input type='checkbox'> Auto-refresh captcha after post</label>"
            );
            if ($fileSelectorLabel.length) {
                $fileSelectorLabel.after($captchaLabel);
            } else {
                // fallback: append to general tab
                Options.get_tab('general').content.append($captchaLabel[0]);
            }
            if (localStorage.captchaAutoRefresh === 'true') {
                $('#captcha-auto-refresh>input').prop('checked', true);
            }
            $('#captcha-auto-refresh>input').on('change', function () {
                localStorage.captchaAutoRefresh = $(this).is(':checked') ? 'true' : 'false';
            });
        }, 0);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    loadCaptcha();
});

// Load or refresh captcha image depending on setting
function loadCaptcha() {
    var captchas = document.querySelectorAll('.captcha_image');
    if (captchas.length === 0) return;
    var url;
    if (localStorage.captchaAutoRefresh === 'true') {
        url = configRoot + 'inc/captcha/captcha.php?refresh=1&' + Math.random();
    } else {
        url = configRoot + 'inc/captcha/captcha.php?t=' + Math.random();
    }
    captchas[0].src = url;
    captchas[0].onload = function () {
        for (var i = 1; i < captchas.length; i++) {
            captchas[i].src = url;
        }
    };
}

// Manual refresh always forces a new captcha
function refreshCaptcha() {
    var captchas = document.querySelectorAll('.captcha_image');
    if (captchas.length === 0) return;
    var url = configRoot + 'inc/captcha/captcha.php?refresh=1&' + Math.random();
    captchas[0].src = url;
    captchas[0].onload = function () {
        for (var i = 1; i < captchas.length; i++) {
            captchas[i].src = url;
        }
    };
}