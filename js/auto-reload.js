/*
 * auto-reload.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/auto-reload.js 
 *
 * Brings AJAX to Tinyboard.
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 * Copyright (c) 2013 undido <firekid109@hotmail.com>
 * Copyright (c) 2014 Fredrick Brennan <admin@8chan.co>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/auto-reload.js';
 *
 */

var auto_reload_enabled = localStorage.auto_reload_enabled !== 'false'; // default is true
var countdown_interval;

$(document).ready(function(){
    // Add toggle option to Options tab
    if (window.Options && Options.get_tab('general')) {
        Options.extend_tab("general", "<label id='toggle-auto-reload'><input type='checkbox' /> "+_('Auto Thread Reload')+"</label>");
        $('#toggle-auto-reload>input').prop('checked', auto_reload_enabled);
        $('#toggle-auto-reload').on('change', function(e) {
            auto_reload_enabled = e.target.checked;
            localStorage.auto_reload_enabled = auto_reload_enabled ? 'true' : 'false';
            if (auto_reload_enabled) init_auto_reload();
            else stop_auto_update();
        });
    }

    // Only activate if auto-reload is enabled
    if (!auto_reload_enabled)
        return;

    init_auto_reload();
});

function init_auto_reload() {
    if ($('div.banner').length == 0 || $(".post.op").length != 1)
        return;

    var poll_interval_mindelay = 5000;
    var poll_interval_maxdelay = 600000;
    var poll_interval_errordelay = 30000;
    var poll_interval_delay = poll_interval_mindelay;
    var poll_current_time = poll_interval_delay;

    var end_of_page = false;
    var new_posts = 0;
    var first_new_post = null;
    var title = document.title;

    var update_title = function() {
        document.title = new_posts ? "(" + new_posts + ") " + title : title;
    };

    if (typeof add_title_collector != "undefined") {
        add_title_collector(() => new_posts);
    }

    var window_active = true;
    $(window).focus(function() {
        window_active = true;
        recheck_activated();
        poll_interval_delay = poll_interval_mindelay;
    });
    $(window).blur(function() {
        window_active = false;
    });

    $('.boardlist.bottom').prev().after("<span id='updater'><a href='#' id='update_thread' style='padding-left:10px'>["+_("Update")+"]</a> (<input type='checkbox' id='auto_update_status' checked> "+_("Auto")+") <span id='update_secs'></span></span>");

    $('#auto_update_status').click(function() {
        if($("#auto_update_status").is(':checked')) {
            auto_update(poll_interval_mindelay);
        } else {
            stop_auto_update();
            $('#update_secs').text("");
        }
    });

    var decrement_timer = function() {
        poll_current_time -= 1000;
        $('#update_secs').text(poll_current_time / 1000);

        if (poll_current_time <= 0) {
            poll(false);
        }
    }

    var auto_update = function(delay) {
        clearInterval(countdown_interval);
        poll_current_time = delay;
        countdown_interval = setInterval(decrement_timer, 1000);
        $('#update_secs').text(poll_current_time / 1000);
    }

    function stop_auto_update() {
        clearInterval(countdown_interval);
    }

    var recheck_activated = function() {
        if (new_posts && window_active &&
            $(window).scrollTop() + $(window).height() >=
            $('div.boardlist.bottom').position().top) {
            new_posts = 0;
        }
        update_title();
        first_new_post = null;
    };

    var epoch = (new Date).getTime();
    var epochold = epoch;

    var timeDiff = function (delay) {
        if ((epoch - epochold) > delay) {
            epochold = epoch = (new Date).getTime();
            return true;
        } else {
            epoch = (new Date).getTime();
            return false;
        }
    }

    var poll = function(manualUpdate) {
        stop_auto_update();
        $('#update_secs').text(_("Updating..."));

        $.ajax({
            url: document.location,
            success: function(data) {
                var loaded_posts = 0;
                var elementsToAppend = [];
                var elementsToTriggerNewpostEvent = [];
                $(data).find('div.post.reply').each(function() {
                    var id = $(this).attr('id');
                    if($('#' + id).length == 0) {
                        if (!new_posts) {
                            first_new_post = this;
                        }
                        new_posts++;
                        loaded_posts++;
                        elementsToAppend.push($(this));
                        elementsToAppend.push($('<br class="clear">'));
                        elementsToTriggerNewpostEvent.push(this);
                    }
                });

                $('div.post:last').next().after(elementsToAppend);
                recheck_activated();
                elementsToTriggerNewpostEvent.forEach(function(ele){
                    $(document).trigger('new_post', ele);
                });

                if ($('#auto_update_status').is(':checked')) {
                    if (loaded_posts == 0 && !manualUpdate) {
                        poll_interval_delay = Math.min(poll_interval_delay * 2, poll_interval_maxdelay);
                    } else {
                        poll_interval_delay = poll_interval_mindelay;
                    }
                    auto_update(poll_interval_delay);
                } else {
                    $('#update_secs').text(loaded_posts > 0
                        ? fmt(_("Thread updated with {0} new post(s)"), [loaded_posts])
                        : _("No new posts found"));
                }
            },
            error: function(xhr, status_text, error_text) {
                var msg = status_text === "error"
                    ? (error_text === "Not Found"
                        ? _("Thread deleted or pruned")
                        : "Error: " + error_text)
                    : status_text
                        ? _("Error: ") + status_text
                        : _("Unknown error");

                $('#update_secs').text(msg);

                if (error_text === "Not Found") {
                    $('#auto_update_status').prop('checked', false).prop('disabled', true);
                    return;
                }

                if ($('#auto_update_status').is(':checked')) {
                    poll_interval_delay = poll_interval_errordelay;
                    auto_update(poll_interval_delay);
                }
            }
        });

        return false;
    }

    $(window).scroll(function() {
        recheck_activated();

        if($(this).scrollTop() + $(this).height() <
            $('div.post:last').position().top + $('div.post:last').height()) {
            end_of_page = false;
            return;
        } else {
            if($("#auto_update_status").is(':checked') && timeDiff(poll_interval_mindelay)) {
                poll(true);
            }
            end_of_page = true;
        }
    });

    $('#update_thread').on('click', function() {
        poll(true);
        return false;
    });

    if($("#auto_update_status").is(':checked')) {
        auto_update(poll_interval_delay);
    }
}
