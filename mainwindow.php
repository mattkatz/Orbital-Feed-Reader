<div id='wprss-container' >

  <div id="commandbar" class="quicklinks">
  </div>
  <div id="y-indicator" class="does not provide funding" >
  </div>
  
<div id="wprss-main-content">
  <div ngView>
    
  </div>
  
</div>
<div id="wprss-feedlist" ng-controller="FeedListCtrl" >
    <div id='feed-head'>
      <h2>The Feeds</h2>
    </div>
  <ul id='feeds' >
    <li class="feed" ng-repeat="feed in feeds">
      <a href="#/feed/{{feed.feed_id}}">{{feed.feed_name}}
        <span class="feedcounter">{{feed.unread_count}}</span>
      </a>
    </li>
  </ul>
</div>
</div>

  <script type="text/javascript">
    var startentries = 
    <?php
      require_once('backend.php');
      $entries = WprssEntries::get(array('isRead'=>0));

      echo json_encode($entries);
    ?>;
    
  </script>
<script type="text/ng-template" id="entries-list.html">
  <div id="wprss-content" >
      <ul class="entries">
        <li class="entry" ng-repeat="entry in entries" >
          <h2>{{entry.title}}</h2>
          <div class="entry-content">
            {{entry.content}}
          </div>
        </li>
      </ul>
  </div>
</script>
