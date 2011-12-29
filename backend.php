<?php

function wprss_list_feeds(){
  global $wpdb;
  global $tbl_prefix;
  $table_name = $tbl_prefix. "feeds";
  $sql = "select * from ".$table_name ;
  $myrows = $wpdb->get_results($sql );
  echo json_encode($myrows);
  exit;
}
add_action('wp_ajax_wprss_get_feeds','wprss_list_feeds');
add_action('wp_ajax_nopriv_wprss_get_feeds','wprss_list_feeds');
?>
