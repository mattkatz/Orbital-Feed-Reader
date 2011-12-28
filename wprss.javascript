jQuery(document).ready(function(){
  alert('begin');
  var data = {
    action: 'wprss_list_feeds'
  };
  alert(get_url.ajaxurl);
  jQuery.get('/wp/wp-content/plugins/Wordprss/wprss.javascript', function(response){alert(response);});
  jQuery.get(get_url.ajaxurl, data, function(response){alert(response);});
  
  alert('BLART');
});

