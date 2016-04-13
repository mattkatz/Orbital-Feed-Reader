<?php
if ( !function_exists( 'add_action' ) ) {
  echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
  exit;
}
if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log("Orbital:");
        error_log( print_r( $message, true ) );
      } else {
        error_log( "Orbital: $message");
      }
    }
  }
}
require_once 'feeds.php';
require_once 'entries.php';


function nonce_dance(){
  check_ajax_referer('orbital_actions','orbital_actions_nonce');
}

//TODO return a nonce or something. Nonce dancing should work better
function orbital_list_feeds_die(){
  orbital_list_feeds();
  exit;
}

function orbital_list_feeds(){
  nonce_dance();
  $myrows = OrbitalFeeds::get(null);

  echo json_encode($myrows);
}
add_action('wp_ajax_orbital_get_feeds','orbital_list_feeds_die');

function orbital_list_tags(){
  nonce_dance();
  $tag_fragment = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);
  $rows = OrbitalFeeds::getTags($tag_fragment);
  echo join($rows,"\n");
  //echo json_encode();
  exit;
}
add_action('wp_ajax_orbital_get_tags','orbital_list_tags');

//remove feed
function orbital_unsubscribe_feed(){
  nonce_dance();
  $feed_id = filter_input(INPUT_POST, 'feed_id', FILTER_SANITIZE_NUMBER_INT);

  $resp = OrbitalFeeds::remove(null,$feed_id);
  echo json_encode($resp);
  exit;
}
add_action('wp_ajax_orbital_unsubscribe_feed','orbital_unsubscribe_feed');

