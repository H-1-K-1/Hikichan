<?php
require 'inc/bootstrap.php';
require 'inc/search_logic.php';

if (!$config['search']['enable']) {
    die(_("Post search is disabled"));
}

$queries_per_minutes = $config['search']['queries_per_minutes'];
$queries_per_minutes_all = $config['search']['queries_per_minutes_all'];
$results_per_page = 10;

$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$archive_page = isset($_GET['archive_page']) && is_numeric($_GET['archive_page']) && $_GET['archive_page'] > 0 ? (int)$_GET['archive_page'] : 1;
$search_archive = isset($_GET['search_archive']) && $_GET['search_archive'] == '1';

$boards = get_search_boards($config);

$body = Element('search_form.html', [
    'boards' => $boards,
    'board' => isset($_GET['board']) ? $_GET['board'] : false,
    'search' => isset($_GET['search']) ? str_replace('"', '&quot;', utf8tohtml($_GET['search'])) : false,
    'search_archive' => $search_archive
]);

if(isset($_GET['search']) && !empty($_GET['search']) && isset($_GET['board']) && in_array($_GET['board'], $boards)) {
    $phrase = $_GET['search'];
    $_body = '';

    // Rate limiting
    $query = prepare("SELECT COUNT(*) FROM ``search_queries`` WHERE `ip` = :ip AND `time` > :time");
    $query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
    $query->bindValue(':time', time() - ($queries_per_minutes[1] * 60));
    $query->execute() or error(db_error($query));
    if($query->fetchColumn() > $queries_per_minutes[0])
        error(_('Wait a while before searching again, please.'));

    $query = prepare("SELECT COUNT(*) FROM ``search_queries`` WHERE `time` > :time");
    $query->bindValue(':time', time() - ($queries_per_minutes_all[1] * 60));
    $query->execute() or error(db_error($query));
    if($query->fetchColumn() > $queries_per_minutes_all[0])
        error(_('Wait a while before searching again, please.'));

    $query = prepare("INSERT INTO ``search_queries`` VALUES (:ip, :time, :query)");
    $query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
    $query->bindValue(':time', time());
    $query->bindValue(':query', $phrase);
    $query->execute() or error(db_error($query));

    _syslog(LOG_NOTICE, 'Searched /' . $_GET['board'] . '/ for "' . $phrase . '"');

    // Cleanup search queries table
    $query = prepare("DELETE FROM ``search_queries`` WHERE `time` <= :time");
    $query->bindValue(':time', time() - ($queries_per_minutes_all[1] * 60));
    $query->execute() or error(db_error($query));

    openBoard($_GET['board']);

    $filters = [];
    $phrase = parse_search_filters($phrase, $filters);

    if(!preg_match('/[^*^\s]/', $phrase) && empty($filters)) {
        _syslog(LOG_WARNING, 'Query too broad.');
        $body .= '<p class="unimportant" style="text-align:center">(Query too broad.)</p>';
        echo Element($config['file_page_template'], [
            'config'=>$config,
            'title'=>'Search',
            'body'=>$body,
        ]);
        exit;
    }

    // Build LIKE clause for posts (change 'body' to 'url' if you want to search URLs)
    $like = build_like_clause($phrase, $filters, $pdo, 'body');

    // Pagination for regular posts
    $offset = ($page - 1) * $results_per_page;
    $query = prepare("SELECT SQL_CALC_FOUND_ROWS * FROM ``posts`` WHERE `board` = :board AND $like ORDER BY `time` DESC LIMIT :limit OFFSET :offset");
    $query->bindValue(':board', $_GET['board']);
    $query->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
    $query->bindValue(':offset', $offset, PDO::PARAM_INT);
    $query->execute() or error(db_error($query));

    $total_query = query("SELECT FOUND_ROWS()");
    $total_results = $total_query->fetchColumn();
    $total_pages = ceil($total_results / $results_per_page);

    $posts = [];
    while($post = $query->fetch()) {
        if(!$post['thread']) {
            $po = new Thread($post);
        } else {
            $po = new Post($post);
        }
        $posts[] = $po->build(true);
    }

    // --- ARCHIVE SEARCH (optional) ---
    $archive_results = [];
    $archive_total_results = 0;
    $archive_total_pages = 0;
    if ($search_archive) {
        $archive_like = '';
        $match = [];
        if(preg_match_all('/"(.+?)"/', $phrase, $m)) {
            foreach($m[1] as &$quote) {
                $match[] = $pdo->quote($quote);
            }
        }
        $words = explode(' ', $phrase);
        foreach($words as &$word) {
            if(empty($word)) continue;
            $match[] = $pdo->quote($word);
        }
        $archive_like = build_archive_like_clause($match);

        $archive_offset = ($archive_page - 1) * $results_per_page;
        $archive_query = prepare("SELECT SQL_CALC_FOUND_ROWS * FROM `archive_threads` WHERE `board_uri` = :board AND $archive_like ORDER BY `lifetime` DESC LIMIT :limit OFFSET :offset");
        $archive_query->bindValue(':board', $_GET['board']);
        $archive_query->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
        $archive_query->bindValue(':offset', $archive_offset, PDO::PARAM_INT);
        $archive_query->execute() or error(db_error($archive_query));

        $archive_total_query = query("SELECT FOUND_ROWS()");
        $archive_total_results = $archive_total_query->fetchColumn();
        $archive_total_pages = ceil($archive_total_results / $results_per_page);

        while($arch = $archive_query->fetch()) {
            $thumb_url = '';
            if (!empty($arch['first_image'])) {
                $thumb_url = sprintf(
                    '%s/%s/%s/archive/%s/thumb/%s',
                    rtrim($config['root'], '/'),
                    trim($board['prefix'], '/'),
                    urlencode($_GET['board']),
                    $arch['path'],
                    $arch['first_image']
                );
            }
            $archive_url = sprintf(
                '%s/%s/%s/archive/%s/res/%d.html',
                rtrim($config['root'], '/'),
                trim($board['prefix'], '/'),
                urlencode($_GET['board']),
                $arch['path'],
                $arch['original_thread_id']
            );
            $archive_results[] = [
                'thumb_url' => $thumb_url,
                'board_id' => $arch['board_id'],
                'snippet' => $arch['snippet'],
                'url' => $archive_url
            ];
        }
    }

    // Render results using a template
    $body .= Element('search_results.html', [
        'posts' => $posts,
        'total_results' => $total_results,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'search' => $_GET['search'],
        'board' => $_GET['board'],
        'archive_results' => $archive_results,
        'archive_total_results' => $archive_total_results,
        'archive_total_pages' => $archive_total_pages,
        'archive_current_page' => $archive_page,
        'search_archive' => $search_archive
    ]);
}

echo Element($config['file_page_template'], [
    'config'=>$config,
    'title'=>_('Search'),
    'boardlist'=>createBoardlist(),
    'body'=>'' . $body
]);