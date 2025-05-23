<?php
    require 'info.php';
    
    function index_build($action, $settings, $board) {
        // Possible values for $action:
        //	- all (rebuild everything, initialization)
        //	- news (news has been updated)
        //	- boards (board list changed)
        //	- post (a post has been made)
        //	- post-thread (a thread has been made)
        
        $b = new index();
        $b->build($action, $settings);
    }
    
    // Wrap functions in a class so they don't interfere with normal Tinyboard operations
    class index {
        public function build($action, $settings) {
            global $config, $_theme;
            
            if ($action == 'all') {
                copy('templates/themes/index/' . $settings['basecss'], $config['dir']['home'] . $settings['css']);
            }
            
            $this->excluded = explode(' ', $settings['exclude_recent_activity']);
            
            if ($action == 'all' || $action == 'post' || $action == 'post-thread' || $action == 'post-delete') {
                $action = generation_strategy('sb_index', array());
                if ($action == 'delete') {
                    file_unlink($config['dir']['home'] . $settings['html']);
                }
                elseif ($action == 'rebuild') {
                    file_write($config['dir']['home'] . $settings['html'], $this->homepage($settings));
                }
            }
            if ($action == 'all' || $action == 'news' || $action == 'boards'){
                file_write($config['dir']['home'] . $settings['html'], $this->homepage($settings));
            }
        }
            
        // Build news page
        public function homepage($settings) {
            global $config, $board, $pdo;

            $recent_activity = Array();
            $stats = Array();

            $boards = listBoards();

            // Build recent activity (posts with images)
            $board_uris = [];
            foreach ($boards as &$_board) {
                if (in_array($_board['uri'], $this->excluded))
                    continue;
                $board_uris[] = $pdo->quote($_board['uri']);
            }
            if (empty($board_uris)) {
                error(_("Can't build the Index theme, because there are no boards to be fetched."));
            }

            $query = prepare('SELECT * FROM ``posts`` WHERE `board` IN (' . implode(',', $board_uris) . ') AND `files` IS NOT NULL ORDER BY `time` DESC LIMIT :limit');
            $query->bindValue(':limit', (int)$settings['limit_activity'], PDO::PARAM_INT);
            $query->execute() or error(db_error($query));

            while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
                openBoard($post['board']);

                $files = isset($post['files']) ? json_decode($post['files']) : null;
                if (!$files || $files[0]->file == 'deleted' || $files[0]->thumb == 'file')
                    continue;

                $post['link'] = $config['root'] . $board['dir'] . $config['dir']['res'] . link_for($post) . '#' . $post['id'];

                if ($files[0]->thumb == 'spoiler') {
                    $tn_size = @getimagesize($config['spoiler_image']);
                    $post['src'] = $config['spoiler_image'];
                    $post['thumbwidth'] = $tn_size[0];
                    $post['thumbheight'] = $tn_size[1];
                } else {
                    $post['src'] = $config['uri_thumb'] . $files[0]->thumb;
                    $post['thumbwidth'] = $files[0]->thumbwidth;
                    $post['thumbheight'] = $files[0]->thumbheight;
                }

                $post['snippet'] = ($post['body'] != "") ? pm_snippet($post['body'], 30) : "<em>" . _("(no comment)") . "</em>";
                $post['board_name'] = $board['name'];
                $post['nsfw'] = in_array($post['board'], explode(' ', $settings['nsfw_boards'])) ? true : false;

                $recent_activity[] = $post;
            }

            // Stats
            // Total posts
            $query = prepare('SELECT COUNT(*) FROM ``posts`` WHERE `board` IN (' . implode(',', $board_uris) . ')');
            $query->execute() or error(db_error($query));
            $stats['total_posts'] = number_format($query->fetchColumn());

            // Unique IPs
            $query = prepare('SELECT COUNT(DISTINCT(`ip`)) FROM ``posts`` WHERE `board` IN (' . implode(',', $board_uris) . ')');
            $query->execute() or error(db_error($query));
            $stats['unique_posters'] = number_format($query->fetchColumn());

            // Active content
            $query = prepare('SELECT `files` FROM ``posts`` WHERE `board` IN (' . implode(',', $board_uris) . ') AND `num_files` > 0');
            $query->execute() or error(db_error($query));
            $files = $query->fetchAll();
            $stats['active_content'] = 0;
            foreach ($files as &$file) {
                preg_match_all('/"size":([0-9]*)/', $file[0], $matches);
                $stats['active_content'] += array_sum($matches[1]);
            }

            // News entries
            $settings['no_recent'] = (int) $settings['no_recent'];
            $query = query("SELECT * FROM ``news`` ORDER BY `time` DESC" . ($settings['no_recent'] ? ' LIMIT ' . $settings['no_recent'] : '')) or error(db_error());
            $news = $query->fetchAll(PDO::FETCH_ASSOC);

            // Excluded boards for boardlist
            $excluded_boards = isset($settings['exclude_board_list']) ? explode(' ', $settings['exclude_board_list']) : [];
            $boardlist = array_filter($boards, function($board) use ($excluded_boards) {
                return !in_array($board['uri'], $excluded_boards);
            });

            return Element('themes/index/index.html', Array(
                'settings' => $settings,
                'config' => $config,
                'boardlist' => createBoardlist(),
                'recent_activity' => $recent_activity,
                'stats' => $stats,
                'news' => $news,
                'boards' => $boardlist
            ));
        }
    };
    
?>