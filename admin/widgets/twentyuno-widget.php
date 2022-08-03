<?php
// If this file is called directly, abort.
defined('WPINC') || die;

class TwentyunoWidget extends WP_Widget
{
    function __construct($lnp_options)
    {
        $this->lnp_options = $lnp_options;

        parent::__construct(
            'lnp_widget_twentyuno', // Base ID
            'Twentyuno Lightning Payment Widget', // Name
            array( 'description' => __('The Lightning Widget by https://widgets.twentyuno.net', 'text_domain'), ) // Args
        );
    }

    public function widget( $args, $instance )
    {
        extract($args);
        $title = $instance['title'];
        $color = $instance['color'];
        $image = $instance['image'];
        ?>
        <?php echo $before_widget ?>
      <div class="wp-lnp-twentyuno-widget">
        <lightning-widget name="<?php esc_attr(echo $title) ?>" accent="<?php echo esc_attr($color) ?>" to="<?php echo esc_attr($this->lnp_options['lnurl']) ?>" image="<?php echo esc_attr($image) ?>" />
        <script src="https://embed.twentyuno.net/js/app.js"></script>
      </div>
        <?php echo $after_widget ?>
        <?php
    }

    public function form( $instance )
    {
        $title = $instance['title'];
        $image = $instance['image'];
        $color = $instance['color'];
        ?>
      <p>
        <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('image'); ?>"><?php _e('Image URL:'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('image'); ?>" name="<?php echo $this->get_field_name('image'); ?>" type="text" value="<?php echo esc_attr($image); ?>" />
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('color'); ?>"><?php _e('Color:'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('color'); ?>" name="<?php echo $this->get_field_name('color'); ?>" type="text" value="<?php echo esc_attr($color); ?>" />
      </p>
        <?php
    }

    public function update( $new_instance, $old_instance )
    {
        $instance = array();
        $instance['title'] = ( ! empty($new_instance['title']) ) ? strip_tags($new_instance['title']) : '';
        $instance['image'] = ( ! empty($new_instance['image']) ) ? strip_tags($new_instance['image']) : '';
        $instance['color'] = ( ! empty($new_instance['color']) ) ? strip_tags($new_instance['color']) : '';
        return $instance;
    }
}
?>
