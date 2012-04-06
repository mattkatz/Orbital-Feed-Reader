
Wprss = Ember.Application.create();
Wprss.Feed = Em.Object.extend({
  feed_url : null,
  feed_name: null,
  feed_id:null,
  site_url: null,
  unread_count:null,
  is_private:false,
});
Wprss.feedsController = Em.ArrayController.create({
  content: [],
  createFeed: function(feed,domain,name,id,unread,priv){
    var feed = Wprss.Feed.create({ feed_url: feed, site_url:domain, feed_id:id,feed_name:name,unread_count:unread,is_private:priv==1});
    this.pushObject(feed);
  },
  
  createFeeds: function(jsonFeeds){
    var feeds = JSON.parse(jsonFeeds);
    feeds.forEach(function(value){
      Wprss.feedsController.createFeed(value.feed_url,value.site_url,value.feed_name,value.id, value.unread_count,value.private);
    });
  },
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
Wprss.selectedFeedController = Em.Object.create({
  content: null,
  select:function(feed){
    this.set('content',feed);
    //Wprss.entriesController.selectFeed(feed.feed_id);
  },
});
Wprss.FeedView = Em.View.extend({
  contentBinding: 'Wprss.selectedFeedController.content',
});
