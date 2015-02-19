<div id='orbital-container' ng-app="mainModule" >
  <div id="orbital-feedlist" ng-controller="FeedListCtrl" >
    <div id='feed-head'>
    <h2><?php _e('The Feeds','orbital-reader'); ?></h2> 
      <div id="orbital-feedlist-actions">
        <a class="action" title="Add a new feed" ng-click="requestNewFeed()">+</a>
        <a class="action" title="Refresh the feed list" ng-click="refresh()">⟳</a>
        <a class="action" ng-show="editable" ng-class="{'is-editable': editable}" title="Edit these feeds" ng-click="setEditable()">∅</a>
        <a class="action" ng-hide="editable" ng-class="{'is-editable': editable}" title="Edit these feeds" ng-click="setEditable()">✎</a>
        <a class="action" ng-hide="showByTags" title="Show feeds organized by tag" ng-click="saveTagView(true)">#</a>
        <a class="action" ng-show="showByTags" title="Show feeds as a list" ng-click="saveTagView(false)">≣</a>
      </div>
      <div class="clickable" ng-class="{'is-editable': editable}" ng-show="editable" ng-click="setEditable()">
        <?php _e('You are in edit mode, click here to exit.','orbital-reader'); ?>
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
  <div id='orbital-cli' class="modal-window" ng-show="reveal" ng-controller="CliCtrl">
  <div><?php _e('Start typing the name of a feed. ⬇ ⬆  select a feed from the list. Enter goes to whatever you\'ve selected. Esc closes the window.','orbital-reader');</div>
    <input id='orbital-cli-input' ng-model='filterstring' focus-me='reveal' ng-keyup='processKeys($event)' type='text'></input>
    <div id='orbital-cli-results' ng-show='filterstring'>
      <ul class='feeds'>
        <li ng-repeat='feed in filteredFeeds() ' ng-include="'feedline.html'"></li>
      </ul>
    </div>
  </div>
  <div id="orbital-main-content" ng-controller="EntriesCtrl">
    <div id='subscription-window' ng-show="reveal" ng-controller="SubsCtrl" class="modal-window" >
      <script type="text/ng-template"  id='feedDetail.html'>
        <div class="feedDetail">
        <h2><?php _e('Feed Details for: ','orbital-reader'); ?><input id="feedCandidateName" ng-model="feedCandidate.feed_name" type='text' placeholder="<?php _e('Example Feed Name','orbital-reader'); ?>" /></h2>
        <label><?php _e('Feed Url','orbital-reader'); ?>
            <input id='feedCandidateUrl' type='url' ng-model="feedCandidate.feed_url"  placeholder="http://www.example.com/rss.xml"/>
          </label>
          <label><?php _e('Site Url','orbital-reader'); ?>
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
            <?php _e('This Feed is Private! Do not show it to other people.','orbital-reader'); ?>
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
            <?php _e('Put a website or a feed URL here:','orbital-reader'); ?>

          </label>
          <input type='url' id='subscriptionUrl' placeholder="http://www.morelightmorelight.com" ng-model="urlCandidate"/>
          <a class='button' ng-click='checkUrl()'><?php _e('Check a URL','orbital-reader'); ?>
</a>
        </div>
        <div class="feedByOpml">
          <form id='opml-form' ng-hide="possibleFeeds.length > 0" class='opml' novalidate>
            <div class="upload-form horizontal-form">
              <label>
                <img id='opml-icon' class='opml icon' src="<?php echo plugins_url("img/opml-icon.svg", __FILE__); ?>">
                <?php _e('Select an OPML file to import','orbital-reader'); ?>
                <input type="file" name="import-opml" value="" id="import-opml" 
                  placeholder="<?php _e('Select an OPML file','orbital-reader'); ?>"
                  onchange="angular.element(this).scope().fileSelected()" />
              </label>
              <div ng-show="fileSize" class="feedsInfo">
                <p><?php _ex('We found {{feedsCount}} feed<span ng-show="feedsCount >1">s</span> in this file of {{fileSize}}.','please leave the ng-show and {{feedsCount}} and {{fileSize}} in the right place. these are angular template notes.','orbital-reader'); ?> </p> 
                <p><?php _e('You can edit those feeds before saving them to your feedlist. When you are ready click to','orbital-reader'); ?>
                  <button type='submit' ng-show="fileSize" id="uploadButton" 
                    ng-disabled="! opmlFile" ng-click='uploadOPML()' >
                    <?php _e('Save these feeds to my feedlist','orbital-reader'); ?>
                  </button>
                </p>
              </div>
              
            </div>
            <div ng-show="feedCandidates" class="opml-candidates horizontal-form">
              <ul>
                <li ng-repeat="feedCandidate in feedCandidates" >
                  <div ng-include="'feedDetail.html'"></div>
                  <a href="#" ng-click="removeCandidate(feedCandidate)"><?php _e('Remove this feed','orbital-reader'); ?>
</a>
                </li>
              </ul>
            </div>
          </form>
        </div>
      </div>
      <div class="horizontal-form" >
        <div class="possibleFeeds" ng-show="possibleFeeds.length > 0" >
          <div>
            <?php _e('We found {{possibleFeeds.length}} feeds:','pleaseleave the {{possibleFeeds.legnth alone. It is an angular template','orbital-reader'); ?>

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
            <div><?php _e('Get rid of this feed! Seriously!','orbital-reader'); ?>

              <a ng-click='unsubscribe(feedCandidate)' class='button'><?php _e('Unsubscribe','orbital-reader'); ?>
</a> 
            </div>
          </label>
          <br/>
          <div class="clickable button" ng-click="saveFeed(feedCandidate)" }}>
            <?php _e('Save','orbital-reader'); ?> {{feedCandidate.feed_name}}
          </div>
          <div class="clickable button" ng-click="toggle()">
            <?php _e('Cancel','orbital-reader'); ?>
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
              <div class="indicator" >
                <span class='clickable' title='<?php _e('Click to just see posts from this feed','orbital-reader'); ?>
' ng-click='selectFeed(entry)' ng-bind-html="getFeedFromEntry(entry).feed_name"></span>
                <a class='clickable' title='<?php _e('Click here to edit the feed details','orbital-reader'); ?>
' ng-click="editFeed(getFeedFromEntry(entry))">⚙</a>
              </div>
              <div class="indicator" ng-show="entry.isLoading">
                <img src="<?php
                  echo plugins_url("img/ajax-loader.gif", __FILE__);
                ?>">
              </div>
              <div class="indicator clickable" title="<?php _e('type \'u\' or click here to mark unread','orbital-reader'); ?>
" ng-click="setReadStatus(entry,0)" ng-show="entry.isRead">
                <?php _e('Read','orbital-reader'); ?>
              </div>
            </div>
              <a href="{{entry.link}}" target='_blank'><h2 class="entry-title" ng-bind-html="entry.title"></h2></a>
              <div class="author" ng-show="entry.author" ng-bind-html="entry.author">
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
              <?php _e('That\'s all we\'ve got so far!','orbital-reader'); ?>
 <span ng-class="{'hide': ! isLoading, 'show': isLoading}"><?php _e('We\'re going to the server mines for more delicious content!','orbital-reader'); ?>
</span>
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
