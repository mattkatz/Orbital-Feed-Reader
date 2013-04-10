function scrollToEntry(currentItem, bottom){

    var body = jQuery('html');
    var adminbar = jQuery('#wpadminbar');
    var commandbar = jQuery('#commandbar');
    var row = jQuery('#'+currentItem.feed_id + "_" +currentItem.id);
    if(null === row.offset()){
      console.log('row.offset() was null');
      return;
    }
    //position is the offset from the parent scrollable element
    var scrollAmount = row.position().top;
    if(bottom){
      console.log('trying to get to the bottom');
      scrollAmount += row.height();
    }


    var currentScroll = jQuery('#wprss-content').scrollTop();
    
    jQuery('#wprss-content').animate({ scrollTop: scrollAmount + currentScroll -  commandbar.height()}, 200); 
}
//Set everything up after page load
jQuery(document).ready(function($){
  function setContentHeight(id,height){
    $(id).css({'height':(($(window).height())-height)+'px'});
  }
  $(window).resize(function(){
    setContentHeight('#wprss-content',28+22);
    setContentHeight('#wprss-feedlist',28);
    $('#wprss-content').css({'width':(($('#wprss-container').width() - 190 )+'px')});
    //setContentHeight('#feeds', 28+63);
    $('#feeds').css({'height':(($('#wprss-feedlist').height()-($('#feed-head').height()+ 10 )) +'px')});
  });
  $(window).resize();
});
