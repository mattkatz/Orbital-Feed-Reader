<?php

require_once 'backend.php';

# create the database tables.
function wprss_install_db()
{
  global $wpdb;
  global $wordprss_db_version;
  global $wordprss_db_version_opt_string;
  global $tbl_prefix;
  require_once(ABSPATH. 'wp-admin/includes/upgrade.php');
  add_option($wordprss_db_version_opt_string,$wordprss_db_version);
  //feeds
  $table_name = $wpdb->prefix.$tbl_prefix."feeds";

  $sql = "CREATE TABLE " . $table_name ." (
    id integer NOT NULL AUTO_INCREMENT,
    feed_url text NOT NULL,
    feed_name text NOT NULL,
    icon_url varchar(250) NOT NULL DEFAULT '',
    site_url varchar(250) NOT NULL DEFAULT '',
    last_updated datetime DEFAULT 0,
    last_error varchar(250) NOT NULL DEFAULT '',
    UNIQUE KEY id (id)
  );";
  dbDelta($sql);
  //User_feeds
  //This is the users view of a feed. 
  //Any value here overrides the feeds value.
  $table_name = $wpdb->prefix.$tbl_prefix."user_feeds";

  $sql = "CREATE TABLE " . $table_name ." (
    id integer NOT NULL AUTO_INCREMENT,
    owner BIGINT NOT NULL, 
    feed_id integer NOT NULL,
    feed_name text NOT NULL,
    icon_url varchar(250) ,
    site_url varchar(250) ,
    unread_count integer NOT NULL,
    private bool NOT NULL DEFAULT false,
    auth_login varchar(250) NOT NULL DEFAULT '',
    auth_pass varchar(250) NOT NULL DEFAULT '',
    UNIQUE KEY id (id)
  );";
  dbDelta($sql);

  //user entries
  //TODO add the foreign key refs from ref id to entries id and feed id
  //TODO add starred
  $table_name = $wpdb->prefix.$tbl_prefix."user_entries";

  $sql = "CREATE TABLE " . $table_name ." (
    id integer NOT NULL AUTO_INCREMENT,
    entry_id integer NOT NULL,
    user_feed_id integer,
    orig_feed_id integer,
    owner_uid integer NOT NULL,
    marked bool NOT NULL DEFAULT false,
    isRead bool NOT NULL DEFAULT false,
    UNIQUE KEY id (id)
  );";
  dbDelta($sql);
  //entries
  $table_name = $wpdb->prefix.$tbl_prefix."entries";

  $sql = "CREATE TABLE " . $table_name ." (
    id integer NOT NULL AUTO_INCREMENT,
    feed_id integer,
    title text NOT NULL,
    guid varchar(255) NOT NULL ,
    link text NOT NULL,
    updated datetime NOT NULL,
    content longtext NOT NULL,
    content_hash varchar(250) NOT NULL,
    no_orig_date bool NOT NULL DEFAULT 0,
    entered datetime NOT NULL,
    author varchar(250) NOT NULL DEFAULT '',
    UNIQUE KEY id (id)
  );";
  dbDelta($sql);
}

//TODO load in everything with admin as owner, 
# load all the first installation data in.
function wprss_install_data(){
  global $wpdb;
  global $tbl_prefix;
  global $current_user;
  $user_id = $current_user->ID;
  //install some sample feeds
  $feed = WprssFeeds::save(
  array(
  //'feed_url'=>'http://www.morelightmorelight.com/feed/',
  'feed_url'=>'http://localhost/morelightmorelight/feed',
  'site_url'=> 'http://www.morelightmorelight.com',
  'is_private'=>0,
  //'owner' => $current_user->ID,
  'feed_name' =>'More Light! More Light!'));
  
  $bb = WprssFeeds::save(
  array(
    //'feed_url'=>'http://boingboing.net/feed/',
    'feed_url'=>'http://localhost/boingboing/iBag',
    'site_url'=> 'http://boingboing.net',
    'is_private'=>0,
    //'owner' => $current_user->ID,
    'feed_name' => 'Boing Boing'));
  $wprssfeed = WprssFeeds::save(
  array(
    //'feed_url' => 'http://mattkatz.github.com/Wordprss/ditz/html/feed.xml',
    'feed_url' => 'http://localhost/Wordprss/ditz/html/feed.xml',
    'site_url' => 'http://mattkatz.github.com/Wordprss/', 
    'is_private'=>0,
    //'owner' => $current_user->ID,
    'feed_name' => 'Wordprss Changes'));

  //Insert a sample entry
  WprssEntries::save(array(
    'feed_id'=> $wprssfeed->feed_id,
    'title'=>'Welcome to Wordprss!',
    'guid'=>'FAKEGUID',
    'link'=>'http://mattkatz.github.com/Wordprss/welcome.html',//TODO 
    'updated'=>date ("Y-m-d H:m:s"),
    'content'=>"Here is where I'll put in some helpful stuff to look at",//TODO
    'entered' =>date ("Y-m-d H:m:s"), 
    'author' => 'Matt Katz'
  ));
  WprssEntries::save(array(
  //$wpdb->insert($table_name, array(
    'feed_id'=> $bb->feed_id,
    'title'=>'Look at this fake post about a banana',
    'guid'=>'FAKEGUID2',
    'link'=>'http://boingboing.net/',//TODO 
    'updated'=>date ("Y-m-d H:m:s"),
    'content'=>"just LOOK AT IT.<br/>Amazing, really how this meme caught on.",//TODO
    'entered' =>date ("Y-m-d H:m:s"), 
    'author' => 'Cory Doctorow'
  ));
}
/*
function wprss_uninstall_db()
{
  //We should remove the DB option for the db version
  delete_option('wordprss_db_version');
  //TODO clean up all the tables
  global $wpdb;
  $sql = "DROP TABLE ". $wpdb->prefix.$tbl_prefix."feeds;";
  $wpdb->query($sql);

}*/

?>
