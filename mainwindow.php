<div id='wprss-container' ng-app="mainModule" >
  <div id="commandbar" class="quicklinks">
  </div>
  <div id="y-indicator" class="does not provide funding" >
  </div>
  <div id="wprss-main-content" ng-controller="EntriesCtrl">
    <div id="wprss-content" >
        <ul class="entries">
          <li class="entry" ng-repeat="entry in entries" >
              <div ng-class="{'is-read': entry.isRead == 1}" >
                <a href="{{entry.link}}"><h2 class="entry-title" ng-bind-html="entry.title"></h2></a>
                <div ng-click="selectEntry(entry)" class="entry-content" ng-bind-html="entry.content"></div>
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
        <a ng-click="select(feed)" >{{feed.feed_name}}
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
