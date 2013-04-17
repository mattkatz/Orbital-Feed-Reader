<div id='wprss-container' ng-app="mainModule" >
  <div id="commandbar" class="quicklinks">
  </div>
  <div id="y-indicator" class="does not provide funding" >
  </div>
  <div id="wprss-main-content" ng-controller="EntriesCtrl">
    <div id="wprss-content" >
        <ul class="entries">
          <li class="entry" ng-repeat="entry in entries" >
              <div id="{{entry.feed_id}}_{{entry.entry_id}}"ng-class="{'is-read': entry.isRead == 1, 'is-current': entry.entry_id == selectedEntry.entry_id}" >
                <a href="{{entry.link}}"><h2 class="entry-title" ng-bind-html="entry.title"></h2></a>
                <div class="author" ng-show="entry.author">
                  {{entry.author}}
                </div>
                <div class="date">
                  {{entry.entered | date:medium }}
                </div>
                <div ng-click="selectEntry(entry)" class="entry-content" ng-bind-html="entry.content"></div>
              </div>
          </li>
        </ul>
    </div>
  </div>
  <div id="wprss-feedlist" ng-controller="FeedListCtrl" >
      <div id='feed-head'>
        <h2>The Feeds</h2> 
        <a class="action" title="Add a new feed" ng-click="requestNewFeed()">+</a>
        <a class="action" title="Refresh the feed list" ng-click="refresh()">⟳</a>
        <a class="action" ng-show="editable" ng-class="{'is-editable': editable}" title="Edit these feeds" ng-click="setEditable()">∅</a>
        <a class="action" ng-hide="editable" ng-class="{'is-editable': editable}" title="Edit these feeds" ng-click="setEditable()">✎</a>
        <div ng-class="{'is-editable': editable}" ng-show="editable" ng-click="setEditable()">
          You are in edit mode, click here to exit.
        </div>
      </div>
    <ul id='feeds' >
      <li class="feed" ng-class="{'is-editable': editable}" ng-click="select(feed)" ng-class="{'is-selected': feed.isSelected}" ng-repeat="feed in feeds">
        {{feed.feed_name}} <span class="feedcounter">{{feed.unread_count}}</span>
        <a ng-show="editable" ng-click="editFeed(feed)">⚙</a>
      </li>
    </ul>
  </div>
  <div id='subscription-window' ng-show="reveal" ng-controller="SubsCtrl" class="modal-window" >
    <div ng-hide="feedCandidate">
      <label for='subscriptionUrl'>Drag or copy paste a feed here</label>
      <input type='url' id='subscriptionUrl' placeholder="http://www.morelightmorelight.com" ng-model="urlCandidate"/>
      <a class='button' ng-click='checkUrl()'>Check a URL</a>
      <a class="dismiss" ng-click="toggle()">X</a>
    </div>
    <div class="horizontal-form" >
      <div class="possibleFeeds" ng-show="possibleFeeds.length > 0" >
        <div>
          We found {{possibleFeeds.length}} feeds:
        </div>
        <ul>
          <li ng-repeat="feed in possibleFeeds" >
            <a ng-click="checkUrl(feed.url)" >{{feed.url}}</a>
          </li>
        </ul>
      </div>
      <div class="feedDetails" ng-show="feedCandidate">
        <h2>Feed Details for: <input id="feedCandidateName" ng-model="feedCandidate.feed_name" type='text' placeholder="Example Feed Name" /></h2>
        <label>Feed Url
          <input id='feedCandidateUrl' type='url' ng-model="feedCandidate.feed_url"  placeholder="http://www.example.com/rss.xml"/>
        </label>
        <label>Site Url
          <input id='feedCandidateSite' type='url' ng-model="feedCandidate.site_url" placeholder="http://www.example.com"/>
        </label>
        <label>
          <input type='checkbox' ng-model="feedCandidate.private" title="" />
          This Feed is Private! Do not show it to other people.
        </label>
        <label ng-show="feedCandidate.feed_id">
            Get rid of this feed! Seriously! 
            <a ng-click='unsubscribe(feedCandidate.feed_id)' class='button'>Unsubscribe</a>
        </label>
        <br/>
        <div class="clickable button" ng-click="saveFeed(feedCandidate)" }}>
          Save {{feedCandidate.feed_name}}
        </div>
        <div class="clickable button" ng-click="toggle()">
          Cancel
        </div>
      </div>
    </div>
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
