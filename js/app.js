var mainModule= angular.module('mainModule', ['ngSanitize','infinite-scroll'], function($httpProvider)
{
  // Use x-www-form-urlencoded Content-Type
  // Angular's POST isn't natively undestood by PHP
  // fix via: http://victorblog.com/2012/12/20/make-angularjs-http-service-behave-like-jquery-ajax/
  $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
 
  // Override $http service's default transformRequest
  $httpProvider.defaults.transformRequest = [function(data)
  {
    /**
     * The workhorse; converts an object to x-www-form-urlencoded serialization.
     * @param {Object} obj
     * @return {String}
     */ 
    var param = function(obj)
    {
      var query = '';
      var name, value, fullSubName, subValue, innerObj, i;
      
      for(name in obj)
      {
        value = obj[name];
        
        if(value instanceof Array)
        {
          for(i=0; i<value.length; ++i)
          {
            subValue = value[i];
            fullSubName = name + '[' + i + ']';
            innerObj = {};
            innerObj[fullSubName] = subValue;
            query += param(innerObj) + '&';
          }
        }
        else if(value instanceof Object)
        {
          for(subName in value)
          {
            subValue = value[subName];
            fullSubName = name + '[' + subName + ']';
            innerObj = {};
            innerObj[fullSubName] = subValue;
            query += param(innerObj) + '&';
          }
        }
        else if(value !== undefined && value !== null)
        {
          query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
        }
      }
      return query.length ? query.substr(0, query.length - 1) : query;
    };
    return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
  }];
});

mainModule.run(function($rootScope){
  /* 
   * receive the emitted messages and rebroadcast
   * use distinct event names to prevent browser explosion
   */
  $rootScope.$on('feedSelect',function(event, args){
    console.log('caught feedSelect!');
    $rootScope.$broadcast('feedSelected',args);
  });
  $rootScope.$on('feedEdit',function(event, args){
    console.log('caught feedEdit!');
    //Ugh, this should have a better name
    $rootScope.$broadcast('feedEditRequest',args);
  });
  //catch and broadcast entry changes
  $rootScope.$on('entryChange', function(event, args){
    console.log('caught entryChange!');
    $rootScope.$broadcast('entryChanged', args);
  });
  $rootScope.$on('newFeedRequested', function(event,args){
    console.log('caught newFeedRequested');
    $rootScope.$broadcast('subscriptionsWindow',args);
  });
  $rootScope.$on('feedsChanged', function(event,args){
    console.log('the feeds are changing');
    $rootScope.$broadcast('refreshFeeds',args);
  });
});
