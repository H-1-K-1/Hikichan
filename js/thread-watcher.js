'use strict';

if (!localStorage.watchlist) {
	localStorage.watchlist = '[]';
}

var watchlist = {};

watchlist.getList = function () {
	return JSON.parse(localStorage.watchlist);
};

watchlist.setList = function (list) {
	localStorage.watchlist = JSON.stringify(list);
};

watchlist.threadInfo = function (sel) {
	var $thread = $(sel).closest('.thread');
	var board = $thread.data('board');
	var board_id = $thread.data('board-id');
	var subject = $thread.find('.subject').first().text().trim().substring(0, 20) || 'Thread';
	var href = $thread.find('.post_no a').last().attr('href') || location.href;
	return [board, subject, board_id, href];
};

watchlist.isWatched = function (info) {
	return watchlist.getList().some(function (e) {
		return e[0] === info[0] && e[2] === info[2];
	});
};

watchlist.add = function (info) {
	if (!watchlist.isWatched(info)) {
		var list = watchlist.getList();
		list.push(info);
		watchlist.setList(list);
	}
};

watchlist.remove = function (info) {
	var list = watchlist.getList().filter(function (e) {
		return !(e[0] === info[0] && e[2] === info[2]);
	});
	watchlist.setList(list);
};

watchlist.render = function (reset) {
	if (reset == null) reset = false;
	if (reset && $('#watchlist').length) $('#watchlist').remove();

	var threads = [];
	watchlist.getList().forEach(function (e, i) {
		threads.push(
			'<div class="watchlist-inner" id="watchlist-' + i + '">' +
			'<span>/' + e[0] + '/ - ' +
			'<a href="' + e[3] + '">' + e[1] + '</a>' +
			' (Post #' + e[2] + ')</span>' +
			'<a class="watchlist-remove" style="cursor:pointer;">X</a>' +
			'</div>'
		);
	});

	var menuStyle = getComputedStyle($('.boardlist')[0]);

	if ($('#watchlist').length) {
		$('#watchlist').html(
			'<div class="watchlist-controls">' +
			'<span><a id="clearList">[Clear List]</a></span>&nbsp;' +
			'<span><a id="clearGhosts">[Clear Ghosts]</a></span>' +
			'</div>' +
			threads.join('')
		);
	} else {
		var insertBeforeSelector =
			active_page === 'ukko' ? 'hr:first' :
				active_page === 'catalog' ? 'body>span:first' :
					'form[name="post"]';

		$('<div id="watchlist">' +
			'<div class="watchlist-controls">' +
			'<span><a id="clearList">[Clear List]</a></span>&nbsp;' +
			'<span><a id="clearGhosts">[Clear Ghosts]</a></span>' +
			'</div>' +
			threads.join('') +
			'</div>')
			.css("background-color", menuStyle.backgroundColor)
			.css("border", menuStyle.borderBottomWidth + " " + menuStyle.borderBottomStyle + " " + menuStyle.borderBottomColor)
			.insertBefore($(insertBeforeSelector));
	}
};

watchlist.exists = function (sel) {
	$.ajax($(sel).find('a').first().attr('href'), {
		type: 'HEAD',
		error: function () {
			var i = parseInt($(sel).attr('id').split('-')[1]);
			var list = watchlist.getList();
			list.splice(i, 1);
			watchlist.setList(list);
			watchlist.render();
		}
	});
};

watchlist.toggleButton = function (sel, info) {
	if (watchlist.isWatched(info)) {
		$(sel).text('[Unwatch Thread]');
	} else {
		$(sel).text('[Watch Thread]');
	}
};

$(document).ready(function () {
	if (!(active_page === 'thread' || active_page === 'index' || active_page === 'catalog' || active_page === 'ukko')) return;

	$('.boardlist').append(' <span>[ <a class="watchlist-toggle" href="#">watchlist</a> ]</span>');

	// Insert watch/unwatch links for each thread
	$('.thread').each(function () {
		var $intro = $(this).find('.intro').first();
		if (!$intro.find('.watchThread').length) {
			$('<a href="#" class="watchThread" style="margin-left:8px;">[Watch Thread]</a>').appendTo($intro);
		}
	});

	// Initialize watchlist buttons
	$('.watchThread').each(function () {
		var info = watchlist.threadInfo(this);
		watchlist.toggleButton(this, info);
	});

	// Click to toggle visibility of the list
	$(document).on('click', '.watchlist-toggle', function (e) {
		e.preventDefault();
		if (e.ctrlKey) watchlist.render(true);
		$('#watchlist').toggle();
	});

	// Click to add/remove thread
	$(document).on('click', '.watchThread', function (e) {
		e.preventDefault();
		var info = watchlist.threadInfo(this);
		if (watchlist.isWatched(info)) {
			watchlist.remove(info);
		} else {
			watchlist.add(info);
		}
		watchlist.toggleButton(this, info);
		watchlist.render();
	});

	// Click to remove thread from watchlist
	$(document).on('click', '.watchlist-remove', function () {
		var i = parseInt($(this).parent().attr('id').split('-')[1]);
		var list = watchlist.getList();
		list.splice(i, 1);
		watchlist.setList(list);
		watchlist.render();
		// Update all watch buttons
		$('.watchThread').each(function () {
			var info = watchlist.threadInfo(this);
			watchlist.toggleButton(this, info);
		});
	});

	// Clear all
	$(document).on('click', '#clearList', function () {
		watchlist.setList([]);
		watchlist.render();
		$('.watchThread').text('[Watch Thread]');
	});

	// Remove dead threads
	$(document).on('click', '#clearGhosts', function () {
		$('.watchlist-inner').each(function () {
			watchlist.exists(this);
		});
	});

	watchlist.render();
});