//find the details of the feed.
function orbital_find_feed(){
  nonce_dance();
  $resp;
  $orig_url = filter_input(INPUT_POST, 'url',FILTER_SANITIZE_URL);
  $contents = "";
  $resp->orig_url = $orig_url;
  if( !class_exists( 'WP_Http' ) )
    include_once( ABSPATH . WPINC. '/class-http.php' );

  $request = new WP_Http;
  $result = $request->request( $orig_url);
  if(is_wp_error($result)){
    //handle and return the result;
    echo(json_encode($result));
    exit;
  }
  $contents= $result['body'];


  //if( !class_exists( 'WP_Http' ) )
    include_once(ABSPATH . WPINC . '/class-feed.php');
    $feed = new SimplePie();
    //If you're cache isn't writable, this is a big deal
    //Better to just disable it for now
    $feed->enable_cache(false);
    $feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_ALL);
    /*
    //TODO: LOOK, I know this is dumb.
    //Simplepie doesn't seem to do a proper $feed->get_type unless we pass in contents
    //feed autodiscovery doesn't work if you do pass in contents.
    //So I'm doing 2 requests to figure out the feed contents
    //WE'LL DO IT LATER
    //http://knowyourmeme.com/memes/bill-oreilly-rant

    $resp->feed_type = $feed->get_type() ;
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
    //If your cache isn't writable, this is a big issue
    $feed->enable_cache(false);
    $feed->init();
    //set the feed_name
    $resp->feed_name = $feed->get_title();
    //set the site_url to the site_url element on this feed
    $resp->site_url = $feed->get_link();
    //Simplepie doesn't support favicon anymore
    //$resp->favicon = $feed->get_favicon();


    //TODO return!

  }else{
    $resp->url_type = "html";
    //if this is an html file, let's see what feeds lurk within.
    $feed->set_feed_url($orig_url);
    //If your cache isn't writable, this is a big issue
    $feed->enable_cache(false);
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
add_action('wp_ajax_orbital_find_feed','orbital_find_feed');

//edit feed
function orbital_save_feed(){
  nonce_dance();
  $feed_id = filter_input(INPUT_POST, 'feed_id', FILTER_SANITIZE_NUMBER_INT);
  $feed_url = filter_input(INPUT_POST, 'feed_url',FILTER_SANITIZE_STRING);
  $site_url = filter_input(INPUT_POST, 'site_url',FILTER_SANITIZE_STRING);
  $feed_name = filter_input(INPUT_POST, 'feed_name',FILTER_SANITIZE_STRING);
  $is_private = filter_input(INPUT_POST, 'is_private',FILTER_SANITIZE_STRING);
  $tags = filter_input(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);

  $table_name = $wpdb->prefix.$tbl_prefix. "feeds ";
  $resp = OrbitalFeeds::save(array('feed_id'=>$feed_id,'feed_url'=>$feed_url,'site_url'=>$site_url,'feed_name'=>$feed_name,'is_private'=>$is_private,'tags'=>$tags));
  echo json_encode($resp);
  exit;
}
add_action('wp_ajax_orbital_save_feed','orbital_save_feed');

//get feed entries
function orbital_get_feed_entries(){
  nonce_dance();
  $filters = array();
  $feed_id = filter_input(INPUT_GET, 'feed_id', FILTER_SANITIZE_NUMBER_INT);
  $show_read =filter_input(INPUT_GET, 'show_read', FILTER_SANITIZE_NUMBER_INT);
  $tag = filter_input(INPUT_GET, 'tag',FILTER_SANITIZE_STRING);
  $sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_NUMBER_INT);
  if($tag !=""){
    $filters['tag'] = $tag;
  }
  if($feed_id == ""){
    //TODO "" should mean return latest entries
   }else{
     $filters['feed_id'] = $feed_id;
   }
  if($show_read=="1"){
    //do nothing
  }
  else{
    //only show unread entries
    $filters['isRead']=$show_read;
  }

  $myrows = OrbitalEntries::get($filters);
  echo json_encode($myrows);
  exit;
}
add_action('wp_ajax_orbital_get_entries','orbital_get_feed_entries');

//update multiple feeds
function orbital_update_feeds(){
  //nonce_dance(); //can't do a nonce dance here because this gets called from wp-cron
  //get the list of feeds to update that haven't been updated recently
  $feeds = OrbitalFeeds::get_stale_feeds();
  //TODO Limit it to a reasonable number of feeds in a batch
  //TODO Maybe we should schedule wp_cron jobs for each update?
  //for each feed call update_feed
  foreach( $feeds as $feed){
    OrbitalFeeds::refresh($feed->id);
  }
}
add_action('wp_ajax_orbital_update_feeds','orbital_update_feeds');


//update single feed
function orbital_update_feed($feed_id="",$feed_url=""){
  nonce_dance();
  //TODO if we didn't get passed a feed, check to see if it is in the url
  if("" == $feed_id){
    $feed_id = filter_input(INPUT_POST, 'feed_id',FILTER_SANITIZE_NUMBER_INT);
    if("" == $feed_id){
      $resp;
      $resp->feed_id = $feed_id;
      $resp->updated = 0;
      $resp->reason = "No feed_id passed";
      echo json_encode($resp);
      exit;
    }
  }
  //if this is coming from a user call with a user_feeds.id
  $resp = OrbitalFeeds::refresh_user_feed($feed_id);
  echo json_encode($resp);
  exit;
}
add_action('wp_ajax_orbital_update_feed','orbital_update_feed');

//Mark items as read
function orbital_mark_items_read($feed_id){
  nonce_dance();
  global $wpdb;
  global $tbl_prefix;
  global $current_user;
  //what do we update?
  $feed_id = filter_input(INPUT_POST, 'feed_id', FILTER_SANITIZE_NUMBER_INT);
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
add_action('wp_ajax_orbital_mark_items_read','orbital_mark_items_read');

//Mark item as read
function orbital_mark_item_read(){
  nonce_dance();
  $entry_id = filter_input(INPUT_POST, 'entry_id', FILTER_SANITIZE_NUMBER_INT);
  $read_status = filter_input(INPUT_POST, 'read_status', FILTER_SANITIZE_NUMBER_INT);
  $resp = OrbitalEntries::save(array(
    'isRead' =>$read_status,//columns to update
    'entry_id' =>$entry_id, //current entry
  ));
  echo json_encode($resp);
  exit;
}
add_action('wp_ajax_orbital_mark_item_read','orbital_mark_item_read');
//No non logged in way to mark an item read for me yet

//Get the current settings for this user
function orbital_get_user_settings(){
  nonce_dance();
  $settings = (array) get_user_option( 'orbital_settings' );
  //TODO what if the settings haven't been set? we should default them.
  //$sort_order = esc_attr($settings['sort-order']);
  echo json_encode($settings);
  exit;
}
add_action('wp_ajax_orbital_get_user_settings','orbital_get_user_settings');

//set the current entry sort order for this user
function orbital_set_user_settings(){
  nonce_dance();
  global $current_user;
  //TODO this is the better way, but I can't get it to work.
  //$user_orbital_settings = filter_input(INPUT_POST, 'orbital_settings', FILTER_SANITIZE_STRING);
  //TODO we should handle if there isn't a setting passed...
  $user_orbital_settings = $_POST['orbital_settings'];
  $settings = (array) get_user_option( 'orbital_settings' );
  //merge arrays
  $new_settings = $user_orbital_settings + $settings;
  if(update_user_option($current_user->ID, 'orbital_settings',  $new_settings)){
    // Send back what we now know
    echo json_encode($new_settings);
  }
  else {
    echo false;
    _log('update failed');
  }
  exit;
}
add_action('wp_ajax_orbital_set_user_settings','orbital_set_user_settings');
?>
