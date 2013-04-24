var mainModule= angular.module('mainModule', ['ngSanitize','infinite-scroll']
  //TODO insert fix for angular->php post here
  //http://victorblog.com/2012/12/20/make-angularjs-http-service-behave-like-jquery-ajax/
);
mainModule.run(function($rootScope){
  /* 
   * receive the emitted messages and rebroadcast
   * use distinct event names to prevent browser explosion
   */
  $rootScope.$on('feedSelect',function(event, args){
    //console.log('caught feedSelect!');
    $rootScope.$broadcast('feedSelected',args);
  });
  $rootScope.$on('feedEdit',function(event, args){
    //console.log('caught feedEdit!');
    //Ugh, this should have a better name
    $rootScope.$broadcast('feedEditRequest',args);
  });
  //catch and broadcast entry changes
  $rootScope.$on('entryChange', function(event, args){
    //console.log('caught entryChange!');
    $rootScope.$broadcast('entryChanged', args);
  });
  $rootScope.$on('newFeedRequested', function(event,args){
    //console.log('caught newFeedRequested');
    $rootScope.$broadcast('subscriptionsWindow',args);
  });
  $rootScope.$on('feedsChanged', function(event,args){
    //console.log('the feeds are changing');
    $rootScope.$broadcast('refreshFeeds',args);
  });
  $rootScope.$on('commandBarEvent',function(event,args){
    $rootScope.$broadcast('commandBarEvented', args);
  });
});
