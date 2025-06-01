<?php
    require 'info.php';
    
    function recentposts_build($action, $settings, $board) {
        // Possible values for $action:
        //	- all (rebuild everything, initialization)
        //	- news (news has been updated)
        //	- boards (board list changed)
        //	- post (a post has been made)
        //	- post-thread (a thread has been made)
        
        $b = new RecentPosts();
        $b->build($action, $settings);
    }
    
    class RecentPosts {
        public function build($action, $settings) {
            global $config;
            
            if ($action == 'all') {
                copy('templates/themes/recent/' . $settings['basecss'], $config['dir']['home'] . $settings['css']);
            }
            
            $this->excluded = explode(' ', $settings['exclude']);
            
            if ($action == 'all' || $action == 'post' || $action == 'post-thread' || $action == 'post-delete') {
                $action = generation_strategy('sb_recent', array());
                if ($action == 'delete') {
                    file_unlink($config['dir']['home'] . $settings['html']);
                }
                elseif ($action == 'rebuild') {
                    file_write($config['dir']['home'] . $settings['html'], $this->homepage($settings));
                }
            }
        }
        
        public function homepage($settings) {
            global $config, $board, $pdo;

            $recent_images = Array();
            $recent_posts = Array();
            $stats = Array();

            $boards = listBoards();
            $board_uris = [];
            foreach ($boards as &$_board) {
                if (in_array($_board['uri'], $this->excluded))
                    continue;
                $board_uris[] = $pdo->quote($_board['uri']);
            }
            if (empty($board_uris)) {
                error(_("Can't build the RecentPosts theme, because there are no boards to be fetched."));
            }

            // Recent images
            $query = prepare('SELECT * FROM ``posts`` WHERE `board` IN (' . implode(',', $board_uris) . ') AND `files` IS NOT NULL ORDER BY `time` DESC LIMIT :limit');
            $query->bindValue(':limit', (int)$settings['limit_images'], PDO::PARAM_INT);
            $query->execute() or error(db_error($query));

            while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
                openBoard($post['board']);

                if (isset($post['files']))
                    $files = json_decode($post['files']);

                if (!$files || $files[0]->file == 'deleted' || $files[0]->thumb == 'file') continue;

                $post_date_path = isset($post['live_date_path']) && $post['live_date_path'] ? $post['live_date_path'] . '/' : '';
                $post['link'] = $config['root'] . $board['dir'] . $config['dir']['res'] . $post_date_path . link_for($post) . '#' . $post['id'];

                if ($files) {
                    if ($files[0]->thumb == 'spoiler') {
                        $tn_size = @getimagesize($config['spoiler_image']);
                        $post['src'] = $config['spoiler_image'];
                        $post['thumbwidth'] = $tn_size[0];
                        $post['thumbheight'] = $tn_size[1];
                    }
                    else {
                        $post['src'] = $config['uri_thumb'] . $files[0]->thumb;
                        $post['thumbwidth'] = $files[0]->thumbwidth;
                        $post['thumbheight'] = $files[0]->thumbheight;
                    }
                }

                $recent_images[] = $post;
            }

            // Recent posts
            $query = prepare('SELECT * FROM ``posts`` WHERE `board` IN (' . implode(',', $board_uris) . ') ORDER BY `time` DESC LIMIT :limit');
            $query->bindValue(':limit', (int)$settings['limit_posts'], PDO::PARAM_INT);
            $query->execute() or error(db_error($query));

            while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
                openBoard($post['board']);

                $post_date_path = isset($post['live_date_path']) && $post['live_date_path'] ? $post['live_date_path'] . '/' : '';
                $post['link'] = $config['root'] . $board['dir'] . $config['dir']['res'] . $post_date_path . link_for($post) . '#' . $post['id'];
                if ($post['body'] != "")
                    $post['snippet'] = pm_snippet($post['body'], 30);
                else
                    $post['snippet'] = "<em>" . _("(no comment)") . "</em>";
                $post['board_name'] = $board['name'];

                $recent_posts[] = $post;
            }

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

            return Element('themes/recent/recent.html', Array(
                'settings' => $settings,
                'config' => $config,
                'boardlist' => createBoardlist(),
                'recent_images' => $recent_images,
                'recent_posts' => $recent_posts,
                'stats' => $stats
            ));
        }
    };
    
?>