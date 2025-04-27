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
	$config['additional_javascript'][] = 'js/archive.js';
$config['additional_javascript'][] = 'js/mobile-style.js';
$config['additional_javascript'][] = 'js/download-original.js';
$config['additional_javascript'][] = 'js/expand-all-images.js';
$config['additional_javascript'][] = 'js/expand-too-long.js';
$config['additional_javascript'][] = 'js/ajax.js';
$config['additional_javascript'][] = 'js/post-hover.js';
$config['additional_javascript'][] = 'js/quick-reply.js';
$config['additional_javascript'][] = 'js/show-backlinks.js';
$config['additional_javascript'][] = 'js/show-own-posts.js';
$config['additional_javascript'][] = 'js/thread-stats.js';
$config['additional_javascript'][] = 'js/jquery-ui.custom.min.js';
$config['additional_javascript'][] = 'js/show-backlinks.js';
$config['additional_javascript'][] = 'js/wPaint/8ch.js';
$config['additional_javascript'][] = 'js/wpaint.js';
$config['additional_javascript'][] = 'js/upload-selection.js';
$config['additional_javascript'][] = 'js/options.js';
$config['additional_javascript'][] = 'js/local-time.js';
$config['additional_javascript'][] = 'js/charcount.js';
$config['additional_javascript'][] = 'js/hide-threads.js';
$config['additional_javascript'][] = 'js/fix-report-delete-submit.js';
$config['additional_javascript'][] = 'js/style-select.js';
$config['additional_javascript'][] = 'js/options/general.js';
$config['additional_javascript'][] = 'js/multi-image.js';
$config['additional_javascript'][] = 'js/post-menu.js';
?>
