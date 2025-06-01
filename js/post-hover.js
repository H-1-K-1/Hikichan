/*
 * post-hover.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/post-hover.js
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 * Copyright (c) 2013 Macil Tech <maciltech@gmail.com>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/post-hover.js';
 *
 */

onReady(function () {
    let dontFetchAgain = [];

    initHover = function () {
        let link = $(this);
        let boardId; // The board_id from citation (e.g., >>90)
        let postId;  // The internal post id (for reply_{id} or op_{id}, e.g., 78)
        let matches;

        // Parse citation: Match >>90 or >>>/board/90
        if (link.is('[data-thread]')) {
            boardId = link.attr('data-thread');
            postId = boardId; // For threads, assume board_id and post_id are the same
            console.log('Found data-thread link with board_id:', boardId); // Debug
        } else if (matches = link.text().match(/^>>(?:>\/([^\/]+)\/)?(\d+)$/)) {
            boardId = matches[2]; // board_id from citation
            console.log('Matched citation with board_id:', boardId, 'board:', matches[1] || 'current'); // Debug
        } else {
            console.log('No valid citation found for link:', link.text()); // Debug
            return;
        }

        // Extract post.id from href (e.g., #78) or onclick (e.g., highlightReply('78', event))
        let href = link.attr('href') || '';
        let hrefMatch = href.match(/#(\d+)$/);
        let onclickMatch = link.attr('onclick')?.match(/highlightReply\(['"](\d+)['"]/);
        if (hrefMatch) {
            postId = hrefMatch[1]; // Extract post.id from href
            console.log('Extracted post_id from href:', postId); // Debug
        } else if (onclickMatch) {
            postId = onclickMatch[1]; // Extract post.id from onclick
            console.log('Extracted post_id from onclick:', postId); // Debug
        } else if (!postId) {
            postId = boardId; // Fallback to board_id if no post.id is found
            console.warn('Could not extract post_id, falling back to board_id:', boardId); // Debug
        }

        // Determine board
        let board = $(this);
        while (board.data('board') === undefined) {
            board = board.parent();
            if (!board.length) {
                console.error('No parent with data-board found for link:', link.text()); // Debug
                return;
            }
        }
        let threadId;
        if (link.is('[data-thread]')) {
            threadId = 0;
        } else {
            threadId = board.attr('id')?.replace("thread_", "") || 0;
            console.log('Thread ID:', threadId); // Debug
        }

        board = board.data('board');
        if (!board) {
            console.error('Board data not found for element:', board); // Debug
            return;
        }

        let parentBoard = board;
        if (link.is('[data-thread]')) {
            parentBoard = $('form[name="post"] input[name="board"]').val() || board;
            console.log('Using parentboard from form:', parentBoard); // Debug
        } else if (matches && matches[1] !== undefined) {
            board = matches[1]; // Cross-board reference
            console.log('Cross-board reference detected, board:', board); // Debug
        }

        let post = false;
        let hovering = false;
        let hoveredAt;

        link.hover(function (e) {
            hovering = true;
            hoveredAt = { 'x': e.pageX, 'y': e.pageY };

            let startHover = function (link) {
                console.log('Starting hover for board_id:', boardId, 'post_id:', postId, 'on board:', board); // Debug
                if (post.is(':visible') &&
                    post.offset().top >= $(window).scrollTop() &&
                    post.offset().top + post.height() <= $(window).scrollTop() + $(window).height()) {
                    // Post is in view
                    post.addClass('highlighted');
                    console.log('Post is in view and highlighted:', post); // Debug
                } else {
                    let newPost = post.clone();
                    newPost.find('>.reply, >br').remove();
                    newPost.find('span.mentioned').remove();
                    newPost.find('a.post_anchor').remove();

                    newPost
                        .attr('id', 'post-hover-' + boardId)
                        .attr('data-board', board)
                        .addClass('post-hover')
                        .css({
                            'border-style': 'solid',
                            'box-shadow': '1px 1px 1px #999',
                            'display': 'block',
                            'position': 'absolute',
                            'font-style': 'normal',
                            'z-index': '100'
                        })
                        .addClass('reply').addClass('post')
                        .insertAfter(link.parent());

                    console.log('Created hover post:', newPost); // Debug
                    link.trigger('mousemove');
                }
            };

            // Look up post by post_id (reply_{id}, op_{id}) or board_id as fallback
            post = $('[data-board="' + board + '"] div.post#reply_' + postId + ', ' +
                '[data-board="' + board + '"] div.post#op_' + postId + ', ' +
                '[data-board="' + board + '"] div.post[data-board-id="' + boardId + '"], ' +
                '[data-board="' + board + '"] div#thread_' + postId);
            console.log('Post lookup result:', post.length, 'for post_id:', postId, 'board_id:', boardId, 'on board:', board); // Debug

            if (post.length > 0) {
                startHover($(this));
            } else {
                let url = link.attr('href')?.replace(/#.*$/, '') || '';
                if (!url) {
                    console.error('No valid href found for link:', link); // Debug
                    return;
                }

                if ($.inArray(url, dontFetchAgain) != -1) {
                    console.log('URL already fetched, skipping:', url); // Debug
                    return;
                }
                dontFetchAgain.push(url);

                console.log('Fetching post via AJAX, URL:', url); // Debug
                $.ajax({
                    url: url,
                    context: document.body,
                    success: function (data) {
                        let myThreadId = $(data).find('div[id^="thread_"]').attr('id')?.replace("thread_", "") || 0;
                        console.log('AJAX success, thread ID:', myThreadId); // Debug

                        // Insert new posts
                        $(data).find('div.post.reply, div.post.op').each(function () {
                            let fetchedPostId = $(this).attr('id')?.replace(/^(reply_|op_)/, '');
                            let fetchedBoardId = $(this).attr('data-board-id') || fetchedPostId;
                            if (!fetchedPostId) return;

                            if (myThreadId == threadId && parentBoard == board) {
                                if ($('[data-board="' + board + '"] [data-board-id="' + fetchedBoardId + '"]').length == 0 &&
                                    $('[data-board="' + board + '"] #reply_' + fetchedPostId).length == 0 &&
                                    $('[data-board="' + board + '"] #op_' + fetchedPostId).length == 0) {
                                    $('[data-board="' + board + '"]#thread_' + threadId + " .post.reply:first").before($(this).hide().addClass('hidden'));
                                    console.log('Inserted hidden post with post_id:', fetchedPostId, 'board_id:', fetchedBoardId); // Debug
                                }
                            } else if ($('[data-board="' + board + '"]#thread_' + myThreadId).length > 0) {
                                if ($('[data-board="' + board + '"] [data-board-id="' + fetchedBoardId + '"]').length == 0 &&
                                    $('[data-board="' + board + '"] #reply_' + fetchedPostId).length == 0 &&
                                    $('[data-board="' + board + '"] #op_' + fetchedPostId).length == 0) {
                                    $('[data-board="' + board + '"]#thread_' + myThreadId + " .post.reply:first").before($(this).hide().addClass('hidden'));
                                    console.log('Inserted hidden post with post_id:', fetchedPostId, 'board_id:', fetchedBoardId); // Debug
                                }
                            } else {
                                $(data).find('div[id^="thread_"]').hide().attr('data-cached', 'yes').prependTo('form[name="postcontrols"]');
                                console.log('Cached new thread:', myThreadId); // Debug
                            }
                        });

                        // Re-check for post after AJAX
                        post = $('[data-board="' + board + '"] div.post#reply_' + postId + ', ' +
                            '[data-board="' + board + '"] div.post#op_' + postId + ', ' +


                            '[data-board="' + board + '"] div.post[data-board-id="' + boardId + '"], ' +
                            '[data-board="' + board + '"] div#thread_' + postId);
                        console.log('Post lookup after AJAX:', post.length, 'for post_id:', postId, 'board_id:', boardId); // Debug

                        if (post.length > 0) {
                            startHover(link);
                        } else {
                            console.warn('Post not found after AJAX for post_id:', postId, 'board_id:', boardId); // Debug
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX failed for URL:', url, 'Status:', status, 'Error:', error); // Debug
                    }
                });
            }
        }, function () {
            hovering = false;
            if (!post) {
                console.log('No post to clean up'); // Debug
                return;
            }

            post.removeClass('highlighted');
            if (post.hasClass('hidden') || post.data('cached') == 'yes') {
                post.css('display', 'none');
            }
            $('.post-hover').remove();
            console.log('Cleaned up hover for board_id:', boardId, 'post_id:', postId); // Debug
        }).mousemove(function (e) {
            if (!post) {
                console.log('No post for mousemove'); // Debug
                return;
            }

            let hover = $('#post-hover-' + boardId + '[data-board="' + board + '"]');
            if (hover.length == 0) {
                console.log('No hover element found for board_id:', boardId); // Debug
                return;
            }

            let scrollTop = $(window).scrollTop();
            if (link.is("[data-thread]")) {
                scrollTop = 0;
            }
            let epy = e.pageY;
            if (link.is("[data-thread]")) {
                epy -= $(window).scrollTop();
            }

            let top = (epy ? epy : hoveredAt['y']) - 10;

            if (epy < scrollTop + 15) {
                top = scrollTop;
            } else if (epy > scrollTop + $(window).height() - hover.height() - 15) {
                top = scrollTop + $(window).height() - hover.height() - 15;
            }

            hover.css('left', (e.pageX ? e.pageX : hoveredAt['x'])).css('top', top);
        });
    };

    // Initialize hover for all non-nofollow links
    console.log('Initializing post-hover for links'); // Debug
    $('div.body a:not([rel="nofollow"])').each(initHover);

    // Support for dynamic post loading (e.g., auto-reload.js)
    $(document).on('new_post', function (e, post) {
        console.log('New post detected, initializing hover:', post); // Debug
        $(post).find('div.body a:not([rel="nofollow"])').each(initHover);
    });
});