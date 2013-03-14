/* Controllers */

function FeedListCtrl($scope, $http){
  $http.get(get_url.ajaxurl+'?action=wprss_get_feeds' )
  .success(function(data){ 
    $scope.feeds = data;
  });
}

function EntriesCtrl($scope, $http){
  $scope.refresh = function(id){
    $http.get(get_url.ajaxurl+'?action=wprss_get_entries&feed_id='+id)
    .success(function(data){
      $scope.entries = data;
    });

  };
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
 $scope.refresh(2);
}


