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
    //TODO this should take in a param.
    // the param should be a userid.
    // if the userid == current user, we export everything but private
    // otherwise, just export the public stuff.
    $feeds = OrbitalFeeds::get();
    foreach($feeds as $feed){
    ?>
      <outline text="<?php echo $feed->feed_name?>" title="<?php echo $feed->feed_name?>" type="rss" xmlUrl="<?php echo $feed->feed_url?>" htmlUrl="<?php echo $feed->site_url?>"/>
    <?php
    }
    ?>
  </body>
</opml>
<?php
exit;
?>
