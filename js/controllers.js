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
  $scope.log("in EntriesCtrl");
  /*
   * select a feed to display entries from
   */
  $scope.displayFeed = function(id){
    $scope.log('Getting feed '+id);
    $http.get(get_url.ajaxurl+'?action=wprss_get_entries&feed_id='+id)
    .success(function(data){
      $scope.entries = data;
    });
  };

  /*
   * Someone has clicked an entry.
   * Mark it as read on the server, then alert the UI
   */

  $scope.selectEntry = function selectEntry(entry) {
    $scope.log('Selected entry ' + entry.entry_id);
    var data = {
      action: 'wprss_mark_item_read',
      read_status: true ,
      entry_id: entry.entry_id,
    };
    //Mark the entry read on the server
    $http.post(get_url.ajaxurl+'?action=wprss_mark_item_read&entry_id='+entry.entry_id+'&read_status='+true,data)
    .success(function(data){
      //mark the entry as read in the UI
      entry.isRead=1
      $scope.info('marked entry ' + entry.entry_id + ' as read');
      //TODO tell the feed list that the entry was marked read.
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
}

