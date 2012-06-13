//Set everything up after page load
jQuery(document).ready(function($){
  
  Wprss.entriesController.createEntries(startentries);
  Wprss.selectedEntryController.set('content',Wprss.entriesController.get('content').get('firstObject'));
  
  Wprss.feedsController.onInit = function(){
    Wprss.selectedFeedController.set('content', Wprss.feedsController.get('content').get('firstObject'));
      Ember.run.next(this,function(){
        scrollToEntry(Wprss.entriesController.get('content')[0]);
      });
  };

  Wprss.selectedFeedController.onSelect = function(feed){
    Wprss.selectedEntryController.clear();
    Wprss.entriesController.selectFeed(feed.feed_id, feed.unread_count== 0?1:0);
  };
  setupKeys();
  feedTimer();
  setupInfiniteScroll();
  setupScrollToRead();
});

function setupScrollToRead(){
  /*jQuery('#wprss-content').mousemove(function(evt){
    //console.log(evt.pageY);
    Wprss.cache.set('mouseY',  evt.pageY);

  });
  jQuery('#wprss-content').mouseout(function(evt){
    console.log('mouseout');
    Wprss.cache.mouseY = null;
  });

  jQuery('#wprss-content').scroll(function(evt){
    //Where is the mouse cursor?
    console.log(Wprss.cache.get('mouseY'));
    //Which element is underneath the mouse cursor?
    //where is the top of that element?
    //Where is the bottom of that element?
  });
  */
  console.log('setting up waypoints');
  Ember.run.next(this,function(){
    jQuery('li .entry').waypoint({
      context: 'ul #wprss-content',
      handler: function(evt, direction){
        var active = jQuery(this);
        console.log(evt.target.id);
        console.log(direction);
      }
    });
  });
  console.log('set up waypoints');
}

function setupInfiniteScroll(){
  //put in some infinite scrolling logic
  jQuery('#wprss-content').endlessScroll({
    loader: '<div class="loading_indicator">LOADING MORE POSTS, BOSS!</div>',
    ceaseFireOnEmpty: false,
    fireOnce:false,
    callback: function(fireSequence,pageSequence,scrollDirection){
      console.log(fireSequence + " page: " + pageSequence + " scroll: " + scrollDirection);
      if("next" == scrollDirection){
        var data = {
          action: 'wprss_get_entries',
          show_read: 0,
          nonce_a_donce:get_url.nonce_a_donce 
          
        };
        var feed = Wprss.selectedFeedController.get('content') ;
        if(feed){
          data['feed_id']=feed.feed_id;
          feed.set('is_loading',true);
        }

        //how are you going to handle the failure?
        //TODO whaddya do when there are no more posts?
        jQuery.get(get_url.ajaxurl, data, function(response){
          Wprss.entriesController.createEntries(response);
          Wprss.selectedFeedController.get('content').set('is_loading',false);
          jQuery('.loading').remove();
          //scrollToEntry(Wprss.selectedEntryController.get('content'));
        });
        return true;
      }
      return true;
    }
  });
}

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
function setupInfiniteScroll(){
  //put in some infinite scrolling logic
  jQuery('#wprss-content').endlessScroll({
    loader: '<div class="loading">LOADING UP MORE POSTS BOSS!</div>',
    ceaseFireOnEmpty: false,
    fireOnce:false,
    callback: function(fireSequence,pageSequence,scrollDirection){
      console.log(fireSequence + " page: " + pageSequence + " scroll: " + scrollDirection);
      if("next" == scrollDirection){
        var data = {
          action: 'wprss_get_entries',
          show_read: 0,
          nonce_a_donce:get_url.nonce_a_donce 
          
        };
        var feed = Wprss.selectedFeedController.get('content') ;
        if(feed){
          data['feed_id']=feed.feed_id;
          feed.set('is_loading',true);
        }

        //how are you going to handle the failure?
        //TODO whaddya do when there are no more posts?
        jQuery.get(get_url.ajaxurl, data, function(response){
          Wprss.entriesController.createEntries(response);
          Wprss.selectedFeedController.get('content').set('is_loading',false);
          jQuery('.loading').remove();
          //scrollToEntry(Wprss.selectedEntryController.get('content'));
        });
        console.log('called for more posts');
      }
    }

  });
}

  
/*
jQuery(window).scroll(function()
{
    //how far from the bottom should we wait?
    if(jQuery('#wprss-content').scrollTop() == jQuery(document).height() - jQuery(window).height())
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
*/
