<script type="text/javascript">
  var feeds= <?php
    require_once('backend.php');
    wprss_list_feeds();
  ?>;
  //Set everything up after page load
  jQuery(document).ready(function($){
    Wprss.feedsController.createFeeds(feeds);
  });
</script>
<div id='wprss-container'>
  <div id="wprss-content" class="horizontal-form">
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
          {{#if  feed_id}}
            <label>
              Get rid of this feed! Seriously! 
              {{#view Em.Button target="Wprss.selectedFeedController" action="unsubscribe"}} Unsubscribe {{/view}}
            </label>
          {{/if}}
          <div>
          {{#view Em.Button target="Wprss.selectedFeedController" action="saveFeed" }}Save{{/view}}
          </div>
      {{/with }}
      {{/view}}
      
    {{else}}
      <div class="no-feed-displayed">Choose a feed on the right to edit</div>
    {{/if}}
    </script>
  </div>
  <div id="wprss-feedlist">
    <div id='feed-head'>
      <h2>The Feeds</h2>
    <script type="text/x-handlebars" >
      {{#view Em.Button className="button"
        tagName="span"
        target="Wprss.feedsController"
        action="showFeed" }}
         +
      {{/view}}
    </script>
    </div>
    <ul id="feeds">
      <script type="text/x-handlebars" >
        {{#each Wprss.feedsController}}
          {{#view Wprss.FeedsView contentBinding="this"}}
          {{#with content}}
            <li class="feed" {{bindAttr id="feed_id" }}>{{feed_name}} <span class="feedcounter">{{unread_count}}</span></li>
          {{/with }}
          {{/view}}
        {{/each}}
      </script>
    </ul>
  </div>  
</div>
