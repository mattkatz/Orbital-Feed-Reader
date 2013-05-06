function scrollToEntry(currentItem, bottom){

    var body = jQuery('html');
    var adminbar = jQuery('#wpadminbar');
    //var commandbar = jQuery('#commandbar');
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
    
    //jQuery('#wprss-content').animate({ scrollTop: scrollAmount + currentScroll -  commandbar.height()}, 200); 
    jQuery('#wprss-content').animate({ scrollTop: scrollAmount + currentScroll }, 200); 
}
//Set everything up after page load
jQuery(document).ready(function($){
  function setContentHeight(id,height){
    $(id).css({'height':(($(window).height())-height)+'px'});
  }
  $(window).resize(function(){
    setContentHeight('#wprss-content',28+$('commandbar').height());
    setContentHeight('#wprss-feedlist',28);
    var w =$('#wprss-container').width();

    $('#wprss-content').css({'width':((w * .8)- 10 +'px')});
    //scrollbar.width probably is 10 px.
    $('#feeds').css({'height':(($('#wprss-feedlist').height()-($('#feed-head').height()+ 10 )) +'px')});
  });
  $(window).resize();
});
