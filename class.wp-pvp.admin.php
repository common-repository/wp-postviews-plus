<?php
defined('WP_PVP_VERSION') or exit('No direct script access allowed');

class WP_PVP_admin
{
    private static $initiated = false;
    private static $views_settings = array('PVP_options', 'widget_views-plus');
    private static $views_postmetas = array('views', 'bot_views');

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_filter('plugin_action_links', [__CLASS__, 'plugin_action_links'], 10, 2);
            add_action('admin_menu', [__CLASS__, 'admin_menu']);
            add_action('admin_init', [__CLASS__, 'register_setting']);
        }
    }

    public static function plugin_action_links($links, $file)
    {
        if ($file == WP_PVP_PLUGIN_BASENAME) {
            $settings_link = '<a href="' . admin_url('options-general.php?page=wp_postviews_plus') . '">' . __('Settings', 'wp-postviews-plus') . '</a>';
            $links = array_merge(array($settings_link), $links);
        }
        return $links;
    }

    public static function admin_menu()
    {
        add_options_page('WP-PostViews Plus', __('PostViews+', 'wp-postviews-plus'), 'manage_options', 'wp_postviews_plus', [__CLASS__, 'setting_page']);
    }

    public static function register_setting()
    {
        register_setting('wp_postviews_plus_options', WP_PVP::$option_prefix . 'options', [
            'sanitize_callback' => [__CLASS__, 'validate_settings']
        ]);
    }

    public static function validate_settings($settings)
    {
        if (!empty($_POST['pvp-defaults'])) {
            $settings = WP_PVP::$default_options;
        }

        if (!empty($_POST['pvp-submit'])) {
            global $wpdb;

            $settings = wp_parse_args($settings, WP_PVP::$default_options);

            $settings['count'] = intval($settings['count']);
            $settings['check_reflash'] = intval($settings['check_reflash']);
            $settings['timeout'] = intval($settings['timeout']);

            $settings['set_thumbnail_size_h'] = intval($settings['set_thumbnail_size_h']);
            $settings['set_thumbnail_size_w'] = intval($settings['set_thumbnail_size_w']);

            $settings['display_home'] = intval($settings['display_home']);
            $settings['display_single'] = intval($settings['display_single']);
            $settings['display_page'] = intval($settings['display_page']);
            $settings['display_archive'] = intval($settings['display_archive']);
            $settings['display_search'] = intval($settings['display_search']);
            $settings['display_other'] = intval($settings['display_other']);

            $settings['botagent'] = str_replace("\r", '', $settings['botagent']);
            $settings['botagent'] = explode("\n", $settings['botagent']);
            if (!is_array($settings['botagent'])) {
                $settings['botagent'] = WP_PVP::$default_options['botagent'];
            }

            if ($settings['check_reflash']) {
                $wpdb->query('CREATE TABLE IF NOT EXISTS `' . $wpdb->postviews_plus_reflash . '` (
					`post_id` BIGINT UNSIGNED NOT NULL DEFAULT "0",
					`user_ip` VARCHAR(100) NOT NULL DEFAULT "",
					`look_time` INT UNSIGNED NOT NULL DEFAULT "0",
					PRIMARY KEY (`post_id`, `user_ip`),
					INDEX (`look_time`)
				) ' . $wpdb->get_charset_collate() . ';');
            } else {
                $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->postviews_plus_reflash . '`');
            }
        }

        return $settings;
    }

    public static function setting_page()
    {
        ?>
<div class="wrap">
	<h1><?php _e('Post Views Plus Options', 'wp-postviews-plus'); ?>
	</h1>

	<form method="post" action="options.php">
		<?php settings_fields('wp_postviews_plus_options'); ?>

		<h2 class="title"><?php _e('Basic Options', 'wp-postviews-plus'); ?>
		</h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('Count Views From:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::select_html('count', [
                        '0' => __('Everyone', 'wp-postviews-plus'),
                        '1' => __('Guests Only', 'wp-postviews-plus'),
                        '2' => __('Registered Users Only', 'wp-postviews-plus')
                    ]); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Reflash check:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::select_html('check_reflash', [
                        '0' => __('Close', 'wp-postviews-plus'),
                        '1' => __('Open', 'wp-postviews-plus')
                    ]); ?>
					<?php _e('Check is based on IP.', 'wp-postviews-plus'); ?><br>
					<?php _e('Reflash timeout:', 'wp-postviews-plus'); ?>
					<?php self::input_html('timeout', 'small-text'); ?> <?php _e('second.', 'wp-postviews-plus'); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('BOT user_agent:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::textarea_html('botagent', 'large-text code'); ?>
					<?php _e('For each BOT user_agent one line.', 'wp-postviews-plus'); ?>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php _e('Template Options', 'wp-postviews-plus'); ?>
		</h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('All views Template:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::input_html('template', 'regular-text'); ?><br>
					<?php _e('Allowed Variables:', 'wp-postviews-plus'); ?> - <code>%VIEW_COUNT%</code><br><br>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Only user views Template:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::input_html('user_template', 'regular-text'); ?><br>
					<?php _e('Allowed Variables:', 'wp-postviews-plus'); ?> - <code>%VIEW_COUNT%</code><br><br>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Only bot views Template:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::input_html('bot_template', 'regular-text'); ?><br>
					<?php _e('Allowed Variables:', 'wp-postviews-plus'); ?> - <code>%VIEW_COUNT%</code>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Most viewed Template:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::textarea_html('most_viewed_template', 'large-text code'); ?>
					<?php _e('Allowed Variables:', 'wp-postviews-plus'); ?> - <code>%VIEW_COUNT%</code> - <code>%POST_TITLE%</code> - <code>%POST_EXCERPT%</code> - <code>%POST_CONTENT%</code> - <code>%POST_DATE%</code> - <code>%POST_URL%</code> - <code>%POST_THUMBNAIL%</code>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Size of post thumbnail: ', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php _e('Width: ', 'wp-postviews-plus'); ?> <?php self::input_html('set_thumbnail_size_w', 'small-text'); ?><br>
					<?php _e('Height: ', 'wp-postviews-plus'); ?> <?php self::input_html('set_thumbnail_size_h', 'small-text'); ?>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php _e('Display Options', 'wp-postviews-plus'); ?>
		</h2>
		<p><?php _e('These options specify where the view counts should be displayed and to whom.<br>Note that the theme files must contain a call to <code>the_views()</code> in order for any view count to be displayed.', 'wp-postviews-plus'); ?>
		</p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('Home Page:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::select_html('display_home', [
                        '0' => __('Display to everyone', 'wp-postviews-plus'),
                        '1' => __('Display to registered users only', 'wp-postviews-plus'),
                        '2' => __('Don\'t display on home page', 'wp-postviews-plus'),
                    ]); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Singe Posts:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::select_html('display_single', [
                        '0' => __('Display to everyone', 'wp-postviews-plus'),
                        '1' => __('Display to registered users only', 'wp-postviews-plus'),
                        '2' => __('Don\'t display on single posts', 'wp-postviews-plus'),
                    ]); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Pages:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::select_html('display_page', [
                        '0' => __('Display to everyone', 'wp-postviews-plus'),
                        '1' => __('Display to registered users only', 'wp-postviews-plus'),
                        '2' => __('Don\'t display on pages', 'wp-postviews-plus'),
                    ]); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Archive Pages:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::select_html('display_archive', [
                        '0' => __('Display to everyone', 'wp-postviews-plus'),
                        '1' => __('Display to registered users only', 'wp-postviews-plus'),
                        '2' => __('Don\'t display on archive pages', 'wp-postviews-plus'),
                    ]); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Search Pages:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::select_html('display_search', [
                        '0' => __('Display to everyone', 'wp-postviews-plus'),
                        '1' => __('Display to registered users only', 'wp-postviews-plus'),
                        '2' => __('Don\'t display on search pages', 'wp-postviews-plus'),
                    ]); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Other Pages:', 'wp-postviews-plus'); ?>
				</th>
				<td>
					<?php self::select_html('display_other', [
                        '0' => __('Display to everyone', 'wp-postviews-plus'),
                        '1' => __('Display to registered users only', 'wp-postviews-plus'),
                        '2' => __('Don\'t display on other pages', 'wp-postviews-plus'),
                    ]); ?>
				</td>
			</tr>
		</table>

		<p class="submit">
			<?php submit_button(null, 'primary', 'pvp-submit', false); ?>
			<?php submit_button(__('Reset to Default', 'wp-postviews-plus'), 'primary', 'pvp-defaults', false); ?>
		</p>
	</form>
</div>
<?php
        /*
<h2><?php _e('Uninstall WP-PostViews Plus', 'wp-postviews-plus'); ?></h2>
<p><?php _e('Deactivating WP-PostViews Plus plugin does not remove any data that may have been created, such as the views data. To completely remove this plugin, you can uninstall it here.', 'wp-postviews-plus'); ?></p>
<div style="color: red">
    <h3 class="title"><?php _e('WARNING:', 'wp-postviews-plus'); ?></h3>
    <?php _e('Once uninstalled, this cannot be undone. You should use a Database Backup plugin of WordPress to back up all the data first.', 'wp-postviews-plus'); ?>
    <p>
        <?php printf(__('The database table <strong>%s</strong> will be DELETED.', 'wp-postviews-plus'), $wpdb->postviews_plus); ?><br>
        <?php printf(__('The database table <strong>%s</strong> will be DELETED.', 'wp-postviews-plus'), $wpdb->postviews_plus_reflash); ?>
    </p>
    <?php _e('The following WordPress Options/PostMetas will be DELETED:', 'wp-postviews-plus'); ?><br>
    <?php _e('WordPress Options', 'wp-postviews-plus'); ?><br>
    <ol>
        <?php foreach( self::$views_settings as $settings) {
            echo '<li>'.$settings.'</li>'."\n";
        } ?>
    </ol>
    <?php _e('WordPress PostMetas', 'wp-postviews-plus'); ?><br>
    <ol>
        <?php foreach( self::$views_postmetas as $postmeta ) {
            echo '<li>'.$postmeta.'</li>'."\n";
        } ?>
    </ol>
</div>
<form method="post" action="">
    <input type="checkbox" name="uninstall_views_yes" value="yes" />&nbsp;<?php _e('Yes', 'wp-postviews-plus'); ?>
    <input type="submit" name="do" value="<?php _e('UNINSTALL WP-PostViews Plus', 'wp-postviews-plus'); ?>" class="button" onclick="return confirm('<?php _e('You Are About To Uninstall WP-PostViews Plus From WordPress.\nThis Action Is Not Reversible.\n\n Choose [Cancel] To Stop, [OK] To Uninstall.', 'wp-postviews-plus'); ?>')" />
    <?php wp_nonce_field('wp-pvp-uninstall', 'wp-pvp-uninstall'); ?>
</form>
        */
    }

    protected static function select_html($key, $list)
    {
        echo '<select name="' . WP_PVP::$option_prefix . 'options[' . $key . ']">';
        foreach ($list as $value => $name) {
            echo '<option value="' . esc_attr($value) . '" ' . selected(WP_PVP::$options[$key], $value, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }

    protected static function input_html($key, $class = '')
    {
        echo '<input type="text" name="' . WP_PVP::$option_prefix . 'options[' . $key . ']" value="' . esc_attr(WP_PVP::$options[$key]) . '" class="' . esc_attr($class) . '" />';
    }

    protected static function textarea_html($key, $class = '')
    {
        $rows = 3;
        $value = WP_PVP::$options[$key];
        if (is_array($value)) {
            $rows = count($value) + 1;
            $value = implode("\n", $value);
        }
        echo '<textarea name="' . WP_PVP::$option_prefix . 'options[' . $key . ']" class="' . esc_attr($class) . '" cols="65" rows="' . $rows . '">' . esc_html($value) . '</textarea>';
    }
}

WP_PVP_admin::init();
