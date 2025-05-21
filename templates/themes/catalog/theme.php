<?php
// theme.php — Catalog theme with pagination and working thumbnails

require 'info.php';

/** Fetch all board URIs from the database. */
function get_all_boards() {
    $boards = [];
    $query = query("SELECT uri FROM ``boards``") or error(db_error());
    while ($b = $query->fetch(PDO::FETCH_ASSOC)) {
        $boards[] = $b['uri'];
    }
    return $boards;
}

/**
 * Entry point for rebuilding the catalog.
 *
 * @param string     $action   'all', 'post-thread', 'post', etc.
 * @param array      $settings Theme settings.
 * @param string|bool $board   Current board URI (or false).
 */
function catalog_build($action, $settings, $board) {
    global $config;

    $boards = explode(' ', $settings['boards']);
    if (in_array('*', $boards)) {
        $boards = get_all_boards();
    }

    $build_board = function($bname) use ($settings) {
        $cat = new Catalog();
        $strategy = generation_strategy("sb_catalog", [$bname]);

        if ($strategy === 'delete') {
            @unlink($GLOBALS['config']['dir']['home'] . $bname . '/catalog.html');
            @unlink($GLOBALS['config']['dir']['home'] . $bname . '/index.rss');
        } elseif ($strategy === 'rebuild') {
            $cat->build($settings, $bname);
        }
    };

    if ($action === 'all') {
        foreach ($boards as $bname) {
            $build_board($bname);
        }
    } elseif (
        $action === 'post-thread'
        || ($settings['update_on_posts'] && in_array($action, ['post', 'post-delete']))
    ) {
        if ($board && in_array($board, $boards)) {
            $build_board($board);
        }
    }
}

class Catalog {
    /**
     * Build the catalog HTML (paginated).
     *
     * @param array  $settings   Theme settings.
     * @param string $board_name Board URI.
     * @param bool   $mod        Moderator preview?
     * @return string|null       HTML for mod, null when writing files.
     */
    public function build($settings, $board_name, $mod = false) {
        global $config, $board;

        // Ensure correct board context
        if (!isset($board) || $board['uri'] !== $board_name) {
            if (!openBoard($board_name)) {
                error(sprintf(_("Board %s doesn't exist"), $board_name));
            }
        }

        $recent_posts = [];
        $stats        = [];

        //
        // ─── FETCH THREADS ───────────────────────────────────────────────────────
        //
        $table = "posts_" . $board_name;
        $sql   = "
            SELECT
              *,
              `id` AS `thread_id`,
              (SELECT COUNT(`id`) FROM `{$table}` WHERE `thread` = `thread_id`) AS `reply_count`,
              (SELECT SUM(`num_files`) FROM `{$table}` WHERE `thread` = `thread_id` AND `num_files` IS NOT NULL) AS `image_count`,
              '{$board_name}' AS `board`
            FROM `{$table}`
            WHERE `thread` IS NULL
            ORDER BY `bump` DESC
        ";
        $query = query($sql) or error(db_error());

        while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
            // ─── Build thread link ───────────────────────────────────────────────
            if ($mod) {
                $post['link'] = $config['root']
                              . $config['file_mod'] . '?/'
                              . $board['dir'] . $config['dir']['res']
                              . link_for($post);
            } else {
                $post['link'] = $config['root']
                              . $board['dir'] . $config['dir']['res']
                              . link_for($post);
            }

            // ─── YouTube embed? ─────────────────────────────────────────────────
            if (!empty($post['embed'])
                && preg_match(
                    '/^https?:\/\/(\w+\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9\-_]{10,11})/',
                    $post['embed'], $m
                )
            ) {
                $post['youtube'] = $m[2];
            }

            // ─── Thumbnail / file logic ─────────────────────────────────────────
            if (!empty($post['files'])) {
                $files = json_decode($post['files']);
                $thumb = $files[0];

                // Deleted?
                if ($thumb->file === 'deleted') {
                    foreach ($files as $f) {
                        if ($f->file !== 'deleted') {
                            $thumb = $f;
                            break;
                        }
                    }
                    if ($thumb->file === 'deleted') {
                        $post['file'] = $config['image_deleted'];
                    } else {
                        $post['file'] = $config['uri_thumb'] . $thumb->thumb;
                    }
                }
                // Spoiler?
                elseif ($thumb->thumb === 'spoiler') {
                    $post['file'] = $config['root'] . $config['spoiler_image'];
                }
                // Normal
                else {
                    $post['file'] = $config['uri_thumb'] . $thumb->thumb;
                }
            } else {
                // No files
                $post['file'] = $config['root'] . $config['image_deleted'];
            }

            // Fallbacks
            $post['image_count'] = $post['image_count'] ?? 0;
            $post['pubdate']     = date('r', $post['time']);

            $recent_posts[] = $post;
        }

        //
        // ─── INCLUDE JS ────────────────────────────────────────────────────────
        //
        foreach (['js/jquery.min.js','js/jquery.mixitup.min.js','js/catalog.js','js/catalog-search.js'] as $js) {
            if (!in_array($js, $config['additional_javascript'])) {
                $config['additional_javascript'][] = $js;
            }
        }

        $base_link = $mod
                   ? $config['root'] . $config['file_mod'] . '?/' . $board['dir']
                   : $config['root'] . $board['dir'];

        //
        // ─── PAGINATION ─────────────────────────────────────────────────────────
        //
        $per_page    = $settings['items_per_page'];
        $total       = count($recent_posts);
        $total_pages = (int)ceil($total / $per_page);

        for ($page = 1; $page <= $total_pages; $page++) {
            $slice = array_slice($recent_posts, ($page-1)*$per_page, $per_page);

            $html = Element('themes/catalog/catalog.html', [
                'settings'     => $settings,
                'config'       => $config,
                'boardlist'    => createBoardlist($mod),
                'recent_posts' => $slice,
                'stats'        => $stats,
                'board'        => $board_name,
                'link'         => $base_link,
                'mod'          => $mod,
                'current_page' => $page,
                'total_pages'  => $total_pages,
            ]);

            if ($mod) {
                // Return only first page for moderator preview
                if ($page === 1) {
                    return $html;
                }
            } else {
                $filename = ($page === 1) ? 'catalog.html' : "catalog_page_{$page}.html";
                file_write($config['dir']['home'] . $board['dir'] . '/' . $filename, $html);
            }
        }

        return null;
    }
}
