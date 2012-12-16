<?php
if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}
/* Users subscribe to feeds through user_feeds.
 * Feeds get updated to contain entries, and users see these through user_entries
 * TODO User_entries should link to user_feeds which links to feeds
 * user_entries link to entries
 * Feeds get updated in a batch and each time a feed is update with entries, users get a bunch of user entries.
 * When users look at user feeds, the only thing feeds have in common is the underlying feed_url and site_url.  What if someone wants to change that?
 * Everything but the underlying url belongs to the user_feeds. Those are the presentation.
 * 
 * If a feed already exists, changing anything but the name is no good.
 *
 * CLASSES
 * Feeds Class
 */
class WprssFeeds {

/* Method to save a feed
 *   - check to see if there is a feed_id.
 *     - Yes means we are updating
 *       - Just update user_feeds
 *     - No means we are inserting
 *       - Check to see if the feed_url exists in feeds.
 *       - Then insert a link or insert into feed_url and then insert a link.
 */
  static function save($feed){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds ";
    $user_feeds = $wpdb->prefix.$tbl_prefix. "user_feeds ";
    $resp = "";
    if(array_key_exists('feed_id', $feed) && $feed['feed_id']){
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
      //we are updating.  just do an update on user_feeds
      $sql = "UPDATE $user_feeds
              SET feed_name = %s
              , site_url = %s
              , private = %d
              WHERE feed_id = %d
              AND owner = %d";
      $sql = $wpdb->prepare($sql,$feed['feed_name'],$feed['site_url'],$feed['is_private'],$feed['feed_id'], $current_user->ID);
      $resp->updated = $wpdb->query($sql);
      if(false=== $resp->updated ) {
        $resp->update_error = $wpdb->print_error();
      }
    }
    else{
      //we are inserting a feed.  Does it already exist in feeds?
      $sql = 'SELECT id 
            FROM ' .$feeds.'
            WHERE feed_url = %s';
      $sql = $wpdb->prepare($sql, $feed['feed_url']);
      $feed_id = $wpdb->get_var($sql);
      if (! $feed_id){//we will insert the feed id and then link
        //insert the feed and get the feed_id.
        $sql = 'INSERT INTO ' . $feeds.'
                ( `feed_url`, `feed_name`,  `site_url`)
                VALUES
                ( %s, %s, %s )
        ';
        $sql = $wpdb->prepare($sql, $feed['feed_url'], $feed['feed_name'],$feed['site_url']);
        //TODO we should have some sane error checking here
        $ret = $wpdb->query($sql);
        $resp->inserted = $ret;
        if(false=== $ret){
          $resp->feeds_error = $wpdb->print_error();
        }

        $feed_id = $wpdb->insert_id;
      }
      //Now let's link in the feed to user_feeds
      $sql = 'INSERT INTO ' .$user_feeds.'
        (feed_id, feed_name, site_url,owner, private,unread_count)
         VALUES
         (%d,%s,%s,%d,%d,0)';
      $sql = $wpdb->prepare($sql, $feed_id,  $feed['feed_name'],$feed['site_url'],$current_user->ID,$feed['is_private']);
      if(false=== $wpdb->query($sql)){
        $resp->user_feeds_error = $wpdb->print_error();
      }
    }

    //TODO this should be eliminated
    //$resp->sql = $sql;
    $resp->user = $current_user->ID;
    $resp->feed_id = "".$feed_id;
    $resp->feed_url = $feed['feed_url'];
    $resp->site_url = $feed['site_url'];
    $resp->feed_name = $feed['feed_name'];
    $resp->is_private = $feed['is_private'];
    $resp->unread_count ="0";
    return $resp;
  }

/* Method to list all feeds
 *   - Just return all feeds from user_feeds
 */
  static function get(){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds ";
    $user_feeds = $wpdb->prefix.$tbl_prefix. "user_feeds ";
    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries ";
    $sql = "
        select 
        feeds.id as feed_id,
        COALESCE(u_feeds.feed_name,feeds.feed_name ) as feed_name,
        feeds.feed_url, 
        COALESCE(u_feeds.icon_url, feeds.icon_url ) as icon_url,
        COALESCE(u_feeds.site_url, feeds.site_url ) as site_url,
        feeds.last_updated,
        feeds.last_error,
        u_feeds.private,
        sum(if(coalesce(ue.isRead,1)=0,1,0)) AS unread_count
        from ".$user_feeds." as u_feeds
        inner join ".$feeds." as feeds
        on u_feeds.feed_id = feeds.id
        left outer join ".$user_entries." as ue
        on ue.feed_id=feeds.id

        and u_feeds.owner = ". $current_user->ID."
        group by feeds.id,
        feeds.feed_url,
        feeds.feed_name,
        feeds.icon_url,
        feeds.site_url,
        feeds.last_updated,
        feeds.last_error,
        u_feeds.private
        

        ";
        //sum( if ue.isRead then 0 else 1 end) as unread_count,
    // AND feeds.owner = " . $current_user->ID."
    $myrows = $wpdb->get_results($sql );
    return $myrows;

  }
/* Method to unsubscribe a feed
 *   - Should delete a feed from user_feeds for current user
 *   - Should delete all user_entries for current user
 *   - Should delete the feed from feeds if there are no more user_feeds entries
 *   - then delete all entries for the feed.
 */
  static function remove($feed_id){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;
    $current_user = wp_get_current_user();
    
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds";
    $user_feeds = $wpdb->prefix.$tbl_prefix. "user_feeds";
    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries ";
    $entries = $wpdb->prefix.$tbl_prefix. "entries";

    $resp->user = $current_user->ID;
    //User feeds
    $sql = "
      DELETE 
      FROM $user_feeds 
      WHERE owner = $current_user->ID 
      AND feed_id = %d";
    $sql = $wpdb->prepare($sql,$feed_id);
    if(false === $wpdb->query($sql)){
      $resp->uf_error = $wpdb->print_error();
    }
    //delete all user_entries for current user
    //TODO we should probably only link user_entries to user_feeds
    $sql = "
      DELETE 
      FROM $user_entries 
      WHERE owner_uid = $current_user->ID 
      AND feed_id = %d";
    $sql = $wpdb->prepare($sql,$feed_id);
    if(false ===  $wpdb->query($sql)){
      $resp->ue_error = $wpdb->print_error();
    }

    //was that the last person subscribed to the feed?
    //if so, we should remove the feed and all entries
    $sql = "
      SELECT COUNT(*)
      FROM $user_feeds
      WHERE feed_id = %d";
    $subscribers = $wpdb->get_var($wpdb->prepare($sql,$feed_id));

    if(0<= $subscribers){
      $sql = "
        DELETE
        FROM $entries
        WHERE feed_id = %d";
      $sql = $wpdb->prepare($sql,$feed_id);
      if(false === $wpdb->query($sql)){
        $resp->entries_error = $wpdb->print_error();
      }

      //TODO we are getting a weird blank error on delete for this
      //Is that valid for postgres?  How can we just eliminate that error?
      $sql = "
        DELETE 
        FROM $feeds
        WHERE id = %d;";
      $sql = $wpdb->prepare($sql,$feed_id);
      if(false === $wpdb->query($sql)){
        $resp->feeds_error = $wpdb->print_error();
      }
    }

    //$resp->result = $res;
    $resp->feed_id = $feed_id;
    return $resp;
  }

