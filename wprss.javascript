
Wprss = Ember.Application.create();
Wprss.Feed = Em.Object.extend({
  feed_url : null,
  feed_name: null,
  feed_id:null,
  site_url: null,
  unread_count:null,
  is_private: null,
  has_unread: function(){
    return unread_count > 0;

  }.property(),
  is_loading: false,
});

Wprss.feedsController = Em.ArrayController.create({
  content: [],
  changeUnreadCount:function(id,delta){
    var feed = this.get('content').findProperty('feed_id',id);
    //console.log(feed.feed_name + "("+feed.unread_count+")");
    feed.set('unread_count', +feed.unread_count + delta);
    //console.log(feed.unread_count);
  },
  createFeed: function(feed,domain,name,id,unread,priv){
    var feed = Wprss.Feed.create({ feed_url: feed, site_url:domain, feed_id:id,feed_name:name,unread_count:unread,is_private:priv==1});
    this.pushObject(feed);
  },
  createFeeds: function(feeds){
    //var feeds = JSON.parse(jsonFeeds);
    Wprss.feedsController.createFeed('','','Fresh Entries',null,'lots');
    feeds.forEach(function(value){
      Wprss.feedsController.createFeed(value.feed_url,value.site_url,value.feed_name,value.id, value.unread_count,value.private);
    });
  },
  //does the actual work of finding an unread feed in an array
  findUnreadFeed: function(array){
    return array.find(function(item,index,self){
      if(item.unread_count > 0){return true;}
    });
  },
  //for convenience, a function for the fist unread feed;
  firstUnreadFeed: function(){
    return this.findUnreadFeed(Wprss.feedsController.get('content'));
  },
  //a function for the last unread feed
  lastUnreadFeed: function(){
    return this.findUnreadFeed(Wprss.feedsController.get('content').toArray().reverse());
  },
  markAsRead: function(id){
    //call the markfeedread backend
    var data = {
      action: 'wprss_mark_items_read',
      nonce_a_donce:get_url.nonce_a_donce ,
      feed_id: id,
      
    };
    jQuery.post(get_url.ajaxurl, data, function(response){
      //TODO: put in error checks for bad responses, errors,etc.
      var resp = JSON.parse(response);
      Wprss.feedsController.changeUnreadCount(resp.feed_id,-1* resp.updated);
      //TODO move onto next feed?
    });

  },
  //Select the next  unread feed
  nextUnreadFeed: function(){
    this.unreadFeedNextSelect(this.get('content'));
  },
  //Select the previous unread feed
  previousUnreadFeed:function(id){
    this.unreadFeedNextSelect(this.get('content').toArray().reverse());
  },
  refreshFeeds: function(unreadOnly){
    var data = {
      action: 'wprss_get_feeds',
      nonce_a_donce:get_url.nonce_a_donce 
      
    };
    jQuery.get(get_url.ajaxurl, data, function(response){
      //TODO: put in error checks for bad responses, errors,etc.
      Wprss.feedsController.createFeeds(response);
    },'json');
  },
  removeFeed: function(feed_id){
    var feed = this.findProperty('feed_id',feed_id);
    this.removeObject(feed);
  },
  saveFeed: function(feed){
    var data = {
      action: 'wprss_save_feed',
      feed_id: feed.feed_id,
      feed_url : feed.feed_url,
      feed_name: feed.feed_name,
      site_url: feed.site_url,
      is_private: feed.is_private,
      nonce_a_donce:get_url.nonce_a_donce 
    };
    jQuery.post(get_url.ajaxurl,data, function(response){
      if(response.updated || response.inserted )// test to see if the feed actually got saved
      {
        //indicate somehow?
        //TODO this should be agnostic per screen.
        //the main window should update the list of feeds.
        //and close the subscribe window
        //the feed management window should also update the feed list
        jQuery('#subscribe-window').toggleClass('invisible');
        
      }
      else{
        //TODO Alert the user?
        console.log(response.updated);
      }
    },'json');

  },
  //select a feed
  //expects the feed to not be null!
  selectFeed: function(feed){
    if(null == feed){return;}
    Wprss.selectedFeedController.select(feed);
  },
  set: function(id,property,value){
    var content = Wprss.feedsController.get('content');
    var feed = content.findProperty('feed_id',id);
    if(feed){
      feed.set(property,value);
      return true;
    }
    else{
      return false;
    }

  },
  showFeed: function(){
    //show the add feed window
    var dlg = jQuery('#subscribe-window');
    dlg.toggleClass('invisible');
  },
  //a list of all unread feeds
  unreadFeeds: function(){
    return this.content.filter(function(item,index,self){
      if(item.unread_count > 0){ return true;}
    });
  }.property(),
  updateFeeds: function(feeds){
    var content = Wprss.feedsController.get('content');
    feeds.forEach(function(value){
      if(Wprss.feedsController.set(value.id,'unread_count',value.unread_count)){
        //great!
      }
      else
      {
        Wprss.feedsController.createFeed(value.feed_url,value.site_url,value.feed_name,value.id, value.unread_count,value.private);
      }
    });
  },

  //this ugly function is the guts of the previous nice ones
  unreadFeedNextSelect: function(array){
    var current_feed = Wprss.selectedFeedController.content;
    if(null == current_feed){
      //no feed selected?  Let's choose the first unread feed.
      console.log('no feed selected');
      this.selectFeed(this.findUnreadFeed(array));
      return;
    }
    var current_index;
    var next_feed = array.find(function(item,index,self){
      if(item.feed_id== current_feed.feed_id ){
        current_index = index;
      }
      if(current_index < index && item.unread_count > 0){
        return true;
      }
    }, current_feed);
    if(null == next_feed){
      //we should just cycle back around to the first unread
      next_feed= this.findUnreadFeed(array);
    }
    this.selectFeed(next_feed);
  },
  unsubscribe: function(feed_id){
    var data = {
      action: 'wprss_unsubscribe_feed',
      feed_id: feed_id,
      nonce_a_donce:get_url.nonce_a_donce 
    };
    jQuery.post(get_url.ajaxurl,data, function(data){
      if(data.result)//TODO: test to see if the feed actually got deleted
      {
        //remove the feed from the list
        Wprss.feedsController.removeFeed(data.feed_id);
        Wprss.selectedFeedController.set('content',null);
      }
    },'json');
  },
  update: function(id){
    var data = {
      action: 'wprss_update_feed',
      nonce_a_donce: get_url.nonce_a_donce,
      feed_id: id,
    };
    jQuery.post(get_url.ajaxurl,data,function(response){
      Wprss.feedsController.changeUnreadCount(response.feed_id, response.updated);
      feed = Wprss.feedsController.get('content').findProperty('feed_id',response.feed_id);
      Wprss.feedsController.selectFeed(feed);
    },'json');
  },
});
Wprss.Entry = Em.Object.extend({
  feed_id: null,
  id: null,
  title: null,
  link: null,
  author:null,
  isRead:null,
  marked:null,
  content:null,
  entered:null,
  feed_name: function(){
    return Wprss.feedsController.get('content').findProperty('feed_id',this.get('feed_id')).get('feed_name');
  }.property(),

  entryID: function(){
    return this.get('feed_id')+"_"+this.get('id');

  }.property(),
});
Wprss.entriesController = Em.ArrayController.create({
  content: [],
  createEntry: function(entryHash){
    //Don't add the entry if we already have it
    if(this.get('content').findProperty('id',entryHash.id)){return;}
    entryHash.isRead = (1==entryHash.isRead);
    var entry = Wprss.Entry.create(entryHash);
    this.pushObject(entry);
  },
  createEntries: function(jsonEntries){
    var entries = JSON.parse(jsonEntries);
    entries.forEach(function(entry){
      Wprss.entriesController.createEntry(entry);
    });
  },
  clearEntries: function(){
    this.set('content', []);
  },
  toggleEntryRead: function(id){
    var entry = this.content.findProperty('id',id);
    var unreadStatus = entry.get('isRead');
    this.setEntryIsRead(id,!unreadStatus);

  },
  //this is the ugly function for the two pretty ones below
  selectNextEntry: function(array){
    var currentItem = Wprss.selectedEntryController.get('content');
    //if there is no item selected, select the first one.
    if (null == currentItem ){
      console.log('no current item');
      currentItem = array.get('firstObject');

    }else{
      //if there is an item selected, select the next one.
      console.log('current item');
      var idx = array.indexOf(currentItem);
      if(++idx == array.length){
        currentItem = array.get('firstObject');
      }
      else{
        currentItem = array.get(idx);
      }
    }
    Wprss.selectedEntryController.set('content',currentItem);
    //scroll to this element.
    scrollToEntry(currentItem);
    Wprss.entriesController.setEntryIsRead(currentItem.id,true);

  },
  nextEntry: function(){
    this.selectNextEntry(Wprss.entriesController.get('content'));
  },
  previousEntry: function(){
    this.selectNextEntry(Wprss.entriesController.get('content').toArray().reverse());
  },
  setEntryIsRead: function(id,isRead){
    var entry = this.content.findProperty('id',id);
    jQuery('#'+entry.entry_id+"<.entry_isloading").show();
    
    var data = {
      action: 'wprss_mark_item_read',
      read_status: isRead,
      entry_id: id,
      nonce_a_donce:get_url.nonce_a_donce 
    };
    jQuery.post(get_url.ajaxurl,data, function(response){
      response = JSON.parse(response);
      jQuery('#'+entry.entry_id+">.entry_isloading").hide();
      if(response.updated >0){
        //console.log("updating");
        entry.set('isRead', isRead);
        Wprss.feedsController.changeUnreadCount(entry.feed_id,isRead?-1:1);
      }
      else{
        console.log("update of " + response.updated);
        //TODO: alert the user?
      }
    });

  },
  
  selectFeed: function(id,show_read){
    show_read = show_read || 0;
    
    var data = {
      action: 'wprss_get_entries',
      feed_id: id,
      show_read: show_read,
      nonce_a_donce:get_url.nonce_a_donce 
    };
    //Set this feed as loading.
    Wprss.feedsController.set(id,'is_loading',true);
    
    jQuery.get(get_url.ajaxurl, data, function(response){
      //alert(response);
      Wprss.entriesController.clearEntries();
      Wprss.entriesController.createEntries(response);
      scrollToEntry(Wprss.entriesController.get('content')[0]);
      //Set the feed as not loading
      Wprss.feedsController.set(id,'is_loading',false);
    });
  }
});
Wprss.selectedFeedController = Em.Object.create({
  content: null,
  showRead: function(){
    Wprss.entriesController.selectFeed(this.get('content').feed_id,1);
  },
  markAsRead: function(){
    id = this.get('content').feed_id;
    Wprss.feedsController.markAsRead(id);
    //should change this to show next available feed with unread items
    Wprss.entriesController.selectFeed(id);
  },
  unsubscribe: function(){
    Wprss.feedsController.unsubscribe(this.get('content').feed_id);
  },
  saveFeed: function(){
    Wprss.feedsController.saveFeed(this.get('content'));
  },
  select:function(feed){
    this.set('content',feed);
    this.onSelect(feed);
  },
  update: function(feed){
    Wprss.feedsController.update(this.get('content').feed_id);
  },
  onSelect:function(feed_id){
   //null
   return;
  }


  
});

