<script type="text/javascript">
  var feeds= '<?php
    require_once('backend.php');
    wprss_list_feeds();
  ?>';
  //Set everything up after page load
  jQuery(document).ready(function($){
    Wprss.feedsController.createFeeds(feeds);
  });
</script>
<h1>Here there should be a list of your feeds</h1>
<div id="wprss-feedlist">
  <h2>Feeds</h2>
  <script type="text/x-handlebars" >
    <ul class="feeds">
      {{#each Wprss.feedsController}}
        {{#view Wprss.FeedsView contentBinding="this"}}
        {{#with content}}
          <li class="feed" {{bindAttr id="feed_id" }}>{{feed_name}} <span class="feedcounter">{{unread_count}}</span></li>
        {{/with }}
        {{/view}}
      {{/each}}
    </ul>
  </script>
</div>  
<div id="detailContainer" class="horizontal-form">
  <script type="text/x-handlebars" >
  {{#if Wprss.selectedFeedController.content}}
    {{#view Wprss.FeedView }}
    {{#with Wprss.selectedFeedController.content}}
        {{view Em.TextField valueBinding="feed_name" class="heading" }}
        <label>Feed Url
        {{view Em.TextField valueBinding="feed_url" }}
        </label>
        <label>Site Url
          {{view Em.TextField valueBinding="site_url" }}
        </label>
        <label>
          {{view Em.Checkbox valueBinding="is_private" title="This Feed is Private! Don't show it to other people."}}
        </label>
        <label>
          Get rid of this feed! Seriously! 
          {{#view Em.Button target="Wprss.selectedFeedController" action="unsubscribe"}} Unsubscribe {{/view}}
        </label>
        <div>
        {{#view Em.Button target="Wprss.selectedFeedController" action="saveFeed" }}Save{{/view}}
        </div>
    {{/with }}
    {{/view}}
    
  {{else}}
    <div class="no-feed-displayed">No feeds displayed</div>
  {{/if}}
  </script>
</div>

