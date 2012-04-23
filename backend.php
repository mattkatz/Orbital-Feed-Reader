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
function wprss_list_feeds_die(){
  wprss_list_feeds();
  exit;
}
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
}
add_action('wp_ajax_wprss_get_feeds','wprss_list_feeds_die');
add_action('wp_ajax_nopriv_wprss_get_feeds','wprss_list_feeds_die');

//remove feed 
function wprss_unsubscribe_feed(){
  global $wpdb;
  global $tbl_prefix;
  global $current_user;
  $current_user = wp_get_current_user();
  //nonce_dance();
  
  //$prefix = $wpdb->prefix.$tbl_prefix; 
  $table_name = $wpdb->prefix.$tbl_prefix. "feeds ";
  $feed_id = filter_input(INPUT_POST, 'feed_id', FILTER_SANITIZE_NUMBER_INT);
  $resp->user = $current_user->ID;
  //TODO actually unsubscribe
  $sql = '
    DELETE 
    FROM ' . $table_name . '
    WHERE id = %d';
  $res = $wpdb->query(
      $wpdb->prepare($sql,$feed_id)
    );
  $resp->result = $res;
  $resp->error = $wpdb->print_error();
  $resp->feed_id = $feed_id;
  echo json_encode($resp);
  exit;
}
add_action('wp_ajax_wprss_unsubscribe_feed','wprss_unsubscribe_feed');

//find the details of the feed.
function wprss_find_feed(){
  $orig_url = filter_input(INPUT_GET, 'url',FILTER_SANITIZE_URL);
  $contents = "";
  $resp->orig_url = $orig_url;
  //go curl that url.
  if(function_exists('curl_init')){
    $ch = curl_init($orig_url);
    //TODO set any needed curl options
    $contents = @curl_exec($ch);
    curl_close($ch);


  }
  else{
    $contents = file_get_contents($orig_url);

  }
    require_once('simplepie.inc');
    $feed = new SimplePie();
    $feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_ALL);
    /*
    //TODO: LOOK, I know this is dumb.
    //Simplepie doesn't seem to do a proper $feed->get_type unless we pass in contents
    //feed autodiscovery doesn't work if you do pass in contents.
    //So I'm doing 2 requests to figure out the goddamn feed contents
    //WE'LL DO IT LATER
    //http://knowyourmeme.com/memes/bill-oreilly-rant

    $resp->ofeed_type = $feed->get_type() ;
    $resp->feed_none = SIMPLEPIE_TYPE_NONE;
  
    if(($feed->get_type() & SIMPLEPIE_TYPE_NONE) == SIMPLEPIE_TYPE_NONE){
      $resp->feed_type = "NONE";

    }

    if(($feed->get_type() && SIMPLEPIE_TYPE_ALL)>0  ){
      $rest->feed_type= "ALL";
    } */
    

  //check to see if the url is an html file.
  if(stripos($contents, '<html>') === false && stripos($contents,'<html') === false){ //TODO Why check both? don't know, grabbed this from  ttrss, need to research
    $resp->url_type ='feed';
    //$feed->set_feed_url($orig_url);
    $feed->set_raw_data($contents);
    $feed->init();
    //set the feed_name
    $resp->feed_name = $feed->get_title();
    //set the site_url to the site_url element on this feed
    $resp->site_url = $feed->get_link();
    $resp->favicon = $feed->get_favicon();


    //TODO return!

  }else{
    $resp->url_type = "html";
    //if this is an html file, let's see what feeds lurk within.
    $feed->set_feed_url($orig_url);
    $feed->init();
    //add those feeds to the array of feed
    $feeds = $feed->get_all_discovered_feeds();
    $resp->feeds = $feeds;
    //set the site_url to this url.
    $resp->site_url=$orig_url;
    //TODO set the feed name to the title element.
/*
    $doc = new DOMDocument();
    $doc->loadHTML($content);
    $xpath = new DOMXPath($doc);
    //$entries = $xpath->query('/html/head/title');
    //$resp->feed_name = $entries;
    //this is how tt-rss gets the discovery feeds
    $entries = $xpath->query('/html/head/link[@rel="alternate"]');
    $resp->feedEntries = $entries;
    $feedUrls = array();
    foreach ($entries as $entry) {
      if ($entry->hasAttribute('href')) {
        $title = $entry->getAttribute('title');
        if ($title == '') {
          $title = $entry->getAttribute('type');
        }    
        $feedUrl = $entry->getAttribute('href');
        //$feedUrl = rewrite_relative_url(
          //$baseUrl, $entry->getAttribute('href')
        //);   
        $feedUrls[$feedUrl] = $title;
      }    
    }    
    $resp->feeds = $feedUrls;
 */
    //TODO return!

  }
  echo json_encode($resp);
  exit;

}
add_action('wp_ajax_wprss_find_feed','wprss_find_feed');

