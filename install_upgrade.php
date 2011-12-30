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

  $table_name = $wpdb->prefix.$tbl_prefix."feeds";

  $sql = "CREATE TABLE " . $table_name ." (
    id integer NOT NULL AUTO_INCREMENT,
    owner BIGINT NOT NULL,
    feed_url text NOT NULL,
    feed_name text NOT NULL,
    icon_url varchar(250) not null default '',
    site_url varchar(250) not null default '',
    UNIQUE KEY id (id)
  );";
   

  dbDelta($sql);
}
# load all the first installation data in.
function wprss_install_data(){
  global $wpdb;
  global $tbl_prefix;
  $table_name = $wpdb->prefix.$tbl_prefix."feeds";
  $wpdb->insert($table_name, array('owner'=> 1,'feed_url'=>'http://www.morelightmorelight.com/feed/','site_url'=> 'http://www.morelightmorelight.com', 'feed_name' =>'More Light! More Light!'));
  $wpdb->insert($table_name, array('owner'=> 1,'feed_url'=>'http://boingboing.net/feed/','site_url'=> 'http://boingboing.net', 'feed_name' => 'Boing Boing'));


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
