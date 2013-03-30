//why this instead of the ng-app?  
//Because if you have two apps on the page ng-app doesn't work.
//You have to use bootstrap instead on specific dom elements.
angular.element(document).ready(function(){
  angular.bootstrap(jQuery("#wprss-main-content"));
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
