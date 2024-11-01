<?php
defined('WP_PVP_VERSION') or exit('No direct script access allowed');

class WP_PVP_widget
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            include_once(WP_PVP_PLUGIN_DIR . 'widget/postviews_plus.php');

            register_widget('WP_Widget_PostViews_Plus');
        }
    }
}

function is_selected($id, $check)
{
    if (in_array($id, $check)) {
        return ' selected="selected"';
    }
}
