<?php
  class LnpWidget extends WP_Widget {
    function __construct() {
      parent::__construct(
        'lnp_widget', // Base ID
        'Lightning Subscription', // Name
         array( 'description' => __( 'A Lightning Widget', 'text_domain' ), ) // Args
      );
    }

    public function widget( $args, $instance ) {
      extract($args);
      $title = apply_filters( 'widget_title', $instance['title'] );

      echo $before_widget;
      if ( ! empty( $title ) ) {
          echo $before_title . $title . $after_title;
      }
      echo "hallo";
      echo __( 'Hello, World!', 'text_domain' );
      echo $after_widget;
    }
    public function form( $instance ) {
    }
    public function update( $new_instance, $old_instance ) {
    }
  }
?>
