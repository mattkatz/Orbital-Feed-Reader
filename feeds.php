<?php
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
      $sql = $wpdb->prepare($sql,$feed['feed_name'],$feed['site_url'],$feed['is_private']?1:0,$feed['feed_id'], $user_id);
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

    $tag_ids = $wpdb->get_col($sql, 0);

    $tag_ids = implode(',',$tag_ids);
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
    if(! isset($user_id))
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
  static function remove($user_id = null, $feed_id=null){
    global $wpdb;
    global $tbl_prefix;
    global $current_user;
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds";
    $user_feeds = $wpdb->prefix.$tbl_prefix. "user_feeds";
    $user_entries = $wpdb->prefix.$tbl_prefix. "user_entries ";
    $entries = $wpdb->prefix.$tbl_prefix. "entries";

    if(! isset($user_id)){
      $current_user = wp_get_current_user();
      $user_id = $current_user->ID;
    }
    $resp->user = $user_id;

    $where = array('owner'=>$user_id);
    if(isset($feed_id)){
      $where['id'] = $feed_id;
    }

    //User feeds
    if(false === $wpdb->delete($user_feeds, $where,'%d')){
      $resp->uf_error = $wpdb->print_error();
      _log($resp->uf_error);
    }
    //clean up any feeds that don't have subscriptions from users
    OrbitalFeeds::clean_feeds();

    //delete all user_entries for user
    OrbitalEntries::unlink($user_id,$feed_id);
    $resp->feed_id = $feed_id;
    return $resp;
  }


  /* OrbitalFeeds::clean_feeds
   * Remove any feeds that no one is currently subscribed to 
   */
  static function clean_feeds(){
    global $wpdb;
    global $tbl_prefix;
    $feeds = $wpdb->prefix.$tbl_prefix. "feeds";
    $user_feeds = $wpdb->prefix.$tbl_prefix. "user_feeds";
    $sql = "
      DELETE f
      FROM $feeds f
      LEFT OUTER JOIN $user_feeds uf
        ON uf.feed_id = f.id
      WHERE uf.id IS NULL
      ";
    if(false === $wpdb->query($sql)){
      _log($wpdb->print_error());
    }
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
    $now->modify('-1 hours');
    $then = $now->format('Y-m-d H:i:sP');

    $sql = "
      SELECT feeds.id,feeds.feed_name
      FROM $feeds as feeds
      WHERE feeds.last_updated < %s
      ";
    $sql = $wpdb->prepare($sql,$then);
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
    $resp = array();
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
    $resp['feed_id'] = $feed_id;
    $resp['updated'] = count($items);
    return $resp;
  }
}
?>
