<?php
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
    if(isset($entry['entry_id'])){
      //this is an update
      $resp = OrbitalEntries::update($entry);
    }
    else{
      $entry_id = null;
      //see if the entry exists using entry hash or guid?
      if( isset($entry['guid'])){
        $entry_id = OrbitalEntries::check_guid($entry['guid']);
      }

      if(null === $entry_id){
        //insert the entry, get the ID for the feed
        $resp = OrbitalEntries::insert($entry);
      }
      else {
        //this is an update - let's do it.
        $entry['entry_id'] = $entry_id;
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
    $resp = array();

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
      $resp['updated'] = 0;
      $resp['message'] = "Nothing to update";
    }else{

      $ret = $wpdb->update(
        $user_entries,//the table
        $update_fields,//columns to update
        $filter_fields //where filters
      );
      $resp['updated'] = $ret;
    }
    if(array_key_exists('entry_id',$entry )){
      $resp['entry_id'] = $entry['entry_id'];
    }
    if(array_key_exists('feed_id',$entry)){
      $resp['feed_id'] = $entry['feed_id'];
    }
    return $resp;

  }

  //OrbitalEntries::get_enclosure
  //if we've got enclosures, pick the best
  static function get_enclosures($item){
    
    if($enclosure = $item->get_enclosure()){
      return $enclosure->get_link();
    }
    return "";
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
    $retcount = $wpdb->delete($user_entries, $wheres, '%d');
    OrbitalEntries::clean_entries();
    return $retcount;
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
      DELETE e
      FROM $entries e
      WHERE e.feed_id NOT IN (
        SELECT ue.orig_feed_id
        FROM $user_entries ue
      )
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
    $sort_order = -1;
    if(isset($user_settings['sort_order'])){
      $sort_order = $user_settings['sort_order'];
    }
    $sort = "ORDER BY entries.published ";
    if("-1" == $sort_order ){
      $sort = $sort . "DESC";
    }
    else{
      $sort = $sort ."ASC";
    }
    //We can't let people just put random filters in
    //could be a sql injection vulnerability.
    //TODO allow like queries
    $filter_whitelist = array('tag'=>'name','entry_id'=>'entry_id','title'=>'title','guid'=>'guid', 'link'=> 'link','content'=>'content','author'=>'author','isRead'=>'isRead','marked'=>'marked','id'=>'id','entry_id'=>'entry_id','feed_id'=>'ue.feed_id');
    $filter = "";

    foreach ($filters as $filter_name => $value){
      if(array_key_exists($filter_name,$filter_whitelist)){

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
    $myrows = $wpdb->get_results($sql);
    return $myrows;
  }
}

?>
