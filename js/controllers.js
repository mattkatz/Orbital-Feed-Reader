/* Controllers */

function FeedListCtrl($scope, $http, $log){
  $log.log('in feedscontrol');
  $scope.editable = false;
  $scope.selectedFeed = null;

  /*
   * let the world know a feed has been CHOSEN
   * Mightily emit a roar up the scope chain
   */

  $scope.select = function(feed,showRead){
    $log.log(feed.feed_id);
    if( $scope.editable){
      $scope.$emit('feedEdit',{feed: feed}); 
    }
    else{
      $scope.$emit('feedSelect', {feed: feed,showRead:showRead});
      //Mark feed as loading
    }
    //Mark feed as selected
    $scope.feeds.forEach(function(value,index){
      value.isSelected = value.feed_id == feed.feed_id
    });
    //let's cache this for later
    $scope.selectedFeed = feed;
  };
  $scope.requestNewFeed = function () {
    $log.info('new feed requested');
    $scope.$emit('newFeedRequested');
  }
  /*
   * get the list of feeds and store it
   */
  $scope.refresh = function(){
    $scope.isLoading = true;
    $log.info('refreshing feeds');
    $http.get(opts.ajaxurl+'?action=orbital_get_feeds' )
    .success(function(data){ 
      
      $scope.feeds = data;
      var fresh = {
        feed_id:null,
        feed_name:'All Feeds',
        unread_count:'',
      }
      $scope.feeds.unshift(fresh);
      $scope.isLoading = false;
    });
  };
  //call the refresh to load it all up.
  //TODO change this to load the initial feeds variable
  $scope.refresh();

  /*
   * Get the next unread feed
   *
   */
  $scope.nextUnreadFeed = function(){
    // We should iterate through the feeds
    // Find our selected feed
    // Then keep iterating till we find an unread feed
    // If we reach the end of the list
    // Start looking at the beginning till we find an unread feed
    feeds = $scope.feeds;
    index = feeds.indexOf($scope.selectedFeed);
    $log.info('index is ' + index);
    //we are starting at the index item
    //and circling the array
    for(i=(index+1)%feeds.length;i!=index;i= (i+1)%feeds.length){
      $log.info('i is ' + i);
      if(feeds[i].unread_count >0){
        return feeds[i];
      }
    }
    //NOTHING! let's just return where we started
    return $scope.selectedFeed;
  };

  /*
   * Set editable
   */
  $scope.setEditable = function(){
    $scope.editable = ! $scope.editable;
  }

  /*
   * Update this feed
   */
  $scope.update = function(feed){
    //update feed 
    var data= {
      action: 'orbital_update_feed',
      feed_id: feed.feed_id,
    };
    $http.post(opts.ajaxurl, data)
    .success(function(response){
      $log.info('selecting '+feed.feed_id);
      //refresh the feedlist
      $scope.refresh();
      //refresh the feed if it is still selected
      if(feed == $scope.selectedFeed){
        $scope.select(feed);
      }
    });
  }

  /*
   * Events
   */

  /*
   * Has an entry changed? Update our feedlist
   */
  $scope.$on('entryChanged', function(event,args){
    //find the feed entry that has this entry's feed_id
    entry = args.entry;
    feed_id = entry.feed_id;
    $log.info($scope.feeds);
    //Look down the list of feeds for the one this entry belongs to
    for( i = 0; i < $scope.feeds.length; i++){
      feed = $scope.feeds[i];
      if( feed.feed_id ==  entry.feed_id){
        //decrement the read counter by the isread status
        feed.unread_count = Number(feed.unread_count ) + (entry.isRead ? -1:1);
      }
    }
  });

  /*
   * We should just get the feeds from the DB.
   */
  $scope.$on('refreshFeeds', function(event,args){
    $scope.refresh();
  });
  $scope.$on('updateFeed', function(event,args){
    $log.log('updateFeed event');
    $scope.update(args.feed);
  });
  /* One of the command bar actions fired */
  $scope.$on('commandBarEvented', function  (event, args) {
    feed = args.feed;
    switch(args.name){
      case "markRead":
        //mark feed read
        var data = {
          action: 'orbital_mark_items_read',
          feed_id:feed.feed_id,
        };
        $http.post(opts.ajaxurl, data)
        .success(function(response){
          $scope.refresh();
          $scope.select($scope.nextUnreadFeed());
        });
        break;
      case "updateFeed":
        //update feed 
        $scope.update(feed);
        break;
      case "showRead":
        //refresh this feed, but display read items
        $scope.select(feed,1);
        break;
      default:
        $log.log('requested commandBar action ' + args.name + ' - not implemented yet');
        break;
    }
  });
}

