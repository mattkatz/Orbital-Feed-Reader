jQuery(document).ready(function($){
 // alert('begin');
  var data = {
    action: 'wprss_get_feeds',
    nonce_a_donce:get_url.nonce_a_donce 
    
  };
  $.get(get_url.ajaxurl, data, function(response){
    //TODO: put in error checks for bad responses, errors,etc.
    Wprss.feedsController.createFeeds(response);
  });

  data.action='wprss_get_entries';
  $.get(get_url.ajaxurl, data, function(response){
    //alert(response);
    Wprss.entriesController.createEntries(response);
  });
  


  
});

  Wprss = Ember.Application.create();
  Wprss.Feed = Em.Object.extend({
    feed_url : null,
    feed_name: null,
    feed_id:null,
    site_url: null
    

  });

  Wprss.feedsController = Em.ArrayProxy.create({
    content: [],
    createFeed: function(feed,domain,name,id){
      var feed = Wprss.Feed.create({ feed_url: feed, site_url:domain, feed_id:id,feed_name:name});
      this.pushObject(feed);
    },
    createFeeds: function(jsonFeeds){
      var feeds = JSON.parse(jsonFeeds);
      feeds.forEach(function(value){
        Wprss.feedsController.createFeed(value.feed_url,value.site_url,value.feed_name,value.id);
      });
    }
    

  });
  Wprss.Entry = Em.Object.extend({
    feed_id: null,
    title: null,
    link: null,
    description: null
  });
  Wprss.entriesController = Em.ArrayProxy.create({
    content: [],
    createEntry: function(feed,head, url,des){
      var entry = Wprss.Entry.create({
      feed_id: feed, 
      title:head,
      link:url,
      description:des});
      this.pushObject(entry);
    },
    createEntries: function(jsonEntries){
      var entries = JSON.parse(jsonEntries);
      entries.forEach(function(entry){
        Wprss.entriesController.createEntry(entry.feed_id,entry.title, entry.link,entry.content);
      });
    }

  });
  Wprss.selectedFeedController = Em.Object.create({
    content: null
  });

  Wprss.FeedsView = Em.View.extend({
    //templateName: feedsView,
    click: function(evt){
      var content = this.get('content');
      //alert(content.feed_id);
      Wprss.selectedFeedController.set('content', content);
    },
    isSelected: function(){
      var selectedItem = Wprss.selectedFeedController.get('content'),
        content = this.get('content');
      if(content === selectedItem){return true;}
    
    }.property('Wprss.selectedFeedController.content'),
    classNameBindings:['isSelected']
  });



