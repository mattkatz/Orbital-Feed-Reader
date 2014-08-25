<?php
// Set the headers so the file downloads
header('Content-type: application/xml+opml');
header('Content-Disposition: attachment; filename="Orbital-Feed-Reader-OPML-Export.xml"');
//header('Content-type: text/xml');
//oh PHP.  Why you gotta be so difficult about escaping things?
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?".">";
?>
<opml version="1.0" >
  <head>
    <dateCreated>
      <?php echo date("r", time())?>
    </dateCreated>
    <title>
      Orbital Feed Reader OPML Feed Export
    </title>
  </head>
  <body>
    <?php
    require_once 'backend.php';
    //this takes in a param.
    // the param should be a userid.
    $uid = intval(get_query_var('export_opml'));
    $show_privates = false;
    // if the userid == current user, we export everything including private
    if($uid  == wp_get_current_user()->ID){
      $show_privates = true; //naughty
    }
    // otherwise, just export the public stuff.
    $feeds = OrbitalFeeds::get($uid);
    foreach($feeds as $feed){
      if($feed->private == true && ! $show_privates){continue;}
      
    ?>
    <outline text="<?php echo $feed->feed_name?>" title="<?php echo $feed->feed_name?>" type="rss" xmlUrl="<?php echo $feed->feed_url?>" htmlUrl="<?php echo $feed->site_url?>" category="<?php echo $feed->tags ?>"/>
    <?php
    }
    ?>
  </body>
</opml>
<?php
exit;
?>
