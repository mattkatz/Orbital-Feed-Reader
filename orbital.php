<?php
/*
* Plugin Name: Orbital Feed Reader
* Plugin URI: http://mattkatz.github.com/Orbital-Feed-Reader/
* Description:A voracious feed reader
* Version: 0.1.3
* Author: Matt Katz
* Author URI: http://www.morelightmorelight.com
* License: GPL2
* */
global $orbital_slug;
$orbital_slug = 'orbital.php';
global $orbital_settings_slug;
$orbital_settings_slug = 'orbital_plugin_settings';
global $orbital_db_version ;
$orbital_db_version = '0.1';
global $orbital_db_version_opt_string;
$orbital_db_version_opt_string = 'orbital_db_version';
global $tbl_prefix;
$tbl_prefix = 'orbital_' ;

if ( !function_exists( 'add_action' ) ) {
  echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
  exit;
}
require_once 'backend.php';

//add_action('plugins_loaded', 'orbital_update_db_check');
function orbital_update_db_check(){
  global $orbital_db_version;
  global $orbital_db_version_opt_string;
  if(get_site_option($orbital_db_version_opt_string) != $orbital_db_version){
    _log(get_site_option($orbital_db_version_opt_string) );
    //upgrayedd the db
    _log("orbital: Installing or Upgrayedding Database");
    //Two D's for a double dose of that primping.
    require_once 'install_upgrade.php';
    orbital_install_db();
    update_option($orbital_db_version_opt_string, $orbital_db_version);
  }
  _log('finished DB update check');
}
function orbital_sample_data_check(){
  _log('check for sampledata');
  $samples_loaded = get_site_option('orbital_sample_data_loaded');
  _log("Are the samples loaded: $samples_loaded ");
  if( $samples_loaded != 1)
  {
    _log("orbital: Installing Sample Data");
    require_once 'install_upgrade.php';
    orbital_install_data();
    update_option('orbital_sample_data_loaded', 1);
  }
  else{
    _log('Sample Date already in there, never mind');

  }
}

add_action('admin_menu', 'orbital_plugin_menu');
function orbital_plugin_menu(){
  //TODO should this be global? Probably not. 
  global $orbital_slug;
  global $orbital_settings_slug;

  require_once 'backend.php';
  $unread_count = OrbitalFeeds::get_unread_count();

  $page_title = '('.$unread_count.') Orbital';
  $menu_title = $page_title;
  $capability = 'edit_posts';
  //We add the hook for our menu item on the main menu
  $main = add_menu_page( $page_title, $menu_title, $capability, $orbital_slug, 'generate_main_page',plugins_url('img/satellite.svg',__FILE__));
  //Settings page
  $settings = add_submenu_page( $orbital_slug, 'Settings', 'Settings', $capability, $orbital_settings_slug, 'orbital_settings');
  //add hook for feed management page
  //TODO remove this. We don't need submenu pages now.
  //$feed_mgmt = add_submenu_page('orbital.php', 'Manage Feeds', 'Feeds', 'edit_posts','subscriptions_management','feed_management');
  /* Using registered $page handle to hook script load */
  add_action('admin_print_styles-' . $main, 'orbital_enqueue_scripts');
  add_action('admin_print_styles-' . $main, 'orbital_main_scripts');
  //add_action('admin_print_styles-' . $feed_mgmt, 'orbital_enqueue_scripts');
  //_log(_get_cron_array());
  //_log(date("Y-m-d H:i:s",wp_next_scheduled('orbital_update_event')));



}
/* to style our SVG icon we need to enqueue one style to fix width */
add_action('admin_head', 'orbital_icon_style');
function orbital_icon_style(){
  echo '<style>
    #toplevel_page_orbital div.wp-menu-image img {
      width:16px;
    
    }
    </style>';


}

add_action( 'admin_init', 'orbital_admin_init' );
/* Reqister our scripts so they can be enqueued
 */
