<?php
defined('WP_PVP_VERSION') or exit('No direct script access allowed');

class WP_PVP
{
    public static $option_prefix = 'PVP_';
    public static $post_meta_views = 'views';
    public static $post_meta_botviews = 'bot_views';
    public static $options = [];
    public static $default_options = [
        'count' => 1,
        'check_reflash' => 0,
        'timeout' => 300,
        'display_home' => 0,
        'display_single' => 0,
        'display_page' => 0,
        'display_archive' => 0,
        'display_search' => 0,
        'display_other' => 0,
        'template' => '%VIEW_COUNT% views',
        'user_template' => '%VIEW_COUNT% views',
        'bot_template' => '%VIEW_COUNT% views',
        'botagent' => array('bot', 'spider', 'slurp'),
        'most_viewed_template' => '<li><a href="%POST_URL%"  title="%POST_TITLE%">%POST_TITLE%</a> - %VIEW_COUNT% views</li>',
        'set_thumbnail_size_h' => 30,
        'set_thumbnail_size_w' => 30
    ];

    protected static $should_count = false;

    private static $initiated = false;
    private static $table_initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;
            self::init_tables();

            load_plugin_textdomain('wp-postviews-plus', false, plugin_basename(dirname(WP_PVP_PLUGIN_BASENAME)) . '/languages');

            self::$options = self::get_option('options', self::$default_options);

            include_once(WP_PVP_PLUGIN_DIR . 'class.wp-pvp.update.php');
            WP_PVP_update::update();

            if (is_admin()) {
                include_once(WP_PVP_PLUGIN_DIR . 'class.wp-pvp.admin.php');
            }

            include_once(WP_PVP_PLUGIN_DIR . 'class.wp-pvp.ajax.php');
            include_once(WP_PVP_PLUGIN_DIR . 'class.wp-pvp.template.php');

