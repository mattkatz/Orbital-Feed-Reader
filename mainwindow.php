
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
          {{#view Wprss.EntriesView contentBinding="this"}}
            <li class="entry">
              <a {{bindAttr href="content.link"}}><h2>{{content.title}}</h2></a> {{#if content.author}}<span class="attribution">by {{content.author}}</span>{{/if}}
              {{content.description}}
              <div class="attributes">
              {{checkable  "content" contentBinding="content"}}
              

              </div>
            </li>
          {{/view}}
        {{/each}}
      </ul>
    {{else}}
      <div class="no-feed-displayed">No feeds displayed</div>
    {{/if}}
    </script>
  </div>
</div>
  <script type="text/x-handlebars" data-template-name="read-check">
    {{author}}
              {{view Em.Checkbox titleBinding="readStatus" valueBinding="content.isRead" }}
              {{ readStatus}}
              {{#if content.isRead}}
                Read  
                
              {{else }}
                unRead  

              {{/if}}

    
  </script>
<?php

?>
