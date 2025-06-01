/*
 * show-backlinks.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/show-backlinks.js
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   // $config['additional_javascript'][] = 'js/post-hover'; (optional; must come first)
 *   $config['additional_javascript'][] = 'js/show-backlinks.js';
 *
 */

onReady(function () {
    let showBackLinks = function () {
        let $post = $(this);
        let reply_id = $post.attr('id').replace(/(^reply_)|(^op_)/, '');
        let board_id = $post.find('a.post_no:last').text(); // Extract board_id from the last .post_no link
        console.log('Processing post: post_id:', reply_id, 'board_id:', board_id); // Debug

        $post.find('div.body a:not([rel="nofollow"])').each(function () {
            let mentionedBoardId, mentionedPostId, $mentioned;
            let matches;

            // Parse citation: Match >>90 or >>>/board/90
            if (matches = $(this).text().match(/^>>(?:>\/([^\/]+)\/)?(\d+)$/)) {
                mentionedBoardId = matches[2]; // board_id from citation
                console.log('Matched citation: board_id:', mentionedBoardId, 'board:', matches[1] || 'current'); // Debug
            } else {
                console.log('No valid citation found for link:', $(this).text()); // Debug
                return;
            }

            // Extract mentioned post.id from href (e.g., #78) or onclick (e.g., highlightReply('78', event))
            let href = $(this).attr('href') || '';
            let hrefMatch = href.match(/#(\d+)$/);
            let onclickMatch = $(this).attr('onclick')?.match(/highlightReply\(['"](\d+)['"]/);
            if (hrefMatch) {
                mentionedPostId = hrefMatch[1]; // Extract post.id from href
                console.log('Extracted mentioned post_id from href:', mentionedPostId); // Debug
            } else if (onclickMatch) {
                mentionedPostId = onclickMatch[1]; // Extract post.id from onclick
                console.log('Extracted mentioned post_id from onclick:', mentionedPostId); // Debug
            } else {
                mentionedPostId = mentionedBoardId; // Fallback to board_id
                console.warn('Could not extract mentioned post_id, falling back to board_id:', mentionedBoardId); // Debug
            }

            // Find the mentioned post by post.id or board_id
            let board = matches[1] || $post.closest('[data-board]').data('board') || $('form[name="post"] input[name="board"]').val();
            if (!board) {
                console.error('No board found for citation:', $(this).text()); // Debug
                return;
            }

            $mentioned = $('[data-board="' + board + '"] div.post#reply_' + mentionedPostId + ', ' +
                '[data-board="' + board + '"] div.post#op_' + mentionedPostId + ', ' +
                '[data-board="' + board + '"] div.post[data-board-id="' + mentionedBoardId + '"]');
            if ($mentioned.length == 0) {
                console.log('No post found for post_id:', mentionedPostId, 'board_id:', mentionedBoardId, 'on board:', board); // Debug
                return;
            }

            // Add backlink to the mentioned post
            let $mentionedSpan = $mentioned.find('p.intro span.mentioned');
            if ($mentionedSpan.length == 0) {
                $mentionedSpan = $('<span class="mentioned unimportant"></span>').appendTo($mentioned.find('p.intro'));
                console.log('Created new span.mentioned for post:', mentionedPostId); // Debug
            }

            if ($mentionedSpan.find('a.mentioned-' + reply_id).length != 0) {
                console.log('Backlink already exists for post_id:', reply_id, 'in mentioned post:', mentionedPostId); // Debug
                return;
            }

            let link = $('<a class="mentioned-' + reply_id + '" onclick="highlightReply(\'' + reply_id + '\', event);" href="#' + reply_id + '">>>' + board_id + '</a>');
            link.appendTo($mentionedSpan);
            console.log('Added backlink to post_id:', reply_id, 'in mentioned post:', mentionedPostId); // Debug

            if (window.init_hover) {
                link.each(window.init_hover);
                console.log('Initialized hover for backlink:', board_id); // Debug
            }
        });
    };

    // Initialize for existing posts
    console.log('Initializing show-backlinks for posts'); // Debug
    $('div.post.reply').each(showBackLinks);
    $('div.post.op').each(showBackLinks);

    // Handle dynamic post loading
    $(document).on('new_post', function (e, post) {
        console.log('New post detected:', post); // Debug
        if ($(post).hasClass("op")) {
            $(post).find('div.post.reply').each(showBackLinks);
        } else {
            $(post).parent().find('div.post.reply').each(showBackLinks);
        }
    });
});