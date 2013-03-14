<div id='wprss-container' ng-app="wprss">

  <div id="commandbar" class="quicklinks">
    <script type="text/x-handlebars" >
  <ul>
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
    </li>
    <li class="command">
      {{#view Em.Button classBinding="isActive"
        tagName="span"
        target="Wprss.selectedFeedController"
        action="showRead" }}
        Show Read Items
      {{/view}}
    </li>
    {{#if Wprss.selectedEntryController.content}}
      <li class="title">
        {{Wprss.selectedEntryController.content.feed_name}}
      </li>
    {{/if}}
  </ul>
    </script>
  </div>
  <div id="y-indicator" class="does not provide funding" >
  </div>
  
<div id="wprss-content" ng-controller="EntriesCtrl" >
    <ul class="entries">
      <li class="entry" ng-repeat="entry in entries" >
        <h2>{{entry.title}}</h2>
        <div class="entry-content">
          {{entry.content}}
        </div>
      </li>
    </ul>
</div>
<div id="wprss-feedlist" ng-controller="FeedListCtrl" >
    <div id='feed-head'>
      <h2>The Feeds</h2>
    </div>
  <ul id='feeds' >
    <li class="feed" ng-repeat="feed in feeds">{{feed.feed_name}}<span class="feedcounter">{{feed.unread_count}}</span></li>
  </ul>
</div>
</div>

<script type="text/x-handlebars" data-template-name="entry" >
  {{#with view.content}}
  <li class="entry" {{bindAttr id="entryID"}} >
    <a {{bindAttr href="link"}} target="_blank">
      <h2>{{{title}}}</h2>
    </a> 
    {{#if author}}
      <span class="attribution">by {{{author}}}</span>
    {{/if}} 
    {{#if entered}}
      <span class="entry-time"> {{{entered}}}</span>
    {{/if}}
    <div class="entry-content">
      {{{content}}}
    </div>
    <div class="attributes">
      {{checkable  view.content }}
      <div class="entry-isloading" style="display:none;">
       loading 
      </div>
    </div>
  </li>
  {{/with}}
</script>
  <script type="text/x-handlebars" data-template-name="command-item">
    {{commandName}}
  </script>
  <script type="text/javascript">
    var startentries = 
    <?php
      require_once('backend.php');
      $entries = WprssEntries::get(array('isRead'=>0));

      echo json_encode($entries);
    ?>;
    
  </script>
