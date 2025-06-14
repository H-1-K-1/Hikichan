/*
 * adult-content.js
 * Adds a "Show adult images" option to the Options > General tab.
 * Hides images with the .adult-image class by default.
 */

$(document).ready(function () {
    'use strict';

    if (typeof config !== 'undefined' && config.enable_adult_posts === false) return;

    // Add option to Options > General tab
    if (window.Options && Options.get_tab('general')) {
        Options.extend_tab('general',
            '<span id="adult-content-toggle">' +
            '<label><input type="checkbox" id="show-adult-images"> Show adult images</label>' +
            '</span>'
        );

        var $checkbox = $('#show-adult-images');
        var show = localStorage.showAdultImages === 'true';
        $checkbox.prop('checked', show);
        setAdultVisibility(show);

        $checkbox.on('change', function () {
            var checked = $(this).is(':checked');
            localStorage.showAdultImages = checked ? 'true' : 'false';
            setAdultVisibility(checked);
        });
    } else {
        setAdultVisibility(true);
    }

    function setAdultVisibility(show) {
        $('.adult-image').each(function () {
            if (show) {
                $(this).removeClass('adult-image');
            } else {
                if (!$(this).hasClass('adult-image')) {
                    $(this).addClass('adult-image');
                }
            }
        });
    }
});