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
      //TODO change this back to a post
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
    selectFeed: function(id){
      var data = {
        action: 'wprss_get_entries',
        feed_id: id,
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
    content: null
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
    //console.log(window.scrollTop());
    //TODO why is entryID coming up undefined in this context?
    //var row = jQuery('#'+currentItem.entryID);
    console.log('current entry id: ' + currentItem.feed_id + "_" +currentItem.id);
    var row = jQuery('#'+currentItem.feed_id + "_" +currentItem.id);
    console.log('current row: ' + row.offset().top);
    //body.scrollTop(row.offset().top - adminbar.height());
    
    jQuery('html').animate({
      scrollTop: row.offset().top - adminbar.height()}, 200);

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

  });
  //h should go to previous feed
  key('h',function(event,handler){
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
    
  });
  //l should go to next feed
  key('l',function(event,handler){
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
    
  });
  //u should toggle the current item's read status
  key('u',function(event,handler){
    var currentItem = Wprss.selectedEntryController.content;
    if(null == currentItem)
      return;
    Wprss.entriesController.toggleEntryRead(currentItem.id);

  });

}



