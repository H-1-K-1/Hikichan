<?php

/*
 *  Instance Configuration
 *  ----------------------
 *  Edit this file and not config.php for imageboard configuration.
 *
 *  You can copy values from config.php (defaults) and paste them here.
 */



	// Database stuff
	$config['db']['type']		= 'mysql';
	$config['db']['server']		= 'localhost';
	$config['db']['user']		= '';
	$config['db']['password']	= '';
	$config['db']['database']	= '';
	
	//$config['root']				= '/';
	
	@include('inc/secrets.php');

$config['additional_javascript'][] = 'js/jquery.min.js';
$config['additional_javascript'][] = 'js/jquery-ui.custom.min.js';
$config['additional_javascript'][] = 'js/options.js';
$config['additional_javascript'][] = 'js/options/general.js';
$config['additional_javascript'][] = 'js/expand.js';
$config['additional_javascript'][] = 'js/forced-anon.js';
$config['additional_javascript'][] = 'js/archive.js';
$config['additional_javascript'][] = 'js/upload-selection.js';
$config['additional_javascript'][] = 'js/mobile-style.js';
$config['additional_javascript'][] = 'js/download-original.js';
$config['additional_javascript'][] = 'js/expand-all-images.js';
$config['additional_javascript'][] = 'js/gallery-view.js';
$config['additional_javascript'][] = 'js/expand-too-long.js';
$config['additional_javascript'][] = 'js/live-index.js';
$config['additional_javascript'][] = 'js/captcha.js';
$config['additional_javascript'][] = 'js/ajax.js';
$config['additional_javascript'][] = 'js/show-own-posts.js';
$config['additional_javascript'][] = 'js/ajax-post-controls.js';
$config['additional_javascript'][] = 'js/auto-reload.js';
$config['additional_javascript'][] = 'js/auto-scroll.js';
$config['additional_javascript'][] = 'js/post-hover.js';
$config['additional_javascript'][] = 'js/quick-reply.js';
$config['additional_javascript'][] = 'js/show-backlinks.js';
$config['additional_javascript'][] = 'js/thread-stats.js';
$config['additional_javascript'][] = 'js/webm-settings.js';
$config['additional_javascript'][] = 'js/show-backlinks.js';
$config['additional_javascript'][] = 'js/compact-boardlist.js';
$config['additional_javascript'][] = 'js/watch.js';
$config['additional_javascript'][] = 'js/youtube.js';
$config['additional_javascript'][] = 'js/file-selector.js';
$config['additional_javascript'][] = 'js/wPaint/8ch.js';
$config['additional_javascript'][] = 'js/wpaint.js';
$config['additional_javascript'][] = 'js/options/user-css.js';
$config['additional_javascript'][] = 'js/options/user-js.js';
$config['additional_javascript'][] = 'js/local-time.js';
$config['additional_javascript'][] = 'js/charcount.js';
$config['additional_javascript'][] = 'js/hide-threads.js';
$config['additional_javascript'][] = 'js/fix-report-delete-submit.js';
$config['additional_javascript'][] = 'js/style-select.js';
$config['additional_javascript'][] = 'js/favorites.js';
$config['additional_javascript'][] = 'js/options/fav.js';
$config['additional_javascript'][] = 'js/multi-image.js';
$config['additional_javascript'][] = 'js/post-menu.js';
$config['additional_javascript'][] = 'js/twemoji/twemoji.js';
$config['additional_javascript'][] = 'js/mod/recent-posts.js';
$config['additional_javascript'][] = 'js/mod/mod-snippet.js';
$config['additional_javascript'][] = 'js/mod/ban-list.js';
$config['additional_javascript'][] = 'js/hide-threads.js';
$config['additional_javascript'][] = 'js/hide-images.js';
$config['additional_javascript'][] = 'js/image-hover.js';
$config['additional_javascript'][] = 'js/comment-toolbar.js';
//$config['additional_javascript'][] = 'js/hide-form.js';

?>
