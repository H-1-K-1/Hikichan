// Thanks to Khorne on #8chan at irc.rizon.net
// https://gitlab.com/aymous/8chan-watchlist

'use strict';
/* jshint globalstrict:true, quotmark:single */
/* jshint browser:true, jquery:true, devel:true, unused:true, undef:true */
/* global active_page:false, board_name:false, _ */

if (!localStorage.watchlist) {
    // Initialize watchlist as an empty array if undefined
    localStorage.watchlist = '[]';
}

var watchlist = {};

/**
 * Renders the watchlist container and populates it with watched threads.
 * @param {Boolean} [reset=false] If true, removes the existing watchlist before rendering.
 */
watchlist.render = function (reset) {
    /* jshint eqnull:true */
    if (reset == null) reset = false;
    /* jshint eqnull:false */
    if (reset && $('#watchlist').length) $('#watchlist').remove();
    var threads = [];
    // Parse watchlist and create a container for each thread
    JSON.parse(localStorage.watchlist).forEach(function (e, i) {
        // e = [board, threadName, postCount, threadUrl, boardId, postId]
        threads.push('<div class="watchlist-inner" id="watchlist-' + i + '">' +
            '<span>/' + e[0] + '/ - ' +
            '<a href="' + e[3] + '">' + e[1].replace('thread_', _('Thread #')) + ' (ID: ' + e[4] + ')</a>' +
            ' (' + e[2] + ') </span>' +
            '<a class="watchlist-remove" title="Remove from watchlist">X</a>' +
            '</div>');
    });
    if ($('#watchlist').length) {
        // Update existing watchlist
        $('#watchlist').children('.watchlist-inner').remove();
        $('#watchlist').append(threads.join(''));
    } else {
        // Create new watchlist
        var menuStyle = getComputedStyle($('.boardlist')[0]);
        $((active_page == 'ukko') ? 'hr:first' : (active_page == 'catalog') ? 'body>span:first' : 'form[name="post"]').before(
            $('<div id="watchlist">' +
                '<div class="watchlist-controls">' +
                '<span><a id="clearList">[' + _('Clear List') + ']</a></span> ' +
                '<span><a id="clearGhosts">[' + _('Clear Ghosts') + ']</a></span>' +
                '</div>' +
                threads.join('') +
                '</div>').css('background-color', menuStyle.backgroundColor)
                .css('border', menuStyle.borderBottomWidth + ' ' + menuStyle.borderBottomStyle + ' ' + menuStyle.borderBottomColor));
    }
    console.log('Rendered watchlist with', threads.length, 'threads'); // Debug
    return this;
};

/**
 * Adds a thread to the watchlist.
 * @param {Object|string} sel An unwrapped jQuery selector.
 */
