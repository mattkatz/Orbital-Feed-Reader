<?php

require_once 'backend.php';

// perform migrations if needed
function orbital_migrate_db(){
  global $wpdb;
  global $orbital_db_version;
  global $orbital_db_version_opt_string;
  global $tbl_prefix;
  $current_version = get_site_option($orbital_db_version_opt_string);
  if(null == $current_version){
    //This is a new install, exit
    return;
  }
  if ( version_compare($current_version,'0.1.3','<=') ){
    _log("migrating from 0.1.3 or less
       We had an entered and updated entry column
       We are consolidating this to just an updated column and 
       moving the entry data, which is good into updated.
     ");
    $entries = $wpdb->prefix.$tbl_prefix."entries";
    $sql = "
      ALTER TABLE $entries
        DROP COLUMN updated;
      ";
    $res = $wpdb->query($sql);
    if(false === $res){
      _log('something went wrong migrating from 0.1.3. Here is the error');
      _log($wpdb->print_error());
      exit;
    }
    $sql = "
      ALTER TABLE $entries
        CHANGE COLUMN entered published DATETIME NOT NULL;
      ";
    $res = $wpdb->query($sql);
    if(false === $res){
      _log('something went wrong migrating from 0.1.3. Here is the error');
      _log($wpdb->print_error());
      exit;
    }
  }
}

# create the database tables.
function orbital_install_db()
{
  global $wpdb;
  global $orbital_db_version;
  global $orbital_db_version_opt_string;
  global $tbl_prefix;
  $charset_collate = '';

  if ( ! empty( $wpdb->charset ) )
    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
  if ( ! empty( $wpdb->collate ) )
    $charset_collate .= " COLLATE $wpdb->collate";

  require_once(ABSPATH. 'wp-admin/includes/upgrade.php');
  //perform any migrations dbdelta can't handle
  orbital_migrate_db();
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
  ) $charset_collate;";
  dbDelta($sql);
  _log("Added $table_name");
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
    private bool NOT NULL DEFAULT false,
    UNIQUE KEY id (id)
  ) $charset_collate;";
  dbDelta($sql);
  _log("Added $table_name");
  

  //user entries
  //TODO add the foreign key refs from ref id to entries id and feed id
  //TODO add starred
  $table_name = $wpdb->prefix.$tbl_prefix."user_entries";

  $sql = "CREATE TABLE " . $table_name ." (
    id integer NOT NULL AUTO_INCREMENT,
    entry_id integer NOT NULL,
    feed_id integer,
    orig_feed_id integer,
    owner_uid integer NOT NULL,
    marked bool NOT NULL DEFAULT false,
    isRead bool NOT NULL DEFAULT false,
    UNIQUE KEY id (id)
  ) $charset_collate;";
  dbDelta($sql);
  _log("Added $table_name");

  //entries
  $table_name = $wpdb->prefix.$tbl_prefix."entries";
  _log("Adding $table_name");

  $sql = "CREATE TABLE " . $table_name ." (
    id integer NOT NULL AUTO_INCREMENT,
    feed_id integer,
    title text NOT NULL,
    guid varchar(255) NOT NULL UNIQUE,
    link text NOT NULL,
    published datetime NOT NULL,
    content longtext NOT NULL,
    content_hash varchar(250) NOT NULL,
    author varchar(250) NOT NULL DEFAULT '',
    UNIQUE KEY id (id)
  ) $charset_collate;";
  dbDelta($sql);
  _log("Added $table_name");

  //Tags
  $table_name = $wpdb->prefix.$tbl_prefix."tags";
  $sql = "CREATE TABLE " . $table_name ." (
    id integer NOT NULL AUTO_INCREMENT,
    name varchar(200)  NOT NULL,
    UNIQUE KEY id (id)
  ) $charset_collate;";
  dbDelta($sql);
  _log("Added $table_name");

  /* map tags to users feeds
   * Users assign tags to feeds and they are a way for users to organize feeds
   */
  
  $table_name = $wpdb->prefix.$tbl_prefix."user_feed_tags";
  $sql = "CREATE TABLE " . $table_name ." (
    tag_id integer NOT NULL DEFAULT '0',
    user_feed_id integer NOT NULL DEFAULT '0',
    PRIMARY KEY (tag_id,user_feed_id)
  ) $charset_collate;";
  dbDelta($sql);
  _log("Added $table_name");
  update_option($orbital_db_version_opt_string,$orbital_db_version);
}