//edit feed
function wprss_save_feed(){
  global $wpdb;
  global $tbl_prefix;
  global $current_user;
  $current_user = wp_get_current_user();
  //nonce_dance();
  
  $prefix = $wpdb->prefix.$tbl_prefix; 
  $feed_id = filter_input(INPUT_POST, 'feed_id', FILTER_SANITIZE_NUMBER_INT);
  $feed_url = filter_input(INPUT_POST, 'feed_url',FILTER_SANITIZE_STRING);
  $site_url = filter_input(INPUT_POST, 'site_url',FILTER_SANITIZE_STRING);
  $feed_name = filter_input(INPUT_POST, 'feed_name',FILTER_SANITIZE_STRING);
  $is_private = $_POST['is_private']=="true"?1:0;

  $table_name = $wpdb->prefix.$tbl_prefix. "feeds ";
  /*
   //TODO NO IDEA WHY THIS DOESN'T WORK!
  $ret = $wpdb->update(
    $table_name,//the table
    array(
      'feed_url' => $feed_url,
      'feed_name' => $feed_name,
      'site_url' => $site_url,
      'private' => $is_private,
    ),//columns to update
    array(//where filters
      'id' =>$feed_id, //current feed
      'owner'=>$current_user->ID //logged in user
    )
  );*/
  $sql = '';
  //We are inserting
  if(null == $feed_id){
    $sql = 'INSERT INTO ' . $table_name .'
              ( `owner`, `feed_url`, `feed_name`,  `site_url`, `private`)
              VALUES
              ( %d, %s, %s, %s, %d)
      ';
    $sql = $wpdb->prepare($sql, $current_user->ID, $feed_url, $feed_name,$site_url,$is_private);

  }
  else{
    $sql = 'UPDATE '. $table_name .'
            SET feed_name = %s
            , site_url = %s
            , feed_url = %s
            , private = %d
            WHERE id = %d
            AND owner = %d';
    $sql = $wpdb->prepare($sql,$feed_name,$site_url,$feed_url,$is_private,$feed_id, $current_user->ID);
  }
  $ret = $wpdb->query($sql);
          
  $resp->updated = $ret;
  $resp->sql = $sql;
  $resp->user = $current_user->ID;
  $resp->feed_id = $feed_id;
  $resp->feed_url = $feed_url;
  $resp->site_url = $site_url;
  $resp->feed_name = $feed_name;
  $resp->is_private = $is_private;
  $resp->error = $wpdb->print_error();

  echo json_encode($resp);
  exit;
}
add_action('wp_ajax_wprss_save_feed','wprss_save_feed');

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
function wprss_mark_item_read($entry_id,$read_status=true){
  global $wpdb;
  global $tbl_prefix;
  global $current_user;
  //$entry_id = $_POST['entry_id'];
  //$unread_status = $_POST['unread_status'];
  $entry_id = $_POST['entry_id'];
  if($entry_id == null){
    $entry_id = $_GET['entry_id'];
  }
  $read_status = $_POST['read_status'];
  if($read_status == null){
    $read_status = $_GET['read_status'];
  }
  $prefix = $wpdb->prefix.$tbl_prefix; 
  $ret = $wpdb->update(
    $prefix.'user_entries',//the table
    array('isRead' =>($read_status=="true"?1:0)),//columns to update
    array(
      'id' =>$entry_id, //current entry
      'owner_uid'=>$current_user->ID //logged in user
    )//where filters
  );
  $returnval;
  $returnval->updated = $ret;
  $returnval->id = $entry_id;
  $returnval->read_status = $read_status;
  echo json_encode($returnval);

  exit;
}
add_action('wp_ajax_wprss_mark_item_read','wprss_mark_item_read');
//No non logged in way to mark an item read for me yet






?>
