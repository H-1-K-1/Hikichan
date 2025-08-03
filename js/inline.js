$(document).ready(function() {
  var App = {
    cache: {},
    get: function(url, cb) {
      if (App.cache[url]) return cb(App.cache[url]);

      $.get(url, function(data) {
        var $page = $(data);
        App.cache[url] = $page;
        cb($page);
      });
    },
    options: {
      add: function(key, description, tab) {
        tab = tab || 'general';
        var checked = App.options.get(key);
        var $el = $(
          '<div>' +
            '<label>' +
              '<input type="checkbox">' +
              description +
            '</label>' +
          '</div>');

        $el
          .find('input')
          .prop('checked', checked)
          .on('change', App.options.check(key));

        window.Options.extend_tab(tab, $el);
      },
      get: function(key) {
        return localStorage[key] ? JSON.parse(localStorage[key]) : false;
      },
      check: function(key) {
        return function() {
          localStorage[key] = JSON.stringify(this.checked);
        };
      }
    }
  };

  var inline = function(e) {
    e.preventDefault();

    var $link = $(this);
    var $root = $link.closest('.post');
    var boardId, postId, matches;

    // Parse citation: >>90 or >>>/board/90
    if (matches = $link.text().match(/^>>(?:>\/([^\/]+)\/)?(\d+)$/)) {
      boardId = matches[2]; // Citation number (board_id)
    } else {
      return; // Invalid citation
    }

    // Extract post_id from href (#78) or onclick (highlightReply('78', event))
    var href = $link.attr('href') || '';
    var hrefMatch = href.match(/#(\d+)$/);
    var onclickMatch = $link.attr('onclick')?.match(/highlightReply\(['"](\d+)['"]/);
    if (hrefMatch) {
      postId = hrefMatch[1];
    } else if (onclickMatch) {
      postId = onclickMatch[1];
    } else {
      postId = boardId; // Fallback to board_id
    }

    // Determine board and thread
    var $board = $root.closest('[data-board]');
    var board = $board.data('board') || $('form[name="post"] input[name="board"]').val();
    var threadId = $root.closest('[id^=thread]').attr('id')?.replace('thread_', '') || '0';
    if (matches && matches[1]) {
      board = matches[1]; // Cross-board reference
    }

    var isBacklink = $link.hasClass('mentioned');
    var $node = isBacklink ? $root.find('> .intro') : $link;
    var linkId = 'inline_' + boardId;

    // Selector for the target post
    var selector = postId === threadId ? `#op_${postId}` : `#reply_${postId}`;

    // Check if inline post already exists
    var $clone = $root.find('#' + linkId);
    if ($clone.length) {
      $clone.remove();
      $(selector).show().next().show();
      return;
    }

    // Check if post exists on the current page
    var $target = $(`[data-board="${board}"] ${selector}, [data-board="${board}"] [data-board-id="${boardId}"]`);
    if ($target.length) {
      addInline({ id: linkId, isBacklink: isBacklink, node: $node }, $target);
      return;
    }

    // Fetch post via AJAX
    var url = $link.attr('href')?.replace(/#.*$/, '') || `/${board}/${threadId}.html`;
    var $loading = $('<div class="inline post">loading...</div>').attr('id', linkId).insertAfter($node);

    App.get(url, function($page) {
      $loading.remove();
      var $fetchedPost = $page.find(selector);
      if ($fetchedPost.length) {
        // Cache fetched post
        if ($(`[data-board="${board}"] #thread_${threadId}`).length) {
          $(`[data-board="${board}"] #thread_${threadId} .post.reply:first`)
            .before($fetchedPost.clone().hide().addClass('hidden'));
        } else {
          $page.find(`[id^="thread_"]`).hide().attr('data-cached', 'yes').prependTo('form[name="postcontrols"]');
        }
        addInline({ id: linkId, isBacklink: isBacklink, node: $node }, $fetchedPost);
      } else {
        $('<div class="inline post" style="color:red;">Post not found.</div>')
          .attr('id', linkId)
          .insertAfter($node);
      }
    });
  };

  var addInline = function(link, $target) {
    var $clone = $target.clone(true);
    if (link.isBacklink && App.options.get('hidePost')) {
      $target.hide().next().hide();
    }

    $clone.find('.inline').remove();
    $clone.attr({
      'class': 'inline post',
      id: link.id,
      style: null
    });
    $clone.insertAfter(link.node);
  };

  // Add options
  App.options.add('useInlining', 'Enable inlining');
  App.options.add('hidePost', 'Hide inlined backlinked posts');

  // Add CSS
  $('head').append(
    '<style>' +
      '.inline {' +
        'border: 1px dashed black;' +
        'white-space: normal;' +
        'overflow: auto;' +
      '}' +
    '</style>'
  );

  if (App.options.get('useInlining')) {
    var assignInline = function() {
      $('.body a[href*="' + location.pathname + '"]').not('[rel]').not('.toolong > a').add('.mentioned a')
        .attr('onclick', null)
        .off('click')
        .on('click', inline);
    };

    assignInline();
    $(document).on('new_post', assignInline);
  }
});