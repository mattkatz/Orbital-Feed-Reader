/* Controllers */

function FeedListCtrl($scope, $http, $log){
  $log.log('in feedscontrol');
  $scope.log = $log.log;
  $scope.refresh = function (){
    $http.get(get_url.ajaxurl+'?action=wprss_get_feeds' )
    .success(function(data){ 
      $scope.feeds = data;
    });
  };
  $scope.select = function($event){
    $scope.log($event);
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
}

function TestCtrl($scope, $http,$routeParams,$log) {
  $log.log('in test ctrl');
}