            add_action('delete_post', [__CLASS__, 'delete_post']);
        }
    }

    private static function init_tables()
    {
        if (!self::$table_initiated) {
            self::$table_initiated = true;

            global $wpdb;
            $wpdb->postviews_plus = $wpdb->prefix . 'postviews_plus';
            $wpdb->postviews_plus_reflash = $wpdb->prefix . 'postviews_plus_reflash';
        }
    }

    public static function delete_post($post_ID)
    {
        if (!wp_is_post_revision($post_ID)) {
            delete_post_meta($post_ID, self::$post_meta_views);
            delete_post_meta($post_ID, self::$post_meta_botviews);
        }
    }

    public static function add_views($post_ID)
    {
        if ($post_ID > 0) {
            if (!wp_is_post_revision($post_ID)) {
                switch (self::$options['count']) {
                    case 0:
                        self::$should_count = true;
                        break;
                    case 1:
                        if (!is_user_logged_in()) {
                            self::$should_count = true;
                        }
                        break;
                    case 2:
                        if (is_user_logged_in()) {
                            self::$should_count = true;
                        }
                        break;
                }
            }

            if (self::$should_count) {
                if (self::$options['check_reflash']) {
                    global $wpdb;
                    $ip = preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);
                    $wpdb->query('DELETE FROM `' . $wpdb->postviews_plus_reflash . '` WHERE `look_time` < ' . (time() - self::$options['timeout']));
                    $data = $wpdb->get_var('SELECT `look_time` FROM `' . $wpdb->postviews_plus_reflash . '` WHERE `post_id` = "' . $post_ID . '" AND `user_ip` = "' . $ip . '"');
                    if ($data) {
                        $wpdb->update(
                            $wpdb->postviews_plus_reflash,
                            array(
                                'look_time' => time()
                            ),
                            array(
                                'post_id' => $post_ID,
                                'user_ip' => $ip,
                            ),
                            array(
                                '%d',
                            ),
                            array(
                                '%d',
                                '%s'
                            )
                        );
                        return ;
                    } else {
                        $wpdb->insert(
                            $wpdb->postviews_plus_reflash,
                            array(
                                'post_id' => $post_ID,
                                'user_ip' => $ip,
                                'look_time' => time()
                            ),
                            array(
                                '%d',
                                '%s',
                                '%d',
                            )
                        );
                    }
                }

                $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT']: '';
                $useragent = strtolower(trim($useragent));
                $bot = false;
                if (is_array(self::$options['botagent'])) {
                    $regex = '/(' . str_replace('@@@@@@', ')|(', preg_quote(implode('@@@@@@', self::$options['botagent']), '/')) . ')/si';
                    $bot = preg_match($regex, $useragent);
                }

                if ($bot) {
                    $post_views = (int) get_post_meta($post_ID, self::$post_meta_botviews, true) + 1;
                    update_post_meta($post_ID, self::$post_meta_botviews, $post_views);
                } else {
                    $post_views = (int) get_post_meta($post_ID, self::$post_meta_views, true) + 1;
                    update_post_meta($post_ID, self::$post_meta_views, $post_views);
                }
            }
        }
    }

    public static function add_cache_stats($addin, $id, $with_bot = true, $type = '')
    {
        global $wpdb;
        static $first_run = true;
        $count_id = md5($_SERVER['REQUEST_URI']);
        if ($first_run) {
            $wpdb->query('DELETE FROM `' . $wpdb->postviews_plus . '` WHERE `count_id` = "' . $count_id . '"');
            $wpdb->query('DELETE FROM `' . $wpdb->postviews_plus . '` WHERE `add_time` < ' . (time() - 86400 * 7));
            $first_run = false;
        }
        $data = $wpdb->get_row('SELECT * FROM `' . $wpdb->postviews_plus . '` WHERE `count_id` = "' . $count_id . '"');
        if ($data) {
            switch ($addin) {
                case 'tv':
                    if ($data->tv == '') {
                        $update = array('tv' => $id);
                    } else {
                        if (!in_array($id, explode(',', $data->tv))) {
                            $update = array('tv' => $data->tv . ',' . $id);
                        }
                    }
                    if (isset($update)) {
                        $wpdb->update($wpdb->postviews_plus, $update, array('count_id' => $count_id));
                    }
                    break;
                case 'gt':
                    $add_word = get_totalviews_stats_word($id, $with_bot, $type);
                    if ($data->gt == '') {
                        $update = array('gt' => $add_word);
                    } else {
                        if (!in_array($add_word, explode(',', $data->gt))) {
                            $update = array('gt' => $data->gt . ',' . $add_word);
                        }
                    }
                    if (isset($update)) {
                        $wpdb->update($wpdb->postviews_plus, $update, array('count_id' => $count_id));
                    }
                    break;
            }
        } else {
            switch ($addin) {
                case 'tv':
                    $wpdb->insert($wpdb->postviews_plus, array('tv' => $id, 'count_id' => $count_id, 'add_time' => time()));
                    break;
                case 'gt':
                    $add_word = get_totalviews_stats_word($id, $with_bot, $type);
                    $wpdb->insert($wpdb->postviews_plus, array('gt' => $add_word, 'count_id' => $count_id, 'add_time' => time()));
                    break;
            }
        }
    }

    public static function get_option($option, $default = false)
    {
        return get_option(self::$option_prefix . $option, $default);
    }

    public static function update_option($option, $value)
    {
        return update_option(self::$option_prefix . $option, $value);
    }

    public static function delete_option($option)
    {
        return delete_option(self::$option_prefix . $option);
    }

    public static function plugin_activation()
    {
        self::init_tables();

        global $wpdb;

        self::update_option('options', self::$default_options, 'Post Views Plus Options');
        $charset_collate = $wpdb->get_charset_collate();
        $wpdb->query('CREATE TABLE IF NOT EXISTS ' . $wpdb->postviews_plus . ' (
			`count_id` VARCHAR(32) NOT NULL,
			`add_time` int(10) unsigned NOT NULL,
			`tv` VARCHAR(255) NOT NULL,
			`gt` VARCHAR(255) NOT NULL,
			PRIMARY KEY (`count_id`)
		) ' . $charset_collate . ';');
        $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . 'postviewsplus`');

        delete_option('PV+_botagent');
        delete_option('PV+_option');
        delete_option('PV+_useragent');
        delete_option('PV+_views');
        delete_option('PV+_DBversion');
    }

    public static function plugin_deactivation()
    {
        delete_option('PV+_botagent');
        delete_option('PV+_option');
        delete_option('PV+_useragent');
        delete_option('PV+_views');
        delete_option('PV+_DBversion');
    }
}
