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

$page_title = "WordPrss";
$menu_title = "CONSUME";
$capability = 'edit_posts';
$slug = 'wordprss.php';
global $wordprss_db_version;
$wordprss_db_version = '0.1';
global $wordprss_db_version_opt_string;
$wordprss_db_version_opt_string = 'wordprss_db_version';
global $tbl_prefix;
$tbl_prefix = 'wprss_' ;

if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
      exit;
}
require_once 'backend.php';


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

/* Reqister our scripts so they can be enqueued
 */
function wprss_admin_init(){
  //Register the js that we need
  wp_register_script( 'emberjs_script', plugins_url('/ember-0.9.3.min.js', __FILE__) ,array('jquery'));
  wp_register_script( 'wordprss_script', plugins_url('/wprss.javascript', __FILE__),array('jquery', 'json2', 'emberjs_script'));
  wp_register_script( 'mainwindow_script', plugins_url('/mainwindow.javascript', __FILE__),array('jquery', 'json2', 'emberjs_script','wordprss_script'));
  wp_register_script( 'feedmgmt_script', plugins_url('/feed_management.javascript', __FILE__),array('jquery', 'json2', 'emberjs_script'));
  //keyboard shortcut handling
  wp_register_script( 'keymaster_script', plugins_url('/js/keymaster.min.js', __FILE__),array('jquery', 'emberjs_script'));
  /* Register our stylesheet. */
  wp_register_style( 'wprsscss', plugins_url('style.css', __FILE__) );

}

// these are common to all of our pages
function wprss_enqueue_scripts()
{
  wp_enqueue_script( 'json2' );
  wp_enqueue_script('emberjs_script');
  wp_enqueue_script('wordprss_script');
  wp_localize_script( 'wordprss_script', 'get_url', array( 
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
  //here we set up hook like the shortcuts
  //also things like what to do when a feed is selected
  wp_enqueue_script('mainwindow_script');

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
//Something is wrong.  this thing never fires.
function wprss_uninstall_db()
{
  //We should remove the DB option for the db version
  delete_option('wordprss_db_version');
  //TODO clean up all the tables
  global $wpdb;
  global $tbl_prefix;
  $tables =array('feeds','user_feeds','entries','user_entries');
  foreach($tables as $table){
    $sql = "DROP TABLE ". $wpdb->prefix.$tbl_prefix.$table.";";
    $wpdb->query($sql);

  }
  

}

function wprss_install_db_and_data(){
  require_once 'install_upgrade.php';
  wprss_install_db();
  wprss_install_data();
}
add_action('admin_menu', 'wprss_plugin_menu');
add_action( 'admin_init', 'wprss_admin_init' );
//Turns out you can't just do __FILE__ like it says in the wordpress codex!
register_activation_hook(WP_PLUGIN_DIR.'/Wordprss/wordprss.php','wprss_install_db_and_data');
register_deactivation_hook(WP_PLUGIN_DIR.'/Wordprss/wordprss.php','wprss_uninstall_db');

?>
