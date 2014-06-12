<?php
if ( !function_exists( 'add_action' ) ) {
  echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
  exit;
}
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
class OrbitalFeeds {

  /* Method to save a feed
   * OrbitalFeeds::save
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
    $resp = new stdClass;
    $feed_id = '';
    $user_id = $current_user->ID;
    if(isset($feed['owner']) && (current_user_can('install_plugin') || current_user_can('create_users'))){
      //We are saving this feed for a SPECIFIC user!
      //We must check to see if this is someone who has admin access to install plugins and such - in that case we should allow the user to save feeds for other users.
      $user_id = $feed['owner'];
    }
    if(isset( $feed['feed_id'])){
      //we are updating.  just do an update on user_feeds
      $sql = "UPDATE $user_feeds
              SET feed_name = %s
              , site_url = %s
              , private = %d
              WHERE id = %d
              AND owner = %d";
      $sql = $wpdb->prepare($sql,$feed['feed_name'],$feed['site_url'],$feed['is_private'],$feed['feed_id'], $user_id);
      $resp->feed_updated = $wpdb->query($sql);
      if(false=== $resp->feed_updated ) {
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
      //$feed->feed_id = $wpdb->get_var($sql);
      if (! $feed_id){//we will insert the feed id and then link
        //insert the feed and get the feed_id.
        $sql = 'INSERT INTO ' . $feeds.'
                ( `feed_url`, `feed_name`,  `site_url`)
                VALUES
                ( %s, %s, %s )
        ';
        $sql = $wpdb->prepare($sql, $feed['feed_url'], $feed['feed_name'],$feed['site_url']);
        //TODO we should have some sane error checking here
        $resp->feed_inserted = $wpdb->query($sql);
        if(false=== $resp->feed_inserted){
          $resp->feeds_error = $wpdb->print_error();
        }

        $feed_id = $wpdb->insert_id;
      }
      //Now let's link in the feed to user_feeds
      $sql = 'INSERT INTO ' .$user_feeds.'
        (feed_id, feed_name, site_url,owner, private)
         VALUES
         (%d,%s,%s,%d,%d)';
      $sql = $wpdb->prepare($sql, $feed_id,  $feed['feed_name'],$feed['site_url'],$user_id,$feed['is_private']);
      $resp->user_feed_inserted = $wpdb->query($sql);
      if(false=== $resp->user_feed_inserted){
        $resp->user_feeds_error = $wpdb->print_error();
        return resp;
      }
      else{
        // we really want to show the USER_FEEDS.id, not the FEEDS.ID
        $feed['feed_id'] = $wpdb->insert_id;
      }
    }

    //Tag update here
    //We should always expect a feed_id at this point.
    OrbitalFeeds::saveFeedTags($feed);

    /* we should associate the entries from the entries table
     * with this user_feeds row throug the user_entries table
     */



