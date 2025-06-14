/*
 * expand-audio.js
 * Audio settings for always-visible <audio class="post-audio"> elements.
 * Usage:
 *   $config['additional_javascript'][] = 'js/expand-audio.js';
 */

(function () {
    'use strict';

    // Default settings
    var defaultSettings = {
        audiohover: false,
        audiovolume: 1.0
    };

    // Settings helpers
    function getSetting(name) {
        if (window.localStorage && localStorage[name] !== undefined)
            return JSON.parse(localStorage[name]);
        return defaultSettings[name];
    }
    function setSetting(name, value) {
        if (window.localStorage)
            localStorage[name] = JSON.stringify(value);
    }

    // Add settings tab if Options is available
    function addSettingsTab() {
        if (window.Options && Options.add_tab) {
            var tab = Options.add_tab("audio", "volume-up", "Audio");
            var html = '' +
                '<label><input type="checkbox" id="audiohover"> Play audio on hover</label><br>' +
                '<label>Default volume: <input type="range" id="audiovolume" min="0" max="1" step="0.01" style="width:4em;vertical-align:middle;"></label><br>';
            $(tab.content).append(html);

            $('#audiohover').prop('checked', getSetting('audiohover')).on('change', function () {
                setSetting('audiohover', this.checked);
            });
            $('#audiovolume').val(getSetting('audiovolume')).on('input change', function () {
                setSetting('audiovolume', parseFloat(this.value));
                applyAudioSettings();
            });
        }
    }

    // Show overlay message
    var overlayShown = false;
    function showAudioBlockedMessage() {
        if (overlayShown) return;
        overlayShown = true;
        var $overlay = $('<div id="audio-blocked-overlay" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;background:rgba(0,0,0,0.7);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.5em;text-align:center;">' +
            'Audio playback was blocked by your browser.<br>Click anywhere to enable audio on hover.' +
            '</div>');
        $('body').append($overlay);
        $(document).one('click', function () {
            $overlay.fadeOut(200, function () { $overlay.remove(); });
        });
    }

    // Apply settings to all .post-audio elements
    function applyAudioSettings(context) {
        var volume = getSetting('audiovolume');
        var hover = getSetting('audiohover');
        var $audios = $(context || document).find('audio.post-audio');
        $audios.each(function () {
            this.volume = volume;
            $(this).off('.audiohover');
            if (hover) {
                $(this).on('mouseenter.audiohover', function () {
                    var audio = this;
                    var playPromise = audio.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(function (err) {
                            showAudioBlockedMessage();
                        });
                    }
                }).on('mouseleave.audiohover', function () {
                    this.pause();
                });
            }
        });
    }

    // Initial setup
    $(function () {
        addSettingsTab();
        applyAudioSettings(document);

        // For dynamically added posts (e.g., auto-reload)
        $(document).on('new_post', function (e, post) {
            applyAudioSettings(post);
        });
    });

    // Also re-apply on tab change (if Options panel is used)
    if (window.Options) {
        $(document).on('options_tab_changed', function () {
            applyAudioSettings(document);
        });
    }
})();