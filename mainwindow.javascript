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
  Wprss.selectedFeedController.onSelect = function(feed_id){
    Wprss.entriesController.selectFeed(feed_id);
  };

  setupKeys();
  
});

function setupKeys(){
  //handle the down arrow keys and j to scroll the next item to top of scren
  key('j,down',function(event,handler){
    Wprss.entriesController.nextEntry();
  });
  //up and k should scroll the previous item to the top of the screen
  key('k,up',function(event,handler){
    Wprss.entriesController.previousEntry();
  });
  //h should go to previous feed
  key('h,left',function(event,handler){
    Wprss.feedsController.previousUnreadFeed();
  });
  //l should go to next feed
  key('l,right',function(event,handler){
    Wprss.feedsController.nextUnreadFeed();
  });
  //u should toggle the current item's read status
  key('u',function(event,handler){
    var currentItem = Wprss.selectedEntryController.content;
    if(null == currentItem)
      return;
    Wprss.entriesController.toggleEntryRead(currentItem.id);
  });
}