    //TODO this should be eliminated
    //$resp->sql = $sql;
    $resp->user = $user_id;
    $resp->feed_id = $feed['feed_id'];
    $resp->feed_url = $feed['feed_url'];
    $resp->site_url = $feed['site_url'];
    $resp->feed_name = $feed['feed_name'];
    $resp->is_private = $feed['is_private'];
    $resp->unread_count ="0";//TODO: A LIE. But it gets corrected quickly
    return $resp;
  }
  /* OrbitalFeeds::link_old_entries
   * 
   * Method to grab entries that aren't 
   * associated with a particular user_feed
   * and correct that
   */
  static function link_old_entries($user_id)
  {
    global $wpdb;
    global $tbl_prefix;
    $entries = $wpdb->prefix.$tbl_prefix. "entries ";
    $user_feeds = $wpdb->prefix.$tbl_prefix. "user_feeds ";
    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries ";
    $resp = new stdClass;
    $sql = "
      INSERT INTO $user_entries 
        (entry_id
        ,feed_id
        ,orig_feed_id
        ,owner_uid
        ,marked
        ,isRead
        )
      SELECT 
       e.id AS entry_id
      ,uf.id AS feed_id
      ,uf.feed_id AS orig_feed_id
      ,uf.owner AS owner_uid
      ,0 AS marked
      ,0 AS isRead
      FROM $user_feeds uf
      INNER JOIN $entries  e
        ON e.feed_id = uf.feed_id
      LEFT OUTER JOIN $user_entries ue
       ON ue.feed_id = uf.id
      WHERE uf.owner = %d 
      AND ue.id IS NULL
    ";
    $sql = $wpdb->prepare($sql,$user_id );
    $resp->entry_inserted = $wpdb->query($sql);
    if(false=== $resp->entry_inserted){
      $resp->entries_error = $wpdb->print_error();
    }
    return $resp;
  }
  
  /* OrbitalFeeds::saveFeedTags($feed);
   *
   * Method to save the tags for a feed
   *
   * We split the tags out into individual tags
   * for each we find the tag.id and save a link.
   * If there is no link, we save a new tag
   *
   * Finally we do some cleanup - check orphan tags
   * and kill the orphans
   */
  static function saveFeedTags($feed){
    global $wpdb;
    global $tbl_prefix;
    $user_feed_tags =$wpdb->prefix.$tbl_prefix. "user_feed_tags"; 
    $tags =$wpdb->prefix.$tbl_prefix. "tags"; 

    $feedtags = preg_split("/[\s,]+/", $feed["tags"]);
    foreach($feedtags as $tag){
      OrbitalFeeds::saveFeedTag($feed["feed_id"],$tag);
    }
    //TODO - this looks like a security risk, should go through $wpdb->prepare
    $tagsarray = '"' . implode('","', $feedtags) . '"';

    $sql = $wpdb->prepare("
      SELECT uft.tag_id,tags.name
      FROM $user_feed_tags uft
      INNER JOIN $tags tags
        ON tags.id = uft.tag_id
        AND uft.user_feed_id = %d
      WHERE COALESCE(tags.name,'Untagged') NOT IN ($tagsarray )",$feed['feed_id']);
    //_log($sql);

    $tag_ids = $wpdb->get_col($sql, 0);

    //_log($tag_ids);
    //_log($wpdb->get_col($sql,1));
    $tag_ids = implode(',',$tag_ids);
    //_log($tag_ids);
    //do nothing if we don't have any ids to process
    if($tag_ids){
      $wpdb->query($wpdb->prepare("
        DELETE 
        FROM $user_feed_tags
        WHERE tag_id IN ( $tag_ids )
        AND user_feed_id = %d ",$feed['feed_id']));
    }
  }

  static function saveFeedTag($feed_id, $tag){
    global $wpdb;
    global $tbl_prefix;
    $tags =$wpdb->prefix.$tbl_prefix. "tags"; 

    //let's find the tag_id for this tag
    $sql = " SELECT id
      FROM $tags
      WHERE name = %s";
    $tag_id = $wpdb->get_var($wpdb->prepare($sql, $tag));
    if($tag_id){
      // save a link to this tag_id
      OrbitalFeeds::linkTag($feed_id, $tag_id);
    }else{
      //TODO: save the tag to tags, then save a link 
      $wpdb->insert( $tags, array('name'=>$tag));
      OrbitalFeeds::linkTag($feed_id, $wpdb->insert_id);
    }
  }
  static function linkTag ($feed_id, $tag_id){
    global $wpdb;
    global $tbl_prefix;
    $user_feed_tags =$wpdb->prefix.$tbl_prefix. "user_feed_tags"; 
    $rows_affected = $wpdb->replace(
      $user_feed_tags,
      array(
        'tag_id' =>$tag_id,
        'user_feed_id' =>$feed_id
      ),
      array(
        '%d',
        '%d',
      )
    );
  }


  /* OrbitalFeeds::getTags
   *
   * Method to list all feeds by tag or search by fragment
   * 
   * We list each tag that the user has and then union
   * all of the feeds which aren't linked by a tag
   */
  static function getTags($tag_fragment){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;
    $user_feeds = $wpdb->prefix.$tbl_prefix. "user_feeds ";
    $user_feed_tags =$wpdb->prefix.$tbl_prefix. "user_feed_tags"; 
    $tags =$wpdb->prefix.$tbl_prefix. "tags"; 
    $sql = "
      SELECT tag.name 
      FROM $tags tag
      INNER JOIN $user_feed_tags uft ON uft.tag_id = tag.id
      INNER JOIN $user_feeds uf ON uf.id = tag.id
      WHERE uf.owner = $current_user->ID
      AND tag.name LIKE (%s)
      GROUP BY tag.name
        ";
    $myrows = $wpdb->get_col($wpdb->prepare($sql,'%'.like_escape($tag_fragment ).'%'), 0 );
    return $myrows;
    
  }

  /* OrbitalFeeds::get
   *
   * Method to list all feeds
   *   - Just return all feeds from user_feeds
   */
  static function get($user_id){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds ";
    $user_feeds = $wpdb->prefix.$tbl_prefix. "user_feeds ";
    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries ";
    $user_feed_tags = $wpdb->prefix.$tbl_prefix. "user_feed_tags ";
    if(! $user_id)
    { 
      $user_id =  get_current_user_id(); 
    }
    $tags = $wpdb->prefix.$tbl_prefix. "tags ";
    $sql = "
    SELECT 
    f.feed_id,
    f.feed_name,
    f.feed_url,
    f.icon_url,
    f.site_url,
    f.last_updated,
    f.last_error,
    f.private,
    f.unread_count,
    GROUP_CONCAT(DISTINCT COALESCE(tags.name,'Untagged')) as tags
    FROM (
        SELECT 
          u_feeds.id AS feed_id,
          COALESCE(u_feeds.feed_name,feeds.feed_name ) AS feed_name,
          feeds.feed_url, 
          COALESCE(u_feeds.icon_url, feeds.icon_url ) AS icon_url,
          COALESCE(u_feeds.site_url, feeds.site_url ) AS site_url,
          feeds.last_updated,
          feeds.last_error,
          u_feeds.private,
          SUM(IF(COALESCE(ue.isRead,1)=0,1,0)) AS unread_count
        FROM $user_feeds AS u_feeds
        INNER JOIN $feeds AS feeds
          ON u_feeds.feed_id = feeds.id
          AND u_feeds.owner =  $user_id
        LEFT OUTER JOIN $user_entries AS ue
          ON ue.feed_id=u_feeds.id
        GROUP BY 
          feed_id,
          feed_url,
          feed_name,
          icon_url,
          site_url,
          last_updated,
          last_error,
          private) f
    LEFT OUTER JOIN $user_feed_tags uft
      ON uft.user_feed_id = f.feed_id
    LEFT OUTER JOIN $tags tags
      ON uft.tag_id = tags.id
    GROUP BY 
      f.feed_id,
      f.feed_name,
      f.feed_url,
      f.icon_url,
      f.site_url,
      f.last_updated,
      f.last_error,
      f.private,
      f.unread_count

        ";
    _log($sql);

    $myrows = $wpdb->get_results($sql );
    return $myrows;
  }
  /* Method to find total unread feeds
   */
  static function get_unread_count(){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;
    $current_user = wp_get_current_user();
    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries ";
    $sql = "
      select 
      count(*) as unread_count 
      from ".$user_entries." as ue
      where ue.isRead !=1
      and ue.owner_uid = ". $current_user->ID."
      ";
    $myrows = $wpdb->get_var($sql );
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
    //$orig_feed_id
    //User feeds
    //Let's get the underlying feed_id now
    $sql = "
      SELECT feed_id
      FROM $user_feeds
      WHERE owner = $current_user->ID
        AND id = %d";
    $orig_feed_id = $wpdb->get_var($wpdb->prepare($sql,$feed_id));

    $sql = "
      DELETE 
      FROM $user_feeds 
      WHERE owner = $current_user->ID 
      AND id = %d";
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
    $subscribers = $wpdb->get_var($wpdb->prepare($sql,$orig_feed_id));

    if(0<= $subscribers){
      $sql = "
        DELETE
        FROM $entries
        WHERE feed_id = %d";
      $sql = $wpdb->prepare($sql,$orig_feed_id);
      if(false === $wpdb->query($sql)){
        $resp->entries_error = $wpdb->print_error();
      }

      //TODO we are getting a weird blank error on delete for this
      //Is that valid for postgres?  How can we just eliminate that error?
      $sql = "
        DELETE 
        FROM $feeds
        WHERE id = %d;";
      $sql = $wpdb->prepare($sql,$orig_feed_id);
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
    // this won't work on php 5.2
    //$then = date_sub($now,new DateInterval('PT1H'))->format('Y-m-d H:i:sP');
    //_log('subtracting an hour');
    //_log($now);
    $now->modify('-1 hours');
    //_log($now);
    $then = $now->format('Y-m-d H:i:sP');

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

  static function get_orig_feed_id($user_feed_id){
    global $wpdb;
    global $tbl_prefix;
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds";
    $u_feeds = $wpdb->prefix.$tbl_prefix. "user_feeds";
    $feed_id = null;
    $feed_id = $wpdb->get_var($wpdb->prepare("
      SELECT feed_id
      FROM $u_feeds
      WHERE id = %d",$user_feed_id));
    return $feed_id;

  }

  // OrbitalFeeds::refresh_user_feed
  /* Function: refresh_user_feed
   *
   * takes a user feed id, figures out the underlying feed
   * then calls to refresh the main feed
   * 
   * returns: a count of updates and inserts made
   */
  static function refresh_user_feed($user_feed_id){
    //If we are looking up by user_feed, we need the original feed id
    $feed_id = OrbitalFeeds::get_orig_feed_id($user_feed_id);
    return OrbitalFeeds::refresh($feed_id);
  }


  // OrbitalFeeds::refresh 
  /* Function: Refresh
   *
   * Refresh a feed from it's underlying source.  
   * Process it and save all new or updated entries
   *
   * Returns: a count of updates and inserts made
   */
  static function refresh($feed_id){
    //TODO update the feeds last updated time
    include_once(ABSPATH . WPINC . '/class-feed.php');
    $feedrow = OrbitalFeeds::get_feed($feed_id);

    $feed = new SimplePie();
    //If your cache isn't writable, this is a big deal
    //Better to just disable it for now
    $feed->enable_cache(false);
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
      //_log('saving an item');
      //_log($item); 
      OrbitalEntries::save(array(
        'feed_id'=>$feed_id,
        'title'=>$item->get_title(),
        'guid'=>$item->get_id(),
        'link'=>$item->get_permalink(),
        'published'=>$item->get_date("Y-m-d H:i:s"), // this is really updated or entered
        'content'=>$item->get_content(),
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
class OrbitalEntries{
/* OrbitalEntries::save
 * Insert an entry for a feed
 *    - TODO check to see if entry exists, using entry hash?
 *    - insert entry, then link for each user subscribed to the feed.
 *    - alternately, update the stored entry - this should be used to mark feeds updated or to update their content when the feed is updated.
 *    - TODO compare the content_hash on old and new before resetting isread
 */
  static function save($entry){
    _log('in save');
    _log($entry);

    if(isset($entry['entry_id'])){
      //this is an update
      _log('sending to update');
      $resp = OrbitalEntries::update($entry);
    }
    else{
      $entry_id = null;
      //see if the entry exists using entry hash or guid?
      if( isset($entry['guid'])){
        $entry_id = OrbitalEntries::check_guid($entry['guid']);
      }

      if(null === $entry_id){
        _log('sending to insert');
        //insert the entry, get the ID for the feed
        $resp = OrbitalEntries::insert($entry);
      }
      else {
        //this is an update - let's do it.
        $entry['entry_id'] = $entry_id;
        _log("found an $entry_id  and sending to update");
        $resp = OrbitalEntries::update($entry);
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
    //_log('entry is ');
    //_log($entry);
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

  //OrbitalEntries::insert
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
    
    $resp=new stdClass;

    $wpdb->insert($entries, array(
      'feed_id'=>$entry['feed_id'],
      'title'=>$entry['title'],
      'guid'=>$entry['guid'],
      'link'=>$entry['link'],
      'published'=>$entry['published'],
      'content'=>$entry['content'],
      'author' => $entry['author']
    ));
    $entry_id = $wpdb->insert_id;
    $resp->insert_id = $entry_id;
    //insert the link to user_entries
    $sql = "INSERT INTO ".$user_entries."
            (entry_id, feed_id, orig_feed_id, owner_uid, marked, isRead)
            SELECT
            %d,id,feed_id,owner,0,0
            FROM ".$user_feeds." 
            WHERE feed_id = %d" ;
    $sql = $wpdb->prepare($sql,$entry_id, $entry['feed_id']);
    $resp->entry_inserted = $wpdb->query($sql);
    if(false=== $resp->entry_inserted){
      $resp->entries_error = $wpdb->print_error();
    }
    
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
  /*
   * OrbitalEntries::link_to_users
   * Take an entry and associate it to any users not currently associated with it.
   * with an entry in user_entries
   */
  static function link_to_users($entry_id){
    //TODO: SHOULD I EVEN DO THIS?

  }
  /*
   * OrbitalEntries::unlink
   * Remove any links from entries to a particular user
   */
  static function unlink($user_id=null, $feed_id=null){
    global $wpdb;
    global $tbl_prefix;
    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries";
    $entries = $wpdb->prefix.$tbl_prefix. "entries";
    $wheres = array();
    if( isset($user_id)){
      $wheres['owner_uid'] = $user_id;
    }
    if( isset($feed_id )){
      $wheres['feed_id'] = $feed_id;
    }
    if(count($wheres) <1){
      //we don't want to remove ALL entries
      return 0;
    }
    retcount = $wpdb->delete($user_entries, $wheres, '%d');
    OrbitalEntries::clean_entries();
    return retcount;
  }
  /* OrbitalEntries::clean_entries
   * Clean up unviewable entries
   * If an entry doesn't have any user_entry records associated, no one can see it.
   * Kill it
   */
  static function clean_entries(){
    global $wpdb;
    global $tbl_prefix;
    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries";
    $entries = $wpdb->prefix.$tbl_prefix. "entries";
    $sql = "
      DELETE $entries
      FROM $entries e
      LEFT OUTER JOIN $user_entries ue
        ON e.feed_id = ue.orig_feed_id
      WHERE ue.feed_id IS NULL
      ";
    $wpdb->query($sql);
  }


  /* Get entries for a feed
   *    - for a user, filter by a condition - unread = true..
   *    OrbitalEntries::get
   */
  static function get($filters){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;
    $current_user = wp_get_current_user();
    $entries = $wpdb->prefix.$tbl_prefix. "entries";
    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries";
    $user_feed_tags =$wpdb->prefix.$tbl_prefix. "user_feed_tags"; 
    $tags =$wpdb->prefix.$tbl_prefix. "tags"; 
    $user_settings = (array) get_user_option( 'orbital_settings' );
    $sort_order = $user_settings['sort_order'] || -1;
    $sort = "ORDER BY entries.published ";
    if("-1" == $sort_order ){
      $sort = $sort . "DESC";
    }
    else{
      $sort = $sort ."ASC";
    }
    //We can't let people just put random filters in
    //could be a sql injection vulnerability.
    //_log($filters);
    //TODO allow like queries
    $filter_whitelist = array('tag'=>'name','entry_id'=>'entry_id','title'=>'title','guid'=>'guid', 'link'=> 'link','content'=>'content','author'=>'author','isRead'=>'isRead','marked'=>'marked','id'=>'id','entry_id'=>'entry_id','feed_id'=>'ue.feed_id');
    $filter = "";

    /*
    _log('constructing get filters');
    _log('filters are');
    _log($filters);
     */

    foreach ($filters as $filter_name => $value){
      if(array_key_exists($filter_name,$filter_whitelist)){

        //_log("filterName: $filter_name, value: $value");
        if(null == $value || 'null' == $value){
          $filter= $filter. " AND $filter_whitelist[$filter_name] IS NULL ";
        }
        else if ( -1 == $value){
          //I'm interpreting -1 as a "skip this" value mainly for the "all feeds"
          continue;
        }
        else{
          $filter = $filter . 
            $wpdb->prepare( " AND $filter_whitelist[$filter_name]  = %s ", $value);
        }
        //_log("Filter: $filter");
      }
    }

    //TODO change get feed entries to support non logged in use
    $sql = "select entries.id AS entry_id,
        entries.title AS title,
        entries.guid AS guid,
        entries.link AS link,
        entries.content AS content,
        entries.author AS author,
        ue.isRead AS isRead,
        ue.marked AS marked,
        ue.id AS id,
        ue.feed_id AS feed_id,
        DATE_FORMAT(entries.published, '%Y-%m-%dT%TZ') AS published

        FROM  $entries  AS entries
        INNER JOIN  $user_entries  AS ue
          ON ue.entry_id=entries.id
        LEFT OUTER JOIN $user_feed_tags AS user_feed_tags
          ON user_feed_tags.user_feed_id = ue.feed_id
        LEFT OUTER JOIN $tags AS tags
          ON tags.id = user_feed_tags.tag_id
        WHERE ue.owner_uid = ". $current_user->ID."
        ". $filter . " 
        GROUP BY 
          title, guid, link, content, author, isRead, marked, id, feed_id, published
        ". $sort . "
        LIMIT 30
    ;";
    _log($sql);
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
function orbital_list_feeds_die(){
  orbital_list_feeds();
  exit;
}

function orbital_list_feeds(){
  //nonce_dance();
  $myrows = OrbitalFeeds::get();

  echo json_encode($myrows);
}
add_action('wp_ajax_orbital_get_feeds','orbital_list_feeds_die');

function orbital_list_tags(){
  $tag_fragment = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);
  $rows = OrbitalFeeds::getTags($tag_fragment);
  echo join($rows,"\n");
  //echo json_encode();
  exit;
}
add_action('wp_ajax_orbital_get_tags','orbital_list_tags');

//remove feed 
function orbital_unsubscribe_feed(){
  //nonce_dance();
  
  $feed_id = filter_input(INPUT_POST, 'feed_id', FILTER_SANITIZE_NUMBER_INT);

  $resp = OrbitalFeeds::remove($feed_id);
  echo json_encode($resp);
  exit;
}
add_action('wp_ajax_orbital_unsubscribe_feed','orbital_unsubscribe_feed');

//find the details of the feed.
function orbital_find_feed(){
  $orig_url = filter_input(INPUT_POST, 'url',FILTER_SANITIZE_URL);
  $contents = "";
  $resp->orig_url = $orig_url;
  if( !class_exists( 'WP_Http' ) )
    include_once( ABSPATH . WPINC. '/class-http.php' );

  $request = new WP_Http;
  $result = $request->request( $orig_url);
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
  //$is_private = $_POST['is_private']=="true"?1:0;
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
  $filters = array();
  $feed_id = filter_input(INPUT_GET, 'feed_id', FILTER_SANITIZE_NUMBER_INT);
  $show_read =filter_input(INPUT_GET, 'show_read', FILTER_SANITIZE_NUMBER_INT); 
  $tag = filter_input(INPUT_GET, 'tag',FILTER_SANITIZE_STRING);
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
  //_log('filters');

  $myrows = OrbitalEntries::get($filters);
  echo json_encode($myrows);
  exit;
}
add_action('wp_ajax_orbital_get_entries','orbital_get_feed_entries');
add_action('wp_ajax_nopriv_orbital_get_entries','orbital_get_feed_entries');

//update multiple feeds
function orbital_update_feeds(){
  //get the list of feeds to update that haven't been updated recently
  _log('wp_cron update fired!');
  $feeds = OrbitalFeeds::get_stale_feeds();
  _log($feeds);
  
  //TODO Limit it to a reasonable number of feeds in a batch
  //TODO Maybe we should schedule wp_cron jobs for each update?
  //for each feed call update_feed
  foreach( $feeds as $feed){
    _log($feed);
    OrbitalFeeds::refresh($feed->id);
  }
}
add_action('wp_ajax_orbital_update_feeds','orbital_update_feeds');
add_action('wp_ajax_nopriv_orbital_update_feeds','orbital_get_update_feeds');


//update single feed
function orbital_update_feed($feed_id="",$feed_url=""){
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
add_action('wp_ajax_nopriv_orbital_update_feed','orbital_get_update_feed');

//Mark items as read
function orbital_mark_items_read($feed_id){
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
  global $wpdb;
  global $tbl_prefix;
  global $current_user;
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

  $settings = (array) get_user_option( 'orbital_settings' );
  //TODO what if the settings haven't been set? we should default them.
  //$sort_order = esc_attr($settings['sort-order']);
  echo json_encode($settings);
  exit;
}
add_action('wp_ajax_orbital_get_user_settings','orbital_get_user_settings');

//set the current entry sort order for this user
function orbital_set_user_settings(){
  global $current_user;
  //TODO this is the better way, but I can't get it to work.
  //$user_orbital_settings = filter_input(INPUT_POST, 'orbital_settings', FILTER_SANITIZE_STRING);
  $user_orbital_settings = $_POST['orbital_settings'];
  $settings = (array) get_user_option( 'orbital_settings' );
  //merge arrays
  $new_settings = $user_orbital_settings + $settings;
  /*
  _log("posted settings");
  _log($user_orbital_settings);
  _log("db settings");
  _log($settings);
  _log("merged settings");
  _log($new_settings);
   */
  
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
