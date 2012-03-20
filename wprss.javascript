//Set everything up after page load
jQuery(document).ready(function($){
  var data = {
    action: 'wprss_get_feeds',
    nonce_a_donce:get_url.nonce_a_donce 
    
  };
  $.get(get_url.ajaxurl, data, function(response){
    //TODO: put in error checks for bad responses, errors,etc.
    Wprss.feedsController.createFeeds(response);
  });

  //TODO this should just be fed into the page on initial load
  data.action='wprss_get_entries';
  $.get(get_url.ajaxurl, data, function(response){
    //alert(response);
    Wprss.entriesController.createEntries(response);
  });

  setupKeys();
  
});


Wprss = Ember.Application.create();
Wprss.Feed = Em.Object.extend({
  feed_url : null,
  feed_name: null,
  feed_id:null,
  site_url: null,
  unread_count:null,
});

Wprss.feedsController = Em.ArrayProxy.create({
  content: [],
  createFeed: function(feed,domain,name,id,unread){
    var feed = Wprss.Feed.create({ feed_url: feed, site_url:domain, feed_id:id,feed_name:name,unread_count:unread});
    this.pushObject(feed);
  },
  createFeeds: function(jsonFeeds){
    var feeds = JSON.parse(jsonFeeds);
    feeds.forEach(function(value){
      Wprss.feedsController.createFeed(value.feed_url,value.site_url,value.feed_name,value.id, value.unread_count);
    });
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
  //TODO this and nextunreadfeed should be made DRY
  previousUnreadFeed:function(id){
    var current_feed = Wprss.selectedFeedController.content;
    if(null == current_feed){
      //no feed selected?  Let's choose the last unread feed.
      this.selectFeed(this.lastUnreadFeed());
      return;
    }
    var current_index;
    var next_feed = this.get('content').toArray().reverse().find(function(item,index,self){
      console.log(item.feed_name);
      if(item.feed_id== current_feed.feed_id ){
        current_index = index;
      }
      if(current_index < index && item.unread_count > 0){
        return true;
      }
    }, current_feed);
    if(null == next_feed){
      //we should just cycle back around to the last unread
      next_feed= this.lastUnreadFeed();
    }
    this.selectFeed(next_feed);
  },
  //We should push the index into this function.
  nextUnreadFeed: function(){
    this.unreadFeedNextSelect(this.get('content'));
    /*
    var current_feed = Wprss.selectedFeedController.content;
    if(null == current_feed){
      //no feed selected?  Let's choose the first unread feed.
      console.log('no feed selected');
      this.selectFeed(this.firstUnreadFeed());
      return;
    }
    var current_index;
    var next_feed = this.content.find(function(item,index,self){
      if(item.feed_id== current_feed.feed_id ){
        current_index = index;
      }
      if(current_index < index && item.unread_count > 0){
        return true;
      }
    }, current_feed);
    if(null == next_feed){
      //we should just cycle back around to the first unread
      next_feed= this.firstUnreadFeed();
    }
    this.selectFeed(next_feed);
  */
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
      next_feed= findUnreadFeed(array);
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
      //callback to do what when done?
      //update the fiedview to show this item has no read count?
      //move onto next feed?
    });

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
Wprss.entriesController = Em.ArrayProxy.create({
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
  select:function(feed){
    this.set('content',feed);
    Wprss.entriesController.selectFeed(feed.feed_id);
  },
  
});

Wprss.FeedsView = Em.View.extend({
  //templateName: feedsView,
  click: function(evt){
    var content = this.get('content');
    //alert(content.feed_id);
    Wprss.selectedFeedController.set('content', content);
    Wprss.entriesController.selectFeed(content.feed_id);
    
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
    console.log('current entry id: ' + currentItem.feed_id + "_" +currentItem.id);
    var row = jQuery('#'+currentItem.feed_id + "_" +currentItem.id);
    console.log('current row: ' + row.offset().top);
    //body.scrollTop(row.offset().top - adminbar.height());
    
    jQuery('html').animate({
      scrollTop: row.offset().top - adminbar.height() - commandbar.height()}, 200);

}

function setupKeys(){
  //handle the down arrow keys and j to scroll the next item to top of scren
  key('j,down',function(event,handler){
    var currentItem = Wprss.selectedEntryController.content;
    //if there is no item selected, select the first one.
    if (null == currentItem ){
      console.log('no current item');
      currentItem = Wprss.entriesController.get('firstObject');

    }else{
      //if there is an item selected, select the next one.
      console.log('current item');
      var idx = Wprss.entriesController.content.indexOf(currentItem);
      currentItem = Wprss.entriesController.content.get(++idx);
    }
    Wprss.selectedEntryController.set('content',currentItem);
    //scroll to this element.
    scrollToEntry(currentItem);
    Wprss.entriesController.setEntryIsRead(currentItem.id,true);
  });
  //up and k should scroll the previous item to the top of the screen
  key('k,up',function(event,handler){
      
    var currentItem = Wprss.selectedEntryController.content;
    //if there is no item selected, select the first one.
    if (null == currentItem ){
      console.log('no current item');
      currentItem = Wprss.entriesController.get('firstObject');

    }else{
      //if there is an item selected, select the next one.
      console.log('current item');
      var idx = Wprss.entriesController.content.indexOf(currentItem);
      currentItem = Wprss.entriesController.content.get(--idx);
    }
    Wprss.selectedEntryController.set('content',currentItem);
    scrollToEntry(currentItem);
    Wprss.entriesController.setEntryIsRead(currentItem.id,true);

  });
  //h should go to previous feed
  key('h,left',function(event,handler){
    Wprss.feedsController.previousUnreadFeed();
/*
    var currentFeed = Wprss.selectedFeedController.content;
    if(null == currentFeed){
      currentFeed = Wprss.feedsController.get('firstObject');
    }else{
      var idx = Wprss.feedsController.content.indexOf(currentFeed);
      currentFeed = Wprss.feedsController.content.get(--idx);
      //TODO make this loop instead
      if(null==currentFeed)
        return;

    }
    Wprss.selectedFeedController.set('content',currentFeed);
    Wprss.entriesController.selectFeed(currentFeed.feed_id);
*/
    
  });
  //l should go to next feed
  key('l,right',function(event,handler){
    Wprss.feedsController.nextUnreadFeed();
    
    /*
    var currentFeed = Wprss.selectedFeedController.content;
    if(null == currentFeed){
      currentFeed = Wprss.feedsController.get('firstObject');
    }else{
      var idx = Wprss.feedsController.content.indexOf(currentFeed);
      currentFeed = Wprss.feedsController.content.get(++idx);
      //TODO make this loop instead
      if(null==currentFeed)
        return;
    }
    Wprss.selectedFeedController.set('content',currentFeed);
    Wprss.entriesController.selectFeed(currentFeed.feed_id);
    */
    
  });
  //u should toggle the current item's read status
  key('u',function(event,handler){
    var currentItem = Wprss.selectedEntryController.content;
    if(null == currentItem)
      return;
    Wprss.entriesController.toggleEntryRead(currentItem.id);

  });

}



