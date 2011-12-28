jQuery(document).ready(function(){
  alert('begin');
  var data = {
    action: 'wprss_list_feeds',
    stuff: 'things'
  };
  jQuery.get('/wp/wp-content/plugins/wordprss/wprss.javascript', function(response){alert(response);});
  jQuery.get(get_url.ajaxurl, data, function(response){alert(response);});
  
  alert('BLART');
});

