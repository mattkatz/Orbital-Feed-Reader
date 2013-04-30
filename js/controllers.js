/* Controllers */

function FeedListCtrl($scope, $http, $log){
  $log.log('in feedscontrol');
  $scope.log = $log.log;
  $scope.info = $log.info;
  $scope.editable = false;
  $scope.selectedFeed = null;

  /*
   * let the world know a feed has been CHOSEN
   * Mightily emit a roar up the scope chain
   */

  $scope.select = function(feed,showRead){
    $scope.log(feed.feed_id);
    if( $scope.editable){
      $scope.$emit('feedEdit',{feed: feed}); 
    }
    else{
      $scope.$emit('feedSelect', {feed: feed,showRead:showRead});
      //Mark feed as loading
    }
    //Mark feed as selected
    $scope.feeds.forEach(function(value,index){
      value.isSelected = value.feed_id == feed.feed_id
    });
    //let's cache this for later
    $scope.selectedFeed = feed;
  };
  $scope.requestNewFeed = function () {
    $scope.info('new feed requested');
    $scope.$emit('newFeedRequested');
  }
  /*
   * get the list of feeds and store it
   */
  $scope.refresh = function(){
    $scope.info('refreshing feeds');
    $http.get(get_url.ajaxurl+'?action=wprss_get_feeds' )
    .success(function(data){ 
      $scope.feeds = data;
    });
  };
  //call the refresh to load it all up.
  //TODO change this to load the initial feeds variable
  $scope.refresh();

  /*
   * Get the next unread feed
   *
   */
  $scope.nextUnreadFeed = function(){
    // We should iterate through the feeds
    // Find our selected feed
    // Then keep iterating till we find an unread feed
    // If we reach the end of the list
    // Start looking at the beginning till we find an unread feed
    feeds = $scope.feeds;
    index = feeds.indexOf($scope.selectedFeed);
    $log.info('index is ' + index);
    //we are starting at the index item
    //and circling the array
    for(i=(index+1)%feeds.length;i!=index;i= (i+1)%feeds.length){
      $log.info('i is ' + i);
      if(feeds[i].unread_count >0){
        return feeds[i];
      }
    }
    //NOTHING! let's just return where we started
    return $scope.selectedFeed;
  };

  /*
   * Set editable
   */
  $scope.setEditable = function(){
    $scope.editable = ! $scope.editable;
  }

  /*
   * Events
   */

  /*
   * Has an entry changed? Update our feedlist
   */
  $scope.$on('entryChanged', function(event,args){
    //find the feed entry that has this entry's feed_id
    entry = args.entry;
    feed_id = entry.feed_id;
    $scope.info($scope.feeds);
    //Look down the list of feeds for the one this entry belongs to
    for( i = 0; i < $scope.feeds.length; i++){
      feed = $scope.feeds[i];
      if( feed.feed_id ==  entry.feed_id){
        //decrement the read counter by the isread status
        feed.unread_count = Number(feed.unread_count ) + (entry.isRead ? -1:1);
      }
    }
  });

  /*
   * We should just get the feeds from the DB.
   */
  $scope.$on('refreshFeeds', function(event,args){
    $scope.refresh();
  });
  /* One of the command bar actions fired */
  $scope.$on('commandBarEvented', function  (event, args) {
    feed = args.feed;
    switch(args.name){
      case "markRead":
        //mark feed read
        var data = {
          action: 'wprss_mark_items_read',
          feed_id:feed.feed_id,
        };
        $http.post(get_url.ajaxurl, data)
        .success(function(response){
          $scope.refresh();
          $scope.select($scope.nextUnreadFeed());
        });
        break;
      case "updateFeed":
        //update feed 
        var data= {
          action: 'wprss_update_feed',
          feed_id: feed.feed_id,
        };
        $http.post(get_url.ajaxurl, data)
        .success(function(response){
          $log.info('selecting '+feed.feed_id);
          //refresh the feedlist
          $scope.refresh();
          //refresh the feed if it is still selected
          if(feed == $scope.selectedFeed){
            $scope.select(feed);
          }
        });
        break;
      case "showRead":
        //refresh this feed, but display read items
        $scope.select(feed,1);
        break;
      default:
        $log.log('requested commandBar action ' + args.name + ' - not implemented yet');
        break;
    }
  });
}