  /*
   * Stale feeds haven't been updated in over an hour
   * Get a list of them
   */
  static function get_stale_feeds(){
    global $wpdb;
    global $tbl_prefix;
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds";
    $now = new DateTime();
    //lets go back 1 hour
    $then = date_sub($now,new DateInterval('PT1H'))->format('Y-m-d H:i:sP');
    //_log($then);
    $sql = "
      SELECT feeds.id,feeds.feed_name
      FROM $feeds as feeds
      WHERE feeds.last_updated < %s
      ";
    $sql = $wpdb->prepare($sql,$then);
    _log($sql);
    $myrows = $wpdb->get_results($sql);
    return $myrows;

  }

  /* Function: get_feed
   *
   * Returns:  a single feed by id
   */
  static function get_feed($feed_id)
  {
    global $wpdb;
    global $tbl_prefix;
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds";
    //echo $feed_id;
    $sql = "select *
      from $feeds
      where id=".$feed_id."
      ;";
    //_log($sql);
    $feedrow = $wpdb->get_row($sql);
    return $feedrow;
  }


  /* Function: Refresh
   *
   * Refresh a feed from it's underlying source.  
   * Process it and save all new or updated entries
   *
   * Returns: a count of updates and inserts made
   */
  static function refresh($feed_id){
    //TODO update the feeds last updated time
    require_once('simplepie.inc');
    //_log($feedrow);
    //echo $feedrow->feed_url;
    $feedrow = WprssFeeds::get_feed($feed_id);

    $feed = new SimplePie();
    $feed->set_feed_url($feedrow->feed_url);
    $feed->force_feed(true);

    // Remove these tags from the list
    $strip_htmltags = $feed->strip_htmltags;
    array_splice($strip_htmltags, array_search('object', $strip_htmltags), 1);
    array_splice($strip_htmltags, array_search('param', $strip_htmltags), 1);
    array_splice($strip_htmltags, array_search('embed', $strip_htmltags), 1);
     
    $feed->strip_htmltags($strip_htmltags);

    //Here is where the feed parsing/fetching/etc. happens
    $feed->init();
    $items = $feed->get_items();
    foreach($items as $item)
    {
      $name = "";
      $author = $item->get_author();
      if(null != $author){
        $name =$author->get_name(); 
      }
      WprssEntries::save(array(
        'feed_id'=>$feed_id,
        'title'=>$item->get_title(),
        'guid'=>$item->get_id(),
        'link'=>$item->get_link(),//TODO 
        'updated'=>date ("Y-m-d H:i:s"),
        'content'=>$item->get_content(),//TODO
        'entered' =>date ("Y-m-d H:i:s"),
        'author' => $name
      ));
    }
    // We update the last updated time for the feed no matter what
    // This prevents us from hitting the feed repeatedly if there aren't
    // new items
    //TODO extract this to the update method
    global $wpdb;
    global $tbl_prefix;
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds";
      $ret = $wpdb->update(
        $feeds,//the table
        array('last_updated'=>date("Y-m-d H:i:s")),//columns to update
        array('id'=>$feed_id)//where filters
      );
    //echo $feedrow->feed_url;
    $resp->feed_id = $feed_id;
    $resp->updated = count($items);
    return $resp;
  }
}
/*
 * Entries Class
 * Methods 
 * Update an entry underlying
 *    - update the content etc, then update the read flag on every user
 * Mark an entry read
 *
 *
 * */
