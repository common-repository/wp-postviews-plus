<?php
defined('WP_PVP_VERSION') or exit('No direct script access allowed');

class WP_Widget_PostViews_Plus extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array('description' => __('WP-PostViews plus views statistics', 'wp-postviews-plus'));
        parent::__construct('views-plus', __('Views Stats', 'wp-postviews-plus'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        $cache = wp_cache_get('widget_postviews_plus', 'widget');
        if (!is_array($cache)) {
            $cache = array();
        }
        if (!isset($args['widget_id'])) {
            $args['widget_id'] = $this->id;
        }
        if (isset($cache[$args['widget_id']])) {
            echo $cache[$args['widget_id']];
            return;
        }

        $title = apply_filters('widget_title', esc_attr($instance['title']));
        $template = $instance['template'];
        $type = esc_attr($instance['type']);
        $mode = esc_attr($instance['mode']);
        $withbot = esc_attr($instance['withbot']);
        $thumbnail_width = intval($instance['thumbnail_width']);
        $thumbnail_height = intval($instance['thumbnail_height']);
        $limit = intval($instance['limit']);
        $chars = intval($instance['chars']);
        $cat_ids = $instance['cat_ids'];
        if (!is_array($cat_ids)) {
            $cat_ids = explode(',', $car_ids);
        }
        $tag_ids = explode(',', esc_attr($instance['tag_ids']));

        ob_start();

        echo $args['before_widget'];
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        echo '<ul>'."\n";
        switch ($type) {
            case 'most_viewed':
                get_most_viewed($mode, $limit, $chars, true, $withbot, $template, $thumbnail_width, $thumbnail_height);
                break;
            case 'most_viewed_category':
                get_most_viewed_category($cat_ids, 'post', $limit, $chars, true, $withbot, $template, $thumbnail_width, $thumbnail_height);
                break;
            case 'most_viewed_tag':
                get_most_viewed_tag($tag_ids, 'post', $limit, $chars, true, $withbot, $template, $thumbnail_width, $thumbnail_height);
                break;
        }
        echo '</ul>'."\n";
        echo $args['after_widget'];

        $cache[$args['widget_id']] = ob_get_flush();
        wp_cache_set('widget_postviews_plus', $cache, 'widget');
    }

    public function update($new_instance, $old_instance)
    {
        if (!isset($new_instance['submit'])) {
            return false;
        }
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['template'] = trim($new_instance['template']);
        $instance['type'] = strip_tags($new_instance['type']);
        if (!in_array($instance['type'], array('most_viewed', 'most_viewed_category', 'most_viewed_tag'))) {
            $instance['type'] = 'most_viewed';
        }
        $instance['mode'] = strip_tags($new_instance['mode']);
        if (!in_array($instance['mode'], array('both', 'post', 'page'))) {
            $instance['mode'] = 'both';
        }
        $instance['withbot'] = ($new_instance['withbot'] == 1) ? 1 : 0;
        $instance['limit'] = intval($new_instance['limit']);
        if ($instance['limit'] <= 0) {
            $instance['limit'] = 10;
        }
        $instance['chars'] = intval($new_instance['chars']);
        $instance['thumbnail_width'] = intval($new_instance['thumbnail_width']);
        $instance['thumbnail_height'] = intval($new_instance['thumbnail_height']);
        if ($instance['limit'] <= 0) {
            $instance['limit'] = 100;
        }
        $instance['cat_ids'] = $new_instance['cat_ids'];
        if (!is_array($instance['cat_ids'])) {
            $instance['cat_ids'] = array(1);
        }
        $instance['tag_ids'] = strip_tags($new_instance['tag_ids']);

        return $instance;
    }

    public function form($instance)
    {
        global $wpdb;
        $instance = wp_parse_args(
            (array) $instance,
            array(
                'title' => __('Views', 'wp-postviews-plus'),
                'template' => WP_PVP::$options['most_viewed_template'],
                'thumbnail_width' => WP_PVP::$options['set_thumbnail_size_w'],
                'thumbnail_height' => WP_PVP::$options['set_thumbnail_size_h'],
                'type' => 'most_viewed',
                'mode' => 'both',
                'limit' => 10,
                'chars' => 100,
                'cat_ids' => '0',
                'tag_ids' => '0',
                'withbot' => '1'
            )
        );
        $title = esc_attr($instance['title']);
        $template = $instance['template'];
        $type = esc_attr($instance['type']);
        $mode = esc_attr($instance['mode']);
        $withbot = esc_attr($instance['withbot']);
        $thumbnail_width = intval($instance['thumbnail_width']);
        $thumbnail_height = intval($instance['thumbnail_height']);
        $limit = intval($instance['limit']);
        $chars = intval($instance['chars']);
        $cat_ids = $instance['cat_ids'];
        if (!is_array($cat_ids)) {
            $cat_ids = explode(',', $car_ids);
        }
        $tag_ids = esc_attr($instance['tag_ids']); ?>
<p>
	<label for="<?=$this->get_field_id('title') ?>"><?=__('Title:', 'wp-postviews-plus') ?></label>
	<input type="text" class="widefat" id="<?=$this->get_field_id('title') ?>" name="<?=$this->get_field_name('title') ?>" value="<?=$title ?>">
</p>
<p>
	<label for="<?=$this->get_field_id('type') ?>"><?=__('Statistics Type:', 'wp-postviews-plus') ?></label><br>
	<select id="<?=$this->get_field_id('type') ?>" name="<?=$this->get_field_name('type') ?>">
		<option value="most_viewed" <?php selected('most_viewed', $type); ?>><?=__('Most Viewed', 'wp-postviews-plus') ?>
		</option>
		<option value="most_viewed_category" <?php selected('most_viewed_category', $type); ?>><?=__('Most Viewed By Category', 'wp-postviews-plus') ?>
		</option>
		<option value="most_viewed_tag" <?php selected('most_viewed_tag', $type); ?>><?=__('Most Viewed By Tag', 'wp-postviews-plus') ?>
		</option>
	</select>
</p>
<p id="<?=$this->get_field_id('mode') ?>_p" <?php if ($type != 'most_viewed') {
            echo('style="display:none;"');
        } ?>>
	<label for="<?=$this->get_field_id('mode') ?>"><?=__('Include Views From:', 'wp-postviews-plus') ?></label>
	<select id="<?=$this->get_field_id('mode') ?>" name="<?=$this->get_field_name('mode') ?>">
		<option value="both" <?php selected('both', $mode); ?>><?=__('Posts &amp; Pages', 'wp-postviews-plus') ?>
		</option>
		<option value="post" <?php selected('post', $mode); ?>><?=__('Posts Only', 'wp-postviews-plus') ?>
		</option>
		<option value="page" <?php selected('page', $mode); ?>><?=__('Pages Only', 'wp-postviews-plus') ?>
		</option>
	</select>
</p>
<p id="<?=$this->get_field_id('cat_ids') ?>_p" <?php if ($type != 'most_viewed_category') {
            echo('style="display:none;"');
        } ?>>
	<label for="<?=$this->get_field_id('cat_ids') ?>"><?=__('Category IDs:', 'wp-postviews-plus') ?></label>
	<select id="<?=$this->get_field_id('cat_ids') ?>" name="<?=$this->get_field_name('cat_ids') ?>[]" size="3" multiple="multiple" class="widefat" style="height:auto;">
		<?php
                $cats = get_categories(array(
                    'orderby' => 'id',
                    'hide_empty' => 0,
                    'taxonomy' => 'category'
                ));
        foreach ($cats as $cat) {
            echo('<option value="' . $cat->term_id . '"' . is_selected($cat->term_id, $cat_ids) . '>' . esc_html($cat->name) . '</option>');
        } ?>
	</select>
	<small><?=__('Seperate mutiple categories with commas.', 'wp-postviews-plus') ?></small>
</p>
<p id="<?=$this->get_field_id('tag_ids') ?>_p" <?php if ($type != 'most_viewed_tag') {
            echo('style="display:none;"');
        } ?>>
	<label for="<?=$this->get_field_id('tag_ids') ?>"><?=__('Tag IDs:', 'wp-postviews-plus') ?></label>
	<input type="text" class="widefat" id="<?=$this->get_field_id('tag_ids') ?>" name="<?=$this->get_field_name('tag_ids') ?>" value="<?=$tag_ids ?>">
	<small><?=__('Seperate mutiple categories with commas.', 'wp-postviews-plus') ?></small>
</p>
<p>
	<label for="<?=$this->get_field_id('template') ?>"><?=__('Views Template:', 'wp-postviews-plus') ?></label><br>
	<textarea id="<?=$this->get_field_id('template') ?>" name="<?=$this->get_field_name('template') ?>" class="widefat"><?=htmlspecialchars(stripslashes($template)) ?></textarea><br>
	<?=__('Allowed Variables:', 'wp-postviews-plus') ?> - %VIEW_COUNT% - %POST_TITLE% - %POST_EXCERPT% - %POST_CONTENT% - %POST_DATE% - %POST_URL% - %POST_THUMBNAIL%
</p>
<p>
	<label for="<?=$this->get_field_id('limit') ?>"><?=__('No. Of Records To Show:', 'wp-postviews-plus') ?></label>
	<input type="text" id="<?=$this->get_field_id('limit') ?>" name="<?=$this->get_field_name('limit') ?>" value="<?=$limit; ?>" size="4" maxlength="2"><br>
	<label for="<?=$this->get_field_id('chars') ?>"><?=__('Maximum Post Title Length (Characters):', 'wp-postviews-plus') ?></label>
	<input type="text" id="<?=$this->get_field_id('chars') ?>" name="<?=$this->get_field_name('chars') ?>" value="<?=$chars ?>" size="4">
	<small><?=__('<strong>0</strong> to disable.', 'wp-postviews-plus') ?> <?=__(' Chinese characters to calculate the two words', 'wp-postviews-plus') ?></small><br>
	<?=__('Size of post thumbnail: ', 'wp-postviews-plus') ?>
	<label for="<?=$this->get_field_id('thumbnail_width') ?>"><?=__('Width: ', 'wp-postviews-plus') ?></label>
	<input type="text" id="<?=$this->get_field_id('thumbnail_width') ?>" name="<?=$this->get_field_name('thumbnail_width') ?>" size="3" value="<?=$thumbnail_width ?>">
	<label for="<?=$this->get_field_id('thumbnail_height') ?>"><?=__('Height: ', 'wp-postviews-plus') ?></label>
	<input type="text" id="<?=$this->get_field_id('thumbnail_height') ?>" name="<?=$this->get_field_name('thumbnail_height') ?>" size="3" value="<?=$thumbnail_height ?>">
</p>
<p>
	<label for="<?=$this->get_field_id('withbot') ?>"><?=__('With BOT Views:', 'wp-postviews-plus') ?></label>
	<select id="<?=$this->get_field_id('withbot') ?>" name="<?=$this->get_field_name('withbot') ?>">
		<option value="1" <?php selected('1', $withbot); ?>><?=__('With BOT', 'wp-postviews-plus') ?>
		</option>
		<option value="0" <?php selected('0', $withbot); ?>><?=__('Without BOT', 'wp-postviews-plus') ?>
		</option>
	</select>
</p>
<input type="hidden" id="<?=$this->get_field_id('submit') ?>" name="<?=$this->get_field_name('submit') ?>" value="1">
<script type="text/javascript">
	jQuery('#<?=$this->get_field_id("type") ?>').change(function() {
		jQuery('#<?=$this->get_field_id("mode") ?>_p').hide();
		jQuery('#<?=$this->get_field_id("cat_ids") ?>_p').hide();
		jQuery('#<?=$this->get_field_id("tag_ids") ?>_p').hide();
		switch (jQuery(this).val()) {
			case 'most_viewed':
				jQuery('#<?=$this->get_field_id("mode") ?>_p').show();
				break;
			case 'most_viewed_category':
				jQuery('#<?=$this->get_field_id("cat_ids") ?>_p').show();
				break;
			case 'most_viewed_tag':
				jQuery('#<?=$this->get_field_id("tag_ids") ?>_p').show();
				break;
			default:
				break;
		}
	});

</script>
<?php
    }
}
