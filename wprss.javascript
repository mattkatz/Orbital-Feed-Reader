jQuery(document).ready(function($){
 // alert('begin');
  var data = {
    action: 'wprss_get_feeds',
    nonce_a_donce:get_url.nonce_a_donce 
    
  };
  //alert(get_url.ajaxurl + data.action);
//  $.get('/wp/wp-content/plugins/Wordprss/wprss.javascript', function(response){alert(response);});
  $.get(get_url.ajaxurl, data, function(response){
    $('#wprss-content').html('OH SNAP');
    var feeds = JSON.parse(response);
    //alert(response);
    $.each(feeds,function(index,value){
     // alert(value.feed_url + " " + value.site_url + " " + value.feed_name);
      Wprss.feedsController.createFeed(value.feed_url,value.site_url,value.feed_name);

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
