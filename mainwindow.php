
<div id='wprss-container'>
  <div id="commandbar" class="quicklinks">
    <script type="text/x-handlebars" >
  <ul>
    <li class="command"><a href="http://localhost/wp/wp-admin/admin-ajax.php?action=wprss_update_feed&feed_id=1">Update Feed</a></li>
    <li class="command">
      {{#view Em.Button classBinding="isActive"
        tagName="span"
        target="Wprss.selectedFeedController"
        action="update" }}
        Update Feed
      {{/view}}
    </li>
    
    <li class="command">
      {{#view Em.Button classBinding="isActive"
        tagName="span"
        target="Wprss.selectedFeedController"
        action="markAsRead" }}
        Mark all as Read
      {{/view}}
    </li>
    <li class="command">
      {{#view Em.Button classBinding="isActive"
        tagName="span"
        target="Wprss.feedsController"
        action="showFeed" }}
        Subscribe +
      {{/view}}
    </li>
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
      <div id="loadmoreajaxloader" style="">
        <center>
          loading, just a sec...
        </center>
      </div>
  <div><a class="button" href="http://localhost/wp/wp-admin/admin-ajax.php?action=wprss_update_feeds">Refresh Feeds</a></div>
  <h2>The Feeds</h2>
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
  <div id="wprss-content">
    <script type="text/x-handlebars">
    {{#if Wprss.selectedFeedController.content}}
      <ul class="entries">
        {{#each Wprss.entriesController}}
          {{#view Wprss.EntriesView contentBinding="this"}}
            <li class="entry" {{bindAttr id="content.entryID"}} >
              <a {{bindAttr href="content.link"}} target="_blank"><h2>{{content.title}}</h2></a> {{#if content.author}}<span class="attribution">by {{{content.author}}}</span>{{/if}}
              {{{content.description}}}
              <div class="attributes">
              {{checkable  "content" contentBinding="content"}}
              

              </div>
            </li>
          {{/view}}
        {{/each}}
      </ul>
    {{else}}
      <div class="no-feed-displayed"><p>Whoa - there's nothing to show right now.</p> <p>Try clicking on one of the feeds on the right.</p></div>
    {{/if}}
    </script>
  </div>
</div>

  <div id="subscribe-window" class="modal-window invisible">
  <script type="text/x-handlebars">
    {{view Wprss.AddFeedView 
      name="addFeedView" 
      placeholder="Drag or copy paste a feed here" 
      valueBinding="Wprss.feedFinder.url" }}

      {{#view Em.Button classBinding="isActive"
        target="Wprss.feedFinder"
        action="findFeed" }}
        Add Feed
      {{/view}}
      <div class="horizontal-form">
        
        {{#if Wprss.feedFinder.feedCandidate}}
          {{#view Wprss.FeedView }}
          {{#with Wprss.feedFinder.feedCandidate}}
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
              {{#view Em.Button target="Wprss.feedFinder" action="saveFeed" }}Save{{/view}}
              </div>
          {{/with }}
          {{/view}}
        {{/if}}
        {{#if Wprss.feedFinder.possibleFeeds }}
          <div>
            We found {{Wprss.feedFinder.possibleFeeds.length }} feeds:
          </div>
          {{#each Wprss.feedFinder.possibleFeeds}}
            {{#view Wprss.PossibleFeedView contentBinding="this"}}
              {{#with content}}
                <div class="possibleFeed">
                {{url}}
                </div>
              {{/with}}
            {{/view}}
          {{/each}}
        {{/if}}
      </div>
  </script>

  </div>
  <script type="text/x-handlebars" data-template-name="read-check">
              {{#if content.isRead}}
                Read  
              {{else }}
                Unread  
              {{/if}}

    
  </script>
  <script type="text/x-handlebars" data-template-name="command-item">
    {{commandName}}
  </script>
<?php

?>
