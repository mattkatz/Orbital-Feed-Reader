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

function wprss_plugin_menu(){
  $hook = add_menu_page('WordPrss', 'WordPrss','edit_posts','wordprss.php','generate_main_page');

}
function generate_main_page()
{
  echo '<p>IT WORKS</p>';
}

add_action('admin_menu', 'wprss_plugin_menu');
?>
