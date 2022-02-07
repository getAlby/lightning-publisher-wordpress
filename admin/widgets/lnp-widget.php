<?php
  class LnpWidget extends WP_Widget {
    function __construct($has_paid, $lnp_options) {
      $this->has_paid = $has_paid;
      $this->lnp_options = $lnp_options;

      parent::__construct(
        'lnp_widget', // Base ID
        'Lightning Subscription', // Name
         array( 'description' => __( 'A Lightning Widget', 'text_domain' ), ) // Args
      );
    }

    public function widget( $args, $instance ) {
      extract($args);
      $title = apply_filters( 'widget_title', $instance['title'] );
      $text = $instance['text'];
      $button = $instance['button'];
    ?>
      <?php echo $before_widget ?>
      <?php echo $before_title . $title . $after_title; ?>
      <div class="wp-lnp-all">
        <?php
          if ($this->has_paid) {
            echo '<p class="wp-all-confirmation">' . $this->lnp_options['all_confirmation'] . '</p>';
          } else {
        ?>
          <p class="wp-lnp-all-text"><?php echo str_replace(['%days', '%amount'], [$this->lnp_options['all_days'], $this->lnp_options['all_amount']], $text) ?></p>
          <p><button class="wp-lnp-btn"><?php echo $button ?></button></p>
        <?php
          }
        ?>
      </div>
      <?php echo $after_widget ?>
    <?php
    }

    public function form( $instance ) {
      $title = empty($instance['title']) ? 'Subscription' : $instance['title'];
      $text = empty($instance['text']) ? 'Get articles for %days days only %amount sats' : $instance['text'];
      $button = empty($instance['button']) ? 'Pay' : $instance['button'];
    ?>
      <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
      </p>
      <p>
        <label for="<?php echo $this->get_field_id( 'text' ); ?>"><?php _e( 'Text:' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'text' ); ?>" name="<?php echo $this->get_field_name( 'text' ); ?>" type="text" value="<?php echo esc_attr( $text ); ?>" />
      </p>
      <p>
        <label for="<?php echo $this->get_field_id( 'button' ); ?>"><?php _e( 'Button:' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'button' ); ?>" name="<?php echo $this->get_field_name( 'button' ); ?>" type="text" value="<?php echo esc_attr( $button ); ?>" />
      </p>
    <?php
    }

    public function update( $new_instance, $old_instance ) {
      $instance = array();
      $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
      $instance['text'] = ( ! empty( $new_instance['text'] ) ) ? strip_tags( $new_instance['text'] ) : '';
      $instance['button'] = ( ! empty( $new_instance['button'] ) ) ? strip_tags( $new_instance['button'] ) : '';
      return $instance;
    }
  }
?>
