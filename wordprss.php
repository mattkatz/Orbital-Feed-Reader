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
  $hook = add_menu_page('WordPrss', 'Consume','edit_posts','wordprss.php','generate_main_page');
  //TODO add hook for feed management page
  $subhook = add_submenu_page('wordprss.php', 'Manage Feeds', 'Feeds', 'edit_posts','subscriptions_management','feed_management');
  
  //Register the js that we need
  wp_register_script( 'emberjs_script', plugins_url('Wordprss/ember-0.9.3.min.js', dir(__FILE__)) ,array('jquery'));
  wp_register_script( 'wordprss_script', plugins_url('Wordprss/wprss.javascript', dir(__FILE__)),array('jquery', 'json2', 'emberjs_script'));
  //keyboard shortcut handling
  wp_register_script( 'keymaster_script', plugins_url('Wordprss/js/keymaster.min.js', dir(__FILE__)),array('jquery', 'emberjs_script'));
  /* Register our stylesheet. */
  wp_register_style( 'wprsscss', plugins_url('style.css', __FILE__) );
  

}
function generate_main_page()
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
  wp_enqueue_script('keymaster_script');
  //add our stylesheet
  wp_enqueue_style('wprsscss');
  require_once('mainwindow.php');
}
function feed_management(){

  //add our stylesheet
  wp_enqueue_style('wprsscss');
  wp_enqueue_script( 'json2' );
  wp_enqueue_script('emberjs_script');
  require_once('feed_management.php');
}
//Something is wrong.  this thing never fires.
function wprss_uninstall_db()
{
  //We should remove the DB option for the db version
  delete_option('wordprss_db_version');
  //TODO clean up all the tables
  global $wpdb;
  
  //$wpdb->insert($wpdb->prefix.$tbl_prefix, array('owner'=> 1,'feed_url'=>'http://boingboing.net/feed/','site_url'=> 'http://boingboing.net', 'feed_name' => 'NARF NARF'));
  $sql = "DROP TABLE ". $wpdb->prefix.$tbl_prefix."feeds;";
  $wpdb->query($sql);

}

function wprss_install_db_and_data(){
  require_once 'install_upgrade.php';
  wprss_install_db();
  wprss_install_data();
}
add_action('admin_menu', 'wprss_plugin_menu');
//Turns out you can't just do __FILE__ like it says in the wordpress codex!
register_activation_hook(WP_PLUGIN_DIR.'/Wordprss/wordprss.php','wprss_install_db_and_data');
register_deactivation_hook(WP_PLUGIN_DIR.'/Wordprss/wordprss.php','wprss_uninstall_db');

?>