watchlist.add = function (sel) {
    var threadName, threadInfo, boardName, postCount, threadLink, boardId, postId;

    boardName = $(sel).closest('.thread').data('board') || board_name;
    if (!boardName) {
        console.error('No board name found for selector:', sel); // Debug
        return this;
    }

    // Extract board_id from the last .post_no link
    boardId = $(sel).closest('.op').find('.post_no:last').text();
    if (!boardId) {
        console.warn('No board_id found, using post_id as fallback'); // Debug
    }

    if (active_page === 'thread') {
        // In thread page
        postId = $('.op').parent().attr('id').replace('thread_', '');
        if ($('.subject').length) {
            threadName = $('.subject').text().substring(0, 20);
        } else {
            threadName = 'thread_' + boardId; // Use board_id for display
        }
        postCount = $('.post').length;
        threadLink = location.href;
    } else if (active_page === 'index' || active_page === 'ukko') {
        // In index or ukko page
        postId = $(sel).closest('.thread').attr('id').replace('thread_', '');
        if ($(sel).parent().find('.subject').length) {
            threadName = $(sel).parent().find('.subject').text().substring(0, 20);
        } else {
            threadName = 'thread_' + boardId; // Use board_id for display
        }
        if ($(sel).closest('.op').find('.omitted').length) {
            postCount = parseInt($(sel).closest('.op').find('.omitted').text().split(' ')[0]) + 1;
        } else {
            postCount = $(sel).closest('.op').siblings('.post').length + 1;
        }
        threadLink = $(sel).siblings('a:not(.watchThread, .unwatchThread)').last().attr('href');
    } else {
        alert('Functionality not yet implemented for this type of page.');
        console.warn('Unsupported page type:', active_page); // Debug
        return this;
    }

    // Extract post.id from threadLink if possible
    var linkMatch = threadLink.match(/#(\d+)$/);
    if (linkMatch) {
        postId = linkMatch[1];
        console.log('Extracted post_id from threadLink:', postId); // Debug
    }

    if (!boardId) boardId = postId; // Fallback to post_id if board_id is missing
    threadInfo = [boardName, threadName, postCount, threadLink, boardId, postId];

    // Check if thread is already watched
    if (localStorage.watchlist.indexOf(JSON.stringify(threadInfo)) !== -1) {
        console.log('Thread already watched:', threadInfo); // Debug
        return this;
    }

    // Add to watchlist
    var _watchlist = JSON.parse(localStorage.watchlist);
    _watchlist.push(threadInfo);
    localStorage.watchlist = JSON.stringify(_watchlist);
    console.log('Added thread to watchlist:', threadInfo); // Debug

    // Toggle buttons: hide Watch, show Unwatch
    $(sel).hide().after('<a class="unwatchThread" href="#">[' + _('Unwatch Thread') + ']</a>');
    return this;
};

/**
 * Removes a thread from the watchlist.
 * @param {number} n The index to remove.
 */
watchlist.remove = function (n) {
    var _watchlist = JSON.parse(localStorage.watchlist);
    var removed = _watchlist.splice(n, 1)[0];
    localStorage.watchlist = JSON.stringify(_watchlist);
    console.log('Removed thread from watchlist:', removed); // Debug
    // Toggle buttons for matching threads
    $('.thread').each(function () {
        var threadId = $(this).attr('id').replace('thread_', '');
        if (threadId === removed[5]) { // Match postId
            $(this).find('.unwatchThread').remove();
            $(this).find('.watchThread').show();
        }
    });
    return this;
};

/**
 * Clears the watchlist.
 */
watchlist.clear = function () {
    localStorage.watchlist = '[]';
    $('.unwatchThread').remove();
    $('.watchThread').show();
    console.log('Cleared watchlist'); // Debug
    return this;
};

/**
 * Checks if watched threads exist, removing those that don't.
 * @param {Object|string} sel An unwrapped jQuery selector.
 */
watchlist.exists = function (sel) {
    var $link = $(sel).find('a').first(); // Get the thread link
    var url = $link.attr('href');
    if (!url) {
        console.error('No URL found for watchlist item:', sel); // Debug
        var index = parseInt($(sel).attr('id').split('-')[1]);
        watchlist.remove(index).render();
        return;
    }
    $.ajax(url, {
        type: 'HEAD',
        error: function (xhr, status, error) {
            var index = parseInt($(sel).attr('id').split('-')[1]);
            watchlist.remove(index).render();
            console.log('Removed ghost thread at index:', index, 'URL:', url, 'Error:', status, error); // Debug
        },
        success: function () {
            console.log('Thread still exists:', url); // Debug
        }
    });
};

$(document).ready(function () {
    if (!(active_page == 'thread' || active_page == 'index' || active_page == 'catalog' || active_page == 'ukko')) {
        console.log('Watchlist not supported on page:', active_page); // Debug
        return;
    }

    // Add watchlist toggle button
    $('.boardlist').append(' <span>[ <a class="watchlist-toggle" href="#">' + _('watchlist') + '</a> ]</span>');
    // Add Watch Thread button after OP post number
    $('.op>.intro>.post_no:odd').after('<a class="watchThread" href="#">[' + _('Watch Thread') + ']</a>');

    // Render watchlist (hidden)
    watchlist.render();

    // Initialize button states based on watchlist
    $('.thread').each(function () {
        var $thread = $(this);
        var postId = $thread.attr('id').replace('thread_', '');
        var _watchlist = JSON.parse(localStorage.watchlist);
        var isWatched = _watchlist.some(function (w) { return w[5] === postId; });
        if (isWatched) {
            $thread.find('.watchThread').hide().after('<a class="unwatchThread" href="#">[' + _('Unwatch Thread') + ']</a>');
            console.log('Set Unwatch Thread for post_id:', postId); // Debug
        }
    });

    // Toggle watchlist visibility
    $(document).on('click', '.watchlist-toggle', function (e) {
        e.preventDefault();
        if (e.ctrlKey) {
            watchlist.render(true);
            console.log('Reset watchlist'); // Debug
        }
        $('#watchlist').css('display', $('#watchlist').css('display') !== 'none' ? 'none' : 'block');
    });

    // Watch thread
    $(document).on('click', '.watchThread', function (e) {
        e.preventDefault();
        watchlist.add(this).render();
    });

    // Unwatch thread
    $(document).on('click', '.unwatchThread', function (e) {
        e.preventDefault();
        var $thread = $(this).closest('.thread');
        var postId = $thread.attr('id').replace('thread_', '');
        var _watchlist = JSON.parse(localStorage.watchlist);
        var index = _watchlist.findIndex(function (e) { return e[5] === postId; });
        if (index !== -1) {
            watchlist.remove(index).render();
            console.log('Unwatched thread with post_id:', postId); // Debug
        } else {
            console.warn('Thread not found in watchlist for post_id:', postId); // Debug
        }
    });

    // Remove watchlist item
    $(document).on('click', '.watchlist-remove', function () {
        var index = parseInt($(this).parent().attr('id').split('-')[1]);
        watchlist.remove(index).render();
        console.log('Clicked X button to remove index:', index); // Debug
    });

    // Clear watchlist
    $(document).on('click', '#clearList', function () {
        watchlist.clear().render();
        console.log('Cleared watchlist via Clear List'); // Debug
    });

    // Clear ghost threads
    $(document).on('click', '#clearGhosts', function () {
        $('.watchlist-inner').each(function () {
            watchlist.exists(this);
            console.log('Checking ghost thread:', $(this).find('a').first().attr('href')); // Debug
        });
    });
});