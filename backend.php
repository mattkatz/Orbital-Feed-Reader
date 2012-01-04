<?php

function nonce_dance(){
  $nonce = $_GET['nonce_a_donce'];

  // check to see if the submitted nonce matches with 
  // the generated nonce we created earlier
  if ( ! wp_verify_nonce( $nonce, 'nonce_a_donce' ) ){
      die ( 'Busted!');
  }

}  

//TODO return a nonce or something. Nonce dancing should work better

function wprss_list_feeds(){

  global $wpdb;
  global $tbl_prefix;
  //echo $tbl_prefix . " WAHEY";
  //nonce_dance();
  //TODO check to see what current user is 
  //TODO qualify this to just a user  
  $table_name = $wpdb->prefix.$tbl_prefix. "feeds";
  $sql = "select * from ".$table_name ;
  $myrows = $wpdb->get_results($sql );
  echo json_encode($myrows);
  exit;
}
add_action('wp_ajax_wprss_get_feeds','wprss_list_feeds');
add_action('wp_ajax_nopriv_wprss_get_feeds','wprss_list_feeds');

//get feed entries
function wprss_get_feed_entries(){
  global $wpdb;
  global $tbl_prefix;
  global $current_user;
  $current_user = wp_get_current_user();
  nonce_dance();
  $prefix = $wpdb->prefix.$tbl_prefix; 
  //TODO change get feed entries to support non logged in use
  $sql = "select entries.id as entry_id,
      entries.title as title,
      entries.guid as guid,
      entries.link as link,
      entries.content as content,
      entries.author as author,
      ue.feed_id as feed_id
      from " . $prefix . "entries as entries
      inner join " . $prefix . "user_entries as ue
      on ue.ref_id=entries.id
      where ue.owner_uid = ". $current_user->ID."
;";


      
  $myrows = $wpdb->get_results($sql);
  echo json_encode($myrows);
  exit;
}
add_action('wp_ajax_wprss_get_entries','wprss_get_feed_entries');
add_action('wp_ajax_nopriv_wprss_get_entries','wprss_get_feed_entries');

function wprss_update_feeds(){

}


?>
