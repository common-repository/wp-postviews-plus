<?php
defined('WP_PVP_VERSION') or exit('No direct script access allowed');

class WP_PVP_ajax
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('wp_ajax_wp_pvp_count', array('WP_PVP_ajax', 'wp_pvp_count'));
            add_action('wp_ajax_nopriv_wp_pvp_count', array('WP_PVP_ajax', 'wp_pvp_count'));
        }
    }

    public static function wp_pvp_count()
    {
        global $wpdb;

        $post_id = (int) $_GET['post_id'];
        WP_PVP::add_views($post_id);

        $json = array();
        $data = $wpdb->get_row('SELECT * FROM ' . $wpdb->postviews_plus . ' WHERE count_id = "' . esc_attr($_GET['count_id']) . '"');
        if ($data) {
            if (!empty($data->tv)) {
                $post_list = explode(',', $data->tv);
                if (is_array($post_list)) {
                    foreach ($post_list as $post_ID) {
                        $user_views = (int) get_post_meta($post_ID, WP_PVP::$post_meta_views, true);
                        $bot_views = (int) get_post_meta($post_ID, WP_PVP::$post_meta_botviews, true);
                        $json['wppvp_tv_' . $post_ID] = number_format_i18n($user_views + $bot_views);
                        $json['wppvp_tuv_' . $post_ID] = number_format_i18n($user_views);
                        $json['wppvp_tbv_' . $post_ID] = number_format_i18n($bot_views);
                    }
                }
            }
            if (!empty($data->gt)) {
                $gts = explode(',', $data->gt);
                foreach ($gts as $gt) {
                    $with_bot = (bool) substr($gt, 0, 1);
                    switch (substr($gt, 1, 1)) {
                        case 1:
                            $type = 'category';
                            break;
                        case 2:
                            $type = 'post_tag';
                            break;
                        default:
                            $type = '';
                            break;
                    }
                    $term_id = explode('-', substr($gt, 2));
                    if (count($term_id) == 1) {
                        $term_id = $term_id[0];
                    }
                    $json['wppvp_gt_' . $gt] = number_format_i18n(get_totalviews_term($term_id, false, $with_bot, $type));
                }
            }
        }

        wp_send_json($json);
    }
}

WP_PVP_ajax::init();
