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
    {{#view Wprss.FeedsView }}
    {{#with Wprss.selectedFeedController.content}}
      <h1 id="feedname">{{feed_name}}</h1>
        {{view Em.TextField valueBinding="Wprss.selectedFeedController.content.feed_name"}}
      <input type="text" name="Feed Name" {{bindAttr value="feed_name"}} id="feedName" placeholder="Feed Name" class="heading"/>
    {{/with }}
    {{/view}}
    
  {{else}}
    <div class="no-feed-displayed">No feeds displayed</div>
  {{/if}}
  </script>
  <input type="text" name="Feed Name" value="" id="feedName" placeholder="Feed Name" class="heading"/>
  <label for="feedUrl">Feed Url</label>
  <input type="text" name="feedUrl" value="http://churning.org/feed" id="feedUrl"/> 
  <label for="siteUrl">Site Url</label>
  <input type="text" name="siteUrl" value="http://churning.com" id="siteUrl"/>
  <label for="private">This Feed is Private! Don't show it.</label>
  <input type="checkbox" name="" value="false" id="private"/>
</div>

<p>
 You should be able to:
  <ul>
    <li>Remove feeds</li>
    <li>Add feeds</li>
    <li>Change the privacy setting of a feed</li>
    <li>Add feeds to tags</li>
  </ul>
</p>