function EntriesCtrl($scope, $http, $log){
  $scope.log = $log.log;
  $scope.info = $log.info;
  $scope.warn = $log.warn;
  $scope.error = $log.error;
  $scope.selectedEntry = null;
  $scope.currentFeedId = null;
  $scope.log("in EntriesCtrl");
  
  /*
   * select a feed to display entries from
   */
  $scope.displayFeed = function(id,showRead){
    $scope.currentFeedId = id;
    $scope.isLoading = true;
    $http.get(get_url.ajaxurl+'?action=wprss_get_entries&feed_id='+$scope.currentFeedId+'&show_read='+showRead)
    .success(function(data){
      $scope.isLoading = false;
      //$scope.info(data);
      $scope.entries = data;
      $scope.selectedEntry = null;
    });
  };

  $scope.addMoreEntries = function(){
    $http.get(get_url.ajaxurl+'?action=wprss_get_entries&feed_id='+$scope.currentFeedId)
    .success(function  (response) {
      $scope.info('going to the server mines for more delicious content');
      //$scope.info(response);
      $scope.entries = _.union($scope.entries, response);
      //TODO is this really a good union? Is it double entering?
    });
  }

  /*
   * Someone has clicked an entry.
   * Toggle read on the server, then alert the UI
   */

  $scope.selectEntry = function selectEntry(entry) {
    $scope.log('Selected entry ' + entry.entry_id);
    var newReadStatus = entry.isRead == 0?1:0;
    var data = {
      action: 'wprss_mark_item_read',
      read_status: newReadStatus ,
      entry_id: entry.entry_id,
    };
    //Set this as the selected entry
    $scope.selectedEntry = entry;
    entry.isLoading = true;
    //Mark the entry read on the server
    $http.post(get_url.ajaxurl,data)
    .success(function(data){
      //mark the entry as read in the UI
      entry.isRead= entry.isRead == 0 ? 1:0;
      entry.isLoading = false;
      //tell the feed list that the entry was toggled read.
      $scope.$emit('entryChange', {entry:entry});
    });
  }
  $scope.displayFeed();
  /*
   * Catch the feedSelected event, display entries from that feed
   */
  $scope.$on('feedSelected',function(event,args){
    //$scope.log('feedSelected in Entries!');
    $scope.displayFeed(args['feed'].feed_id, args['showRead']);
  });
  $scope.nextEntry = function(currentEntry){
    $scope.info('next entry finds the entry after the current entry, selects it');
    var index =0;//by default we select the first entry
    if( $scope.entries.length == 0){
      return;//can't select anything
    }
    if(null != currentEntry){ //if there is a current entry, get the index after it
      var index = $scope.entries.indexOf(currentEntry);
      //If we are at the last entry just go to the first
      index = (index +1) % $scope.entries.length;
    }
    var next = $scope.entries[index];
    $scope.selectEntry(next);
    //scroll to the entry
    scrollToEntry(next);
  };
  $scope.previousEntry = function (currentEntry) {
    $scope.info('prev entry finds the entry before the current entry, selects it');
    var index = $scope.entries.length;//by default we select the last entry
    if( $scope.entries.length == 0){
      return;//can't select anything
    }
    if(null != currentEntry){ //if there is a current entry, get the index after it
      index = $scope.entries.indexOf(currentEntry);
      //If we are at the last entry just go to the first
      index = Math.max((index -1),0) ;
    }
    var previous = $scope.entries[index];
    $scope.selectEntry(previous);
    //scroll to the entry
    scrollToEntry(previous);
  };

  /* Set up keyboard shortcuts
   */

  //handle the down arrow keys and j to scroll the next item to top of scren
  key('j,down',function(event,handler){
    $scope.nextEntry($scope.selectedEntry);
  });
  //up and k should scroll the previous item to the top of the screen
  key('k,up',function(event,handler){
    $scope.previousEntry($scope.selectedEntry);
  });
  //o should open the original article
  key('o',function(event,handler){
    var entry = $scope.selectedEntry;
    $scope.log(entry);
    //TODO get a canonical link - or maybe we should only store canonical links when we do inserts
    if(entry){
      window.open(entry.link);
    }
  });
  //u should toggle the current item's read status
  key('u',function(event,handler){
    var entry = $scope.selectedEntry;
    if(null == entry)
      return;
    $scope.selectEntry(entry);
  });
}