Wprss.FeedsView = Em.View.extend({
  //templateName: feedsView,
  click: function(evt){
    var content = this.get('content');
    Wprss.selectedFeedController.select(content);
    
  },
  isSelected: function(){
    var selectedItem = Wprss.selectedFeedController.get('content'),
      content = this.get('content');
    if(content === selectedItem){return true;}
  
  }.property('Wprss.selectedFeedController.content'),
  classNameBindings:['isSelected']
});
Wprss.selectedEntryController = Em.Object.create({
  content: null
});

Wprss.FeedView = Em.View.extend({
  contentBinding: 'Wprss.selectedFeedController.content',
});

Wprss.EntriesView = Em.View.extend({
  //templateName: feedsView,
  click: function(evt){
    var content = this.get('content');
    Wprss.selectedEntryController.set('content', content);
    this.toggleRead(content.id);
    
  },
  isCurrent: function(){
    var selectedItem = Wprss.selectedEntryController.get('content'),
      content = this.get('content');
    if(content === selectedItem){return true;}
  
  }.property('Wprss.selectedEntryController.content'),
  toggleRead: function(contentId){
    Wprss.entriesController.toggleEntryRead(contentId);
    return false;
  },
  //classNameBindings:['isCurrent']
});

Wprss.commandController = Em.ArrayController.create({
  content: null,
  addFeed: function(){
    console.log("we should be showing the feed view");
  },

});


