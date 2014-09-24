<div id='orbital-container' ng-app="mainModule" >
  <div id="orbital-feedlist" ng-controller="FeedListCtrl" >
    <div id='feed-head'>
      <h2>The Feeds</h2> 
      <div id="orbital-feedlist-actions">
        <a class="action" title="Add a new feed" ng-click="requestNewFeed()">+</a>
        <a class="action" title="Refresh the feed list" ng-click="refresh()">⟳</a>
        <a class="action" ng-show="editable" ng-class="{'is-editable': editable}" title="Edit these feeds" ng-click="setEditable()">∅</a>
        <a class="action" ng-hide="editable" ng-class="{'is-editable': editable}" title="Edit these feeds" ng-click="setEditable()">✎</a>
        <a class="action" ng-hide="showByTags" title="Show feeds organized by tag" ng-click="saveTagView(true)">#</a>
        <a class="action" ng-show="showByTags" title="Show feeds as a list" ng-click="saveTagView(false)">≣</a>
      </div>
      <div class="clickable" ng-class="{'is-editable': editable}" ng-show="editable" ng-click="setEditable()">
        You are in edit mode, click here to exit.
      </div>
    </div>
    <script type="text/ng-template"  id='feedline.html'>
      <div class="feed" id="feed-{{feed.feed_id}}" ng-class="{'is-editable': editable, 'is-selected': feed == selectedFeed}" ng-click="select(feed)"  >
            {{feed.feed_name}} <span class="feedcounter" data-blart="{{feedUnreadCount(feed)}}">{{feedUnreadCount(feed)}} </span>
            <a ng-show="editable" ng-click="editFeed(feed)">⚙</a>
      </div>
    </script>
    <ul id='feeds' ng-hide="showByTags" >
      <li ng-repeat="feed in feeds" ng-include="'feedline.html'"> </li>
    </ul>
    <ul id='tags' ng-show="showByTags">
      <li ng-repeat="(tag, feeds) in tags" >
        <a href="#" class="orbital-treeindicator" ng-class="{'open':show}" ng-click="show = !show">▹</a>
        <span id="{{tag}}" class="tag" ng-click="select(tag)" ng-class="{'is-selected':tag == selectedFeed}" >#{{tag}} <span class="feedcounter">{{tagUnreadCount(tag)}}</span> </span>
        <ul ng-show="show">
          <li ng-repeat="feed in feeds" ng-include="'feedline.html'"> </li>
        </ul>
      </li>
    </ul>
  </div>
  <div id='orbital-cli' class="modal-window" ng-show="reveal" ng-controller="CliCtrl">
    <div>CONTROLLER</div>
    <input id='orbital-cli-input' ng-model='filterstring' type='text'></input>
    <div id='orbital-cli-results' ng-show='fileterstring'>
      RESULTS
    </div>
  </div>
  <div id="orbital-main-content" ng-controller="EntriesCtrl">
    <div id='subscription-window' ng-show="reveal" ng-controller="SubsCtrl" class="modal-window" >
      <script type="text/ng-template"  id='feedDetail.html'>
        <div class="feedDetail">
          <h2>Feed Details for: <input id="feedCandidateName" ng-model="feedCandidate.feed_name" type='text' placeholder="Example Feed Name" /></h2>
          <label>Feed Url
            <input id='feedCandidateUrl' type='url' ng-model="feedCandidate.feed_url"  placeholder="http://www.example.com/rss.xml"/>
          </label>
          <label>Site Url
            <input id='feedCandidateSite' type='url' ng-model="feedCandidate.site_url" placeholder="http://www.example.com"/>
          </label>
          <label>Tags:
            <div class="tagchecklist">
              <span class="atag" ng-repeat="tag in feedCandidate.tags | split "><a ng-click="removeTag(tag)" class="ntdelbutton">X</a>{{tag}}</span>
            </div>
            <div>
              <mk-autocomplete id='tagentry' ng-model="feedCandidate.tags" data-suggestion-source="availableTags" data-select-class='tagselected' ></mk-autocomplete>
            </div>
          </label>
          <label>
            <input type='checkbox' ng-model="feedCandidate.is_private" ng-checked="feedCandidate.is_private" title="" />
            This Feed is Private! Do not show it to other people.
          </label>
        </div>
      </script>
      <div class='indicator' ng-show="isLoading" >
        <img src="<?php echo plugins_url("img/ajax-loader.gif", __FILE__); ?>">
      </div>
      <div ng-hide="feedCandidate">
        <a class="dismiss clickable" ng-click="toggle()">X</a>
        <div class="feedByUrl">
          <label for='subscriptionUrl'>
            <img id="feed-icon" class="feed icon" src="<?php echo plugins_url("img/feed-icon.svg", __FILE__); ?>">
            Put a website or a feed URL here:
          </label>
          <input type='url' id='subscriptionUrl' placeholder="http://www.morelightmorelight.com" ng-model="urlCandidate"/>
          <a class='button' ng-click='checkUrl()'>Check a URL</a>
        </div>
        <div class="feedByOpml">
          <form id='opml-form' ng-hide="possibleFeeds.length > 0" class='opml' novalidate>
            <div class="upload-form horizontal-form">
              <label>
                <img id='opml-icon' class='opml icon' src="<?php echo plugins_url("img/opml-icon.svg", __FILE__); ?>">
                Select an OPML file to import
                <input type="file" name="import-opml" value="" id="import-opml" 
                  placeholder="Select an OPML file"
                  onchange="angular.element(this).scope().fileSelected()" />
              </label>
              <div ng-show="fileSize" class="feedsInfo">
                <p>We found {{feedsCount}} feed<span ng-show="feedsCount >1">s</span> in this file of {{fileSize}}.</p> 
                <p>You can edit those feeds before saving them to your feedlist. When you are ready click to
                  <button type='submit' ng-show="fileSize" id="uploadButton" 
                    ng-disabled="! opmlFile" ng-click='uploadOPML()' >
                    Save these feeds to my feedlist
                  </button>
                </p>
              </div>
              
            </div>
            <div ng-show="feedCandidates" class="opml-candidates horizontal-form">
              <ul>
                <li ng-repeat="feedCandidate in feedCandidates" >
                  <div ng-include="'feedDetail.html'"></div>
                  <a href="#" ng-click="removeCandidate(feedCandidate)">Remove this feed</a>
                </li>
              </ul>
            </div>
          </form>
        </div>
      </div>
      <div class="horizontal-form" >
        <div class="possibleFeeds" ng-show="possibleFeeds.length > 0" >
          <div>
            We found {{possibleFeeds.length}} feeds:
          </div>
          <ul>
            <li ng-repeat="feed in possibleFeeds" >
              <a ng-click="checkUrl(feed.url)" ><span class="feed name" ng-show="feed.name">{{feed.name}} - </span>{{feed.url}}</a>
            </li>
          </ul>
        </div>
        <div class="feedDetails" ng-show="feedCandidate">
          <div ng-include="'feedDetail.html'"></div>
          <label ng-show="feedCandidate.feed_id">
            <div>Get rid of this feed! Seriously!
              <a ng-click='unsubscribe(feedCandidate)' class='button'>Unsubscribe</a> 
            </div>
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
    <div id="orbital-content" >
        <div class="indicator" ng-show="isLoading">
          <img src="<?php
            echo plugins_url("img/ajax-loader.gif", __FILE__);
          ?>">
        </div>
        <ul id='orbital-entries' class="entries" infinite-scroll="addMoreEntries()" infinite-scroll-disabled='isLoading' infinite-scroll-parent='true' infinite-scroll-distance="2" >
          <li id="{{entry.feed_id}}_{{entry.id}}" class="entry" ng-repeat="entry in entries" ng-class="{'is-read': entry.isRead == 1, 'is-current': entry.id == selectedEntry.id}" >
            <div class='indicators'>
              <div class="indicator">
                {{getFeedName(entry)}}
              </div>
              <div class="indicator" ng-show="entry.isLoading">
                <img src="<?php
                  echo plugins_url("img/ajax-loader.gif", __FILE__);
                ?>">
              </div>
              <div class="indicator clickable" title="type 'u' or click here to mark unread" ng-click="setReadStatus(entry,0)" ng-show="entry.isRead">
                Read
              </div>
            </div>
              <a href="{{entry.link}}" target='_blank'><h2 class="entry-title" ng-bind-html="entry.title"></h2></a>
              <div class="author" ng-show="entry.author">
                {{entry.author}}
              </div>
              <div class="date" title="{{entry.published | date:mediumTime }}">
                {{entry.published | date:medium }}
              </div>
              <div ng-click="selectEntry(entry)" class="entry-content" ng-bind-html="entry.content"></div>
              <div class='entry-tools'>
                <a href="#" class="button" ng-click="pressThis(entry,'<?php echo admin_url('press-this.php') ?>')">Blog This!</a>
              </div>
          </li>
          <li >
            <div class="no-feed-displayed end-of-line">
              That's all we've got so far! <span ng-class="{'hide': ! isLoading, 'show': isLoading}">We're going to the server mines for more delicious content!</span>
            </div>
          </li>
        </ul>
    </div>
  </div>
</div>
<script type="text/javascript">
  var startentries = 
  <?php
    require_once('backend.php');
    $entries = OrbitalEntries::get(array('isRead'=>0));

    echo json_encode($entries);
  ?>;
</script>
