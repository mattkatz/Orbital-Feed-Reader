
var mainModule= angular.module('mainModule', []);
mainModule.run(function($rootScope){

  /* 
   * receive the emitted messages and rebroadcast
   * use distinct event names to prevent browser explosion
   */
  $rootScope.$on('feedSelect',function(event, args){
    console.log('caught feedSelect!');
    $rootScope.$broadcast('feedSelected',args);
  });
});
//why this instead of the ng-app?  
//Because if you have two apps on the page ng-app doesn't work.
//You have to use bootstrap instead on specific dom elements.
angular.element(document).ready(function(){
  angular.bootstrap(jQuery("#wprss-main-content"),['mainModule']);
  angular.bootstrap(jQuery("#wprss-feedlist"),['mainModule']);
});
