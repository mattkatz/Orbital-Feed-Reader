<?php
if ( !function_exists( 'add_action' ) ) {
  echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
  exit;
}
class orbital_blogroll_widget extends WP_Widget {
  // constructor
  function orbital_blogroll_widget() {
    parent::WP_Widget(false, $name = __('Orbital Blogroll', 'orbital_blogroll_widget') );
  }

  // widget form creation
  function form($instance) {
    // Check values
    if( $instance) {
      $title = esc_attr($instance['title']);
      $user = esc_attr($instance['user']);
    } else {
      $title = '';
      $text = '';
      $textarea = '';
      $user = '';
    }
    $all_users = get_users(array('role'=>'administrator'));
    ?>

    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'orbital_blogroll_widget'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('user'); ?>"><?php _e('Whose feeds should this widget display?', 'orbital_blogroll_widget'); ?></label>
      <select class="widefat" id="<?php echo $this->get_field_id('user'); ?>" name="<?php echo $this->get_field_name('user'); ?>" >
        <?php 
        foreach($all_users as $a_user){
        ?>
        <option value="<?php echo $a_user->ID ?>" <?php if ($user == $a_user->ID) { echo ' selected ';}?>><?php echo $a_user->user_nicename ?></option>
        <?php
        }
        ?>
      </select>
    </p>


    <?php
  }
  // update widget
  function update($new_instance, $old_instance) {
    $instance = $old_instance;
    // Fields
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['user'] = strip_tags($new_instance['user']);
    return $instance;
  }
  // display widget
  function widget($args, $instance) {
    require_once('feeds.php');
    extract( $args );

    // these are the widget options
    $title = apply_filters('widget_title', $instance['title']);
    $user = $instance['user'];
    echo $before_widget;
    // Display the widget
    echo '<div class="widget-text wp_widget_plugin_box">';

    // Check if title is set
    if ( $title ) {
      echo $before_title . $title . $after_title;
    }

    $feeds = OrbitalFeeds::get($user);
    echo '<ul id="orbital-feeds" class="orbital feeds">';
    foreach($feeds as $feed){
      if($feed->private){continue;}
      echo "<li class='orbital feed'>";
      echo "<a href='$feed->site_url'>$feed->feed_name</a>";
      echo "</li>";
    }
    echo '</ul>';

    echo '</div>';
    echo $after_widget;
  }
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("orbital_blogroll_widget");'));


?>
