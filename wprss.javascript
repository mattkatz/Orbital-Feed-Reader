jQuery(document).ready(function($){
 // alert('begin');
  var data = {
    action: 'wprss_get_feeds'
  };
//  alert(get_url.ajaxurl);
//  $.get('/wp/wp-content/plugins/Wordprss/wprss.javascript', function(response){alert(response);});
  $.get(get_url.ajaxurl, data, function(response){
    $('#wprss-content').html('OH SNAP');
    var feeds = JSON.parse(response);
    alert(response);
    $.each(feeds,function(index,value){
      alert(value.feed_url + " " + value.site_url );
      Wprss.feedsController.createFeed(value.feed_url,value.site_url);

    });
  });


  
});

  Wprss = Ember.Application.create();
  Wprss.Feed = Em.Object.extend({
    feed_url : null,
    site_url: null
  });

  Wprss.feedsController = Em.ArrayProxy.create({
    content: [],
    createFeed: function(feed,domain){
      var feed = Wprss.Feed.create({ feed_url: feed, site_url:domain});
      this.pushObject(feed);
    }

  });
