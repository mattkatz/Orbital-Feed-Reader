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

mainModule.factory('feedService',   function($http){
  /*
   * The Feed Service should be the interface to feeds. 
   *
   * It maintains a list of feeds, a pointer to a current feed, 
   * and ways to refresh the list, select the next feed, mark a feed read
   * or get the entries from a feed.
   */
  // the currently selected feed
  var _selectedFeed = null;
  // the list of feeds;
  var _feeds = [];
  var _tags = [];
  //is this service doing work?
  var _isLoading = false;
  var _sortOrder = "-1";
  var _sortOptions = [
    { sortOrder: "-1",
      sortName: "Newest First",
    },
    { sortOrder: "1",
      sortName: "Oldest First",
    },
  ];
  var _refresh = function refresh(callback){
      console.log('refresh');
      _isLoading = true;
      /*
        var fresh = {
          feed_id:null, //TODO start using neg integers for special feed ids
          feed_name:'All Feeds',
          unread_count:'',//TODO put in actual unread count;
        }
      _feeds.unshift(fresh);
      */
      $http.get(opts.ajaxurl + '?action=orbital_get_feeds')
      .success( function( data ){
        _feeds= data;
        var fresh = {
          feed_id:null, //TODO start using neg integers for special feed ids
          feed_name:'All Feeds',
          unread_count:'',//TODO put in actual unread count;
        }
        _feeds.unshift(fresh);
        _isLoading = false;
        if(callback){
          callback(_feeds);
        }
      });

      //Tags
      _isLoading = true;
      $http.get(opts.ajaxurl + '?action=orbital_get_feed_tags')
      .success( function( data ){
        _tags= _.groupBy(data, "tag"); 
        var keys = _.keys(_tags);
        for ( key in _tags ){
          feeds = _tags[key];
          unread_count = _.reduce(feeds,function( count, feed){
            return count + Number.parseInt(feed.unread_count);},0);
          _tags[key].unread_count = unread_count;
        }
        _isLoading = false;
        if(callback){
          callback(_tags);
        }
      });
      $http.get(opts.ajaxurl + '?action=orbital_get_user_settings')
      .success(function(data){
        console.log(data);
        _sortOrder = data['sort_order'];
      });
    };

  return {
    feeds : function(){
      if(  _feeds.length == 0 && ! _isLoading){
        _refresh();
      }
      return _feeds;
    },
    isLoading : function(){
      return _isLoading;
    },

    tags: function(){
      if(_tags.length == 0 && ! _isLoading ){ _refresh();}
      return _tags; 
    },


    // get the list of feeds from backend, inject a "fresh" feed.
    refresh : _refresh,
    select : function(feed, showRead){
      //Mark this feed as selected
      _feeds.forEach(function(value,index){
        value.isSelected = value.feed_id == feed.feed_id
      });
      //let's cache this for later
      _selectedFeed = feed;

    },
    saveFeed: function(feed, successCallback){
      var data = {
        action: 'orbital_save_feed',
        feed_id: feed.feed_id,
        feed_url: feed.feed_url,
        feed_name: feed.feed_name,
        site_url: feed.site_url,
        is_private: feed.private,
      };
      $http.post(opts.ajaxurl,data)
      .success(function(response){
        if(successCallback){ successCallback(response, data);}
      });

    },
    getFeed: function(feed_id){
      return _.find(_feeds, function(feed){return feed.feed_id == feed_id});
    },
    getFeedName: function(feed_id){
      var feed = _.find(_feeds, function(feed){return feed.feed_id == feed_id});
      if (feed){
        return feed.feed_name;
      }else{
        return null;
      }
    },
    selectedFeed: function(){
      return _selectedFeed;
    },
    sortOrder: function(){
      return _sortOrder;
    },
    sortOptions: function(){
      return _sortOptions;
    },
    saveSort: function(sortOrder, callback){
      var data = {
        action: 'orbital_set_user_settings',
        orbital_settings: {
          sort_order: sortOrder,
        },
      };
      //console.log('And app thinks data is : ' + _sortOrder );
      $http.post(opts.ajaxurl, data)
      .success(function(response){
        //TODO Store the settings somewhere?
        console.log(response);
        if(callback){
          callback();
        }
      });

    },

    changeSortOrder: function( sortOrder){

      console.log(sortOrder);
      //todo post the sort order to the settings.
      
    },
  };

  
});

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
  $rootScope.$on('feedSaved', function(event,args){
    //console.log('the feeds are changing');
    $rootScope.$broadcast('updateFeed',args);
  });
  $rootScope.$on('commandBarEvent',function(event,args){
    $rootScope.$broadcast('commandBarEvented', args);
  });
});
