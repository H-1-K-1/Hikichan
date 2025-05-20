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
$config['additional_javascript'][] = 'js/auto-reload.js';
$config['additional_javascript'][] = 'js/expand.js';
$config['additional_javascript'][] = 'js/expand-video.js';
$config['additional_javascript'][] = 'js/forced-anon.js';
$config['additional_javascript'][] = 'js/archive.js';
$config['additional_javascript'][] = 'js/upload-selection.js';
$config['additional_javascript'][] = 'js/mobile-style.js';
$config['additional_javascript'][] = 'js/download-original.js';
$config['additional_javascript'][] = 'js/expand-all-images.js';
$config['additional_javascript'][] = 'js/expand-too-long.js';
$config['additional_javascript'][] = 'js/live-index.js';
$config['additional_javascript'][] = 'js/captcha.js';
$config['additional_javascript'][] = 'js/ajax.js';
$config['additional_javascript'][] = 'js/show-own-posts.js';
$config['additional_javascript'][] = 'js/save-user_flag.js';
$config['additional_javascript'][] = 'js/auto-scroll.js';
$config['additional_javascript'][] = 'js/post-hover.js';
$config['additional_javascript'][] = 'js/quick-reply.js';
$config['additional_javascript'][] = 'js/show-backlinks.js';
$config['additional_javascript'][] = 'js/thread-stats.js';
$config['additional_javascript'][] = 'js/compact-boardlist.js';
$config['additional_javascript'][] = 'js/thread-watcher.js';
$config['additional_javascript'][] = 'js/youtube.js';
$config['additional_javascript'][] = 'js/file-selector.js';
$config['additional_javascript'][] = 'js/wPaint/8ch.js';
$config['additional_javascript'][] = 'js/wpaint.js';
$config['additional_javascript'][] = 'js/options/user-css.js';
$config['additional_javascript'][] = 'js/options/user-js.js';
$config['additional_javascript'][] = 'js/local-time.js';
$config['additional_javascript'][] = 'js/charcount.js';
$config['additional_javascript'][] = 'js/style-select.js';
$config['additional_javascript'][] = 'js/multi-image.js';
$config['additional_javascript'][] = 'js/post-menu.js';
$config['additional_javascript'][] = 'js/fix-report-delete-submit.js';
$config['additional_javascript'][] = 'js/post-filter.js';
$config['additional_javascript'][] = 'js/hide-images.js';
$config['additional_javascript'][] = 'js/image-hover.js';
$config['additional_javascript'][] = 'js/comment-toolbar.js';
$config['additional_javascript'][] = 'js/show-op.js';
$config['additional_javascript'][] = 'js/id-highlighter.js';
$config['additional_javascript'][] = 'js/infinite-scroll.js';
$config['additional_javascript'][] = 'js/id_colors.js';
$config['additional_javascript'][] = 'js/voice-record.js';
$config['additional_javascript'][] = 'js/treeview.js';
$config['additional_javascript'][] = 'js/inline.js';
$config['additional_javascript'][] = 'js/pepe-colored-quotes.js';
$config['additional_javascript'][] = 'js/inline-expanding-filename.js';
$config['additional_javascript'][] = 'js/mascots+spread.js';
$config['additional_javascript'][] = 'js/expand-audio.js';

// Custom stylesheets available for the user to choose. See the "stylesheets/" folder for a list of
	// available stylesheets (or create your own).
	$config['stylesheets'] = [
		// Default; there is no additional/custom stylesheet for this.
		'Yotsuba B' => '',
		'Yotsuba' => 'yotsuba.css',
		'Tomorrow' => 'tomorrow.css'
	];
	// $config['stylesheets']['Futaba'] = 'futaba.css';
	// $config['stylesheets']['Dark'] = 'dark.css';

?>
