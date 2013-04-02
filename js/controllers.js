/* Controllers */

function FeedListCtrl($scope, $http, $log){
  $log.log('in feedscontrol');
  $scope.log = $log.log;

  /*
   * let the world know a feed has been CHOSEN
   * Mightily emit a roar up the scope chain
   */
  $scope.select = function(id){
    $scope.log(id);
    $scope.$emit('feedSelect', {feed_id: id});
  };
  /*
   * get the list of feeds and store it
   */
  $scope.refresh = function (){
    $http.get(get_url.ajaxurl+'?action=wprss_get_feeds' )
    .success(function(data){ 
      $scope.feeds = data;
    });
  };
  $scope.refresh();
}

function EntriesCtrl($scope, $http, $log){
  $scope.log = $log.log;
  $scope.log("in EntriesCtrl");
  /*
   * select a feed to display entries from
   */
  $scope.select = function(id){
    $scope.log('Getting feed '+id);
    $http.get(get_url.ajaxurl+'?action=wprss_get_entries&feed_id='+id)
    .success(function(data){
      $scope.entries = data;
    });
  };
  $scope.select();
  /*
   * Catch the feedSelected event, display entries from that feed
   */
  $scope.$on('feedSelected',function(event,args){
    $scope.log('feedSelected in Entries!');
    $scope.refresh(args.feed_id);
  });
}

