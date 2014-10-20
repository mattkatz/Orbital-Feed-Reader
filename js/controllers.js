/* Controllers */
function FeedListCtrl($scope, $http, $log, feedService){
  $scope.showByTags = feedService.showByTags()
  $scope.editable = false;
  $scope.feeds = feedService.feeds();
  $scope.tags = feedService.tags();
  $scope.isLoading = feedService.isLoading();
  $scope.showRead = 0;
  $scope.selectedFeed = null;
  $scope.$watch(feedService.feeds,function(newValue){
    //console.log('listener');
    //$scope.feeds = feedService.feeds();
    $scope.feeds = newValue;
  });
  $scope.$watch(feedService.tags,function(newValue){
    //console.log('listener');
    $scope.tags = newValue;
  });
  $scope.$watch(feedService.isLoading,function(newValue){
    //console.log('listener');
    $scope.isLoading = newValue;
  });
  $scope.$watch(feedService.showByTags,function(newValue,oldValue){
    $scope.showByTags = newValue;
  });
  $scope.$watch(feedService.selectedFeed,function(newValue,oldValue){
    $scope.selectedFeed = newValue;
  });
  $scope.saveTagView = function(showTags){
    feedService.saveTagView(showTags,null);
  };

  /*
   * let the world know a feed has been CHOSEN
   * Mightily emit a roar up the scope chain
   */

  $scope.select = function(feed,showRead){
    $log.log("selecting " + feed);
    if( $scope.editable){
      $scope.$emit('feedEdit',{feed: feed}); 
    }
    else{
      $scope.$emit('feedSelect', {feed: feed,showRead:showRead});
      feedService.select(feed,showRead);
      //Mark feed as loading
    }
  };
  $scope.requestNewFeed = function () {
    $log.info('new feed requested');
    $scope.$emit('newFeedRequested');
  }

  /*
   * get the list of feeds and store it
   */
  $scope.refresh = function(callback){
    feedService.refresh(callback);
  };

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
    index = feeds.indexOf(feedService.selectedFeed());
    //we are starting at the index item
    //and circling the array
    for(i=(index+1)%feeds.length;i!=index;i= (i+1)%feeds.length){
      if(feeds[i].unread_count >0){
        return feeds[i];
      }
    }
    //NOTHING! let's just return where we started
    return feedService.selectedFeed();
  };

  /*
   * Set editable
   */
  $scope.setEditable = function(){
    $scope.editable = ! $scope.editable;
  }
  $scope.markRead = function(feed){
    //console.log(feedService.selectedFeed());
    if( null == feed){
      feed = feedService.selectedFeed();
    }
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

  }

  /*
   * Update this feed
   * TODO move this into the feedService
   */
  $scope.update = function(feed){
    if(null == feed){
      feed = feedService.selectedFeed();
    }
    //update feed 
    var data= {
      action: 'orbital_update_feed',
      feed_id: feed.feed_id,
    };
    $http.post(opts.ajaxurl, data)
    .success(function(response){
      //refresh the feedlist
      $scope.refresh();
      //refresh the feed if it is still selected
      if(feed == $scope.selectedFeed){
        $scope.select(feed, $scope.showRead);
      }
    });
  }
  $scope.showReadItems = function(){
    //refresh this feed, but display read items
    $scope.showRead = 1 - $scope.showRead;
    $scope.select(feedService.selectedFeed(),$scope.showRead);
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
    feedService.refresh();
  });
  $scope.$on('updateFeed', function(event,args){
    $scope.update(args.feed);
  });
  
}
function CliCtrl($scope, $filter,$timeout,feedService){
  $scope.reveal = false;
  $scope.selectedFeed = null;
  $scope.feeds = feedService.feeds();
  //$scope.filteredFeeds = $filter('filter')(feedService.feeds(),$scope.filterstring);
  $scope.filteredFeeds = function(){
    return $filter('filter')(feedService.feeds(),{'feed_name':$scope.filterstring});
  };
  $scope.tags = feedService.tags();
  $scope.filterstring = null;
  $scope.$watch(feedService.tags,function(newValue){
    //console.log('listener');
    $scope.tags = newValue;
  });
  $scope.toggleReveal = function(){
    $scope.reveal=! $scope.reveal;
    $scope.filterstring = null;
    $scope.selectedFeed = null;
  };
  $scope.nextResult = function(){
    feeds = $scope.filteredFeeds();
    //if no feeds, there can be no selected result
    if(feeds.length <= 0){
      $scope.selectedFeed = null;
      return;
    }
    index = feeds.indexOf($scope.selectedFeed);
    if(index <0){ //selectedFeed is null or not in the list
      $scope.selectedFeed = feeds[0];
      return;
    }
    if (index < feeds.length -1){
      $scope.selectedFeed = feeds[index+1];
    }
  };
  $scope.prevResult = function(){
    feeds = $scope.filteredFeeds();
    if(feeds.length<=0){
      $scope.selectedFeed = null;
      return;
    }
    index = feeds.indexOf($scope.selectedFeed);
    if(index <0){ //selectedFeed is null or not in the list
      $scope.selectedFeed = feeds[0];
      return;
    }
    if (index > 0){
      $scope.selectedFeed = feeds[index-1];
    }
  };
  $scope.select = function(feed){
    feedService.select(feed);
    $scope.toggleReveal();
  };
  $scope.setSelectedFeedIfNull = function(){
    var filtFeeds = $scope.filteredFeeds();
    if (! _.contains(filtFeeds,$scope.selectedFeed)){
      $scope.selectedFeed = filtFeeds[0];
    }
  };
  $scope.processKeys = function($event){
    $scope.setSelectedFeedIfNull();
    var enter = 13, tab = 9, esc = 27, up = 38, down = 40, left = 37, right = 39;
    //cancel other handlers if we've got it
    //if up/down/escape and we have results
    //or if enter/tab and we have results and one has been selected
    if( ([enter,tab,esc,up,down].indexOf($event.keyCode) != -1) &&
      (([enter,tab].indexOf($event.keyCode) ==-1)|| $scope.selectedFeed)){
        if($event.stopImmediatePropagation) $event.stopImmediatePropagation();
        if($event.preventDefault) $event.preventDefault();
        if($event.stopPropagation) $event.stopPropagation();
        if($event.cancelBubble) $event.cancelBubble = true;
    }else{
      //$scope.toggleReveal();
      return;
    }
    switch($event.keyCode){
      case up:
        $scope.prevResult();
        break;
      case tab:
      case down:
        $scope.nextResult();
        break;
      case enter:
        $event.stopImmediatePropagation();
        $scope.select($scope.selectedFeed);
        $event.target.blur();
        break;
      case esc:
        $event.stopImmediatePropagation();
        //hide the results or empty them
        $scope.toggleReveal();
        //deselect the input element so we don't send more input there.
        $event.target.blur();
        break;
    }
  };

  key('g', function(event,handler){
    $scope.$apply(function(){$scope.toggleReveal()});
  });

}
function EntriesCtrl($scope, $http, $log,feedService){
  $scope.selectedEntry = null;
  $scope.isRead = false;
  $scope.entries = [];
  $scope.$watch(feedService.entries, function(){
    $scope.entries = feedService.entries();
  });
  $scope.$watch(feedService.selectedFeed, function (){
    if(feedService.selectedFeed()){
      $scope.displayFeed(feedService.selectedFeed());
    }
  });
  $scope.$watch(feedService.isEntriesLoading, function(){
    $scope.isLoading = feedService.isEntriesLoading();
  });
  $scope.$watch(feedService.selectedEntry, function(){
    $scope.selectedEntry=feedService.selectedEntry();
  });

  
  /*
   * select a feed to display entries from
   */
  $scope.displayFeed = function(feed,showRead){
    feedService.getFeedEntries(feed,showRead);
  };
  $scope.changeSortOrder = function(newSort){
    newSort = newSort || feedService.sortOrder() * -1;
    //console.log("pre saving " + $scope.sortOrder);
    feedService.saveSort(newSort,function(){$scope.$emit('feedSelect', {feed: feedService.selectedFeed()})});
    //console.log("post saving " + $scope.sortOrder);
  };

  $scope.selectFeed = function(entry){
    console.log('selecting feed');
    feedService.select(feedService.getFeed(entry.feed_id));
  }

  $scope.addMoreEntries = function(){
    var feed = feedService.selectedFeed();
    if(! feed){ return; }
    
    //$log.log('showRead='+showRead);
    var showRead = $scope.isRead;
    if(!showRead){
      showRead=0;
    }
    feedService.getFeedEntries(feed,showRead);
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
    //console.log(opts.settings['quote-text']);
    if(opts.settings[ 'quote-text' ] && ! content ){
      var div = document.createElement("div");
      div.innerHTML = entry.content;
      content = e(div.textContent || div.innerText || "");
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

    //console.log(entry);
  }

  /*
   * toggle the entry's read status
   */
  $scope.setReadStatus = function( entry, status){
    entry.isLoading = true;
    var newReadStatus = status || (entry.isRead == 0?1:0);
    var data = {
      action: 'orbital_mark_item_read',
      read_status: newReadStatus ,
      entry_id: entry.entry_id,
    };
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

  /*
   * Someone has clicked an entry.
   * Toggle read on the server, then alert the UI
   */

  $scope.selectEntry = function (entry) {
    //Set this as the selected entry
    feedService.selectEntry(entry);
    //$scope.selectedEntry = entry;
    if( "0" == entry.isRead ){
      $scope.setReadStatus(entry);
    }
  }
  $scope.getFeedFromEntry = function (entry){
    return feedService.getFeedFromEntry(entry);
  }
  $scope.displayFeed(null,$scope.isRead);
  //$scope.displayFeed();
  /*
   * Catch the feedSelected event, display entries from that feed
   */
  $scope.$on('feedSelected',function(event,args){
    //$log.log('feedSelected in Entries!');
    //$scope.displayFeed(args['feed'], args['showRead']);
  });
  $scope.nextEntry = function(currentEntry){
    //$log.info('next entry finds the entry after the current entry, selects it');
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
    //$log.info('prev entry finds the entry before the current entry, selects it');
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
    $scope.setReadStatus(entry,"0");
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
function SubsCtrl($scope,$http,$log,feedService ){
  //The normal status of this window is to be hidden.
  $scope.reveal = false;
  $scope.possibleFeeds = null;
  $scope.urlCandidate = '';
  $scope.feedCandidate = null;
  $scope.newTags = '';
  $scope.opmlFile=null;
  $scope.files = null;
  $scope.fileSize = null;
  $scope.close = function(){
    $scope.reveal = false;
    $scope.clear();
  }
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
    $scope.newTags = '';
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
        //console.log('found a feed!');
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
        //console.log('sticking in feed names');
        _.each(response.feeds,function(feed){
          //console.log('looking for a title for '+ feed);
          //console.log(feed);
          if(feed.body){
            //TODO this all seeems a bit fragile. Could be done better
            var xml = jQuery.parseXML(feed.body);
            xml = jQuery(xml);
            var title = xml.find('channel > title');
            feed.name = title.text();
            //console.log(feed.name);
          }
        });
        //if it returns possibleFeeds, display them.
        $scope.possibleFeeds = response.feeds;

        //remove the old feedCandidate if there is one
        $scope.feedCandidate = null;
      }
    });
  }
  
  /*
   * Remove a tag from a feedCandidate
   */
  $scope.removeTag = function(tag){
    currenttags = _.chain(String.split($scope.feedCandidate.tags,',' ))
                   .map(function(item){return item.trim();})
                   .unique()
                   .compact()
                   .value();
    $scope.feedCandidate.tags = _.reject(currenttags,function(item){return item==tag}).join(',');
  }
  $scope.availableTags = [];
  $scope.$watch(feedService.allTags,function(newValue){
    $scope.availableTags = newValue});
  /*
   * We are using wp's tags scripts, but we need them to be done in an angularJS context:
   */
 // jQuery('#tagentry').suggest(opts.ajaxurl + '?action=orbital_get_tags',{ delay: 500, minchars: 2, multiple: true, multipleSep: ', ', onSelect:function(){$scope.$apply()} });
  
  /*
   * save changes or additions in a feed back to storage
   */
  $scope.saveFeed = function(feed, batchmode){
    //mark the save button busy
    if(! batchmode) {$scope.isLoading = true;}
    feedService.saveFeed(feed,function(response,data){
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
      $scope.close();
      $scope.feedsChanged();
    });
  }
  $scope.getFile = function(){

    var file = document.getElementById('import-opml').files[0];
    $scope.opmlFile = file;
    return file;
  }

  /*
   * When an opml file is selected, read the size and name out
   */
  $scope.fileSelected = function(){
    // Check for the various File API support.
    if (window.File && window.FileReader && window.FileList && window.Blob) {
      // Great success! All the File APIs are supported.
      var file = $scope.getFile();
      $scope.fileSize = 0;
      if(file.size > 1024 * 1024){
        $scope.fileSize = (Math.round(file.size * 100 / (1024 * 1024)) / 100).toString() + 'MB';
      }
      else{
        $scope.fileSize = (Math.round(file.size * 100 / 1024) / 100).toString() + 'KB';
      }
      var reader = new FileReader();
      //reader.onprogress = updateProgress;
      reader.onload = (function (theFile){
        return function (e){
          //parse the opml and upload it
          //console.log(e.target.result);
          $scope.$apply(function(){
            $scope.isLoading = true;
            $scope.parseOPML(e.target.result);
            $scope.isLoading = false;
          });

        };
      })(file);
      reader.readAsText(file);
    } else {
      //TODO better error telling you specific versions of FF, Chrome, IE to use
      alert('Unfortunately, this browser is a bit busted.  File reading will not work, and I have not written a different way to upload opml.  Try using the latest firefox or chrome');
    }
  }

  /*
   * We createa  parseXML function dependend on the browser
   */
  $scope.parseXML = function(xmlStr){ }
  if (typeof window.DOMParser != "undefined") {
    $scope.parseXML = function(xmlStr) {
      return ( new window.DOMParser() ).parseFromString(xmlStr, "text/xml");
    };
  } else if (typeof window.ActiveXObject != "undefined" &&
            new window.ActiveXObject("Microsoft.XMLDOM")) {
    $scope.parseXML = function(xmlStr) {
      var xmlDoc = new window.ActiveXObject("Microsoft.XMLDOM");
      xmlDoc.async = "false";
      xmlDoc.loadXML(xmlStr);
      return xmlDoc;
    };
  }

  /*
   * This function takes in an OPML document and pushes feeds into the feedCandidates array
   * or it throws an informative error
   */
  $scope.parseOPML = function(opml) {
    $scope.feedCandidates = [];
    var doc = $scope.parseXML(opml);
    var outlines = doc.querySelectorAll('outline[xmlUrl]');
    $scope.feedsCount = outlines.length;
    $scope.doneFeeds = 0;
    for(index=0;index<outlines.length;index++){
      var node = outlines.item(index);
      var feed = {};
      feed.feed_id = null;
      feed.is_private = false;
      feed.feed_name = node.getAttribute('text');
      feed.feed_url = node.getAttribute('xmlUrl');
      feed.site_url = node.getAttribute('htmlUrl');
      /*
       * TODO: a category can also be provided by nesting feed nodes within
       * a text node. The title or text attribute should be the category then.
       * Right now we don't support that.
       */
      feed.tags ="";
      if(node.hasAttribute('category')){
        feed.tags += node.getAttribute('category');
      }
      pnode = node.parentNode;
      if(pnode.tagName != "body"){
        //AHA! you are nested, aren't you?
        //Give up your secrets you potential folder/tag
        if(pnode.hasAttribute('text')){
          feed.tags += pnode.getAttribute('text');
        }
        else if(pnode.hasAttribute('title')){
          feed.tags += pnode.getAttribute('title');
        }

      }
      $scope.doneFeeds = $scope.feedCandidates.push(feed);
    }
  }
  /*
   * Remove a candidate from the list of feedCandidates
   */
  $scope.removeCandidate = function(feed){
    $scope.feedCandidates = _.without($scope.feedCandidates, feed);
  }

  /*
   * Feeds have previously been parsed out of the opml file
   * This function saves those feed candidates to the server.
   * save that feed back up to the server.
   * TODO It would be even better to hand this off to a web worker if supported
   */
  $scope.uploadOPML = function(){
    $log.info('uploading OPML');
    $scope.isLoading=true;
    _.each($scope.feedCandidates, function(feed, index, list){
      $scope.doneFeeds=0;
      $scope.saveFeed(feed,true);
      $scope.doneFeeds++;
    });
    $scope.isLoading=false;
    $scope.feedsChanged();
    $scope.close();;
    return false;
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
    feedService.refresh();
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


function changeSortOrder( newSort){
  scope = angular.element('#orbital-main-content').scope();
  scope.$apply(function(){
    scope.changeSortOrder( newSort);
  });
}

function markFeedRead(feed){
  scope = angular.element('#orbital-feedlist').scope();
  scope.$apply(function(){
    scope.markRead( feed);
  });
}
function showRead(feed){
  scope=angular.element('#orbital-feedlist').scope();
  scope.$apply(function(){
    scope.showReadItems( feed);
  });
}
function updateFeed(feed){
  scope = angular.element('#orbital-feedlist').scope();
  scope.$apply(function(){
    scope.update( feed);
  });
}