class WprssEntries{
/*
 * Insert an entry for a feed
 *    - TODO check to see if entry exists, using entry hash?
 *    - insert entry, then link for each user subscribed to the feed.
 *    - alternately, update the stored entry - this should be used to mark feeds updated or to update their content when the feed is updated.
 *    - TODO compare the content_hash on old and new before resetting isread
 */
  static function save($entry){
    //_log('in save');
    //_log($entry);

    if(array_key_exists('entry_id',$entry )&& $entry['entry_id'] ){
      //this is an update
      $resp = WprssEntries::update($entry);
      //_log('sending to update');
    }
    else{
      $entry_id = null;
      //TODO see if the entry exists using entry hash or guid?
      if(array_key_exists('guid', $entry) && $entry['guid']){
        $entry_id = WprssEntries::check_guid($entry['guid']);
        //_log('check guid says entry id is');
        //_log($entry_id);
      }

      if(null === $entry_id){
        //_log('sending to insert');
        //insert the entry, get the ID for the feed
        $resp = WprssEntries::insert($entry);
      }
      else {
        //this is an update - let's do it.
        $entry['entry_id'] = $entry_id;
        //_log("found an $entry_id  and sending to update");
        $resp = WprssEntries::update($entry);
      }
    }
   return $resp; 

  }
  /* Function: check_guid
   *
   * Checks to see if we've already stored an entry with that guid
   * Returns: the entry_id of the entry or null;
   */
  static function check_guid($guid){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;

    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries";
    $entries = $wpdb->prefix.$tbl_prefix. "entries";

    $sql = "SELECT id
            FROM $entries
            WHERE guid = %s;";

    $sql = $wpdb->prepare($sql, $guid);
    $entry_id = $wpdb->get_var($sql);
    return  $entry_id;


  }

