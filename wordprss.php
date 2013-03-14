<?php
/*
* Plugin Name: WordPrss
* Plugin URI: http://mattkatz.github.com/Wordprss/
* Description:A voracious feed reader
* Version: 0.1
* Author: Matt Katz
* Author URI: http://www.morelightmorelight.com
* License: GPL2
* */
$page_title = "Voracious Reader";
$menu_title = "CONSUME";
$capability = 'edit_posts';
$slug = 'wordprss.php';
global $wprss_db_version ;
$wprss_db_version = '0.1';
global $wordprss_db_version_opt_string;
$wrss_db_v_opt_string = 'wordprss_db_version';
global $tbl_prefix;
$tbl_prefix = 'wprss_' ;

if ( !function_exists( 'add_action' ) ) {
  echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
  exit;
}
require_once 'backend.php';

add_action('plugins_loaded', 'wprss_update_db_check');
function wprss_update_db_check(){
  global $wprss_db_version;
  global $wrss_db_v_opt_string;
  if(get_site_option($wrss_db_v_opt_string) != $wprss_db_version){
    _log(get_site_option($wrss_db_v_opt_string) );
    //upgrayedd the db
    _log("Wordprss: Installing or Upgrayedding Database");
    //Two D's for a double dose of that primping.
    require_once 'install_upgrade.php';
    wprss_install_db();
    update_option($wrss_db_v_opt_string, $wprss_db_version);
  }
}
function wprss_sample_data_check(){
  $samples_loaded = get_site_option('wprss_sample_data_loaded');
  _log("Are the samples loaded: $samples_loaded ");
  if( $samples_loaded != 1)
  {
    _log("Wordprss: Installing Sample Data");
    require_once 'install_upgrade.php';
    wprss_install_data();
    update_option('wprss_sample_data_loaded', 1);
  }
}

add_action('admin_menu', 'wprss_plugin_menu');
function wprss_plugin_menu(){
  //We add the hook for our menu item on the main menu
  $main = add_menu_page('WordPrss', 'Consume','edit_posts','wordprss.php','generate_main_page');
  //add hook for feed management page
  $feed_mgmt = add_submenu_page('wordprss.php', 'Manage Feeds', 'Feeds', 'edit_posts','subscriptions_management','feed_management');
  
  
   /* Using registered $page handle to hook script load */
  add_action('admin_print_styles-' . $main, 'wprss_enqueue_scripts');
  add_action('admin_print_styles-' . $main, 'wprss_main_scripts');
  add_action('admin_print_styles-' . $feed_mgmt, 'wprss_enqueue_scripts');

}

add_action( 'admin_init', 'wprss_admin_init' );
/* Reqister our scripts so they can be enqueued
 */
function wprss_admin_init(){
  //Register the js that we need
  wp_register_script( 'handlebars_script', plugins_url('/js/handlebars-1.0.rc.1.js', __FILE__) ,array('jquery'));
  //wp_register_script( 'emberjs_script', plugins_url('/js/ember-1.0.pre.min.js', __FILE__) ,array('jquery','handlebars_script'));
  wp_register_script( 'angular_script', plugins_url('/js/angular.js', __FILE__) ,array('jquery',));
  wp_register_script( 'angular_app_script', plugins_url('/js/app.js', __FILE__) ,array('jquery','angular_script'));
  wp_register_script( 'angular_controllers_script', plugins_url('/js/controllers.js', __FILE__) ,array('jquery','angular_app_script','angular_script'));
  //wp_register_script( 'geturl_script', plugins_url('/js/geturl.js', __FILE__) ,array());
  //wp_register_script( 'wordprss_script', plugins_url('/wprss.javascript', __FILE__),array('jquery', 'json2', 'emberjs_script'));
  //wp_register_script( 'feedmgmt_script', plugins_url('/feed_management.javascript', __FILE__),array('jquery', 'json2', 'emberjs_script'));
  //keyboard shortcut handling
  //wp_register_script( 'keymaster_script', plugins_url('/js/keymaster.min.js', __FILE__),array('jquery'));
  //wp_register_script( 'endless_scroll', plugins_url('/js/jquery.endless-scroll.js', __FILE__),array('jquery'));
  //wp_register_script( 'jquery_waypoints', plugins_url('/js/waypoints.js', __FILE__),array('jquery'));
  //wp_register_script( 'mainwindow_script', plugins_url('/mainwindow.javascript', __FILE__),array('jquery', 'json2', 'emberjs_script','wordprss_script','keymaster_script','endless_scroll'));
  /* Register our stylesheet. */
  wp_register_style( 'wprsscss', plugins_url('style.css', __FILE__) );

}

// these are common to all of our pages
function wprss_enqueue_scripts()
{
  wp_enqueue_script( 'json2' );
  //wp_enqueue_script('emberjs_script');
  wp_enqueue_script('angular_script');
  wp_enqueue_script('angular_controllers_script');
  

  //wp_enqueue_script('handlebars_script');
  //wp_enqueue_script('wordprss_script');

  wp_localize_script( 'angular_controllers_script', 'get_url', array( 
    'ajaxurl' => admin_url( 'admin-ajax.php' ) ,
    // generate a nonce with a unique ID "myajax-post-comment-nonce"
    // so that you can check it later when an AJAX request is sent
    'nonce_a_donce' => wp_create_nonce( 'nonce_a_donce' ),
  ) );
  //add our stylesheet
  wp_enqueue_style('wprsscss');
}

//these are just for the main page
function wprss_main_scripts()
{
  //here we set up our keyboard shortcuts
  wp_enqueue_script('keymaster_script');
  wp_enqueue_script('endless_scroll');
  wp_enqueue_script('jquery_waypoints');
  //here we set up hook like the shortcuts
  //also things like what to do when a feed is selected
  //wp_enqueue_script('mainwindow_script');

}

function generate_main_page()
{
  require_once('mainwindow.php');
}

function feed_management(){

  //I HAVE VIOLATED YAGNI bc this has nothing in it.
  //wp_enqueue_script('feedmgmt_script');
  require_once('feed_management.php');
}
function wprss_uninstall_db()
{

  //We should remove the DB option for the db version
  delete_option($wrss_db_v_opt_string);
  //clean up all the tables
  global $wpdb;
  global $tbl_prefix;
  $tables =array('feeds','user_feeds','entries','user_entries');
  foreach($tables as $table){
    $sql = "DROP TABLE ". $wpdb->prefix.$tbl_prefix.$table.";";
    $wpdb->query($sql);

  }
}
add_filter('cron_schedules', 'one_hour');
function one_hour( $schedules ) {
  $schedules['1hour'] = array(
    'interval' => 36000, //that's how many seconds in 1 hour, for the unix timestamp
    'display' => __('60 Minutes')
  );
  return $schedules;
}
add_action('wprss_update_event', 'wprss_update_job');
function wprss_set_up_cron(){
  wp_schedule_event( current_time( 'timestamp' ), '1hour', 'wprss_update_event');
}

function wprss_update_job(){
  //call feeds update.
  _log('wprss_update_job called');
  wprss_update_feeds();
  //TODO somehow signal a pop to the front end that the job, it is done.
}

function wprss_activate(){
  wprss_update_db_check();
  wprss_sample_data_check();
  wprss_set_up_cron();
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
//Turns out you can't just do __FILE__ like it says in the wordpress codex!
register_activation_hook(WP_PLUGIN_DIR.'/Wordprss/wordprss.php','wprss_activate');

register_uninstall_hook(WP_PLUGIN_DIR.'/Wordprss/wordprss.php','wprss_uninstall_db');

?>
