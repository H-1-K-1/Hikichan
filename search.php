<?php
require 'inc/bootstrap.php';

if (!$config['search']['enable']) {
    die(_("Post search is disabled"));
}

$queries_per_minutes = $config['search']['queries_per_minutes'];
$queries_per_minutes_all = $config['search']['queries_per_minutes_all'];
$search_limit = $config['search']['search_limit'];

// Pagination setup
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$archive_page = isset($_GET['archive_page']) && is_numeric($_GET['archive_page']) && $_GET['archive_page'] > 0 ? (int)$_GET['archive_page'] : 1;
$results_per_page = 10;

//Is there a whitelist? Let's list those boards and if not, let's list everything.
if (isset($config['search']['boards'])) {
    $boards = $config['search']['boards'];
} else {
    $boards = listBoards(TRUE);
}

//Let's remove any disallowed boards from the above list (the blacklist)
if (isset($config['search']['disallowed_boards'])) {
    $boards = array_values(array_diff($boards, $config['search']['disallowed_boards']));
}

$body = Element('search_form.html', Array('boards' => $boards, 'board' => isset($_GET['board']) ? $_GET['board'] : false, 'search' => isset($_GET['search']) ? str_replace('"', '&quot;', utf8tohtml($_GET['search'])) : false));

if(isset($_GET['search']) && !empty($_GET['search']) && isset($_GET['board']) && in_array($_GET['board'], $boards)) {		
    $phrase = $_GET['search'];
    $_body = '';

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

    $filters = Array();

    function search_filters($m) {
        global $filters;
        $name = $m[2];
        $value = isset($m[4]) ? $m[4] : $m[3];

        if(!in_array($name, array('id', 'thread', 'subject', 'name'))) {
            // unknown filter
            return $m[0];
        }

        $filters[$name] = $value;

        return $m[1];
    }

    $phrase = trim(preg_replace_callback('/(^|\s)(\w+):("(.*)?"|[^\s]*)/', 'search_filters', $phrase));

    if(!preg_match('/[^*^\s]/', $phrase) && empty($filters)) {
        _syslog(LOG_WARNING, 'Query too broad.');
        $body .= '<p class="unimportant" style="text-align:center">(Query too broad.)</p>';
        echo Element($config['file_page_template'], Array(
            'config'=>$config,
            'title'=>'Search',
            'body'=>$body,
        ));
        exit;
    }

    // Escape escape character
    $phrase = str_replace('!', '!!', $phrase);

    // Remove SQL wildcard
    $phrase = str_replace('%', '!%', $phrase);

    // Use asterisk as wildcard to suit convention
    $phrase = str_replace('*', '%', $phrase);

    // Remove `, it's used by table prefix magic
    $phrase = str_replace('`', '!`', $phrase);

    $like = '';
    $match = Array();

    // Find exact phrases
    if(preg_match_all('/"(.+?)"/', $phrase, $m)) {
        foreach($m[1] as &$quote) {
            $phrase = str_replace("\"{$quote}\"", '', $phrase);
            $match[] = $pdo->quote($quote);
        }
    }

    $words = explode(' ', $phrase);
    foreach($words as &$word) {
        if(empty($word))
            continue;
        $match[] = $pdo->quote($word);
    }

    $like = '';
    foreach($match as &$phrase) {
        if(!empty($like))
            $like .= ' AND ';
        $phrase = preg_replace('/^\'(.+)\'$/', '\'%$1%\'', $phrase);
        $like .= '`body` LIKE ' . $phrase . ' ESCAPE \'!\'';
    }

    foreach($filters as $name => $value) {
        if(!empty($like))
            $like .= ' AND ';
        $like .= '`' . $name . '` = '. $pdo->quote($value);
    }

    $like = str_replace('%', '%%', $like);

    // Pagination for regular posts
    $offset = ($page - 1) * $results_per_page;
    $query = prepare("SELECT SQL_CALC_FOUND_ROWS * FROM ``posts`` WHERE `board` = :board AND " . $like . " ORDER BY `time` DESC LIMIT :limit OFFSET :offset");
    $query->bindValue(':board', $_GET['board']);
    $query->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
    $query->bindValue(':offset', $offset, PDO::PARAM_INT);
    $query->execute() or error(db_error($query));

    $total_query = query("SELECT FOUND_ROWS()");
    $total_results = $total_query->fetchColumn();
    $total_pages = ceil($total_results / $results_per_page);

    $temp = '';
    while($post = $query->fetch()) {
        if(!$post['thread']) {
            $po = new Thread($post);
        } else {
            $po = new Post($post);
        }
        $temp .= $po->build(true) . '<hr/>';
    }

    if(!empty($temp))
        $_body .= '<fieldset><legend>' .
                sprintf(ngettext('%d result in', '%d results in', $total_results), 
                $total_results) . ' <a href="/' .
                sprintf($config['board_path'], $board['uri']) . $config['file_index'] .
        '">' .
        sprintf($config['board_abbreviation'], $board['uri']) . ' - ' . $board['title'] .
        '</a></legend>' . $temp . '</fieldset>';

    // Pagination links for regular results
    if ($total_pages > 1) {
        $_body .= '<div class="pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                $_body .= " <strong>$i</strong> ";
            } else {
                $_body .= ' <a href="?search=' . urlencode($_GET['search']) . '&board=' . urlencode($_GET['board']) . '&page=' . $i . '">' . $i . '</a> ';
            }
        }
        $_body .= '</div>';
    }

    // --- ARCHIVE SEARCH ---
    // We'll search the 'snippet' field in archive_threads
    $archive_like = '';
    foreach($match as &$phrase) {
        if(!empty($archive_like))
            $archive_like .= ' AND ';
        $phrase = preg_replace('/^\'(.+)\'$/', '\'%$1%\'', $phrase);
        $archive_like .= '`snippet` LIKE ' . $phrase . ' ESCAPE \'!\'';
    }
    // No filters for archive, but you could add them if you want

    $archive_like = str_replace('%', '%%', $archive_like);

    // Pagination for archive results
    $archive_offset = ($archive_page - 1) * $results_per_page;
    $archive_query = prepare("SELECT SQL_CALC_FOUND_ROWS * FROM `archive_threads` WHERE `board_uri` = :board AND " . $archive_like . " ORDER BY `lifetime` DESC LIMIT :limit OFFSET :offset");
    $archive_query->bindValue(':board', $_GET['board']);
    $archive_query->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
    $archive_query->bindValue(':offset', $archive_offset, PDO::PARAM_INT);
    $archive_query->execute() or error(db_error($archive_query));

    $archive_total_query = query("SELECT FOUND_ROWS()");
    $archive_total_results = $archive_total_query->fetchColumn();
    $archive_total_pages = ceil($archive_total_results / $results_per_page);

    $archive_temp = '';
    while($arch = $archive_query->fetch()) {
        $archive_temp .= '<div class="archiveresult" style="display:flex;align-items:center;">';

        // Show thumbnail if available
        if (!empty($arch['first_image'])) {
			$thumb_url = sprintf(
				'%s/%s/%s/archive/%s/thumb/%s',
				rtrim($config['root'], '/'),
				trim($board['prefix'], '/'),
				urlencode($_GET['board']),
				$arch['path'],
				$arch['first_image']
			);
			$archive_temp .= '<img src="' . htmlspecialchars($thumb_url) . '" alt="thumb" style="max-width:100px;max-height:100px;margin-right:10px;">';
		}

        $archive_temp .= '<div>';
        $archive_temp .= '<b>Archived Thread No.' . htmlspecialchars($arch['board_id']) . '</b>: ';
        $archive_temp .= $arch['snippet']; // Output as raw HTML
       	$archive_url = sprintf(
			'%s/%s/%s/archive/%s/res/%d.html',
			rtrim($config['root'], '/'),
			trim($board['prefix'], '/'),
			urlencode($_GET['board']),
			$arch['path'],
			$arch['original_thread_id']
		);
        $archive_temp .= ' <a href="' . $archive_url . '">[View]</a>';
        $archive_temp .= '</div></div><hr/>';
    }

    if(!empty($archive_temp))
        $_body .= '<fieldset><legend>Archived Threads</legend>' . $archive_temp . '</fieldset>';

    // Pagination links for archive results
    if ($archive_total_pages > 1) {
        $_body .= '<div class="pagination">';
        for ($i = 1; $i <= $archive_total_pages; $i++) {
            if ($i == $archive_page) {
                $_body .= " <strong>$i</strong> ";
            } else {
                $_body .= ' <a href="?search=' . urlencode($_GET['search']) . '&board=' . urlencode($_GET['board']) . '&archive_page=' . $i . '">' . $i . '</a> ';
            }
        }
        $_body .= '</div>';
    }

    $body .= '<hr/>';
    if(!empty($_body))
        $body .= $_body;
    else
        $body .= '<p style="text-align:center" class="unimportant">('._('No results.').')</p>';
}

echo Element($config['file_page_template'], Array(
    'config'=>$config,
    'title'=>_('Search'),
    'boardlist'=>createBoardlist(),
    'body'=>'' . $body
));