function EntriesCtrl($scope, $http, $log){
  $scope.selectedEntry = null;
  $scope.currentFeedId = null;
  $log.log("in EntriesCtrl");
  
  /*
   * select a feed to display entries from
   */
  $scope.displayFeed = function(id,showRead){
    $scope.currentFeedId = id;
    $scope.isLoading = true;
    $http.get(opts.ajaxurl+'?action=orbital_get_entries&feed_id='+$scope.currentFeedId+'&show_read='+showRead)
    .success(function(data){
      $scope.isLoading = false;
      //$log.info(data);
      $scope.entries = data;
      $scope.selectedEntry = null;
      scrollToEntry(null);
    });
  };

  $scope.addMoreEntries = function(){
    $scope.isLoading = true;
    $http.get(opts.ajaxurl+'?action=orbital_get_entries&feed_id='+$scope.currentFeedId)
    .success(function  (response) {
      $scope.isLoading = false;
      $log.info('going to the server mines for more delicious content');
      response.forEach( function(value, index, array){
        //check to see if the value is in entries.
        if(! _.some($scope.entries, function(item){ return item.id == value.id})){
          //If not in entries then append it
          $scope.entries.push(value);
        };
      });
    });
  }

  $scope.pressThis = function(entry,pressThisUrl) {
    //Get the selected text
    //This is ripped from the pressthisbookmarklet
    var d=document,
    w=window,
    e=w.getSelection,
    k=d.getSelection,
    x=d.selection,
    s=(e?e():(k)?k():(x?x.createRange().text:0)),
    f=pressThisUrl;
    e=encodeURIComponent;
    url = e(entry.link);
    title = e(entry.title);
    content = e(s);
    console.log(opts.settings['quote-text']);
    if(opts.settings[ 'quote-text' ]){
      content = content?content:e(entry.content);
    }
    g=f+'?u='+url+'&t='+title+'&s='+content+'&v=2';
    function a(){
      if(!w.open(g,'t','toolbar=0,resizable=0,scrollbars=1,status=1,width=720,height=570'))
        {l.href=g;}
    }
    setTimeout(a,0);
    void(0);

    //Use the entry details to construct a pressthis URL
    //Reveal a pressthis iframe window.

    console.log(entry);
  }

  /*
   * Someone has clicked an entry.
   * Toggle read on the server, then alert the UI
   */

  $scope.selectEntry = function selectEntry(entry) {
    $log.log('Selected entry ' + entry.entry_id);
    var newReadStatus = entry.isRead == 0?1:0;
    var data = {
      action: 'orbital_mark_item_read',
      read_status: newReadStatus ,
      entry_id: entry.entry_id,
    };
    //Set this as the selected entry
    $scope.selectedEntry = entry;
    entry.isLoading = true;
    //Mark the entry read on the server
    $http.post(opts.ajaxurl,data)
    .success(function(data){
      //mark the entry as read in the UI
      entry.isRead= entry.isRead == 0 ? 1:0;
      entry.isLoading = false;
      //tell the feed list that the entry was toggled read.
      $scope.$emit('entryChange', {entry:entry});
    });
  }
  $scope.displayFeed();
  /*
   * Catch the feedSelected event, display entries from that feed
   */
  $scope.$on('feedSelected',function(event,args){
    //$log.log('feedSelected in Entries!');
    $scope.displayFeed(args['feed'].feed_id, args['showRead']);
  });
  $scope.nextEntry = function(currentEntry){
    $log.info('next entry finds the entry after the current entry, selects it');
    var index =0;//by default we select the first entry
    if( $scope.entries.length == 0){
      return;//can't select anything
    }
    if(null != currentEntry){ //if there is a current entry, get the index after it
      var index = $scope.entries.indexOf(currentEntry);
      //If we are at the last entry just go to the first
      index = (index +1) % $scope.entries.length;
    }
    var next = $scope.entries[index];
    $scope.selectEntry(next);
    //scroll to the entry
    scrollToEntry(next);
  };
  $scope.previousEntry = function (currentEntry) {
    $log.info('prev entry finds the entry before the current entry, selects it');
    var index = $scope.entries.length;//by default we select the last entry
    if( $scope.entries.length == 0){
      return;//can't select anything
    }
    if(null != currentEntry){ //if there is a current entry, get the index after it
      index = $scope.entries.indexOf(currentEntry);
      //If we are at the last entry just go to the first
      index = Math.max((index -1),0) ;
    }
    var previous = $scope.entries[index];
    $scope.selectEntry(previous);
    //scroll to the entry
    scrollToEntry(previous);
  };

  /* Set up keyboard shortcuts
   */

  //handle the down arrow keys and j to scroll the next item to top of scren
  key('j,down',function(event,handler){
    $scope.nextEntry($scope.selectedEntry);
  });
  //up and k should scroll the previous item to the top of the screen
  key('k,up',function(event,handler){
    $scope.previousEntry($scope.selectedEntry);
  });
  //o should open the original article
  key('o',function(event,handler){
    var entry = $scope.selectedEntry;
    $log.log(entry);
    //TODO get a canonical link - or maybe we should only store canonical links when we do inserts
    if(entry){
      window.open(entry.link);
    }
  });
  //u should toggle the current item's read status
  key('u',function(event,handler){
    var entry = $scope.selectedEntry;
    if(null == entry)
      return;
    $scope.selectEntry(entry);
  });
}

