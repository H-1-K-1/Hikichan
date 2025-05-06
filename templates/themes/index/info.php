<?php
	$theme = Array();
	
	// Theme name
	$theme['name'] = 'Index';
	// Description (you can use Tinyboard markup here)
	$theme['description'] = 'Show a homepage';
	$theme['version'] = 'v2.0';
	
	// Theme configuration	
	$theme['config'] = Array();
	
	$theme['config'][] = Array(
		'title' => 'Icon',
		'name' => 'icon',
		'type' => 'text',
		'default' => $config['root'] . 'templates/themes/index/icon.png',
		'size' => 50
	);
	
	$theme['config'][] = Array(
		'title' => 'Title',
		'name' => 'title',
		'type' => 'text',
		'default' => 'Welcome to my Image Board',
		'size' => 50
	);
	
	$theme['config'][] = Array(
		'title' => 'Subtitle',
		'name' => 'subtitle',
		'type' => 'text',
		'default' => 'Your subtitle here',
		'size' => 50
	);

	$theme['config'][] = Array(
		'title' => 'Use boardlist HTML instead of auto-generated links',
		'name' => 'use_boardlist',
		'type' => 'checkbox',
		'default' => true
	);

	$theme['config'][] = Array(
		'title' => 'Boardlist',
		'name'  => 'boardlist',
		'type'  => 'textarea',
		'rows'  => 10, 
    	'cols'  => 50,
		'default' => '<strong>Global</strong><div class="global-link"><a href="/hikichan/b">Random</a></div>'
	);
	
	$theme['config'][] = Array(
		'title' => 'Description',
		'name' => 'description',
		'type' => 'textarea',
		'default' => 'Short description for your website.'
	);
	
	$theme['config'][] = Array(
		'title' => 'Featured Image',
		'name' => 'featured_image',
		'type' => 'text',
		'default' => $config['root'] . 'templates/themes/index/chan.png',
		'size' => 50
	);
	
	$theme['config'][] = Array(
		'title' => 'Quote',
		'name' => 'quote',
		'type' => 'textarea',
		'default' => '"Your quote here." - QUOTE'
	);
	
	$theme['config'][] = Array(
		'title' => 'Video embed',
		'name' => 'video_embed',
		'type' => 'text',
		'default' => 'https://www.youtube.com/embed/YbaTur4A1OU',
		'size' => 50
	);
	
	$theme['config'][] = Array(
		'title' => '# of recent news entries',
		'name' => 'no_recent',
		'type' => 'text',
		'default' => 5,
		'size' => 3,
		'comment' => '(number of recent news entries to display; "0" is infinite)'
	);
	
	$theme['config'][] = Array(
		'title' => 'Excluded boards in recent activity.',
		'name' => 'exclude_recent_activity',
		'type' => 'text',
		'comment' => '(space seperated)'
	);

	$theme['config'][] = Array(
		'title' => 'NSFW Boards',
		'name' => 'nsfw_boards',
		'type' => 'text',
		'default' => '',
		'comment' => 'Space-separated list of NSFW board URIs (e.g. "b h l")'
	);
	
	$theme['config'][] = Array(
		'title' => 'Excluded boards (boardlist)',
		'name' => 'exclude_board_list',
		'type' => 'text',
		'comment' => '(space seperated)'
	);
	
	$theme['config'][] = Array(
		'title' => '# of recent activity items',
		'name' => 'limit_activity',
		'type' => 'text',
		'default' => '30',
		'comment' => '(maximum recent posts with images to display)'
	);
	
	$theme['config'][] = Array(
		'title' => 'HTML file',
		'name' => 'html',
		'type' => 'text',
		'default' => 'index.html',
		'comment' => '(eg. "index.html")'
	);
	
	$theme['config'][] = Array(
		'title' => 'CSS file',
		'name' => 'css',
		'type' => 'text',
		'default' => 'index.css',
		'comment' => '(eg. "index.css")'
	);

	$theme['config'][] = Array(
		'title' => 'CSS stylesheet name',
		'name' => 'basecss',
		'type' => 'text',
		'default' => 'index.css',
		'comment' => '(eg. "index.css" - see templates/themes/index for details)'
	);
	
	// Unique function name for building everything
	$theme['build_function'] = 'index_build';
	$theme['install_callback'] = 'index_install';

	if (!function_exists('index_install')) {
		function index_install($settings) {
			if (!is_numeric($settings['limit_activity']) || $settings['limit_activity'] < 0)
				return Array(false, '<strong>' . utf8tohtml($settings['limit_activity']) . '</strong> is not a non-negative integer.');
			if (!is_numeric($settings['no_recent']) || $settings['no_recent'] < 0)
				return Array(false, '<strong>' . utf8tohtml($settings['no_recent']) . '</strong> is not a non-negative integer.');
		}
	}
	
