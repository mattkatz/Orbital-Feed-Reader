Wprss = Ember.Application.create();
Wprss.cache = Ember.Object.create({
  mouseX: null,
  mouseY: null,
});
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

// #THE FEEDS#
Wprss.feedsController = Em.ArrayController.create({
  content: [],
  onInit: null,
  changeUnreadCount:function(id,delta){
    var feed = this.get('content').findProperty('feed_id',id);
    feed.set('unread_count', +feed.unread_count + delta);
  },
  createFeed: function(feedHash){
    feedHash.is_private = (1==feedHash.is_private);
    var feed = Wprss.Feed.create(feedHash);
    this.pushObject(feed);
  },
  createFeeds: function(feeds){
    Wprss.feedsController.createFeed({feed_url:'',site_url:'',feed_name:'Fresh Entries',feed_id:null,unread_count:'lots', is_private:true});
    feeds.forEach(function(value){
      Wprss.feedsController.createFeed(value);
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
      //move onto next feed?
      Wprss.feedsController.nextUnreadFeed();
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
  saveFeed: function(feed,successFunction,failFunction){
    var data = {
      action: 'wprss_save_feed',
      feed_id: feed.feed_id,
      feed_url : feed.feed_url,
      feed_name: feed.feed_name,
      site_url: feed.site_url,
      is_private: feed.is_private,
      nonce_a_donce:get_url.nonce_a_donce 
    };
    jQuery.ajax(
      get_url.ajaxurl,{
      type: 'POST',
      data: data, 
      dataType:'json',
      success: function(response){
        if(response.updated || response.inserted )// test to see if the feed actually got saved
        {
          if(response.inserted){
            Wprss.feedsController.createFeed(response);
          }
          //this should be agnostic per screen.
          //So whoever calls save feed can have something trigger on yay
          if(successFunction){
            successFunction(response);
          }
        }
        else{
          if(failFunction){
            failFunction(response);
            console.log(response);
          }else{
            //TODO Alert the user?
            console.log(response);
          }
        }
      },
      error: failFunction,

    });

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
  showOpmlImport: function(){
    var dlg = jQuery('#opml-dialog');
    dlg.toggleClass('invisible');
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
    feeds.forEach(function(feed){
      if(Wprss.feedsController.set(feed.feed_id,'unread_count',feed.unread_count)){
        //great!
      }
      else
      {
        Wprss.feedsController.createFeed(feed);
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
  unsubscribe: function(feed_id,successFunc,failFunc){
    var data = {
      action: 'wprss_unsubscribe_feed',
      feed_id: feed_id,
      nonce_a_donce:get_url.nonce_a_donce 
    };
    jQuery.ajax({
      url:get_url.ajaxurl,
      type:'POST',
      data: data, 
      success: function(data){
        if( data.feed_id)//TODO: test to see if the feed actually got deleted
        {
          //remove the feed from the list
          Wprss.feedsController.removeFeed(data.feed_id);
          Wprss.selectedFeedController.set('content',null);
          if(successFunc)
            successFunc();
          
        }
        else{
          console.log(data);
          failFunc();
        }

      },
      error: failFunc,
      dataType:'json',
    });
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

//#THE ENTRIES
Wprss.entriesController = Em.ArrayController.create({
  content: [],
  clearEntries: function(){
    this.set('content', []);
    Wprss.selectedEntryController.clear();
  },
  createEntry: function(entryHash){
    //Don't add the entry if we already have it
    if(this.get('content').findProperty('id',entryHash.id)){return;}
    entryHash.isRead = (1==entryHash.isRead);
    var entry = Wprss.Entry.create(entryHash);
    this.pushObject(entry);
  },
  createEntries: function(jsonEntries){
    var entries = jsonEntries;
    entries.forEach(function(entry){
      Wprss.entriesController.createEntry(entry);
    });
  },
  nextEntry: function(){
    this.selectNextEntry(Wprss.entriesController.get('content'));
  },
  previousEntry: function(){
    this.selectNextEntry(Wprss.entriesController.get('content').toArray().reverse());
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
      Ember.run.next(this,function(){
        scrollToEntry(Wprss.entriesController.get('content')[0]);
      });
      //Set the feed as not loading
      Wprss.feedsController.set(id,'is_loading',false);
    },'json');
  },
  //this is the ugly function for the two pretty ones 
  selectNextEntry: function(array){
    var currentItem = Wprss.selectedEntryController.get('content');
    //if there is no item selected, select the first one.
    if (null == currentItem ){
      currentItem = array.get('firstObject');

    }else{
      //if there is an item selected, select the next one.
      var idx = array.indexOf(currentItem);
      var bottom = false;
      if(++idx == array.length){
        //currentItem = array.get('firstObject');
        //if we have reached the end, we should just wait for the endless loader
        //to handle this.  
        bottom = true;
      }
      else{
        currentItem = array.get(idx);
      }
    }
    Wprss.selectedEntryController.set('content',currentItem);
    //scroll to this element.
    scrollToEntry(currentItem,bottom);
    Wprss.entriesController.setEntryIsRead(currentItem.id,true);

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
      jQuery('#'+entry.entry_id+">.entry_isloading").hide();
      if(response.updated >0){
        entry.set('isRead', isRead);
        Wprss.feedsController.changeUnreadCount(entry.feed_id,isRead?-1:1);
      }
      else{
        console.log("update of " + response.updated);
        //TODO: alert the user?
      }
    },'json');

  },
  toggleEntryRead: function(id){
    var entry = this.content.findProperty('id',id);
    var unreadStatus = entry.get('isRead');
    this.setEntryIsRead(id,!unreadStatus);

  },
  
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
  unsubscribe: function(successFunc,failFunc){
    Wprss.feedsController.unsubscribe(this.get('content').feed_id,
                                      successFunc,failFunc);
  },
  saveFeed: function(successFunction, failFunction){
    var feed = Wprss.selectedFeedController.get('content');
    Wprss.feedsController.saveFeed(feed,successFunction, failFunction);
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
  content: null,
  clear: function(){
    Wprss.selectedEntryController.set('content',null);
  },

});

//view for looking at a single feed
Wprss.FeedView = Em.View.extend({
  
  contentBinding: 'Wprss.selectedFeedController.content',
  save: function(event){
    console.log('save');
    this.toggleHideButtonsAndSpinner();
    Wprss.selectedFeedController.saveFeed(this.toggleHideButtonsAndSpinner,this.failed);
  },
  unsubscribe: function(event){
    //the event object is currently the button that got pushed.
    //event.set('disabled',true);
    //this seems to be the view itself
    console.log('unsub');
    this.toggleHideButtonsAndSpinner();
    Wprss.selectedFeedController.unsubscribe(null,this.failed);
  },
  failed: function()
  {
    alert("Sorry - there was a problem unsubscribing. Give it another shot." +
          "If this continues, please contact me and let me know so I can troubleshoot. - Matt");
    jQuery('#feedViewSpinner').fadeToggle();
    jQuery('#feedViewUnsubscribeButton > button').fadeToggle()
    jQuery('#feedViewSaveButton > button').fadeToggle()
  },
  toggleHideButtonsAndSpinner: function(){
    jQuery('#feedViewSpinner').fadeToggle();
    jQuery('#feedViewUnsubscribeButton > button').fadeToggle()
    jQuery('#feedViewSaveButton > button').fadeToggle()

  },
});

Wprss.EntriesView = Em.View.extend({
  templateName: 'entry',
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
  didInsertElement: function(){
    this._super();
    var viewElem = this;
    this.$().waypoint( function(evt, direction){
        if(direction == 'down'){
          //var active = jQuery(this);
          var content = viewElem.get('content');
          Wprss.entriesController.setEntryIsRead(content.id,true);
        }
      },
      {
        context: '#wprss-content',
        onlyOnScroll: true,
        //offset: 'bottom-in-view',
        //offset: '50%',
        offset: function(){
          var y = Wprss.cache.mouseY;
          return y - jQuery(this).outerHeight();

        }
        //offset: function(){
        //  var offs = jQuery.waypoints('viewportHeight') - jQuery(this).outerHeight();
        //  console.log('offs: ' + offs);
        //  return offs;
        //},
        
      }
    );
  },

  classNameBindings:['isCurrent', 'content.isRead']
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
        var feed  =  Wprss.Feed.create(
          { feed_url: response.orig_url, 
            site_url: response.site_url, 
            feed_id: null, 
            feed_name: response.feed_name,
            unread_count:0,
            is_private:false
          });
        Wprss.feedFinder.set('feedCandidate',feed);
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
Wprss.FeedsForm = Em.View.extend({
  tagName: 'form',
  urlField: null,
  feedCandidate:null,
  possibleFields:null,
  showHelp: false,
  submit: function(event){
    event.preventDefault();
    //actually begin the submission
    this.findFeed();
  },
  resetDisplay: function(){
    this.set('feedCandidate',null);
    this.set('possibleFeeds',null);
    this.set('showHelp', false);
    

  },
  saveFeed: function(){
    var view = this;
    Wprss.feedsController.saveFeed(this.get('feedCandidate'),function(){
      view.dismiss();
    },null);
  },
  
  dismiss: function(){
    var view = this;
    view.resetDisplay();
    view.urlField.set('value',null);
    jQuery('#subscribe-window').toggleClass('invisible');
  },
  findFeed: function(evt){
    var view = this;
    view.resetDisplay();
    
    // First get the feed url or site url from the link
    var url = this.getPath('urlField.value');
    if(evt){
      url = evt.context;
    }
    //then ask the backend to validate the feed details
    var data = {
      action: 'wprss_find_feed',
      url: url,
      nonce_a_donce:get_url.nonce_a_donce 
    };
    jQuery.get(get_url.ajaxurl, data, function(response){
      //if this was a feed, let's make it saveable!
      if("feed" == response.url_type){
        var feed  =  Wprss.Feed.create(
          { feed_url: response.orig_url, 
            site_url: response.site_url, 
            feed_id: null, 
            feed_name: response.feed_name,
            unread_count:0,
            is_private:false
          });
        view.set('feedCandidate', feed);
        
      }
      else if("html" == response.url_type){
        if(null == response.feeds){
          //No feeds discovered!
          //TODO Tell them no feeds found
          //TODO Tell them how to discover more feeds
          view.set('showHelp',true);
          //TODO Tell them what rss icons look like
          
          console.log(response);

        }
        //if this was a page, let the user choose feeds and then save them.
        else if(1 < response.feeds.length ){
          
          console.log(view);
          view.set('feedCandidate', null);
          view.set('possibleFeeds', response.feeds);
          console.log(view.possibleFeeds);
        }else if( 1==response.feeds.length )
        {
          view.urlField.set('value',response.feeds[0].url);
          view.findFeed();
        }
        else{
          //No feeds discovered!
          //TODO Tell them no feeds found
          //TODO Tell them how to discover more feeds
          view.set('showHelp',true);
          //TODO Tell them what rss icons look like
          
          console.log(response);
        }
      }
      else{
        //we didn't get a feed response back!
        //TODO run and tell that
      }
    },"json");
  },

});
Em.Handlebars.registerHelper('checkable', function(path,options){
  options.hash.valueBinding = path;
  return Em.Handlebars.helpers.view.call(this, Wprss.ReadView,options);
});


function scrollToEntry(currentItem, bottom){

    var body = jQuery('html');
    var adminbar = jQuery('#wpadminbar');
    var commandbar = jQuery('#commandbar');
    var row = jQuery('#'+currentItem.feed_id + "_" +currentItem.id);
    if(null === row.offset()){
      console.log('row.offset() was null');
      return;
    }
    //position is the offset from the parent scrollable element
    var scrollAmount = row.position().top;
    if(bottom){
      console.log('trying to get to the bottom');
      scrollAmount += row.height();
    }


    var currentScroll = jQuery('#wprss-content').scrollTop();
    
    jQuery('#wprss-content').animate({ scrollTop: scrollAmount + currentScroll -  commandbar.height()}, 200); 
}
//Set everything up after page load
jQuery(document).ready(function($){
  function setContentHeight(id,height){
    $(id).css({'height':(($(window).height())-height)+'px'});
  }
  $(window).resize(function(){
    setContentHeight('#wprss-content',28+22);
    setContentHeight('#wprss-feedlist',28);
    $('#wprss-content').css({'width':(($('#wprss-container').width() - 190 )+'px')});
    //setContentHeight('#feeds', 28+63);
    $('#feeds').css({'height':(($('#wprss-feedlist').height()-($('#feed-head').height()+ 10 )) +'px')});
  });
  $(window).resize();
});