Wprss.ReadView = Em.View.extend({
  readStatus: function(){
    if(content.isRead){
      return "Read";
    }else{
      return "Unread";
    }
  }.property(),
  templateName:'read-check',
  click: function(evt){
    console.log("content is "+content);
    var data = {
      action: 'wprss_mark_item_read',
      entry_id: this.content.id,
      unread_status: ! content.isRead,
      nonce_a_donce:get_url.nonce_a_donce 
    };
    jQuery.post(get_url.ajaxurl,data, function(data){
      content.set('isRead',!content.isRead);
    });
  
    return false;
  }

});
Wprss.feedFinder= Em.Object.create({
  url: null,
  possibleFeeds: null,
  feedCandidate: null,
  saveFeed: function(){
    Wprss.feedsController.saveFeed(this.get('feedCandidate'));
  },
  findFeed: function(){
    // First get the feed url or site url from the link
    //TODO: then ask the backend to validate the feed details
    var data = {
      action: 'wprss_find_feed',
      url: this.url,
      nonce_a_donce:get_url.nonce_a_donce 
    };
    jQuery.get(get_url.ajaxurl, data, function(response){
      //alert(response);
      //TODO if this was a feed, let's make it save!
      if("feed" == response.url_type){
        console.log('a feed!');
        console.log(response.orig_url);
        console.log(response.site_url);
        console.log(response.feed_name);
        var feed  =  Wprss.Feed.create(
          { feed_url: response.orig_url, 
            site_url: response.site_url, 
            feed_id: null, 
            feed_name: response.feed_name,
            unread_count:0,
            is_private:false
          });
        console.log("feed " + feed);
        Wprss.feedFinder.set('feedCandidate',feed);
        console.log( "candidate " + Wprss.feedFinder.feedCandidate);


      }
      else{
        //TODO if this was a page, let the user choose feeds and then save them.
        Wprss.feedFinder.set('feedCandidate', null);
        Wprss.feedFinder.set('possibleFeeds', response.feeds);
      }
    },"json");
    
    //TODO: Allow the user to edit the feed details
  },

});
Wprss.AddFeedView = Em.TextField.extend({
  focusOut: function(){
  },
  insertNewLine: function(){
    console.log('rah');
  },

});
Wprss.PossibleFeedView  = Em.View.extend({
  click: function(evt){
    var content = this.get('content');
    //TODO now we pull the feed here and smack it into the feed url etc.
    console.log(content);
    //TODO it would be best if we were pulling the actual feed info bc we could create a feed...  
    //instead we will pull the feed url and then call the click handler on it.
    Wprss.feedFinder.set('url',content.url);
    Wprss.feedFinder.findFeed();


    //clean up the form by erasing the old feedlist
    Wprss.feedFinder.set('possibleFeeds',null);
    
  },
});
Em.Handlebars.registerHelper('checkable', function(path,options){
  options.hash.valueBinding = path;
  return Em.Handlebars.helpers.view.call(this, Wprss.ReadView,options);
});


function scrollToEntry(currentItem){

    var body = jQuery('html');
    var adminbar = jQuery('#wpadminbar');
    var commandbar = jQuery('#commandbar');
    //console.log(window.scrollTop());
    //TODO why is entryID coming up undefined in this context?
    //var row = jQuery('#'+currentItem.entryID);
    //console.log('current entry id: ' + currentItem.feed_id + "_" +currentItem.id);
    var row = jQuery('#'+currentItem.feed_id + "_" +currentItem.id);
    //console.log('current row: ' + row.offset().top);
    //body.scrollTop(row.offset().top - adminbar.height());
    if(null === row.offset()){
      console.log('row.offset() was null');
      return;
    }
    //position is the offset from the parent scrollable element
    var scrollAmount = row.position().top;
    var currentScroll = jQuery('#wprss-content').scrollTop();
    
    jQuery('#wprss-content').animate({ scrollTop: scrollAmount + currentScroll -  commandbar.height()}, 200); 
}
