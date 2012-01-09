<style type="text/css">
  #wprss-feedlist{
    float:right;
    border-left: 1px solid #dddddd;
    padding: 10px;
  }

  .is-selected{
    text-shadow: 1px 1px 2px #666;
  }
    
</style>

<div id='wprss-container'>
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
            <a {{bindAttr href="link"}}><h2>{{title}}</h2></a>
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
