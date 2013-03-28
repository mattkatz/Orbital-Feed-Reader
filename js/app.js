angular.module('main-content', []).
  config(['$routeProvider', function($routeProvider){
  console.log("in router");

  $routeProvider.
    //when('/feed/:id',{controller: EntriesCtrl, templateUrl: 'entries-list.html'}).
    //when('?page=wordprss.php#/feed/2',{controller: EntriesCtrl, template:' <div id="wprss-content" ng-controller="EntriesCtrl" > <ul class="entries"> <li class="entry" ng-repeat="entry in entries" > <h2>{{entry.title}}</h2> <div class="entry-content"> {{entry.content}} </div> </li> </ul> </div> '}).
    when('/feed/:id',{controller: FeedListCtrl});
    //otherwise({controller:TestCtrl });
    //when('/',{controller: TestCtrl}).
}]);

//why this instead of the ng-app?  
//Because if you have two apps on the page ng-app doesn't work.
//You have to use bootstrap instead on specific dom elements.
angular.element(document).ready(function(){
  angular.bootstrap(jQuery("#wprss-main-content"), ["main-content"]);
  angular.bootstrap(jQuery("#wprss-feedlist"));
});
/*
angular.module('main-content', []).
  config(['$routeProvider', function($routeProvider){
  $routeProvider.
    when('/feed/:id',{controller: EntriesCtrl, templateUrl: 'entries-list.html'}).
    otherwise({redirectTo: '/feed/:2'});
}]);
*/