function orbital_admin_init(){
  //Register the js that we need
  wp_register_script( 'handlebars_script', plugins_url('/js/handlebars-1.0.rc.1.js', __FILE__) ,array('jquery'));
  wp_register_script( 'angular_script', plugins_url('/js/angular.js', __FILE__) ,array('jquery',));
  wp_register_script( 'angular_sanitize', plugins_url('/js/angular-sanitize.js', __FILE__) ,array('angular_script',));
  wp_register_script( 'ng_infinite_scroll',plugins_url('/js/ng-infinite-scroll.min.js', __FILE__) ,array('angular_script',));

  wp_register_script( 'angular_app_script', plugins_url('/js/app.js', __FILE__) ,array('jquery','angular_script'));
  wp_register_script( 'angular_controllers_script', plugins_url('/js/controllers.js', __FILE__) ,array('jquery','underscore','angular_app_script','angular_script','ng_infinite_scroll',));

  wp_register_script('scrollToEntry',  plugins_url('/js/scrollToEntry.js', __FILE__),array('jquery'));
  //keyboard shortcut handling
  wp_register_script( 'keymaster_script', plugins_url('/js/keymaster.min.js', __FILE__),array('jquery'));
  //wp_register_script( 'jquery_waypoints', plugins_url('/js/waypoints.js', __FILE__),array('jquery'));
  /* Register our stylesheet. */
  wp_register_style( 'orbitalcss', plugins_url('style.css', __FILE__) );

  /* Register some settings for the settings menu */
  register_setting( 'orbital-settings-group', 'orbital-setting' );
  add_settings_section( 'section-one', 'Blog This Settings', 'section_one_callback',  'orbital-plugin-settings');
  add_settings_field( 'field-one', 'I want to quote the whole article if there is no text selected', 'field_one_callback',   'orbital-plugin-settings', 'section-one' );
  

}
function section_one_callback() {
    echo 'How should the Blog This! button work?';
}
function field_one_callback() {
    $settings = (array) get_option( 'orbital-setting' );
    $quote_text = esc_attr($settings['quote-text']);
    echo "<input type='checkbox' name='orbital-setting[quote-text]' value=1 ". checked( 1, $quote_text, false ) . " />";
}

// these are common to all of our pages
function orbital_enqueue_scripts()
{
  wp_enqueue_script( 'json2' );
  wp_enqueue_script('ng-infinite-scroll');
  wp_enqueue_script('angular_script');
  wp_enqueue_script('angular_sanitize');
  wp_enqueue_script('angular_app_script');
  wp_enqueue_script('angular_controllers_script');
  wp_enqueue_script('scrollToEntry');

  wp_localize_script( 'angular_controllers_script', 'opts', array( 
    'ajaxurl' => admin_url( 'admin-ajax.php' ) ,
    // generate a nonce with a unique ID "myajax-post-comment-nonce"
    // so that you can check it later when an AJAX request is sent
    'nonce_a_donce' => wp_create_nonce( 'nonce_a_donce' ),
    //our main settings
    'settings' => (array) get_option('orbital-setting'),
  ) );
  //add our stylesheet
  wp_enqueue_style('orbitalcss');
}

//these are just for the main page
function orbital_main_scripts()
{
  //here we set up our keyboard shortcuts
  wp_enqueue_script('keymaster_script');
  //here we set up hook like the shortcuts
}

/* This is the main orbital page with all the feed reading goodness */
function generate_main_page()
{
  require_once('mainwindow.php');
}
/* This is the settings page. */
function orbital_settings()
{
  require_once 'settings.php';
}

function feed_management(){

  //I HAVE VIOLATED YAGNI bc this has nothing in it.
  //wp_enqueue_script('feedmgmt_script');
  require_once('feed_management.php');
}
function orbital_uninstall_db()
{

  //We should remove the DB option for the db version
  delete_option($orbital_db_version_opt_string);
  //clean up all the tables
  global $wpdb;
  global $tbl_prefix;
  $tables =array('feeds','user_feeds','entries','user_entries');
  foreach($tables as $table){
    $sql = "DROP TABLE ". $wpdb->prefix.$tbl_prefix.$table.";";
    $wpdb->query($sql);

  }
}
add_filter('cron_schedules', 'orbital_add_cron_schedules');
function orbital_add_cron_schedules( $schedules ) {
  $schedules['5mins'] = array(
    'interval' => 600, //that's how many seconds in 1 hour, for the unix timestamp
    'display' => __('5 minutes')
  );
  

  return $schedules;
}
add_action('orbital_update_event', 'orbital_update_job');
function orbital_set_up_cron(){
	  wp_schedule_event( time(), 'hourly', 'orbital_update_event');
}