/*
 * Subscription control
 * This should manage the workflow of adding or editing a feed
 * Feed CRUD happens here.
 *
 * If you summon this with nothing in it, we'll show the feed search
 * Give it a candidate and we'll hide the rest and let you edit this
 * 
 */
function SubsCtrl($scope,$http,$log){
  $scope.log = $log.log;
  $scope.info = $log.info;

  //The normal status of this window is to be hidden.
  $scope.reveal = false;
  $scope.possibleFeeds = null;
  $scope.urlCandidate = '';
  $scope.feedCandidate = null;
  $scope.toggle = function(){
    $scope.reveal = !$scope.reveal;
    $scope.clear();
  }

  $scope.clear = function() {
    $scope.possibleFeeds = null;
    $scope.urlCandidate = '';
    $scope.feedCandidate = null;
  }

  $scope.checkUrl = function(url){
    if(url){
      $scope.urlCandidate = url;
    }
    //now we should check the candidate
    var data = {
      action: 'wprss_find_feed',
      url: $scope.urlCandidate,
    };
    $scope.isLoading=true;
    //ask the backend to look at it
    $http.post(get_url.ajaxurl,data)
    .success(function(response){
      $scope.isLoading=false;
      if("feed" == response.url_type){
        console.log('found a feed!');
        //if it returns a feed detail, display that.
        $scope.feedCandidate = { 
          feed_url: response.orig_url, 
          site_url: response.site_url, 
          feed_id: null, 
          feed_name: response.feed_name,
          unread_count:0,
          private:false
        };
        $scope.possibleFeeds=null;
      }
      else{
        //if it returns possibleFeeds, display them.
        $scope.possibleFeeds = response.feeds;
        //remove the old feedCandidate if there is one
        $scope.feedCandidate = null;
      }
    });
  }

  $scope.saveFeed = function(feed){
    //TODO mark the save button busy
    var data = {
      action: 'wprss_save_feed',
      feed_id: feed.feed_id,
      feed_url: feed.feed_url,
      feed_name: feed.feed_name,
      site_url: feed.site_url,
      is_private: feed.private,
    };
    $scope.isLoading = true; 
    $http.post(get_url.ajaxurl,data)
    .success(function(response){
      //mark the save button not busy
      $scope.isLoading = false;
      $scope.toggle();
      $scope.feedsChanged();
    });
  }
  $scope.unsubscribe = function(feed){
    //TODO mark the button busy
    //TODO it would be good to give a cancel
    //Maybe it could just be to call the save again
    $scope.info(feed);
    var data = {
      action: 'wprss_unsubscribe_feed',
      feed_id: feed.feed_id,
    };
    $http.post(get_url.ajaxurl,data)
    .success(function(response){
      $scope.feedsChanged();
      //TODO unmark the busy 
      //close the dialogue
      $scope.toggle();
      $scope.feedsChanged();
    });
  }

  //this window has been requested or dismissed
  $scope.$on('subscriptionsWindow',function(event,args){
    $scope.info('subscriptionsWindow');
    //$scope.info(event);
    $scope.toggle();
  });

  $scope.feedsChanged = function(){
    $scope.$emit('feedsChanged');
  }

  //We are going to edit a feed
  //it becomes the feedCandidate so we can edit it there.
  //TODO we should copy the feed, not use the one in the feedlist
  $scope.$on('feedEditRequest', function(event,args){
    //$scope.info('feedEdit');
    $scope.reveal=true;
    $scope.feedCandidate = args.feed;
  });
}

function CommandBarCtrl($scope,$http,$log){
  $scope.$on('feedSelected', function(event,args){
    //$log.info('commandBar feed is:' + args['feed'].feed_name);
    $scope.currentFeed = args.feed;
  });
  $scope.commandBarAction = function(action){
    //$log.info(action.title + (action.name ? ' fired' : ' - not implemented yet'));
    $scope.$emit('commandBarEvent',{name: action.name,feed: $scope.currentFeed});
  };
  $scope.commands = [
    { title: "Mark All As Read",
      name: 'markRead',
    },
    { title: "Update Feed",
      name: 'updateFeed',
    },
    { title: "Show Read Items",
      name: 'showRead',
    },
  ];

}
