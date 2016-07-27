<div id="orbital-container" ng-app="mainModule">
  <div id="orbital-feedlist" ng-controller="FeedListCtrl" >
    <div id="feed-head">
      <h2><?php _e('The Feeds','orbital-reader'); ?></h2> 
      <div id="orbital-feedlist-actions">
        <a class="action" title="Add a new feed" ng-click="requestNewFeed()">+</a>
        <a class="action" title="Refresh the feed list" ng-click="refresh()">⟳</a>
        <a class="action" ng-show="editable" ng-class="{'is-editable': editable}" title="Edit these feeds" ng-click="setEditable()">∅</a>
        <a class="action" ng-hide="editable" ng-class="{'is-editable': editable}" title="Edit these feeds" ng-click="setEditable()">✎</a>
        <a class="action" ng-hide="showByTags" title="Show feeds organized by tag" ng-click="saveTagView(true)">#</a>
        <a class="action" ng-show="showByTags" title="Show feeds as a list" ng-click="saveTagView(false)">≣</a>
      </div>
    </div>
    <script type="text/ng-template"  id='feedline.html'>
      <div class="feed" id="feed-{{feed.feed_id}}" ng-class="{'is-editable': editable, 'is-selected': feed == selectedFeed}" ng-click="select(feed)"  >
            <span ng-bind-html='feed.feed_name'></span> <span class="feedcounter" >{{feed.unreadCount()}} </span>
            <a ng-show="editable" ng-click="editFeed(feed)">⚙</a>
      </div>
    </script>
    <ul id='feeds' ng-hide="showByTags" >
      <li ng-repeat="feed in feeds" ng-include="'feedline.html'"> </li>
    </ul>
    <ul id='tags' ng-show="showByTags">
      <li ng-repeat="(tag, feeds) in tags" >
        <a href="#" class="orbital-treeindicator" ng-class="{'open':show}" ng-click="show = !show">▹</a>
        <span id="{{tag}}" class="tag" ng-click="select(tag)" ng-class="{'is-selected':tag == selectedFeed}" >#{{tag}} <span class="feedcounter">{{feeds.unreadCount()}}</span> </span>
        <ul ng-show="show">
          <li ng-repeat="feed in feeds" ng-include="'feedline.html'"> </li>
        </ul>
      </li>
    </ul>
  </div>
  <div id="orbital-main-content" ng-controller="EntriesCtrl">
    <div id="orbital-content">
      <div class="indicator" ng-show="isLoading"><img src="<?php echo plugins_url("img/ajax-loader.gif", __FILE__)?>" alt="loading"></div>
      <div class="orbital-now-playing">
        <div class="player" >
          <div class="entry-content" ng-bind-html="selectedEntry.content"></div>
          <a href="{{selectedEntry.link}}" target='_blank'><div class="entry-title"  ng-bind-html="selectedEntry.title"></div></a>
          <div id="orbital-player-controls">CONTROLS GO HERE</div>
        </div>
      </div>
      <div class="orbital-stream">
        <ul class="orbital-stream-entries">
          <li class="orbital-stream-entry" ng-repeat="entry in entries" ng-class="{'is-played':entry.isRead==1, 'is-playing': entry.id==selectedEntry.id}" ng-bind-html="entry.title" ng-click="selectEntry(entry)" title='{{selectedEntry.title}}' > </li>
          <li >
            <div class="no-feed-displayed end-of-line">
              <?php _e('That\'s all we\'ve got so far!','orbital-reader'); ?>
  <span ng-class="{'hide': ! isLoading, 'show': isLoading}"><?php _e('We\'re going to the server mines for more delicious content!','orbital-reader'); ?>
  </span>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>
