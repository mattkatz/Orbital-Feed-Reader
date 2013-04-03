var mainModule= angular.module('mainModule', ['ngSanitize']);
mainModule.run(function($rootScope){
  /* 
   * receive the emitted messages and rebroadcast
   * use distinct event names to prevent browser explosion
   */
  $rootScope.$on('feedSelect',function(event, args){
    console.log('caught feedSelect!');
    $rootScope.$broadcast('feedSelected',args);
  });
  //catch and broadcast entry changes
  $rootScope.$on('entryChange', function(event, args){
    console.log('caught entryChange!');
    $rootScope.$broadcast('entryChanged', args);
  });
});
