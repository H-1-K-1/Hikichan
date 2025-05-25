<?php

function get_search_boards($config) {
    if (isset($config['search']['boards'])) {
        $boards = $config['search']['boards'];
    } else {
        $boards = listBoards(TRUE);
    }
    if (isset($config['search']['disallowed_boards'])) {
        $boards = array_values(array_diff($boards, $config['search']['disallowed_boards']));
    }
    return $boards;
}

function parse_search_filters($phrase, &$filters) {
    return trim(preg_replace_callback('/(^|\s)(\w+):("(.*)?"|[^\s]*)/', function($m) use (&$filters) {
        $name = $m[2];
        $value = isset($m[4]) ? $m[4] : $m[3];
        if(!in_array($name, array('id', 'thread', 'subject', 'name'))) {
            return $m[0];
        }
        $filters[$name] = $value;
        return $m[1];
    }, $phrase));
}

function build_like_clause($phrase, $filters, $pdo, $field = 'body') {
    $phrase = str_replace('!', '!!', $phrase);
    $phrase = str_replace('%', '!%', $phrase);
    $phrase = str_replace('*', '%', $phrase);
    $phrase = str_replace('`', '!`', $phrase);

    $like = '';
    $match = array();

    if(preg_match_all('/"(.+?)"/', $phrase, $m)) {
        foreach($m[1] as &$quote) {
            $phrase = str_replace("\"{$quote}\"", '', $phrase);
            $match[] = $pdo->quote($quote);
        }
    }

    $words = explode(' ', $phrase);
    foreach($words as &$word) {
        if(empty($word)) continue;
        $match[] = $pdo->quote($word);
    }

    foreach($match as &$p) {
        if(!empty($like)) $like .= ' AND ';
        $p = preg_replace('/^\'(.+)\'$/', '\'%$1%\'', $p);
        $like .= '`' . $field . '` LIKE ' . $p . ' ESCAPE \'!\'';
    }

    foreach($filters as $name => $value) {
        if(!empty($like)) $like .= ' AND ';
        $like .= '`' . $name . '` = '. $pdo->quote($value);
    }

    $like = str_replace('%', '%%', $like);
    return $like;
}

function build_archive_like_clause($match) {
    $archive_like = '';
    foreach($match as &$phrase) {
        if(!empty($archive_like))
            $archive_like .= ' AND ';
        $phrase = preg_replace('/^\'(.+)\'$/', '\'%$1%\'', $phrase);
        $archive_like .= '`snippet` LIKE ' . $phrase . ' ESCAPE \'!\'';
    }
    return str_replace('%', '%%', $archive_like);
}