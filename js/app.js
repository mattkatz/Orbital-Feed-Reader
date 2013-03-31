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
