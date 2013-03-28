/* Controllers */

function FeedListCtrl($scope, $http, $routeParams,$location,$log){
  $log.log('in feedscontrol');
  $log.log('location is '+ $location.path());
  $log.log($routeParams);
  $http.get(get_url.ajaxurl+'?action=wprss_get_feeds' )
  .success(function(data){ 
    $scope.feeds = data;
  });
}

function EntriesCtrl($scope, $http,$routeParams, $log){
  $scope.refresh = function(id){

  };
  $scope.log = $log;
  $log.log('in entriescontrol');
  $http.get(get_url.ajaxurl+'?action=wprss_get_entries&feed_id='+$routeParams.feedId)
  .success(function(data){
    $scope.entries = data;
  });
  /*
  $scope.entries= [
    {"title": "Lorem Ipsum",
      "is_read":0,
      "content": "blah blah blah",},
    {"title": "Lorem Ipsum",
      "is_read":0,
      "content": "blah blah blah",},
    {"title": "Lorem Ipsum",
      "is_read":0,
      "content": "blah blah blah",},
  ];
  */
 /*$scope.refresh($routeParams.feedId);*/
}

function TestCtrl($scope, $http,$routeParams,$log) {
  $log.log('in test ctrl');
}


