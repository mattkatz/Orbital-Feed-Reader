<style type="text/css">
  #wprss-feedlist{
    float:right;
    border-left: 1px solid #dddddd;
    padding: 10px;
  }

  .is-selected{
    text-shadow: 1px 1px 2px #666;
  }
  #commandbar {
    background: #dddddd;
    display:inline-block; 
  }
  #commandbar ul{
    display:inline; 
  
  }
  #commandbar li{
    float:left;
    padding-left:5px;
    padding-right:5px;
  
  }
    
</style>

<div id='wprss-container'>
  <div id="commandbar" class="quicklinks">
  <ul>
    <li class="command"><a href="http://localhost/wp/wp-admin/admin-ajax.php?action=wprss_update_feed&feedid=1">Update Feed</a></li>
    <li class="command"><a href="http://localhost/wp/wp-admin/admin-ajax.php?action=wprss_update_feed&feedid=1">Mark all as Read</a></li>
    <li class="command"><a href="http://localhost/wp/wp-admin/admin-ajax.php?action=wprss_update_feed&feedid=1">Subscribe +</a></li>
  </ul>
  </div>
  <div id="wprss-feedlist">
  <div>CURRENT USER: <?php 
  $curusr = wp_get_current_user();
  echo $curusr->ID; 

 ?></div>
  <div><a class="button" href="http://localhost/wp/wp-admin/admin-ajax.php?action=wprss_update_feeds">Refresh Feeds</a></div>
  <h2>The Feeds</h2>
    <script type="text/x-handlebars" >
    <ul class="feeds">
    {{#each Wprss.feedsController}}
      {{#view Wprss.FeedsView contentBinding="this"}}
      {{#with content}}
        <li class="feed">{{feed_name}}</li>
      {{/with }}
      {{/view}}
    {{/each}}

    </ul>


    </script>
  </div>
  <div id="wprss-content">
    <script type="text/x-handlebars">
    {{#if Wprss.selectedFeedController.content}}
      <ul class="entries">
        {{#each Wprss.entriesController}}
          <li class="entry">
            <a {{bindAttr href="link"}}><h2>{{title}}</h2></a> {{#if author}}<span class="attribution">by {{author}}</span>{{/if}}
            {{description}}
          </li>
        {{/each}}
      </ul>
    {{else}}
      <div class="no-feed-displayed">No feeds displayed</div>
    {{/if}}
    </script>
  </div>
</div>
<?php

?>
