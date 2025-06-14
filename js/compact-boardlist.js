/*
 * compact-boardlist.js - a compact boardlist implementation making it
 *                        act more like a menubar
 * https://github.com/vichan-devel/Tinyboard/blob/master/js/compact-boardlist.js
 *
 * Released under the MIT license
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Modified to add watchlist toggle to mobile menu
 * Patched: Fixed external website detection (use board.href not board.uri)
 */
compact_boardlist = true;

//var device_type = 'mobile'; for testing purposes

do_boardlist = function () {
    var categories = [];
    var topbl = $('.boardlist:first');

    if (!topbl.length) {
        return;
    }

    // Extract categories and boards
    topbl.find('>.sub').each(function () {
        var cat = { name: $(this).data('description'), boards: [] };
        $(this).find('a').each(function () {
            var board = {
                name: $(this).prop('title'),
                uri: $(this).html(),
                href: $(this).prop('href')
            };
            cat.boards.push(board);
        });
        categories.push(cat);
    });

    topbl.addClass("compact-boardlist").html("");

    // Set device type class on body
    $('body').removeClass('device-mobile device-desktop').addClass('device-' + device_type);

    if (device_type == 'mobile') {
        // Mobile: Hamburger icon with fade-in overlay
        var hamburger = $("<a class='cb-hamburger' href='javascript:void(0)'><i class='fa fa-bars'></i></a>")
            .appendTo(topbl);

        hamburger.click(function (e) {
            e.preventDefault();
            var $this = $(this);
            var isOpen = $this.hasClass('cb-open');

            // Close any open menu
            $('.cb-overlay').fadeOut(200, function () {
                $(this).remove();
            });

            if (!isOpen) {
                // Create overlay
                var overlay = $("<div class='cb-overlay'></div>").appendTo('body');
                var menu = $("<div class='cb-mobile-menu'></div>").appendTo(overlay);
                var closeBtn = $("<a class='cb-close' href='javascript:void(0)'><i class='fa fa-times'></i></a>").appendTo(menu);

                // Add categories and boards
                for (var i in categories) {
                    var item = categories[i];
                    var menuItem;

                    if (item.name.match(/^icon_/)) {
                        var icon = item.name.replace(/^icon_/, '');
                        menuItem = $("<a class='cb-mobile-item cb-mobile-icon' href='" + item.boards[0].href + "'><img src='" + configRoot + "static/icons/" + icon + ".png'></a>");
                    }
                    else if (item.name.match(/^fa_/)) {
                        var icon = item.name.replace(/^fa_/, '');
                        menuItem = $("<a class='cb-mobile-item cb-mobile-fa' href='" + item.boards[0].href + "'><i class='fa fa-" + icon + "'></i>&nbsp;" + icon + "</a>");
                    }
                    else if (item.name.match(/^d_/)) {
                        var icon = item.name.replace(/^d_/, '');
                        menuItem = $("<a class='cb-mobile-item cb-mobile-cat' href='" + item.boards[0].href + "'>" + icon + "</a>");
                    }
                    else {
                        menuItem = $("<div class='cb-mobile-cat'>" + item.name + "</div>");
                        var subList = $("<div class='cb-mobile-sublist'></div>").appendTo(menuItem);
                        for (var j in item.boards) {
                            var board = item.boards[j];
                            var href, label, uriDisplay;

                            if (board.name) {
                                href = configRoot + boardFolder + board.uri + "/index.html";
                                label = board.name || board.uri;
                                uriDisplay = "/" + board.uri + "/";
                            } else {
                                href = board.href;
                                label = board.name || board.uri;
                                uriDisplay = "<i class='fa fa-globe'></i>";
                            }

                            $("<a href='" + href + "'>" + label + "<span class='cb-uri'>" + uriDisplay + "</span></a>")
                                .addClass("cb-mobile-subitem")
                                .appendTo(subList);
                        }
                    }
                    menuItem.appendTo(menu);
                }

                // Add watchlist toggle to mobile menu
                if (!menu.find('.cb-mobile-watchlist').length) {
                    var watchlistButton = $("<a class='cb-mobile-item cb-mobile-watchlist' href='javascript:void(0)'><i class='fa fa-eye'></i> watchlist</a>")
                        .appendTo(menu);
                    watchlistButton.click(function (e) {
                        e.preventDefault();
                        // CLOSE THE MOBILE MENU
                        overlay.fadeOut(200, function () { $(this).remove(); });
                        hamburger.removeClass('cb-open');
                        if (e.ctrlKey) {
                            watchlist.render(true);
                        }
                        $('#watchlist').css('display', $('#watchlist').css('display') !== 'none' ? 'none' : 'block');
                    });
                }

                // Fade in overlay
                overlay.hide().fadeIn(200);
                $this.addClass('cb-open');

                // Close button handler
                closeBtn.click(function (e) {
                    e.preventDefault();
                    overlay.fadeOut(200, function () {
                        $(this).remove();
                    });
                    $this.removeClass('cb-open');
                });

                // Close on overlay click
                overlay.click(function (e) {
                    if (e.target === this) {
                        overlay.fadeOut(200, function () {
                            $(this).remove();
                        });
                        $this.removeClass('cb-open');
                    }
                });
            }
        });
    }
    else if (device_type == 'desktop') {
        // Desktop: Click-based dropdown menu
        for (var i in categories) {
            var item = categories[i];

            if (item.name.match(/^icon_/)) {
                var icon = item.name.replace(/^icon_/, '');
                $("<a class='cb-item cb-icon' href='" + categories[i].boards[0].href + "'><img src='" + configRoot + "static/icons/" + icon + ".png'></a>")
                    .appendTo(topbl);
            }
            else if (item.name.match(/^fa_/)) {
                var icon = item.name.replace(/^fa_/, '');
                $('<a class="cb-item cb-fa" href="' + categories[i].boards[0].href + '"><i class="fa fa-' + icon + ' fa"></i>&nbsp;' + icon + '</a>')
                    .appendTo(topbl);
            }
            else if (item.name.match(/^d_/)) {
                var icon = item.name.replace(/^d_/, '');
                $('<a class="cb-item cb-cat" href="' + categories[i].boards[0].href + '">' + icon + '</a>')
                    .appendTo(topbl);
            }
            else {
                $("<a class='cb-item cb-cat' href='javascript:void(0)'>" + item.name + " <i class='fa fa-caret-down'></i></a>")
                    .appendTo(topbl)
                    .click(function (e) {
                        if ($(e.target).closest('.cb-menuitem').length) {
                            return; // Allow link clicks
                        }
                        e.preventDefault();
                        var $this = $(this);
                        var isOpen = $this.hasClass('cb-open');

                        topbl.find('.cb-menu').remove();
                        topbl.find('.cb-item.cb-cat').removeClass('cb-open');

                        if (!isOpen) {
                            var list = $("<div class='boardlist top cb-menu'></div>")
                                .css({
                                    "position": "absolute",
                                    "top": $this.outerHeight(true),
                                    "left": 0,
                                    "z-index": 1000
                                })
                                .appendTo($this);

                            for (var j in this.item.boards) {
                                var board = this.item.boards[j];
                                var href, label, uriDisplay;

                                if (board.name) {
                                    href = configRoot + boardFolder + board.uri + "/index.html";
                                    label = board.name || board.uri;
                                    uriDisplay = "/" + board.uri + "/";
                                } else {
                                    href = board.href;
                                    label = board.name || board.uri;
                                    uriDisplay = "<i class='fa fa-globe'></i>";
                                }

                                $("<a href='" + href + "'><span>" + label + "</span><span class='cb-uri'>" + uriDisplay + "</span></a>")
                                    .addClass("cb-menuitem")
                                    .appendTo(list);
                            }
                            $this.addClass('cb-open');
                        }
                    })[0].item = item;
            }
        }

        // Close dropdown when clicking outside
        $(document).click(function (e) {
            if (!$(e.target).closest('.cb-item.cb-cat').length && !$(e.target).closest('.cb-menu').length) {
                topbl.find('.cb-menu').remove();
                topbl.find('.cb-item.cb-cat').removeClass('cb-open');
            }
        });
    }

    do_boardlist = undefined;
};
