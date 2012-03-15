
<div id='wprss-container'>
  <div id="commandbar" class="quicklinks">
    <script type="text/x-handlebars" >
  <ul>
    <li class="command"><a href="http://localhost/wp/wp-admin/admin-ajax.php?action=wprss_update_feed&feedid=1">Update Feed</a></li>
    <li class="command">
      {{#view Em.Button classBinding="isActive"
        tagName="span"
        target="Wprss.selectedFeedController"
        action="markAsRead" }}
        Mark all as Read
      {{/view}}
    
<a href="http://localhost/wp/wp-admin/admin-ajax.php?action=wprss_update_feed&feedid=1">Mark all as Read</a></li>
    <li class="command"><a href="http://localhost/wp/wp-admin/admin-ajax.php?action=wprss_update_feed&feedid=1">Subscribe +</a></li>
    <li class="command">
      {{#view Em.Button classBinding="isActive"
        tagName="span"
        target="Wprss.selectedFeedController"
        action="showRead" }}
        Show Read Items
      {{/view}}
    </li>
  </ul>
    </script>
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
        <li class="feed" {{bindAttr id="feed_id" }}>{{feed_name}}</li>
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
            <li class="entry" {{bindAttr id="content.entryID"}} >
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
              {{#if content.isRead}}
                Read  
              {{else }}
                Unread  
              {{/if}}

    
  </script>
<?php

?>
