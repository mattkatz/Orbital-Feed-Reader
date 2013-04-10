/* Controllers */

function FeedListCtrl($scope, $http, $log){
  $log.log('in feedscontrol');
  $scope.log = $log.log;

  /*
   * let the world know a feed has been CHOSEN
   * Mightily emit a roar up the scope chain
   */

  $scope.select = function(feed){
    $scope.log(feed.feed_id);
    $scope.$emit('feedSelect', {feed_id: feed.feed_id});
    //Mark feed as selected
    $scope.feeds.forEach(function(value,index){
      value.isSelected = value.feed_id == feed.feed_id
    });
    //Mark feed as loading
  };
  /*
   * get the list of feeds and store it
   */
  $scope.refresh = function(){
    $http.get(get_url.ajaxurl+'?action=wprss_get_feeds' )
    .success(function(data){ 
      $scope.feeds = data;
    });
  };
  $scope.refresh();

  /*
   * Has an entry changed? Update our feedlist
   */
  $scope.$on('entryChanged', function(event,args){
    //find the feed entry that has this entry's feed_id

    //decrement the read counter by the isread status
    $scope.log('caught entrychanged in feedCtrl');
    $scope.log(event);
    $scope.log(args);



  });
}

function EntriesCtrl($scope, $http, $log){
  $scope.log = $log.log;
  $scope.info = $log.info;
  $scope.warn = $log.warn;
  $scope.error = $log.error;
  $scope.selectedEntry = null;
  $scope.log("in EntriesCtrl");
  
  /*
   * select a feed to display entries from
   */
  $scope.displayFeed = function(id){
    $scope.log('Getting feed '+id);
    $http.get(get_url.ajaxurl+'?action=wprss_get_entries&feed_id='+id)
    .success(function(data){
      $scope.entries = data;
      $scope.selectedEntry = null;
    });
  };

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
    //Mark the entry read on the server
    $http.post(get_url.ajaxurl+'?action=wprss_mark_item_read&entry_id='+entry.entry_id+'&read_status='+newReadStatus,data)
    .success(function(data){
      //mark the entry as read in the UI
      entry.isRead= entry.isRead == 0 ? 1:0;
      //tell the feed list that the entry was toggled read.
      $scope.$emit('entryChange', {entry:entry});
    });
  }
  $scope.displayFeed();
  /*
   * Catch the feedSelected event, display entries from that feed
   */
  $scope.$on('feedSelected',function(event,args){
    $scope.log('feedSelected in Entries!');
    $scope.displayFeed(args.feed_id);
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
      var index = $scope.entries.indexOf(currentEntry);
      //If we are at the last entry just go to the first
      index = Math.max((index -1),0) ;
    }
    $scope.selectEntry($scope.entries[index]);
    //TODO scroll to the entry
    scrollToEntry(entry);
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

