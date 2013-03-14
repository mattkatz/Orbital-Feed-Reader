/* Controllers */

function FeedListCtrl($scope, $http){
  $http.get(get_url.ajaxurl+'?action=wprss_get_feeds' )
  .success(function(data){ 
    $scope.feeds = data;
  });
}


