jQuery(document).ready(function($){
 // alert('begin');
  var data = {
    action: 'wprss_get_feeds',
    nonce_a_donce:get_url.nonce_a_donce 
    
  };
  $.get(get_url.ajaxurl, data, function(response){
    //TODO: put in error checks for bad responses, errors,etc.
    var feeds = JSON.parse(response);
    //alert(response);
    $.each(feeds,function(index,value){
     // alert(value.feed_url + " " + value.site_url + " " + value.feed_name);
      Wprss.feedsController.createFeed(value.feed_url,value.site_url,value.feed_name);
    });
  });

  data.action='wprss_get_entries';
  $.get(get_url.ajaxurl, data, function(response){
    alert(response);
    var entries = JSON.parse(response);
    $.each(entries,function(index,entry){
      //alert(entry.title + " links to " + entry.link + " and has " + entry.content);
      Wprss.entriesController.createEntry(entry.feed_id,entry.title, entry.link,entry.content);
    });
  });
  


  
});

  Wprss = Ember.Application.create();
  Wprss.Feed = Em.Object.extend({
    feed_url : null,
    feed_name: null,
    site_url: null

  });

  Wprss.feedsController = Em.ArrayProxy.create({
    content: [],
    createFeed: function(feed,domain,name){
      var feed = Wprss.Feed.create({ feed_url: feed, site_url:domain, feed_name:name});
      this.pushObject(feed);
    }

  });
  Wprss.Entry = Em.Object.extend({
    feed_id: null,
    title: null,
    link: null,
    description: null
  });
  Wprss.entriesController = Em.ArrayProxy.create({
    content: [],
    createEntry: function(feed,head, url,des){
      var entry = Wprss.Entry.create({
      feed_id: feed, 
      title:head,
      link:url,
      description:des});
      this.pushObject(entry);
    }

  });


