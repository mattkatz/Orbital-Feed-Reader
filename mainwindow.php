<div id='wprss-container' ng-app="mainModule" >

  <div id="commandbar" class="quicklinks">
  </div>
  <div id="y-indicator" class="does not provide funding" >
  </div>
  <div id="wprss-main-content" ng-controller="EntriesCtrl">
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
  </div>
  <div id="wprss-feedlist" ng-controller="FeedListCtrl" >
      <div id='feed-head'>
        <h2>The Feeds</h2>
      </div>
    <ul id='feeds' >
      <li class="feed" ng-repeat="feed in feeds">
        <a ng-click="select(feed.feed_id)" >{{feed.feed_name}}
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
