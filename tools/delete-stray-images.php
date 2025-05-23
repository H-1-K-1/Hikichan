#!/usr/bin/php
<?php
/*
 *  delete-stray-images.php - Remove stray images and thumbnails not referenced in the unified posts table.
 *  This script iterates through every board and deletes any stray files in src/ or thumb/ that don't
 *  exist in the database.
 */

require dirname(__FILE__) . '/../inc/cli.php';

$boards = listBoards();

foreach ($boards as $board) {
    echo "/{$board['uri']}/... ";

    // Get all valid files and thumbs for this board from the unified posts table
    $query = query("SELECT `file`, `thumb` FROM ``posts`` WHERE `board` = " . $pdo->quote($board['uri']) . " AND `file` IS NOT NULL");
    $valid_src = array();
    $valid_thumb = array();

    while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
        $valid_src[] = $post['file'];
        $valid_thumb[] = $post['thumb'];
    }

    $files_src = array_map('basename', glob($board['dir'] . $config['dir']['img'] . '*'));
    $files_thumb = array_map('basename', glob($board['dir'] . $config['dir']['thumb'] . '*'));

    $stray_src = array_diff($files_src, $valid_src);
    $stray_thumb = array_diff($files_thumb, $valid_thumb);

    $stats = array(
        'deleted' => 0,
        'size' => 0
    );

    foreach ($stray_src as $src) {
        $filepath = $board['dir'] . $config['dir']['img'] . $src;
        if (file_exists($filepath)) {
            $stats['deleted']++;
            $stats['size'] += filesize($filepath);
            if (!unlink($filepath)) {
                $er = error_get_last();
                die("error: " . $er['message'] . "\n");
            }
        }
    }

    foreach ($stray_thumb as $thumb) {
        $filepath = $board['dir'] . $config['dir']['thumb'] . $thumb;
        if (file_exists($filepath)) {
            $stats['deleted']++;
            $stats['size'] += filesize($filepath);
            if (!unlink($filepath)) {
                $er = error_get_last();
                die("error: " . $er['message'] . "\n");
            }
        }
    }

    echo sprintf("deleted %s files (%s)\n", $stats['deleted'], format_bytes($stats['size']));
}