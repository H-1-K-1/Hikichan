<?php
require 'inc/bootstrap.php';

// Fetch boards
$boards_query = query("SELECT uri, title, subtitle FROM boards ORDER BY uri") or error(db_error());
$boards_raw = $boards_query->fetchAll(PDO::FETCH_ASSOC);
$boards = [];

foreach ($boards_raw as $board) {
    $uri = $board['uri'];

    // Total posts
    $query = prepare("SELECT COUNT(*) FROM posts WHERE board = :board");
    $query->bindValue(':board', $uri);
    $query->execute();
    $total_posts = $query->fetchColumn();

    // Total threads (no parent thread)
    $query = prepare("SELECT COUNT(*) FROM posts WHERE board = :board AND thread IS NULL");
    $query->bindValue(':board', $uri);
    $query->execute();
    $total_threads = $query->fetchColumn();

    // Unique IPs
    $query = prepare("SELECT COUNT(DISTINCT ip) FROM posts WHERE board = :board");
    $query->bindValue(':board', $uri);
    $query->execute();
    $unique_posters = $query->fetchColumn();

    // Posts in last 24h
    $since = time() - 86400;
    $query = prepare("SELECT COUNT(*) FROM posts WHERE board = :board AND time >= :since");
    $query->bindValue(':board', $uri);
    $query->bindValue(':since', $since, PDO::PARAM_INT);
    $query->execute();
    $post_count_24h = $query->fetchColumn();
    $pph = round($post_count_24h / 24, 2);

    // Add to list
    $boards[] = [
        'uri' => $uri,
        'title' => $board['title'],
        'subtitle' => $board['subtitle'],
        'posts' => number_format($total_posts),
        'threads' => number_format($total_threads),
        'posters' => number_format($unique_posters),
        'pph' => $pph
    ];
}

// Render with template
$body = Element('boards_stats.html', ['boards' => $boards]);

echo Element($config['file_page_template'], [
    'config' => $config,
    'title' => _('Boards'),
    'boardlist' => createBoardlist(),
    'body' => $body
]);
