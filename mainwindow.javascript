//Set everything up after page load
jQuery(document).ready(function($){
  //dynamically set the height of the content to the window
  function setContentHeight(id,height){
    $(id).css({'height':(($(window).height())-height)+'px'});
  }
  $(window).resize(function(){
    setContentHeight('#wprss-content',28+22);
    setContentHeight('#wprss-feedlist',28);
    $('#wprss-content').css({'width':(($('#wprss-container').width() - 200 )+'px')});
  });
  setContentHeight('#wprss-content',28+22);
  setContentHeight('#wprss-feedlist',28);
  $('#wprss-content').css({'width':(($('#wprss-container').width() - 200 )+'px')});
  //TODO This should be just fed in on page load
  Wprss.feedsController.refreshFeeds(true);

  //TODO this should just be fed into the page on initial load
  var data = {
    action: 'wprss_get_entries',
    show_read: 0,
    nonce_a_donce:get_url.nonce_a_donce 
    
  };
  $.get(get_url.ajaxurl, data, function(response){
    //alert(response);
    Wprss.entriesController.createEntries(response);
  });
  Wprss.selectedFeedController.onSelect = function(feed){
    Wprss.entriesController.selectFeed(feed.feed_id, feed.unread_count== 0?1:0);
  };
  setupKeys();
  feedTimer();
});

function feedTimer(){
    setTimeout(function(){  
      var data = {
        action: 'wprss_get_feeds',
        nonce_a_donce:get_url.nonce_a_donce 
        
      };
      jQuery.get(get_url.ajaxurl, data, function(response){
        //TODO: put in error checks for bad responses, errors,etc.
        Wprss.feedsController.updateFeeds(response);
        feedTimer();
      },'json');
    }, 60000);
}

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
  //o should open the original article
  key('o',function(event,handler){
    var entry = Wprss.selectedEntryController.get('content');
    //TODO get a canonical link - or maybe we should only store canonical links when we do inserts
    if(entry){
      window.open(entry.link);
    }
  });
  //u should toggle the current item's read status
  key('u',function(event,handler){
    var currentItem = Wprss.selectedEntryController.content;
    if(null == currentItem)
      return;
    Wprss.entriesController.toggleEntryRead(currentItem.id);
  });
}
//put in some infinite scrolling logic
jQuery(window).scroll(function()
{
    //how far from the bottom should we wait?
    if(jQuery(window).scrollTop() == jQuery(document).height() - jQuery(window).height())
    {
        jQuery('div#loadmoreajaxloader').show();
        var data = {
          action: 'wprss_get_entries',
          show_read: 0,
          nonce_a_donce:get_url.nonce_a_donce 
          
        };
        if(feed = Wprss.selectedFeedController.get('content') ){
          data['feed_id']=feed.feed_id;
        }
        //how are you going to handle the failure?
        //TODO whaddya do when there are no more posts?
        jQuery.get(get_url.ajaxurl, data, function(response){
          //alert(response);
          Wprss.entriesController.createEntries(response);
          jQuery('div#loadmoreajaxloader').hide();
        });
        
    }
});