function orbital_update_job(){
  //call feeds update.
  _log('orbital_update_job called');
  orbital_update_feeds();
  //TODO somehow signal a pop to the front end that the job, it is done.
}

function orbital_activate(){
  _log('orbital activate begin');
  orbital_update_db_check();
  _log('orbital sample begin');
  orbital_sample_data_check();
  orbital_set_up_cron();
  _log('orbital activate end');
}

add_filter('query_vars','plugin_add_trigger');
function plugin_add_trigger($vars) {
  $vars[] = 'export_opml';
  return $vars;
}

add_action('template_redirect', 'plugin_trigger_check');
function plugin_trigger_check() {
  if(0 == wp_get_current_user()->ID){
    //not logged in - return;
    return;
  }
  if(intval(get_query_var('export_opml')) == wp_get_current_user()->ID) {
    require_once 'export_opml.php';
    exit;
  }
}

//Add settings page
add_action( 'admin_menu', 'orbital_admin_menu' );
function orbital_admin_menu() {
  global $orbital_settings_slug;
    add_options_page( 'Orbital', 'Orbital', 'manage_options', $orbital_settings_slug, 'orbital_options_page' );
}
function orbital_options_page() {
  require_once "settings.php";
}

/**
 * Adds a simple WordPress pointer to Settings menu
 * Thanks to http://www.wpexplorer.com/making-themes-plugins-more-usable/
 */
 
function orbital_enqueue_pointer_script_style( $hook_suffix ) {
 
  // Assume pointer shouldn't be shown
  $enqueue_pointer_script_style = false;

  // Get array list of dismissed pointers for current user and convert it to array
  $dismissed_pointers = explode( ',', get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

  // Check if our pointer is not among dismissed ones
  if( !in_array( 'orbital_menu_pointer', $dismissed_pointers ) ) {
    $enqueue_pointer_script_style = true;
    
    // Add footer scripts using callback function
    add_action( 'admin_print_footer_scripts', 'orbital_pointer_print_scripts' );
  }

  // Enqueue pointer CSS and JS files, if needed
  if( $enqueue_pointer_script_style ) {
    wp_enqueue_style( 'wp-pointer' );
    wp_enqueue_script( 'wp-pointer' );
  }
  
}
add_action( 'admin_enqueue_scripts', 'orbital_enqueue_pointer_script_style' );

function orbital_pointer_print_scripts() {

  $pointer_content  = "<h3>Your Orbital Feed Reader is installed here!</h3>";
  $pointer_content .= "<p>See a count of all your unread items right here in the menu.</p>";
  ?>
  
  <script type="text/javascript">
  //<![CDATA[
  jQuery(document).ready( function($) {
    $('#toplevel_page_orbital').pointer({
      content:    '<?php echo $pointer_content; ?>',
      position:    {
                edge:  'left', // arrow direction
                align:  'center' // vertical alignment
              },
      pointerWidth:  350,
      close:      function() {
                $.post( ajaxurl, {
                    pointer: 'orbital_menu_pointer', // pointer ID
                    action: 'dismiss-wp-pointer'
                });
              }
    }).pointer('open');
  });
  //]]>
  </script>

<?php
} 

/**
 * Add action links in Plugins table
 */
 
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'orbital_plugin_action_links' );
function orbital_plugin_action_links( $links ) {
  global $orbital_settings_slug;

	return array_merge(
		array(
			'settings' => '<a href="' . admin_url( "admin.php?page=$orbital_settings_slug" ) . '">' . __( 'Settings', 'ts-fab' ) . '</a>'
		),
		$links
	);

}


register_activation_hook(__FILE__,'orbital_activate');

register_uninstall_hook(__FILE__,'orbital_uninstall_db');

?>
