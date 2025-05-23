<?php

class Archive {

    static public function archiveThread($thread_id) {
        global $config, $board;

        if(!$config['archive']['threads'])
            return;

        $thread_query = prepare(sprintf("SELECT `thread`, `subject`, `body_nomarkup`, `trip` FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
        $thread_query->bindValue(':id', $thread_id, PDO::PARAM_INT);
        $thread_query->execute() or error(db_error($thread_query));
        $thread_data = $thread_query->fetch(PDO::FETCH_ASSOC);

        if($thread_data['thread'] !== NULL)
            error($config['error']['invalidpost']);

        $thread_data['snippet_body'] = strtok($thread_data['body_nomarkup'], "\r\n");
        $thread_data['snippet_body'] = substr($thread_data['snippet_body'], 0, $config['archive']['snippet_len'] - strlen($thread_data['subject']));
        archive_list_markup($thread_data['snippet_body']);
        $thread_data['snippet'] = '<b>' . $thread_data['subject'] . '</b> ';
        $thread_data['snippet'] .= $thread_data['snippet_body'];

        $date_path = date('Y/m/d');
        $archive_path = $board['dir'] . $config['dir']['archive'] . $date_path . '/';

        @mkdir($archive_path . $config['dir']['res'], 0777, true);
        @mkdir($archive_path . $config['dir']['img'], 0777, true);
        @mkdir($archive_path . $config['dir']['thumb'], 0777, true);

        $query = prepare(sprintf("SELECT `id`,`thread`,`files`,`slug` FROM ``posts_%s`` WHERE `id` = :id OR `thread` = :id", $board['uri']));
        $query->bindValue(':id', $thread_id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        $file_list = array();

        while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
            if (!$post['thread']) {
                $thread_file_content = @file_get_contents($board['dir'] . $config['dir']['res'] . link_for($post));
                
                $thread_file_content = str_replace(
                    sprintf('src="'. $config['root'] . $config['board_path'], $board['uri']),
                    sprintf('src="'. $config['root'] . $config['board_path'] . $config['dir']['archive'] . $date_path . '/', $board['uri']),
                    $thread_file_content
                );
                $thread_file_content = str_replace(
                    sprintf('href="'. $config['root'] . $config['board_path'], $board['uri']),
                    sprintf('href="'. $config['root'] . $config['board_path'] . $config['dir']['archive'] . $date_path . '/', $board['uri']),
                    $thread_file_content
                );
                
                $thread_file_content = str_replace('Posting mode: Reply', 'Archived thread', $thread_file_content);
                $thread_file_content = preg_replace("/<form name=\"post\"(.*?)<\/form>/i", "", $thread_file_content);
    
                $thread_file_content = str_replace(sprintf('href="/' . $config['board_path'] . $config['dir']['archive'] . $config['dir']['archive'], $board['uri']), sprintf('href="/' . $config['board_path'] . $config['dir']['archive'], $board['uri']), $thread_file_content);
    
                $thread_file_content = preg_replace("/<form(.*?)>/i", "", $thread_file_content);
                $thread_file_content = preg_replace("/<\/form>/i", "", $thread_file_content);
                $thread_file_content = preg_replace("/<input (.*?)>/i", "", $thread_file_content);
    
                $thread_file_content = preg_replace("/<div id=\"report\-fields\"(.*?)<\/div>/i", "", $thread_file_content);
                $thread_file_content = preg_replace("/<div id=\"thread\-interactions\"(.*?)<\/div>/i", "", $thread_file_content);
                $thread_file_content = preg_replace("/<a id=\"unimportant\" href=\"\/[a-zA-Z0-9]+\/archive\/catalog(.*?)<\/a>/i", "", $thread_file_content);
                $thread_file_content = preg_replace("/\b\/(archive)(\/featured\/)/i", "$2", $thread_file_content);
    
                @file_put_contents($archive_path . $config['dir']['res'] . sprintf($config['file_page'], $thread_id), $thread_file_content, LOCK_EX);
            }
    
            $json_file_content = @file_get_contents($board['dir'] . $config['dir']['res'] . sprintf('%d.json', $thread_id));
            $json_file_content = str_replace(substr($board['dir'], 0, -1) . '\/' . substr($config['dir']['res'], 0, -1), substr($board['dir'], 0, -1) . '\/' . substr($config['dir']['archive'], 0, -1) . '\/' . substr($config['dir']['res'], 0, -1), $json_file_content);
            @file_put_contents($board['dir'] . $config['dir']['archive']. $date_path . '/' . $config['dir']['res'] .  sprintf('%d.json', $thread_id), $json_file_content, LOCK_EX);
    
            if ($post['files']) {
                foreach (json_decode($post['files']) as $i => $f) {
                    if ($f->file !== 'deleted') {
                        @copy($board['dir'] . $config['dir']['img'] . $f->file, $archive_path . $config['dir']['img'] . $f->file);
                        @copy($board['dir'] . $config['dir']['thumb'] . $f->thumb, $archive_path . $config['dir']['thumb'] . $f->thumb);
    
                        $file_list[] = $f;
                    }
                }
            }
        }
        $first_image = null;
        foreach ($file_list as $file) {
            if (isset($file->thumb) && $file->thumb !== 'deleted') {
                $first_image = $file->thumb;
                break;
            }
        }

        $query = prepare("INSERT INTO `archive_threads` (`board_uri`, `original_thread_id`, `snippet`, `lifetime`, `files`, `featured`, `mod_archived`, `votes`, `path`, `first_image`) VALUES (:board_uri, :original_thread_id, :snippet, :lifetime, :files, 0, 0, 0, :path, :first_image)");
        $query->bindValue(':board_uri', $board['uri'], PDO::PARAM_STR);
        $query->bindValue(':original_thread_id', $thread_id, PDO::PARAM_INT);
        $query->bindValue(':snippet', $thread_data['snippet'], PDO::PARAM_STR);
        $query->bindValue(':lifetime', time(), PDO::PARAM_INT);
        $query->bindValue(':files', json_encode($file_list));
        $query->bindValue(':path', $date_path, PDO::PARAM_STR);
        $query->bindValue(':first_image', $first_image, PDO::PARAM_STR);
        $query->execute() or error(db_error($query));

        global $pdo;
        $archive_id = $pdo->lastInsertId();

        if(in_array($thread_data['trip'], $config['archive']['auto_feature_trips']))
            self::featureThread($archive_id, $board['uri']);

        if(!$config['archive']['cron_job']['purge'])
            self::purgeArchive($board['uri']);

        self::buildArchiveIndex($board['uri']);

        return true;
    }

    static public function purgeArchive($board_uri) {
        global $config, $board;

        if(!$config['archive']['lifetime'])
            return;

        if (empty($board) || $board['uri'] !== $board_uri) {
            if (!openBoard($board_uri)) {
                error_log("purgeArchive: Failed to open board: " . $board_uri);
                return 0;
            }
        }

        $query = prepare("SELECT `id`, `original_thread_id`, `files`, `path` FROM `archive_threads` WHERE `board_uri` = :board_uri AND `lifetime` < :lifetime AND `featured` = 0 AND `mod_archived` = 0");
        $query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
        $query->bindValue(':lifetime', strtotime("-" . $config['archive']['lifetime']), PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        while($thread = $query->fetch(PDO::FETCH_ASSOC)) {
            $archive_path = $board['dir'] . $config['dir']['archive'] . $thread['path'] . '/';

            foreach (json_decode($thread['files']) as $f) {
                @unlink($archive_path . $config['dir']['img'] . $f->file);
                @unlink($archive_path . $config['dir']['thumb'] . $f->thumb);
            }
            @unlink($archive_path . $config['dir']['res'] . sprintf($config['file_page'], $thread['original_thread_id']));

            $del_query = prepare("DELETE FROM `archive_votes` WHERE `board` = :board AND `thread_id` = :thread_id");
            $del_query->bindValue(':board', $board_uri, PDO::PARAM_STR);
            $del_query->bindValue(':thread_id', $thread['original_thread_id'], PDO::PARAM_INT);
            $del_query->execute() or error(db_error($del_query));
        }

        if($query->rowCount() != 0) {
            $delete_query = prepare("DELETE FROM `archive_threads` WHERE `board_uri` = :board_uri AND `lifetime` < :lifetime AND `featured` = 0 AND `mod_archived` = 0");
            $delete_query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
            $delete_query->bindValue(':lifetime', strtotime("-" . $config['archive']['lifetime']), PDO::PARAM_INT);
            $delete_query->execute() or error(db_error($delete_query));

            modLog(sprintf("Purged %d archived threads from board %s due to expiration date", $delete_query->rowCount(), $board_uri));
            return $delete_query->rowCount();
        }
        return 0;
    }

    static public function featureThread($archive_entry_id, $board_uri, $mod_archive = false) {
        global $config, $mod;

        global $board;
        if (empty($board) || $board['uri'] !== $board_uri) {
            if (!openBoard($board_uri)) {
                error_log("featureThread: Failed to open board: " . $board_uri);
                return false;
            }
        }

        if(!$mod_archive && !$config['feature']['threads']) return;
        if($mod_archive && !$config['mod_archive']['threads']) return;

        $query_sql = "SELECT `original_thread_id`, `files`, `path` FROM `archive_threads` WHERE `id` = :id AND `board_uri` = :board_uri AND " . ($mod_archive?"`mod_archived`":"`featured`") . " = 0";
        $query = prepare($query_sql);
        $query->bindValue(':id', $archive_entry_id, PDO::PARAM_INT);
        $query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
        $query->execute() or error(db_error($query));

        if(!$thread = $query->fetch(PDO::FETCH_ASSOC))
            error($config['error']['invalidpost']);

        $original_thread_id = $thread['original_thread_id'];

        $featured_path = $board['dir'] . ($mod_archive ? $config['dir']['mod_archive'] : $config['dir']['featured']) . $thread['path'] . '/';

        @mkdir($featured_path . $config['dir']['res'], 0777, true);
        @mkdir($featured_path . $config['dir']['img'], 0777, true);
        @mkdir($featured_path . $config['dir']['thumb'], 0777, true);

        $thread_file_content = @file_get_contents($board['dir'] . $config['dir']['archive'] . $thread['path'] . '/' . $config['dir']['res'] . sprintf($config['file_page'], $original_thread_id));
        
        $thread_file_content = str_replace(sprintf('src="/' . $config['board_path'] . $config['dir']['archive'], $board_uri), sprintf('src="/' . $config['board_path'] . ($mod_archive?$config['dir']['mod_archive']:$config['dir']['featured']) . $thread['path'], $board_uri), $thread_file_content);
        $thread_file_content = str_replace(sprintf('href="/' . $config['board_path'] . $config['dir']['archive'], $board_uri), sprintf('href="/' . $config['board_path'] . ($mod_archive?$config['dir']['mod_archive']:$config['dir']['featured']) . $thread['path'], $board_uri), $thread_file_content);
        $thread_file_content = str_replace('Archived thread', 'Featured thread', $thread_file_content);

        @file_put_contents($featured_path . $config['dir']['res'] . sprintf($config['file_page'], $original_thread_id), $thread_file_content, LOCK_EX);

        foreach (json_decode($thread['files']) as $f) {
            $source_img = $board['dir'] . $config['dir']['archive'] . $thread['path'] . '/' . $config['dir']['img'] . $f->file;
            $dest_img = $featured_path . $config['dir']['img'] . $f->file;
            $source_thumb = $board['dir'] . $config['dir']['archive'] . $thread['path'] . '/' . $config['dir']['thumb'] . $f->thumb;
            $dest_thumb = $featured_path . $config['dir']['thumb'] . $f->thumb;

            error_log("Copying Image: $source_img to $dest_img");
            error_log("Copying Thumbnail: $source_thumb to $dest_thumb");

            if (!@copy($source_img, $dest_img)) {
                error_log("Failed to copy image: $source_img");
            }
            if (!@copy($source_thumb, $dest_thumb)) {
                error_log("Failed to copy thumbnail: $source_thumb");
            }
        }

        $update_query = prepare("UPDATE `archive_threads` SET " . ($mod_archive?"`mod_archived`":"`featured`") . " = 1 WHERE `id` = :id AND `board_uri` = :board_uri");
        $update_query->bindValue(':id', $archive_entry_id, PDO::PARAM_INT);
        $update_query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
        $update_query->execute() or error(db_error($update_query));

        modLog(sprintf("Added thread #%d (original: %d) to " . ($mod_archive?"mod archive":"featured threads") . " for board %s", $archive_entry_id, $original_thread_id, $board_uri));

        self::buildFeaturedIndex($board_uri);
        self::buildArchiveIndex($board_uri);

        return true;
    }

    static public function deleteFeatured($archive_entry_id, $board_uri, $mod_archive = false) {
        global $config, $mod;
        global $board;

        if (empty($board) || $board['uri'] !== $board_uri) {
            if (!openBoard($board_uri)) {
                error_log("deleteFeatured: Failed to open board: " . $board_uri);
                return;
            }
        }

        $query = prepare("SELECT `original_thread_id`, `files`, `path`, `lifetime` FROM `archive_threads` WHERE `id` = :id AND `board_uri` = :board_uri AND (`featured` = 1 OR `mod_archived` = 1)");
        $query->bindValue(':id', $archive_entry_id, PDO::PARAM_INT);
        $query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
        $query->execute() or error(db_error($query));

        if(!$thread = $query->fetch(PDO::FETCH_ASSOC))
            error($config['error']['invalidpost']);

        $original_thread_id = $thread['original_thread_id'];
        $featured_path = $board['dir'] . ($mod_archive ? $config['dir']['mod_archive'] : $config['dir']['featured']) . $thread['path'] . '/';

        foreach (json_decode($thread['files']) as $f) {
            @unlink($featured_path . $config['dir']['img'] . $f->file);
            @unlink($featured_path . $config['dir']['thumb'] . $f->thumb);
        }
        @unlink($featured_path . $config['dir']['res'] . sprintf($config['file_page'], $original_thread_id));

        if($thread['lifetime'] != 0 && $thread['lifetime'] < strtotime("-" . $config['archive']['lifetime'])) {
            $update_query = prepare("UPDATE `archive_threads` SET " . ($mod_archive?"`mod_archived`":"`featured`") . " = 0 WHERE `id` = :id AND `board_uri` = :board_uri");
        } else {
            $update_query = prepare("UPDATE `archive_threads` SET " . ($mod_archive?"`mod_archived`":"`featured`") . " = 0 WHERE `id` = :id AND `board_uri` = :board_uri");
        }
        $update_query->bindValue(':id', $archive_entry_id, PDO::PARAM_INT);
        $update_query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
        $update_query->execute() or error(db_error($update_query));

        modLog(sprintf("Removed thread #%d (original: %d) from " . ($mod_archive?"mod archive":"featured threads") . " for board %s", $archive_entry_id, $original_thread_id, $board_uri));

        self::buildFeaturedIndex($board_uri);
        self::buildArchiveIndex($board_uri);
    }

    static public function deleteArchived($archive_entry_id, $board_uri) {
        global $config, $mod;
        global $board;

        if (empty($board) || $board['uri'] !== $board_uri) {
            if (!openBoard($board_uri)) {
                error_log("deleteArchived: Failed to open board: " . $board_uri);
                return;
            }
        }

        $query = prepare("SELECT `original_thread_id`, `files`, `path` FROM `archive_threads` WHERE `id` = :id AND `board_uri` = :board_uri");
        $query->bindValue(':id', $archive_entry_id, PDO::PARAM_INT);
        $query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
        $query->execute() or error(db_error($query));

        if(!$thread = $query->fetch(PDO::FETCH_ASSOC))
            error($config['error']['invalidpost']);

        $original_thread_id = $thread['original_thread_id'];
        $archived_path = $board['dir'] . $config['dir']['archive'] . $thread['path'] . '/';

        foreach (json_decode($thread['files']) as $f) {
            @unlink($archived_path . $config['dir']['img'] . $f->file);
            @unlink($archived_path . $config['dir']['thumb'] . $f->thumb);
        }
        @unlink($archived_path . $config['dir']['res'] . sprintf($config['file_page'], $original_thread_id));
        @unlink($archived_path . $config['dir']['res'] . sprintf('%d.json', $original_thread_id));

        $delete_query = prepare("DELETE FROM `archive_threads` WHERE `id` = :id AND `board_uri` = :board_uri");
        $delete_query->bindValue(':id', $archive_entry_id, PDO::PARAM_INT);
        $delete_query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
        $delete_query->execute() or error(db_error($delete_query));

        $del_vote_query = prepare("DELETE FROM `archive_votes` WHERE `board` = :board AND `thread_id` = :thread_id");
        $del_vote_query->bindValue(':board', $board_uri, PDO::PARAM_STR);
        $del_vote_query->bindValue(':thread_id', $original_thread_id, PDO::PARAM_INT);
        $del_vote_query->execute() or error(db_error($del_vote_query));

        modLog(sprintf("Deleted archived thread #%d (original: %d) from board %s", $archive_entry_id, $original_thread_id, $board_uri));
        self::buildArchiveIndex($board_uri);
    }

    static public function RebuildArchiveIndexes($board_uri = null) {
        global $config;

        if(!$config['archive']['threads']) return;

        $boards_to_rebuild = [];
        if ($board_uri) {
            $boards_to_rebuild[] = ['uri' => $board_uri];
        } else {
            $query = query("SELECT DISTINCT `board_uri` FROM `archive_threads`");
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $boards_to_rebuild[] = ['uri' => $row['board_uri']];
            }
        }

        foreach ($boards_to_rebuild as $current_board_info) {
            $current_board_uri = $current_board_info['uri'];
            global $board;
            $original_global_board = $board;
            if (empty($board) || $board['uri'] !== $current_board_uri) {
                if (!openBoard($current_board_uri)) {
                    error_log("RebuildArchiveIndexes: Failed to open board: " . $current_board_uri);
                    continue;
                }
            }

            if(!$config['archive']['cron_job']['purge']) {
                self::purgeArchive($current_board_uri);
            }
            self::buildArchiveIndex($current_board_uri);
            self::buildFeaturedIndex($current_board_uri);

            $board = $original_global_board;
        }
    }

    static public function buildArchiveIndex($board_uri, $threads_per_page = 5) {
        global $config;
        global $board;

        if (!$config['archive']['threads']) return;

        if (empty($board) || $board['uri'] !== $board_uri) {
            if (!openBoard($board_uri)) {
                error_log("buildArchiveIndex: Failed to open board: " . $board_uri);
                return;
            }
        }

        $total_threads = self::getArchiveCount($board_uri);
        $total_pages = ceil($total_threads / $threads_per_page);

        for ($page = 1; $page <= $total_pages; $page++) {
            $archive = self::getArchiveListPaginated($board_uri, $page, $threads_per_page);

            foreach ($archive as &$thread) {
                $thread['archived_url'] = $config['root'] . $board['dir'] . $config['dir']['archive'] . $thread['path'] . '/' . $config['dir']['res'] . sprintf($config['file_page'], $thread['original_thread_id']);
                if ($thread['first_image']) {
                    $thread['image_url'] = $config['root'] . $board['dir'] . $config['dir']['archive'] . $thread['path'] . '/' . $config['dir']['thumb'] . $thread['first_image'];
                } else {
                    $thread['image_url'] = null;
                }
            }

            $title = sprintf(_('Archived') . ' %s: ' . $config['board_abbreviation'], _('threads'), $board['uri']);

            $archive_page_content = Element("mod/archive_list.html", array(
                'config' => $config,
                'thread_count' => $total_threads,
                'board' => $board,
                'archive' => $archive,
                'current_page' => $page,
                'total_pages' => $total_pages
            ));

            $archive_page = Element('page.html', array(
                'config' => $config,
                'mod' => false,
                'hide_dashboard_link' => true,
                'boardlist' => createBoardList(false),
                'title' => $title,
                'subtitle' => "",
                'body' => $archive_page_content
            ));

            $filename = $config['dir']['home'] . $board['dir'] . $config['dir']['archive'];
            $filename .= ($page == 1) ? $config['file_index'] : $page . '.html';
            file_write($filename, $archive_page);
        }
    }

    static public function getArchiveListPaginated($board_uri, $page, $threads_per_page) {
        global $config;

        $offset = ($page - 1) * $threads_per_page;
        $query = prepare("SELECT `id`, `original_thread_id`, `snippet`, `featured`, `mod_archived`, `votes`, `path`, `first_image` FROM `archive_threads` WHERE `board_uri` = :board_uri AND `lifetime` > :lifetime ORDER BY `original_thread_id` DESC LIMIT :limit OFFSET :offset");
        $query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
        $query->bindValue(':lifetime', strtotime("-" . $config['archive']['lifetime']), PDO::PARAM_INT);
        $query->bindValue(':limit', $threads_per_page, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function getArchiveCount($board_uri) {
        global $config;

        $query = prepare("SELECT COUNT(*) as count FROM `archive_threads` WHERE `board_uri` = :board_uri AND `lifetime` > :lifetime");
        $query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
        $query->bindValue(':lifetime', strtotime("-" . $config['archive']['lifetime']), PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    static public function getArchiveList($board_uri, $featured = false, $mod_archive = false, $order_by_lifetime = false) {
        global $config;

        $archive = false;
        $sql_common_select = "`id`, `original_thread_id`, `snippet`, `featured`, `mod_archived`, `votes`, `path`, `first_image`";
        $order_clause = $order_by_lifetime ? " ORDER BY `lifetime` DESC" : " ORDER BY `original_thread_id` DESC";

        if($featured) {
            $query = prepare("SELECT $sql_common_select FROM `archive_threads` WHERE `board_uri` = :board_uri AND `featured` = 1" . $order_clause);
        } else if($mod_archive) {
            $query = prepare("SELECT $sql_common_select FROM `archive_threads` WHERE `board_uri` = :board_uri AND `mod_archived` = 1" . $order_clause);
        } else {
            $query = prepare("SELECT $sql_common_select FROM `archive_threads` WHERE `board_uri` = :board_uri AND `lifetime` > :lifetime_val" . $order_clause);
            $query->bindValue(':lifetime_val', strtotime("-" . $config['archive']['lifetime']), PDO::PARAM_INT);
        }
        $query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
        $query->execute() or error(db_error($query));
        $archive = $query->fetchAll(PDO::FETCH_ASSOC);
        return $archive;
    }

    static public function buildFeaturedIndex($board_uri) {
        global $config;
        global $board;

        if(!$config['feature']['threads']) return;

        if (empty($board) || $board['uri'] !== $board_uri) {
            if (!openBoard($board_uri)) {
                error_log("buildFeaturedIndex: Failed to open board: " . $board_uri);
                return;
            }
        }

        $archive = self::getArchiveList($board_uri, true);

        foreach($archive as &$thread) {
            $thread['featured_url'] = $config['root'] . $board['dir'] . $config['dir']['featured'] . $thread['path'] . '/' . $config['dir']['res'] . sprintf($config['file_page'], $thread['original_thread_id']);
            if ($thread['first_image']) {
                $thread['image_url'] = $config['root'] . $board['dir'] . $config['dir']['featured'] . $thread['path'] . '/' . $config['dir']['thumb'] . $thread['first_image'];
            } else {
                $thread['image_url'] = null;
            }
        }

        $title = sprintf(_('Featured') . ' %s: ' . $config['board_abbreviation'], _('threads'), $board['uri']);
        $body_content = Element("mod/archive_featured_list.html", array(
            'config' => $config,
            'board' => $board,
            'archive' => $archive
        ));
        $archive_page = Element('page.html', array(
            'config' => $config,
            'mod' => false,
            'hide_dashboard_link' => true,
            'boardlist' => createBoardList(false),
            'title' => $title,
            'subtitle' => "",
            'body' => $body_content
        ));
        file_write($config['dir']['home'] . $board['dir'] . $config['dir']['featured'] . $config['file_index'], $archive_page);
    }

    static public function addVote($board_uri, $original_thread_id) {
        global $config;

        $query = prepare("SELECT `id` FROM `archive_threads` WHERE `board_uri` = :board_uri AND `original_thread_id` = :original_thread_id");
        $query->bindValue(':board_uri', $board_uri, PDO::PARAM_STR);
        $query->bindValue(':original_thread_id', $original_thread_id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
        $archive_entry = $query->fetch(PDO::FETCH_ASSOC);

        if (!$archive_entry) {
            error($config['error']['nonexistant']);
        }
        $archive_entry_id = $archive_entry['id'];

        $query = prepare("SELECT COUNT(*) FROM `archive_votes` WHERE `board` = :board AND `thread_id` = :thread_id AND `ip` = :ip");
        $query->bindValue(':board', $board_uri, PDO::PARAM_STR);
        $query->bindValue(':thread_id', $original_thread_id, PDO::PARAM_INT);
        $query->bindValue(':ip', get_ip_hash($_SERVER['REMOTE_ADDR']), PDO::PARAM_STR);
        $query->execute() or error(db_error($query));
        if ($query->fetchColumn(0) != 0) {
            error($config['error']['already_voted']);
        }

        $update_query = prepare("UPDATE `archive_threads` SET `votes` = `votes`+1 WHERE `id` = :archive_entry_id");
        $update_query->bindValue(':archive_entry_id', $archive_entry_id, PDO::PARAM_INT);
        $update_query->execute() or error(db_error($update_query));

        $insert_query = prepare("INSERT INTO `archive_votes` VALUES (NULL, :board, :thread_id, :ip)");
        $insert_query->bindValue(':board', $board_uri, PDO::PARAM_STR);
        $insert_query->bindValue(':thread_id', $original_thread_id, PDO::PARAM_INT);
        $insert_query->bindValue(':ip', get_ip_hash($_SERVER['REMOTE_ADDR']), PDO::PARAM_STR);
        $insert_query->execute() or error(db_error($insert_query));

        self::buildArchiveIndex($board_uri);
    }
}
?>