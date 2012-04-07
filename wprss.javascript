
Wprss = Ember.Application.create();
Wprss.Feed = Em.Object.extend({
  feed_url : null,
  feed_name: null,
  feed_id:null,
  site_url: null,
  unread_count:null,
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
  showFeed:function(){
    console.log('in showfeed');
    //show the add feed window
    var dlg = jQuery('#subscribe-window');
    console.log(dlg);
    
    dlg.toggleClass('invisible');
    

  },
  subscribeFeedCommit: function(){
    //get the feed url 
    //validate it
    //add the feed
    //close the dialog
    

  },
  addFeed:function(){
    //post a feed 

  },
  changeUnreadCount:function(id,delta){
    var feed = this.get('content').findProperty('feed_id',id);
    console.log(feed.feed_name + "("+feed.unread_count+")");
    feed.set('unread_count', +feed.unread_count + delta);
    console.log(feed.unread_count);
  },
  //a list of all unread feeds
  unreadFeeds: function(){
    return this.content.filter(function(item,index,self){
      if(item.unread_count > 0){ return true;}
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
  //does the actual work of finding an unread feed in an array
  findUnreadFeed: function(array){
    return array.find(function(item,index,self){
      if(item.unread_count > 0){return true;}
    });
  },

  //select a feed
  //expects the feed to not be null!
  selectFeed: function(feed){
    if(null == feed){return;}
    Wprss.selectedFeedController.select(feed);
  },
  //Select the previous unread feed
  previousUnreadFeed:function(id){
    this.unreadFeedNextSelect(this.get('content').toArray().reverse());
  },
  //Select the next  unread feed
  nextUnreadFeed: function(){
    this.unreadFeedNextSelect(this.get('content'));
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
  markAsRead: function(id){
    //call the markfeedread backend
    var data = {
      action: 'wprss_mark_items_read',
      nonce_a_donce:get_url.nonce_a_donce ,
      feed_id: id,
      
    };
    jQuery.post(get_url.ajaxurl, data, function(response){
      //TODO: put in error checks for bad responses, errors,etc.
      //update the fiedview to show this item has no read count
      var resp = JSON.parse(response);
      Wprss.feedsController.changeUnreadCount(resp.feed_id,-1* resp.updated);
      //move onto next feed?
    });

  },
  unsubscribe: function(feed_id){
    var data = {
      action: 'wprss_unsubscribe_feed',
      feed_id: feed_id,
      nonce_a_donce:get_url.nonce_a_donce 
    };
    jQuery.post(get_url.ajaxurl,data, function(data){
      if(true)//test to see if the feed actually got deleted
      {
         //remove the feed from the list
         console.log(data);
         var feed = Wprss.feedsController.filterProperty('feed_id',data.feed_id)
      //.forEach(Wprss.feedsController.removeObject);
      console.log(feed);

      }
      Wprss.selectedFeedController.set('content',null);
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
  description: null,
  entryID: function(){
    return this.get('feed_id')+"_"+this.get('id');

  }.property(),
});
Wprss.entriesController = Em.ArrayController.create({
  content: [],
  createEntry: function(feed,ref_id,head, url,by,read,mark,des){
    var entry = Wprss.Entry.create({
    feed_id: feed, 
    id: ref_id,
    title:head,
    link:url,
    author:by,
    isRead:read!='0',
    marked:mark!='0',
    description:des});
    this.pushObject(entry);
  },
  createEntries: function(jsonEntries){
    var entries = JSON.parse(jsonEntries);
    entries.forEach(function(entry){
      Wprss.entriesController.createEntry(entry.feed_id,entry.id,entry.title, entry.link,entry.author,entry.isRead,entry.marked,entry.content);
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
      currentItem = array.get(++idx);
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
    
    var data = {
      action: 'wprss_mark_item_read',
      unread_status: isRead,
      entry_id: id,
      nonce_a_donce:get_url.nonce_a_donce 
    };
    jQuery.post(get_url.ajaxurl,data, function(response){
      response = JSON.parse(response);
      if(response.updated >0){
        console.log("updating");
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
    jQuery.get(get_url.ajaxurl, data, function(response){
      //alert(response);
      Wprss.entriesController.clearEntries();
      Wprss.entriesController.createEntries(response);
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
  select:function(feed){
    this.set('content',feed);
    this.onSelect(feed.feed_id);
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
    //alert(content.feed_id);
    //Wprss.selectedFeedController.set('content', content);
    //Wprss.entriesController.selectFeed(content.feed_id);
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
  classNameBindings:['isCurrent']
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
    console.log(content);
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
Wprss.AddFeedView = Em.TextField.extend({
  focusOut: function(){
    console.log('out');
  },
  insertNewLine: function(){
    console.log('rah');
  }
});
Em.Handlebars.registerHelper('checkable', function(path,options){
  options.hash.valueBinding = path;
  return Em.Handlebars.helpers.view.call(this, Wprss.ReadView,options);
});
Wprss.CommandView = Em.View.extend({
  templateName:'commandItem',
  //can we set click after creating a view?

});
Wprss.SubscribeView = Em.View.extend({
  //we need a url input, an add button.
  urlField: null,
  //later we will need things like tags and privacy and such

});





function scrollToEntry(currentItem){

    var body = jQuery('html');
    var adminbar = jQuery('#wpadminbar');
    var commandbar = jQuery('#commandbar');
    //console.log(window.scrollTop());
    //TODO why is entryID coming up undefined in this context?
    //var row = jQuery('#'+currentItem.entryID);
    console.log('current entry id: ' + currentItem.feed_id + "_" +currentItem.id);
    var row = jQuery('#'+currentItem.feed_id + "_" +currentItem.id);
    console.log('current row: ' + row.offset().top);
    //body.scrollTop(row.offset().top - adminbar.height());
    
    jQuery('html').animate({
      scrollTop: row.offset().top - adminbar.height() - commandbar.height()}, 200);

}