  /* Function: update
   *
   * Assumes that you already know the entry has an id and exists.
   * Probably best to just use Save
   */
  static function update($entry){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;

    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries";
    $user_feeds = $wpdb->prefix.$tbl_prefix. "user_feeds";
    $entries = $wpdb->prefix.$tbl_prefix. "entries";
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds";

    //try to update if the entry id exists, otherwise, insert
    //we should iterate over the keys and put them in the update
    //TODO sep out content and content_hash to update the underlying entry
    //TODO we should have a method that updates entries and one that updates user_entries
    $update_whitelist = array('marked'=>'marked','isRead'=>'isRead');
    $filter_whitelist = array('feed_id'=>'feed_id','entry_id'=>'entry_id','id'=>'id');
    $update_fields = array();
    $filter_fields = array(
        'owner_uid'=>$current_user->ID //logged in user
    );
    foreach ($entry as $key => $value){
      if(array_key_exists($key,$update_whitelist)){
        $update_fields[$update_whitelist[$key]] = $value;
      }
      if(array_key_exists($key,$filter_whitelist)){
        $filter_fields[$filter_whitelist[$key]] = $value;
      }
    }
    if(count($update_fields) <=0){
      $resp->updated = 0;
      $resp->message = "Nothing to update";
    }else{

      $ret = $wpdb->update(
        $user_entries,//the table
        $update_fields,//columns to update
        $filter_fields //where filters
      );
      $resp->updated = $ret;
    }
    if(array_key_exists('entry_id',$entry )){
      $resp->entry_id = $entry['entry_id'];
    }
    if(array_key_exists('feed_id',$entry)){
      $resp->feed_id = $entry['feed_id'];
    }
    return $resp;

  }

  //assumes you have already checked that the entry isn't in there.
  //probably best if you just use save, it does the checking
  static function insert($entry){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;

    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries";
    $user_feeds = $wpdb->prefix.$tbl_prefix. "user_feeds";
    $entries = $wpdb->prefix.$tbl_prefix. "entries";
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds";
    
    $resp;

    $wpdb->insert($entries, array(
      'feed_id'=>$entry['feed_id'],
      'title'=>$entry['title'],
      'guid'=>$entry['guid'],
      'link'=>$entry['link'],//TODO 
      'updated'=>date ("Y-m-d H:i:s"),
      'content'=>$entry['content'],//TODO
      'entered' =>date ("Y-m-d H:i:s"),
      'author' => $entry['author']
    ));
    $entry_id = $wpdb->insert_id;
    $resp->insert_id = $entry_id;
    //insert the link to user_entries
    $sql = "INSERT INTO ".$user_entries."
            (entry_id, feed_id, orig_feed_id, owner_uid, marked, isRead)
            SELECT
            %d,%d,%d,owner,0,0
            FROM ".$user_feeds." 
            WHERE feed_id = %d" ;
    $sql = $wpdb->prepare($sql,$entry_id, $entry['feed_id'],$entry['feed_id'],$entry['feed_id']);
    $resp->inserted = $wpdb->query($sql);
    
    //update the last updated time for the feed
    $resp->last_update = $wpdb->update(
      $feeds,//the table
      array('last_updated' => date ("Y-m-d H:i:s")),//columns to update
      array(//where filters
        'id' =>$entry['feed_id'] //current feed
      )
    );
    return $resp;

  }

  /* Get entries for a feed
   *    - for a user, filter by a condition - unread = true..
   */
  static function get($filters){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;
    $current_user = wp_get_current_user();
    $entries = $wpdb->prefix.$tbl_prefix. "entries";
    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries";
    //We can't let people just put random filters in
    //could be a sql injection vulnerability.
    //_log($filters);
    //TODO allow like queries
    $filter_whitelist = array('entry_id'=>'entry_id','title'=>'title','guid'=>'guid', 'link'=> 'link','content'=>'content','author'=>'author','isRead'=>'isRead','marked'=>'marked','id'=>'id','entry_id'=>'entry_id','feed_id'=>'ue.feed_id');
    $filter = "";
    foreach ($filters as $filter_name => $value){
      if(array_key_exists($filter_name,$filter_whitelist)){
        $filter = $filter . 
          $wpdb->prepare( " AND $filter_whitelist[$filter_name]  = %s ", $value);
      }
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
        ue.feed_id as feed_id,
        entries.entered as entered,
        entries.updated as updated

        from  $entries  as entries
        inner join  $user_entries  as ue
        on ue.entry_id=entries.id
        where ue.owner_uid = ". $current_user->ID."
        ". $filter . " 
        limit 30
    ;";
    //_log($sql);
    $myrows = $wpdb->get_results($sql);
    return $myrows;

  }
}

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
  //nonce_dance();
  $myrows = WprssFeeds::get();

