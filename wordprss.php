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
$tbl_prefix = 'wprss_';

if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
      exit;
}


function wprss_plugin_menu(){
  $hook = add_menu_page('WordPrss', 'Consume','edit_posts','wordprss.php','generate_main_page');
  wp_register_script( 'wordprss_script', plugins_url('Wordprss/wprss.javascript', dir(__FILE__)) );

}
function generate_main_page()
{
  echo '<p>IT WORKS</p>' . '<p> wordprss version ' . get_option('wordprss_db_version',"NOTHING"). '</p>';
  echo '<p>IT WORKS</p>' . '<p> wordprss version ' . get_option('admin_email',"NOTHING"). '</p>';
  echo __FILE__;
  wp_enqueue_script('wordprss_script');
  wp_localize_script( 'wordprss_script', 'get_url', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
  #require_once("backend/index.php");
  wprss_list_feeds();
  
  //$script = "<script type='text/javascript' href='". plugins_url('wordprss/wprss.javascript', dir(__FILE__)). "'> </script>";

  //echo $script;
 
}

# create the database tables.
# TODO extract this to a sep file
function wprss_install_db()
{
  global $wpdb;
  global $wordprss_db_version;
  global $wordprss_db_version_opt_string;
  global $tbl_prefix;
  require_once(ABSPATH. 'wp-admin/includes/upgrade.php');
  add_option($wordprss_db_version_opt_string,$wordprss_db_version);

  $table_name = $wpdb->prefix.$tbl_prefix."feeds";

  $sql = "CREATE TABLE " . $table_name ." (
    id integer NOT NULL AUTO_INCREMENT,
    owner BIGINT NOT NULL,
    feed_url text NOT NULL,
    icon_url varchar(250) not null default '',
    site_url varchar(250) not null default '',
    UNIQUE KEY id (id)
  );";
   

  dbDelta($sql);
}
# load all the first installation data in.
function wprss_install_data(){
  global $wpdb;
  global $tbl_prefix;
  $table_name = $wpdb->prefix.$tbl_prefix."feeds";
  $wpdb->insert($table_name, array('owner'=> 1,'feed_url'=>'http://www.morelightmorelight.com/feed/','site_url'=> 'http://www.morelightmorelight.com'));
  $wpdb->insert($table_name, array('owner'=> 1,'feed_url'=>'http://boingboing.net/feed/','site_url'=> 'http://boingboing.net'));


}
function wprss_uninstall_db()
{
  //We should remove the DB option for the db version
  delete_option('wordprss_db_version');
  //TODO clean up all the tables
  global $wpdb;
  $sql = "DROP TABLE ". $wpdb->prefix.$tbl_prefix."feeds;";
  $wpdb->query($sql);


}
function wprss_list_feeds(){
  global $wpdb;
  $sql = "select * from wp_wprss_feeds";
  $myrows = $wpdb->get_results($sql );
  echo $myrows;
  
  
  echo json_encode($myrows);
  //die();
  

}
add_action('admin_menu', 'wprss_plugin_menu');
add_action('wp_ajax_wprss_get_feeds','wprss_list_feeds');
add_action('wp_ajax_nopriv_wprss_get_feeds','wprss_list_feeds');
//Turns out you can't just do __FILE__ like it says in the wordpress codex!
register_activation_hook(WP_PLUGIN_DIR.'/Wordprss/wordprss.php','wprss_install_db');
register_activation_hook(WP_PLUGIN_DIR.'/Wordprss/wordprss.php','wprss_install_data');
register_deactivation_hook(WP_PLUGIN_DIR.'/Wordprss/wordprss.php','wprss_uninstall_db');

?>
