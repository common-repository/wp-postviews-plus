<?php
defined('WP_PVP_VERSION') or exit('No direct script access allowed');

class WP_PVP_update
{
    public static function update()
    {
        $now_version = WP_PVP::get_option('version');

        if ($now_version === false) {
            $now_version = '0.0.0';
        }
        if ($now_version == WP_PVP_VERSION) {
            return;
        }

        if (version_compare($now_version, '2.1.2', '<')) {
            WP_PVP::update_option('version', '2.1.2');
        }
    }
}
