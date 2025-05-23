<?php
    require 'info.php';
    
    function rss_recentposts_build($action, $settings, $board) {
        // Possible values for $action:
        //	- all (rebuild everything, initialization)
        //	- news (news has been updated)
        //	- boards (board list changed)
        //	- post (a post has been made)
        //	- post-thread (a thread has been made)
        
        $b = new RSSRecentPosts();
        $b->build($action, $settings);
    }
    
    class RSSRecentPosts {
        public function build($action, $settings) {
            global $config;
            
            $this->excluded = explode(' ', $settings['exclude']);
            
            if ($action == 'all' || $action == 'post' || $action == 'post-thread' || $action == 'post-delete')
                file_write($config['dir']['home'] . $settings['xml'], $this->homepage($settings));
        }
        
        public function homepage($settings) {
            global $config, $board, $pdo;
            
            $recent_posts = Array();
            
            $boards = listBoards();
            $board_uris = [];
            foreach ($boards as &$_board) {
                if (in_array($_board['uri'], $this->excluded))
                    continue;
                $board_uris[] = $pdo->quote($_board['uri']);
            }
            if (empty($board_uris)) {
                error(_("Can't build the RSS theme, because there are no boards to be fetched."));
            }

            // Unified posts table query for recent posts
            $query = prepare('SELECT * FROM ``posts`` WHERE `board` IN (' . implode(',', $board_uris) . ') ORDER BY `time` DESC LIMIT :limit');
            $query->bindValue(':limit', (int)$settings['limit_posts'], PDO::PARAM_INT);
            $query->execute() or error(db_error($query));
            
            while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
                openBoard($post['board']);
                
                $post['link'] = $config['root'] . $board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], ($post['thread'] ? $post['thread'] : $post['id'])) . '#' . $post['id'];
                $post['snippet'] = pm_snippet($post['body'], 30);
                $post['board_name'] = $board['name'];
                
                $recent_posts[] = $post;
            }
            
            return Element('themes/rss/rss.xml', Array(
                'settings' => $settings,
                'config' => $config,
                'recent_posts' => $recent_posts,
            ));
        }
    };
    
?>