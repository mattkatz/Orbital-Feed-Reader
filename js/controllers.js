/* Controllers */

function FeedListCtrl($scope, $http, $log){
  $log.log('in feedscontrol');
  $scope.log = $log.log;
  $scope.select = function(id){
    $scope.log(id);
    $scope.$emit('feedSelect', {feed_id: id});
    //EntriesCtrl.refresh(id);
  };
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
  $scope.refresh = function(id){
    $scope.log('Getting feed '+id);
    $http.get(get_url.ajaxurl+'?action=wprss_get_entries&feed_id='+id)
    .success(function(data){
      $scope.entries = data;
    });
  };
  $scope.refresh();
  $scope.$on('feedSelected',function(event,args){
    $scope.log('feedSelected in Entries!');
  });
}

function TestCtrl($scope, $http,$routeParams,$log) {
  $log.log('in test ctrl');
}


