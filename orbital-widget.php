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
      $show_download = esc_attr($instance['show_download']);
    } else {
      $title = '';
      $user = '';
      $show_download = true;
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
    <p>
      <label for="<?php echo $this->get_field_id('show_download'); ?>">
        <?php _e('Show an OPML Download Link?', 'orbital_blogroll_widget'); ?>
      </label>
      <input class='widefat' id="<?php echo $this->get_field_id('show_download'); ?>" name="<?php echo $this->get_field_name('show_download'); ?>" type='checkbox' <?php if ($show_download ){echo "checked";} ?> />
    </p>


    <?php
  }
  // update widget
  function update($new_instance, $old_instance) {
    $instance = $old_instance;
    // Fields
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['user'] = strip_tags($new_instance['user']);
    $instance['show_download'] = strip_tags($new_instance['show_download']);
    return $instance;
  }
  // display widget
  function widget($args, $instance) {
    require_once('feeds.php');
    extract( $args );

    // these are the widget options
    $title = apply_filters('widget_title', $instance['title']);
    $user = $instance['user'];
    $show_download = $instance['show_download'];
    echo $before_widget;
    // Display the widget
    echo '<div class="widget-text wp_widget_plugin_box">';

    // Check if title is set
    if ( $title ) {
      echo $before_title . $title . $after_title;
    }
    if( $show_download){
?>
            <a href="<?php echo site_url("?export_opml=".$user) ?>" style='font-size:10px;'><img id="orbital-opml-icon" class="opml icon" width=16 height=16 src="<?php echo plugins_url("img/opml-icon.svg", __FILE__); ?>">Download <span title='Outline Processor Markup Language or OPML is a standard for Feed Readers to exchange subscription lists'>OPML</span></a>
<?php 
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