/*
 * Subscription control
 * This should manage the workflow of adding or editing a feed
 * Feed CRUD happens here.
 *
 * If you summon this with nothing in it, we'll show the feed search
 * Give it a candidate and we'll hide the rest and let you edit this
 * 
 */
function SubsCtrl($scope,$http,$log){
  //The normal status of this window is to be hidden.
  $scope.reveal = false;
  $scope.possibleFeeds = null;
  $scope.urlCandidate = '';
  $scope.feedCandidate = null;
  //$scope.opmlFile=null;
  //$scope.fileSize = null;
  $scope.toggle = function(){
    $scope.reveal = !$scope.reveal;
    $scope.clear();
  }

  $scope.clear = function() {
    $scope.possibleFeeds = null;
    $scope.urlCandidate = '';
    $scope.feedCandidate = null;
    $scope.feedsCount = '';
    $scope.doneFeeds = '';
    $scope.isLoading = false;
    //TODO clear any OPML elements
  }

  $scope.checkUrl = function(url){
    if(url){
      $scope.urlCandidate = url;
    }
    //now we should check the candidate
    var data = {
      action: 'orbital_find_feed',
      url: $scope.urlCandidate,
    };
    $scope.isLoading=true;
    //ask the backend to look at it
    $http.post(opts.ajaxurl,data)
    .success(function(response){
      $scope.isLoading=false;
      if("feed" == response.url_type){
        console.log('found a feed!');
        //if it returns a feed detail, display that.
        $scope.feedCandidate = { 
          feed_url: response.orig_url, 
          site_url: response.site_url, 
          feed_id: null, 
          feed_name: response.feed_name,
          unread_count:0,
          private:false
        };
        $scope.possibleFeeds=null;
      }
      else{
        //if it returns possibleFeeds, display them.
        $scope.possibleFeeds = response.feeds;
        //remove the old feedCandidate if there is one
        $scope.feedCandidate = null;
      }
    });
  }

  /*
   * save changes or additions in a feed back to storage
   */
  $scope.saveFeed = function(feed, batchmode){
    //mark the save button busy
    if(! batchmode) {$scope.isLoading = true;}
    var data = {
      action: 'orbital_save_feed',
      feed_id: feed.feed_id,
      feed_url: feed.feed_url,
      feed_name: feed.feed_name,
      site_url: feed.site_url,
      is_private: feed.private,
    };
    $http.post(opts.ajaxurl,data)
    .success(function(response){
      if(! batchmode){
        //mark the save button not busy
        $scope.isLoading = false;
        //hide the feed away
        $scope.toggle();
        $scope.feedSaved(response);
        $scope.feedsChanged();
      }
    });
  }
  /*
   * Unsubscribe from a feed. 
   *
   */
  $scope.unsubscribe = function(feed){
    //TODO it would be good to give a cancel
    //Maybe it could just be to call the save again
    $scope.isLoading = true;
    $log.info(feed);
    var data = {
      action: 'orbital_unsubscribe_feed',
      feed_id: feed.feed_id,
    };
    $http.post(opts.ajaxurl,data)
    .success(function(response){
      //unmark the busy 
      $scope.isLoading = false;
      $scope.feedsChanged();
      //close the dialogue
      $scope.toggle();
      $scope.feedsChanged();
    });
  }
  $scope.getFile = function(){

    var file = document.getElementById('import-opml').files[0];
    $scope.opmlFile = file;
    console.log(document.getElementById('import-opml').files);
    return file;
  }

  /*
   * When an opml file is selected, read the size and name out
   */
  $scope.fileSelected = function(){
    var file = $scope.getFile();
    $scope.fileSize = 0;
    if(file.size > 1024 * 1024){
      $scope.fileSize = (Math.round(file.size * 100 / (1024 * 1024)) / 100).toString() + 'MB';
    }
    else{
      $scope.fileSize = (Math.round(file.size * 100 / 1024) / 100).toString() + 'KB';
    }
    //TODO this isn't very angular
    //jQuery('#fileName').html('Name: '+ file.name);
    //jQuery('#fileSize').html('Size: '+ $scope.fileSize);
    jQuery('#uploadButton').removeProp('disabled');
  }

  /*
   * When an OPML file is uploaded, we should read that file
   * Extract each feed out of the file
   * save that feed back up to the server.
   * TODO It would be even better to hand this off to a web worker if supported
   */
  $scope.uploadOPML = function(){
    $log.info('uploading OPML');
    // Check for the various File API support.
    if (window.File && window.FileReader && window.FileList && window.Blob) {
    // Great success! All the File APIs are supported.
      var f = $scope.getFile();
      var reader = new FileReader();
      //reader.onprogress = updateProgress;
      reader.onload = (function (theFile){
        return function (e){
          //parse the opml and upload it
          console.log(e.target.result);
          $scope.isLoading = true;
          try{
            var opml = jQuery(e.target.result);
            //var opml =  jQuery.parseXML(e.target.result);
            var outlines = jQuery(opml).find('outline[xmlUrl]');
            $scope.feedsCount = outlines.length;
            $scope.doneFeeds = 0;
            
            outlines.each(function(index){
              var el = jQuery(this);
              console.log(el);
              var feed = {};
              feed.feed_id = null;
              //TODO later we should let people choose before we upload.
              feed.is_private = false;
              feed.feed_name = el.attr('text'); 
              feed.feed_url = el.attr('xmlUrl');
              feed.site_url = el.attr('htmlUrl');
              //orbital.feedsController.saveFeed(feed);

              $scope.saveFeed(feed,true);
              $scope.doneFeeds++;
            });
            $scope.feedsChanged();
            $scope.isLoading = false;
          }
          catch(ex){
            alert('Sorry, we had trouble reading this file through.');
            $scope.isLoading = false;
            console.log(ex);
          }
          //TODO do we clear
          $scope.toggle();;

        };
      })(f);
      reader.readAsText(f);

      console.log('great success!');
      return false;
    } else {
      //TODO better error telling you specific versions of FF, Chrome, IE to use
      alert('Unfortunately, this browser is a bit busted.  File reading will not work, and I have not written a different way to upload opml.  Try using the latest firefox or chrome');
    }

  }

  /* events */

  //this window has been requested or dismissed
  $scope.$on('subscriptionsWindow',function(event,args){
    $log.info('subscriptionsWindow');
    //$log.info(event);
    $scope.toggle();
  });

  $scope.feedSaved = function(feed){
    $scope.$emit('feedSaved',{feed:feed});
  }

  $scope.feedsChanged = function(){
    $scope.$emit('feedsChanged');
  }

  //We are going to edit a feed
  //it becomes the feedCandidate so we can edit it there.
  //TODO we should copy the feed, not use the one in the feedlist
  $scope.$on('feedEditRequest', function(event,args){
    //$log.info('feedEdit');
    $scope.reveal=true;
    $scope.feedCandidate = args.feed;
  });
}

function CommandBarCtrl($scope,$http,$log){
  $scope.$on('feedSelected', function(event,args){
    //$log.info('commandBar feed is:' + args['feed'].feed_name);
    $scope.currentFeed = args.feed;
  });
  $scope.commandBarAction = function(action){
    //$log.info(action.title + (action.name ? ' fired' : ' - not implemented yet'));
    $scope.$emit('commandBarEvent',{name: action.name,feed: $scope.currentFeed});
  };
  $scope.commands = [
    { title: "Mark All As Read",
      name: 'markRead',
    },
    { title: "Update Feed",
      name: 'updateFeed',
    },
    { title: "Show Read Items",
      name: 'showRead',
    },
  ];

}
