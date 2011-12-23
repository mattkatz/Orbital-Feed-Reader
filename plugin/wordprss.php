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



function wprss_plugin_menu(){
  $hook = add_menu_page('WordPrss', 'Consume','edit_posts','wordprss.php','generate_main_page');

}
function generate_main_page()
{
  echo '<p>IT WORKS</p>' . '<p> wordprss version ' . get_option('wordprss_db_version',"NOTHING"). '</p>';
  echo '<p>IT WORKS</p>' . '<p> wordprss version ' . get_option('admin_email',"NOTHING"). '</p>';
  echo __FILE__;
}

# create the database tables.
# TODO extract this to a sep file
function wprss_install_db()
{
  global $wpdb;
  global $wordprss_db_version;
  global $wordprss_db_version_opt_string;
  require_once(ABSPATH. 'wp_admin/includes/upgrade.php');
  add_option($wordprss_db_version_opt_string,$wordprss_db_version);
  //add_option('wordprss_db_version','0.1');
  

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
function wprss_uninstall_db()
{
  //We should remove the DB option for the db version
  delete_option('wordprss_db_version');
  //TODO clean up all the tables
}
add_action('admin_menu', 'wprss_plugin_menu');
//Turns out you can't just do __FILE__ like it says in the wordpress codex!
register_activation_hook(WP_PLUGIN_DIR.'/wordprss/wordprss.php','wprss_install_db');
register_deactivation_hook(WP_PLUGIN_DIR.'/wordprss/wordprss.php','wprss_uninstall_db');

?>