# load all the first installation data in.
# for each user
# When we first install the plugin:
#  - each user that can write a post should get a set of sample feeds
function orbital_install_data(){
  get_currentuserinfo();
  global $wpdb;
  global $tbl_prefix;
  global $current_user;
  //$user_id = $current_user->ID;
  $users = get_users();
  foreach( $users as $user){
    orbital_add_sample_feeds_to_user($user->ID);
  }


}
function orbital_install_instructional_entries($orbitalfeed){
  $i = 0;
  //Insert a sample entry
  OrbitalEntries::save(array(
    'feed_id'=> $orbitalfeed->feed_id,
    'title'=>'Welcome to Orbital!',
    'guid'=>'FAKEGUID'. $i++,
    'link'=>'http://mattkatz.github.com/Orbital-Feed-Reader/welcome.html',//TODO 
    'published'=>date ("Y-m-d H:i:s"),
    'content'=>"Here is where I'll put in some helpful stuff to look at",//TODO
    'author' => 'Matt Katz'
  ));
  //Insert a sample entry
  OrbitalEntries::save(array(
    'feed_id'=> $orbitalfeed->feed_id,
    'title'=>'Getting Started',
    'guid'=>'FAKEGUID' . $i++,
    'link'=>'http://mattkatz.github.com/Orbital-Feed-Reader/getting-started.html',//TODO 
    'published'=>date ("Y-m-d H:i:s"),
    'content'=>"This is <b>your</b> Orbital Reader, a feed reading platform for WordPress. I'll handle polling all your favorite websites for new posts. I've put some favorite samples in the side bar on the right. You'll see those start getting populated with new posts.",//TODO
    'author' => 'Matt Katz'
  ));
  //Insert a sample entry
  OrbitalEntries::save(array(
    'feed_id'=> $orbitalfeed->feed_id,
    'title'=>'Keyboard Shortcuts',
    'guid'=>'FAKEGUID' . $i++,
    'link'=>'http://mattkatz.github.com/Orbital-Feed-Reader/getting-started.html',//TODO 
    'published'=>date ("Y-m-d H:i:s"),
    'content'=>"You can mark entries as read and Orbital will remember for you. As you scroll down, just click on an entry to mark it as read.
    A better way to do this is to take your hand off the mouse and just click the 'j' key or the ⬇ key.
    Watch as you are taken to the next item to be read - we'll also mark it as something you've looked at.
    <p>Go ahead and try it now - see you at the next post.
    </p>    ",
    'author' => 'Matt Katz'
  ));
  //Insert a sample entry
  OrbitalEntries::save(array(
    'feed_id'=> $orbitalfeed->feed_id,
    'title'=>'More Keyboard Shortcuts',
    'guid'=>'FAKEGUID' . $i++,
    'link'=>'http://mattkatz.github.com/Orbital-Feed-Reader/getting-started.html',
    'published'=>date ("Y-m-d H:i:s"),
    'content'=>"<p>What else?</p>
    <p>
      <ul>
        <li>You can press 'u' to toggle whether an item is read or not.  </li>
        <li>'k' or ⬆ will go back to stuff you've already read. </li>
        <li>'o' will open up a new browser tab with the item you are looking at.  </li>
      </ul>
    </p>
    ",
    'author' => 'Matt Katz'
  ));
  //Insert a sample entry
  OrbitalEntries::save(array(
    'feed_id'=> $orbitalfeed->feed_id,
    'title'=>'The feedlist',
    'guid'=>'FAKEGUID' . $i++,
    'link'=>'http://mattkatz.github.com/Orbital-Feed-Reader/getting-started.html',
    'published'=>date ("Y-m-d H:i:s"),
    'content'=>"
    <p>Over in the feed list on the right hand side, look for three icons:
      <ul>
        <li> ⟳ - this is the refresh icon. It will refresh the feed list if for some reason we aren't keeping it up to date.  </li>
        <li>
        + - Add a new feed. This brings up the subscriptions dialog, and I'll tell you more about that in a second.
        </li>
        <li>
        ✎ - Edit and manage your feeds. Rename them, set them as private or public, etc. 
        </li>
        <li>
        ≣ - You'll see this or # as a way to toggle between list or #tag view. More on that later!
        </li>
      </ul>
      Underneath you'll find a list of all your feeds, ready to click on. Click one to just see that or click All to drink from the firehose.
    </p>
    ",
    'author' => 'Matt Katz'
  ));
  //Insert a sample entry
  OrbitalEntries::save(array(
    'feed_id'=> $orbitalfeed->feed_id,
    'title'=>'Adding your own sites to monitor',
    'guid'=>'FAKEGUID' . $i++,
    'link'=>'http://mattkatz.github.com/Orbital-Feed-Reader/getting-started.html',
    'published'=>date ("Y-m-d H:i:s"),
    'content'=>"
    <p>
      I've started you out with some great feeds that I like, but you probably want to add your own. That's easy!
    </p>
    <p>
    On the feedlist click the '+'. There's two ways to go from here. 
    <ol>
      <li>
      If you just want to add a new favorite site, that's easy. Just copy the URL ('http://www.whatever.com') and put it in text box, then hit the 'Check a Url' button. I'll go to the site and try to figure out what feeds it provides and give you a chance to pick, then hit save.  If I can't find one (not all websites make it easy), no problem. Look for the words 'RSS', 'ATOM', 'Feed' or the feed icon.
      </li>
      <li>
      Are you coming from Google Reader or something like that? You can go to the bottom section and just upload your OPML file. I'll do my best to read that file and import all your feeds for you. If you've got a lot, please be chill - it's all happening on your browser.
      </li>
      </ol>
      </p>
    ",
    'author' => 'Matt Katz'
  ));
  //Insert a sample entry
  OrbitalEntries::save(array(
    'feed_id'=> $orbitalfeed->feed_id,
    'title'=>"Press This!",
    'guid'=>'FAKEGUID' . $i++,
    'link'=>'http://mattkatz.github.com/Orbital-Feed-Reader/getting-started.html',
    'published'=>date ("Y-m-d H:i:s"),
    'content'=>"
    <p>So the real benefit of the Orbital Feed Reader is that it should encourage you to write more! All this stuff in your feed reader is really just inspiration juice. So here's how we do that. Highlight the first sentence on this post and click the BlogThis! link below. You'll see attribution and citation in a ready to edit Blog Post!</p>
    ",
    'author' => 'Matt Katz'
  ));
  //Insert a sample entry
  OrbitalEntries::save(array(
    'feed_id'=> $orbitalfeed->feed_id,
    'title'=>"Organize feeds with #Tags",
    'guid'=>'FAKEGUID' . $i++,
    'link'=>'http://mattkatz.github.com/Orbital-Feed-Reader/getting-started.html',
    'published'=>date ("Y-m-d H:i:s"),
    'content'=>"
    <p>When you're reading feeds, you want to read related stuff together. Rather than reading either ALL of your feeds as one river or just a single feed, you want to read a bundle of similar sites.</p>
    <p>You can do that with #tags in Orbital. Your sample feeds are already organized in some sample tags right now.</p>
    <p>At the top of the feed list, click on # or ≣ to toggle between viewing your feeds by #tag or in a ≣list. Try it now.</p>
    <p>When you save a new feed you can give it tags to organize it with others... Just type your tags separated by commas:  'gadgets,boredom,timewasting'</p>
    ",
    'author' => 'Matt Katz'
  ));
  //Insert a sample entry
  OrbitalEntries::save(array(
    'feed_id'=> $orbitalfeed->feed_id,
    'title'=>"That's it for now!",
    'guid'=>'FAKEGUID' . $i++,
    'link'=>'http://mattkatz.github.com/Orbital-Feed-Reader/getting-started.html',//TODO 
    'published'=>date ("Y-m-d H:i:s"),
    'content'=>"
    <p>Try adding some of your favorite sites to get started. When you find something you like, click BlogThis!</p>
    ",
    'author' => 'Matt Katz'
  ));

}
function orbital_add_sample_feeds_to_user($user_id){
    if(! user_can($user_id,'edit_posts')){
      // if this user can't author posts, then we don't want to offer them a feed reader
      return;
    }
    //install some sample feeds
    _log("installing sample feeds for $user_id");
    $orbitalfeed = OrbitalFeeds::save(
    array(
      'feed_url' => 'http://mattkatz.github.io/Orbital-Feed-Reader/ditz/html/feed.xml',
      //'feed_url' => 'http://localhost/orbital/ditz/html/feed.xml',
      'site_url' => 'http://mattkatz.github.com/Orbital-Feed-Reader/', 
      'is_private'=>0,
      'tags'=>'orbital,software',
      'owner'=>$user_id,
      'feed_name' => 'Orbital Changes'));

    OrbitalFeeds::save(
    array(
    'feed_url'=>'http://www.morelightmorelight.com/feed/',
    //'feed_url'=>'http://localhost/morelightmorelight/feed',
    'site_url'=> 'http://www.morelightmorelight.com',
    'is_private'=>0,
    'tags'=>'orbital,mutants',
    'owner'=>$user_id,
    'feed_name' =>'More Light! More Light!'));
    

    OrbitalFeeds::save(
    array(
      'feed_url'=>'http://boingboing.net/feed/',
      //'feed_url'=>'http://localhost/boingboing/iBag',
      'site_url'=> 'http://boingboing.net',
      'is_private'=>0,
      'tags'=>'mutants',
      'owner'=>$user_id,
      'feed_name' => 'Boing Boing'));
    OrbitalFeeds::save(
    array(
      'feed_url'=>'http://feeds.feedburner.com/ButDoesItFloat?format=xml',
      'site_url'=> 'http://butdoesitfloat.com',
      'is_private'=>0,
      'tags'=>'art',
      'owner'=>$user_id,
      'feed_name' => 'But does it float?'));
    OrbitalFeeds::save(
    array(
      'feed_url'=>'http://visitsteve.com/feed',
      'site_url'=> 'http://visitsteve.com/',
      'is_private'=>0,
      'tags'=>'art,mutants',
      'owner'=>$user_id,
      'feed_name' => 'Steve Lambert, art etc.'));
    OrbitalFeeds::save(
    array(
      'feed_url'=>'http://www.techdirt.com/techdirt_rss.xml',
      'site_url'=> 'http://www.techdirt.com/',
      'is_private'=>0,
      'tags'=>'news,economics,copyfight',
      'owner'=>$user_id,
      'feed_name' => 'Techdirt.'));
    OrbitalFeeds::save(
    array(
      'feed_url'=>'http://www.lessig.org/blog/index.rdf',
      'site_url'=> 'http://www.lessig.org/',
      'is_private'=>0,
      'tags'=>'copyfight',
      'owner'=>$user_id,
      'feed_name' => 'Lessig Blog'));
    OrbitalFeeds::save(
    array(
      'feed_url'=>'http://bldgblog.blogspot.com/atom.xml',
      'site_url'=> 'http://bldgblog.blogspot.com/',
      'is_private'=>0,
      'tags'=>'mutants',
      'owner'=>$user_id,
      'feed_name' => 'BLDGBLOG'));
    OrbitalFeeds::save(
    array(
      'feed_url'=>'http://feeds.feedburner.com/wiredbeyond',
      'site_url'=> 'http://www.wired.com/beyond_the_beyond',
      'is_private'=>0,
      'tags'=>'mutants',
      'owner'=>$user_id,
      'feed_name' => 'Bruce Sterling'));
    if( $orbitalfeed->feed_inserted){
      orbital_install_instructional_entries($orbitalfeed);
    }
    else{
      //we need to make sure we associate old entries with this user 
      OrbitalFeeds::link_old_entries($user_id);
    }
}


?>
