<?php



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
    owner BIGINT NOT NULL,
    feed_url text NOT NULL,
    feed_name text NOT NULL,
    icon_url varchar(250) not null default '',
    site_url varchar(250) not null default '',
    last_updated datetime default 0,
    last_error varchar(250) not null default '',
    auth_login varchar(250) not null default '',
    auth_pass varchar(250) not null default '',
    private bool not null default false,
    UNIQUE KEY id (id)
  );";
  dbDelta($sql);
  //user entries
  //TODO add the foreign key refs from ref id to entries id and feed id
  //TODO add starred
  $table_name = $wpdb->prefix.$tbl_prefix."user_entries";

  $sql = "CREATE TABLE " . $table_name ." (
    id integer not null AUTO_INCREMENT,
    ref_id integer not null,
    feed_id integer,
    orig_feed_id integer,
    owner_uid integer not null,
    marked bool not null default false,
    isRead bool not null default false,
    UNIQUE KEY id (id)
  );";
  dbDelta($sql);
  //entries
  $table_name = $wpdb->prefix.$tbl_prefix."entries";

  $sql = "CREATE TABLE " . $table_name ." (
    id integer NOT NULL AUTO_INCREMENT,
    title text not null,
    guid varchar(255) not null unique,
    link text not null,
    updated datetime not null,
    content longtext not null,
    content_hash varchar(250) not null,
    no_orig_date bool not null default 0,
    entered datetime not null,
    author varchar(250) not null default '',
    UNIQUE KEY id (id)
  );";
  dbDelta($sql);
}

//TODO load in everything with admin as owner, 
# load all the first installation data in.
function wprss_install_data(){
  global $wpdb;
  global $tbl_prefix;
  $table_name = $wpdb->prefix.$tbl_prefix."feeds";
  $wpdb->insert($table_name, array(
    'owner'=> 2,
    //'feed_url'=>'http://www.morelightmorelight.com/feed/',
    'feed_url'=>'http://localhost/morelightmorelight/feed',
    'site_url'=> 'http://www.morelightmorelight.com',
    'feed_name' =>'More Light! More Light!'));
  $wpdb->insert($table_name, array(
    'owner'=> 1,
    //'feed_url'=>'http://boingboing.net/feed/',
    'feed_url'=>'http://localhost/boingboing/iBag',
    'site_url'=> 'http://boingboing.net',
    'feed_name' => 'Boing Boing'));
  $wpdb->insert($table_name, array(
    'owner' => 2, 
    //'feed_url' => 'http://mattkatz.github.com/Wordprss/ditz/html/feed.xml',
    'feed_url' => 'http://localhost/Wordprss/ditz/html/feed.xml',
    'site_url' => 'http://mattkatz.github.com/Wordprss/', 
    'feed_name' => 'Wordprss Changes'
));



  //Insert a sample entry
  $table_name = $wpdb->prefix.$tbl_prefix."entries";
  $wpdb->insert($table_name, array(
    'title'=>'Welcome to Wordprss!',
    'guid'=>'FAKEGUID',
    'link'=>'http://mattkatz.github.com/Wordprss/welcome.html',//TODO 
    'updated'=>date ("Y-m-d H:m:s"),
    'content'=>"Here is where I'll put in some helpful stuff to look at",//TODO
    'entered' =>date ("Y-m-d H:m:s"), 
    'author' => 'Matt Katz'
  ));

  $wpdb->insert($table_name, array(
    'title'=>'Look at this fake post about a banana',
    'guid'=>'FAKEGUID2',
    'link'=>'http://boingboing.net/',//TODO 
    'updated'=>date ("Y-m-d H:m:s"),
    'content'=>"just LOOK AT IT.<br/>Amazing, really how this meme caught on.",//TODO
    'entered' =>date ("Y-m-d H:m:s"), 
    'author' => 'Cory Doctorow'
  ));
  //TODO insert a connection for each user that can hit dashboard
  //Insert a connection
  $table_name = $wpdb->prefix.$tbl_prefix."user_entries";
  $wpdb->insert($table_name, array(
    'ref_id' => 1,
    'feed_id' => 3,
    'orig_feed_id' => 3,
    'owner_uid' =>2
  ));

  //TODO insert a connection for each user that can hit dashboard
  //Insert a connection
  $table_name = $wpdb->prefix.$tbl_prefix."user_entries";
  $wpdb->insert($table_name, array(
    'ref_id' => 2,
    'feed_id' => 2,
    'orig_feed_id' => 2,
    'owner_uid' =>2
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
