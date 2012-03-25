<?php

function nonce_dance(){
  $nonce = filter_input(INPUT_GET, 'nonce_a_donce',FILTER_SANITIZE_STRING);

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
  global $current_user;
  //nonce_dance();
  $table_name = $wpdb->prefix.$tbl_prefix. "feeds ";
  $sql = "
      select 
      feeds.id,
      feeds.feed_name,
      feeds.owner, 
      feeds.feed_url, 
      feeds.icon_url, 
      feeds.site_url, 
      feeds.last_updated,
      feeds.last_error,
      feeds.private,
      sum(ue.isRead =0) as unread_count
      from ".$table_name ." as feeds
      inner join " . $wpdb->prefix. $tbl_prefix . "user_entries as ue
      on ue.feed_id=feeds.id

      where ue.owner_uid = ". $current_user->ID."
      group by feeds.id,
      feeds.owner,
      feeds.feed_url,
      feeds.feed_name,
      feeds.icon_url,
      feeds.site_url,
      feeds.last_updated,
      feeds.last_error,
      feeds.private
      
      ";
      //sum( if ue.isRead then 0 else 1 end) as unread_count,
// AND feeds.owner = " . $current_user->ID."
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
  //nonce_dance();
  
  $prefix = $wpdb->prefix.$tbl_prefix; 
  $feed_id = filter_input(INPUT_GET, 'feed_id', FILTER_SANITIZE_NUMBER_INT);
  $show_read =filter_input(INPUT_GET, 'show_read', FILTER_SANITIZE_NUMBER_INT); 
  $feed_qualifier ="";
  $read_qualifer = "";
  if($feed_id == ""){
    //TODO "" should mean return latest entries
   }else{
     $feed_qualifier = " and ue.feed_id = ".$feed_id;
   }
  if($show_read=="1"){
    //do nothing
  }
  else{
    //only show unread entries
    $read_qualifer =" and  ue.isRead = 0  " ;
  }

  
  //TODO change get feed entries to support non logged in use
  $sql = "select entries.id as entry_id,
      entries.title as title,
      entries.guid as guid,
      entries.link as link,
      entries.content as content,
      entries.author as author,
      ue.isRead as isRead,
      ue.marked as marked,
      ue.id as id,
      ue.ref_id as ref_id,
      ue.feed_id as feed_id
      from " . $prefix . "entries as entries
      inner join " . $prefix . "user_entries as ue
      on ue.ref_id=entries.id
      where ue.owner_uid = ". $current_user->ID."
      ".$feed_qualifier."
      ".$read_qualifer."
      limit 30
  ;";


      
  $myrows = $wpdb->get_results($sql);
  echo json_encode($myrows);
  exit;
}
add_action('wp_ajax_wprss_get_entries','wprss_get_feed_entries');
add_action('wp_ajax_nopriv_wprss_get_entries','wprss_get_feed_entries');

//update multiple feeds
function wprss_update_feeds(){
  //get the list of feeds to update that haven't been updated recently
  //TODO Limit it to a reasonable number of feeds in a batch
  //for each feed call update_feed
  

}
add_action('wp_ajax_wprss_update_feeds','wprss_update_feeds');
add_action('wp_ajax_nopriv_wprss_update_feeds','wprss_get_update_feeds');


//update single feed
function wprss_update_feed($feed_id="",$feed_url=""){
  //if we didn't get passed a feed, check to see if it is in the url
  if("" == $feed_id){
    $feed_id = filter_input(INPUT_GET, 'feed_id',FILTER_SANITIZE_NUMBER_INT);
    if("" == $feed_id){return;}
  }

  //TODO update the feeds last updated time
  require_once('simplepie.inc');
  global $wpdb;
  global $tbl_prefix;
  global $current_user;
  //echo $feed_id;
  $prefix = $wpdb->prefix.$tbl_prefix; 
  $sql = "select *
    from ". $prefix . "feeds
    where id=".$feed_id."
    ;";
  $feedrow = $wpdb->get_row($sql);

  $feed = new SimplePie();
  $feed->set_feed_url($feedrow->feed_url);
  // Remove these tags from the list
/*
  $strip_htmltags = $feed->strip_htmltags;
  array_splice($strip_htmltags, array_search('object', $strip_htmltags), 1);
  array_splice($strip_htmltags, array_search('param', $strip_htmltags), 1);
  array_splice($strip_htmltags, array_search('embed', $strip_htmltags), 1);
   
  $feed->strip_htmltags($strip_htmltags);
*/

  //Here is where the feed parsing/fetching/etc. happens
  $feed->init();
  //echo json_encode($feed->get_items());
  $entries_table = $prefix."entries"; 
  $user_entries_table = $prefix."user_entries";
  foreach($feed->get_items() as $item)
  {
    echo $item->get_description();
    $name = "";
    $author = $item->get_author();
    if(null != $author){
      $name =$author->get_name(); 
    }
    echo  $name;
    $wpdb->insert($entries_table, array(
      'title'=>$item->get_title(),
      'guid'=>$item->get_id(),
      'link'=>$item->get_link(),//TODO 
      'updated'=>date ("Y-m-d H:m:s"),
      'content'=>$item->get_content(),//TODO
      'entered' =>date ("Y-m-d H:m:s"), 
      'author' => $name
    ));
    $entry_id = $wpdb->insert_id;


    //TODO - this needs to be generalized for multiple users
    $wpdb->insert($user_entries_table, array(
      'ref_id' => $entry_id,
      'feed_id' => $feed_id,
      'orig_feed_id' => $feed_id,
      'owner_uid' =>$current_user->ID
    ));
  }

  //echo $feedrow->feed_url;
  exit;
}
add_action('wp_ajax_wprss_update_feed','wprss_update_feed');
add_action('wp_ajax_nopriv_wprss_update_feed','wprss_get_update_feed');

//Mark items as read
function wprss_mark_items_read($feed_id){
  global $wpdb;
  global $tbl_prefix;
  global $current_user;
  //what do we update? 
  $feed_id = $_POST['feed_id'];
  
  $prefix = $wpdb->prefix.$tbl_prefix;
  $ret = $wpdb->update(
    $prefix.'user_entries',//the table
    array('isRead' =>1),//columns to update
    array(//where filters
      'feed_id' =>$feed_id, //current feed
      'owner_uid'=>$current_user->ID //logged in user
    )
  );
  $returnval;
  $returnval->updated = $ret;
  $returnval->feed_id = $feed_id;
  echo json_encode($returnval);
  exit;
  
}
add_action('wp_ajax_wprss_mark_items_read','wprss_mark_items_read');

//Mark item as read
function wprss_mark_item_read($entry_id,$unread_status=true){
  global $wpdb;
  global $tbl_prefix;
  global $current_user;
  //$entry_id = $_POST['entry_id'];
  //$unread_status = $_POST['unread_status'];
  $entry_id = $_POST['entry_id'];
  $unread_status = $_POST['unread_status'];
  $prefix = $wpdb->prefix.$tbl_prefix; 
  $ret = $wpdb->update(
    $prefix.'user_entries',//the table
    array('isRead' =>($unread_status=="true"?1:0)),//columns to update
    array(
      'ref_id' =>$entry_id, //current entry
      'owner_uid'=>$current_user->ID //logged in user
    )//where filters
  );
  $returnval;
  $returnval->updated = $ret;
  $returnval->id = $entry_id;
  $returnval->unread_status = $unread_status;
  echo json_encode($returnval);

  exit;
}
add_action('wp_ajax_wprss_mark_item_read','wprss_mark_item_read');
//No non logged in way to mark an item read for me yet






?>