  echo json_encode($myrows);
}
add_action('wp_ajax_wprss_get_feeds','wprss_list_feeds_die');
add_action('wp_ajax_nopriv_wprss_get_feeds','wprss_list_feeds_die');

//remove feed 
function wprss_unsubscribe_feed(){
  //nonce_dance();
  
  $feed_id = filter_input(INPUT_POST, 'feed_id', FILTER_SANITIZE_NUMBER_INT);
  $resp = WprssFeeds::remove($feed_id);
  echo json_encode($resp);
  exit;
}
add_action('wp_ajax_wprss_unsubscribe_feed','wprss_unsubscribe_feed');

//find the details of the feed.
function wprss_find_feed(){
  $orig_url = filter_input(INPUT_GET, 'url',FILTER_SANITIZE_URL);
  $contents = "";
  $resp->orig_url = $orig_url;
  if( !class_exists( 'WP_Http' ) )
    include_once( ABSPATH . WPINC. '/class-http.php' );

  $request = new WP_Http;
  $result = $request->request( $orig_url);
  $contents= $result['body'];
  


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
  $resp = WprssFeeds::save(array('feed_id'=>$feed_id,'feed_url'=>$feed_url,'site_url'=>$site_url,'feed_name'=>$feed_name,'private'=>$is_private));
  echo json_encode($resp);
  exit;
}
add_action('wp_ajax_wprss_save_feed','wprss_save_feed');

//get feed entries
function wprss_get_feed_entries(){
  $feed_id = filter_input(INPUT_GET, 'feed_id', FILTER_SANITIZE_NUMBER_INT);
  $show_read =filter_input(INPUT_GET, 'show_read', FILTER_SANITIZE_NUMBER_INT); 
  $filters = array();
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

  $myrows = WprssEntries::get($filters);
  echo json_encode($myrows);
  exit;
}
add_action('wp_ajax_wprss_get_entries','wprss_get_feed_entries');
add_action('wp_ajax_nopriv_wprss_get_entries','wprss_get_feed_entries');

//update multiple feeds
function wprss_update_feeds(){
  //get the list of feeds to update that haven't been updated recently
  _log('wp_cron update fired!');
  $feeds = WprssFeeds::get_stale_feeds();
  _log($feeds);
  
  //TODO Limit it to a reasonable number of feeds in a batch
  //TODO Maybe we should schedule wp_cron jobs for each update?
  //for each feed call update_feed
  foreach( $feeds as $feed){
    _log($feed);
    WprssFeeds::refresh($feed->id);
    //wprss_update_feed($feed->id);
  }
}
add_action('wp_ajax_wprss_update_feeds','wprss_update_feeds');
add_action('wp_ajax_nopriv_wprss_update_feeds','wprss_get_update_feeds');


//update single feed
function wprss_update_feed($feed_id="",$feed_url=""){
  //if we didn't get passed a feed, check to see if it is in the url
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
  //echo $feed_id;
  $resp = WprssFeeds::refresh($feed_id);

  echo json_encode($resp);
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
  $resp = WprssEntries::save(array(
    'isRead' =>($read_status=="true"?1:0),//columns to update
    'entry_id' =>$entry_id, //current entry
  ));
  echo json_encode($resp);
/*
  $prefix = $wpdb->prefix.$tbl_prefix; 
  $ret = $wpdb->update(
    $prefix.'user_entries',//the table
    array('isRead' =>($read_status=="true"?1:0)),//columns to update
    array(
      'id' =>$entry_id, //current entry
      'owner_uid'=>$current_user->ID //logged in user
    )//where filters
  );
*/
/*
  $returnval;
 // $returnval->updated = $ret;
  $returnval->id = $entry_id;
  $returnval->read_status = $read_status;
  echo json_encode($returnval);
*/

  exit;
}
add_action('wp_ajax_wprss_mark_item_read','wprss_mark_item_read');
//No non logged in way to mark an item read for me yet






?>
